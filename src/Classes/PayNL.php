<?php

namespace Qubiqx\QcommerceEcommercePaynl\Classes;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Qubiqx\QcommerceCore\Classes\Locales;
use Qubiqx\QcommerceCore\Classes\Sites;
use Qubiqx\QcommerceCore\Models\Customsetting;
use Qubiqx\QcommerceEcommerceCore\Classes\Countries;
use Qubiqx\QcommerceEcommerceCore\Models\OrderPayment;
use Qubiqx\QcommerceEcommerceCore\Models\PaymentMethod;

class PayNL
{
    public static function initialize($siteId = null)
    {
        if (! $siteId) {
            $siteId = Sites::getActive();
        }

//        \Paynl\Config::setTokenCode(Customsetting::get('paynl_at_code', $siteId));
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

        $allPaymentMethods = \Paynl\Paymentmethods::getList();
        foreach ($allPaymentMethods as $allPaymentMethod) {
            if (! PaymentMethod::where('psp', 'paynl')->where('psp_id', $allPaymentMethod['id'])->count()) {
                $image = file_get_contents('https://static.pay.nl/' . $allPaymentMethod['brand']['image']);
                $imagePath = '/qcommerce/payment-methods/' . $allPaymentMethod['id'] . '.png';
                Storage::put($imagePath, $image);

                $paymentMethod = new PaymentMethod();
                $paymentMethod->site_id = $site['id'];
                $paymentMethod->available_from_amount = $allPaymentMethod['min_amount'] ?: 0;
                $paymentMethod->psp = 'paynl';
                $paymentMethod->psp_id = $allPaymentMethod['id'];
                $paymentMethod->image = $imagePath;
                foreach (Locales::getLocales() as $locale) {
                    $paymentMethod->setTranslation('name', $locale['id'], $allPaymentMethod['visibleName']);
                }
                $paymentMethod->save();
            }
        }
    }

//    public static function getPaymentMethods($siteId = null, $cache = true)
//    {
//        $site = Sites::get($siteId);
//
//        self::initialize($siteId);
//
//        if (!Customsetting::get('paynl_connected', $site['id'])) {
//            return;
//        }
//
//        if (!$cache) {
//            Cache::forget('paynl-payment-methods-' . $site['id']);
//        }
//
//        $paymentMethods = Cache::remember('paynl-payment-methods-' . $site['id'], 60 * 60 * 24, function () use ($site) {
//            $allPaymentMethods = \Paynl\Paymentmethods::getList();
//            $paymentMethods = [];
//            foreach ($allPaymentMethods as $paymentMethod) {
//                $paymentMethod['active'] = Customsetting::get('paynl_payment_method_' . $paymentMethod['id'], $site['id'], 0) ? true : false;
//                $paymentMethod['postpay'] = Customsetting::get('paynl_payment_method_postpay_' . $paymentMethod['id'], $site['id'], 0) ? true : false;
//                $paymentMethod['costs'] = Customsetting::get('paynl_payment_method_costs_' . $paymentMethod['id'], $site['id'], 0);
//                $paymentMethod['additional_info'] = Customsetting::get('paynl_payment_method_additional_info_' . $paymentMethod['id'], $site['id']);
//                $paymentMethod['payment_instructions'] = Customsetting::get('paynl_payment_method_payment_instructions_' . $paymentMethod['id'], $site['id']);
//                $paymentMethods[] = $paymentMethod;
//            }
//
//            return $paymentMethods;
//        });
//
//        return $paymentMethods;
//    }

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
            'returnUrl' => route('qcommerce.frontend.checkout.complete') . '?orderId=' . $orderPayment->order->hash . '&paymentId=' . $orderPayment->hash,
            'ipaddress' => request()->ip(),
            'paymentMethod' => str_replace('paynl_', '', $orderPayment->psp_payment_method_id),
            'currency' => 'EUR',
            'testmode' => Customsetting::get('paynl_test_mode', $siteId, false) ? true : false,

            'exchangeUrl' => route('qcommerce.frontend.checkout.exchange'),
            'description' => Translation::get('order-by-store', 'orders', 'Order by :storeName:', 'text', [
                'storeName' => Customsetting::get('store_name'),
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

        return $result;
    }

    public static function getOrderStatus(OrderPayment $orderPayment)
    {
        $site = Sites::getActive();

        self::initialize($site);

        $payment = \Paynl\Transaction::get($orderPayment->psp_id);

        if ($payment->isPaid()) {
            return 'paid';
        } elseif ($payment->isRefunded(true)) {
            return '';
        } elseif ($payment->isCancelled()) {
            return 'cancelled';
        } else {
            return 'pending';
        }
    }
}
