<?php

namespace Dashed\DashedEcommercePaynl\Classes;

use Dashed\DashedCore\Classes\Locales;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedEcommerceCore\Classes\Countries;
use Dashed\DashedEcommerceCore\Models\OrderPayment;
use Dashed\DashedEcommerceCore\Models\PaymentMethod;
use Dashed\DashedTranslations\Models\Translation;
use Exception;
use Illuminate\Support\Facades\Storage;

class PayNL
{
    public static function initialize($siteId = null)
    {
        if (! $siteId) {
            $siteId = Sites::getActive();
        }

        \Paynl\Config::setApiToken(Customsetting::get('paynl_at_hash', $siteId));
        \Paynl\Config::setServiceId(Customsetting::get('paynl_sl_code', $siteId));
    }

    public static function isConnected($siteId = null)
    {
        if (! $siteId) {
            $siteId = Sites::getActive();
        }

        self::initialize($siteId);

        try {
            $paymentMethods = \Paynl\Paymentmethods::getList();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function syncPaymentMethods($siteId = null)
    {
        $site = Sites::get($siteId);

        self::initialize($siteId);

        if (! Customsetting::get('paynl_connected', $site['id'])) {
            return;
        }

        try {
            $allPaymentMethods = \Paynl\Paymentmethods::getList();
        } catch (Exception $exception) {
            $allPaymentMethods = [];
        }

        foreach ($allPaymentMethods as $allPaymentMethod) {
            if (! PaymentMethod::where('psp', 'paynl')->where('psp_id', $allPaymentMethod['id'])->count()) {

                $paymentMethod = new PaymentMethod();
                $paymentMethod->site_id = $site['id'];
                $paymentMethod->available_from_amount = $allPaymentMethod['min_amount'] ?: 0;
                $paymentMethod->psp_id = $allPaymentMethod['id'];
                $paymentMethod->psp = 'paynl';
                foreach (Locales::getLocales() as $locale) {
                    $paymentMethod->setTranslation('name', $locale['id'], $allPaymentMethod['visibleName']);
                }
            }

            $image = file_get_contents('https://static.pay.nl/' . $allPaymentMethod['brand']['image']);
            $imagePath = '/dashed/payment-methods/paynl/' . $allPaymentMethod['id'] . '.png';
            Storage::disk('dashed')->put($imagePath, $image);
            $paymentMethod->image = $imagePath;
            $paymentMethod->save();
        }
    }

    public static function startTransaction(OrderPayment $orderPayment)
    {
        $orderPayment->psp = 'paynl';
        $orderPayment->save();

        $siteId = Sites::getActive();

        self::initialize($siteId);

        $paynlProducts = [];
        foreach ($orderPayment->order->orderProducts as $orderProduct) {
            array_push(
                $paynlProducts,
                [
                    'id' => $orderProduct->sku,
                    'name' => $orderProduct->name,
                    'price' => $orderProduct->price,
                    'qty' => $orderProduct->quantity,
                    'vatPercentage' => $orderProduct->vat_rate,
                    'type' => \Paynl\Transaction::PRODUCT_TYPE_ARTICLE,
                ]
            );
        }

        $result = \Paynl\Transaction::start([
            'amount' => number_format($orderPayment->amount, 2, '.', ''),
            'returnUrl' => route('dashed.frontend.checkout.complete') . '?orderId=' . $orderPayment->order->hash . '&paymentId=' . $orderPayment->hash,
            'ipaddress' => request()->ip(),
            'paymentMethod' => $orderPayment->paymentMethod->psp_id,
            'currency' => 'EUR',
            'testmode' => Customsetting::get('paynl_test_mode', $siteId, false) ? true : false,

            'exchangeUrl' => route('dashed.frontend.checkout.exchange'),
            'description' => Translation::get('order-by-store', 'orders', 'Order by :storeName:', 'text', [
                'storeName' => Customsetting::get('site_name'),
            ]),
            'orderNumber' => $orderPayment->order->id,
            'products' => $paynlProducts,
            'invoiceDate' => $orderPayment->order->created_at,
            'enduser' => [
                'firstName' => $orderPayment->order->first_name,
                'lastName' => $orderPayment->order->last_name,
                'phoneNumber' => $orderPayment->order->phone_number,
                'emailAddress' => $orderPayment->order->email,
                'initials' => $orderPayment->order->initials,
                'gender' => $orderPayment->order->gender,
                'dob' => $orderPayment->order->date_of_birth,
            ],
            'address' => [
                'streetName' => $orderPayment->order->street ?: '',
                'houseNumber' => $orderPayment->order->house_nr ?: '',
                'zipCode' => $orderPayment->order->zip_code ?: '',
                'city' => $orderPayment->order->city ?: '',
                'country' => Countries::getCountryIsoCode($orderPayment->order->country) ?? '',
            ],
            'invoiceAddress' => [
                'initials' => $orderPayment->order->initials,
                'lastName' => $orderPayment->order->last_name,
                'streetName' => $orderPayment->order->invoice_street ?: $orderPayment->order->street,
                'houseNumber' => $orderPayment->order->invoice_house_nr ?: $orderPayment->order->house_nr,
                'zipCode' => $orderPayment->order->invoice_zip_code ?: $orderPayment->order->zip_code,
                'city' => $orderPayment->order->invoice_city ?: $orderPayment->order->city,
                'country' => Countries::getCountryIsoCode($orderPayment->order->invoice_country ?: $orderPayment->order->country),
            ],
        ]);

        $orderPayment->psp_id = $result->getTransactionId();
        $orderPayment->save();

        return [
            'transaction' => $result,
            'redirectUrl' => $result->getRedirectUrl(),
        ];
    }

    public static function getOrderStatus(OrderPayment $orderPayment): string
    {
        $site = Sites::getActive();

        self::initialize($site);

        try {
            $payment = \Paynl\Transaction::get($orderPayment->psp_id);
        } catch (Exception $exception) {
            return 'pending';
        }

        if ($payment->isPaid()) {
            return 'paid';
        } elseif ($payment->isRefunded(true)) {
            return 'refunded';
        } elseif ($payment->isCancelled()) {
            return 'cancelled';
        } else {
            return 'pending';
        }
    }
}
