<?php

namespace Qubiqx\QcommerceEcommercePaynl;

use Filament\PluginServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Qubiqx\QcommerceEcommercePaynl\Classes\PayNL;
use Qubiqx\QcommerceEcommercePaynl\Commands\SyncPayNLPaymentMethods;
use Qubiqx\QcommerceEcommercePaynl\Filament\Pages\Settings\PayNLSettingsPage;
use Spatie\LaravelPackageTools\Package;

class QcommerceEcommercePaynlServiceProvider extends PluginServiceProvider
{
    public static string $name = 'qcommerce-ecommerce-paynl';

    public function bootingPackage()
    {
        $this->app->booted(function () {
            $schedule = app(Schedule::class);
            $schedule->command(SyncPayNLPaymentMethods::class)->daily();
        });
    }

    public function configurePackage(Package $package): void
    {
        cms()->builder(
            'settingPages',
            array_merge(cms()->builder('settingPages'), [
                'paynl' => [
                    'name' => 'PayNL',
                    'description' => 'Link PayNL aan je webshop',
                    'icon' => 'cash',
                    'page' => PayNLSettingsPage::class,
                ],
            ])
        );

        ecommerce()->builder(
            'paymentServiceProviders',
            array_merge(ecommerce()->builder('paymentServiceProviders'), [
                'paynl' => [
                    'name' => 'PayNL',
                    'class' => PayNL::class,
                ],
            ])
        );

        $package
            ->name('qcommerce-ecommerce-paynl')
            ->hasCommands([
                SyncPayNLPaymentMethods::class,
            ]);
    }

    protected function getPages(): array
    {
        return array_merge(parent::getPages(), [
            PayNLSettingsPage::class,
        ]);
    }
}
