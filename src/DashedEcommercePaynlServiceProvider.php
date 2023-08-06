<?php

namespace Dashed\DashedEcommercePaynl;

use Filament\PluginServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Dashed\DashedEcommercePaynl\Classes\PayNL;
use Dashed\DashedEcommercePaynl\Commands\SyncPayNLPaymentMethodsCommand;
use Dashed\DashedEcommercePaynl\Filament\Pages\Settings\PayNLSettingsPage;
use Spatie\LaravelPackageTools\Package;

class DashedEcommercePaynlServiceProvider extends PluginServiceProvider
{
    public static string $name = 'dashed-ecommerce-paynl';

    public function bootingPackage()
    {
        $this->app->booted(function () {
            $schedule = app(Schedule::class);
            $schedule->command(SyncPayNLPaymentMethodsCommand::class)->daily();
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
            ->name('dashed-ecommerce-paynl')
            ->hasCommands([
                SyncPayNLPaymentMethodsCommand::class,
            ]);
    }

    protected function getPages(): array
    {
        return array_merge(parent::getPages(), [
            PayNLSettingsPage::class,
        ]);
    }
}
