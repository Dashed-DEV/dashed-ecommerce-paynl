<?php

namespace Dashed\DashedEcommercePaynl;

use Spatie\LaravelPackageTools\Package;
use Illuminate\Console\Scheduling\Schedule;
use Dashed\DashedEcommercePaynl\Classes\PayNL;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Dashed\DashedEcommercePaynl\Commands\SyncPayNLPinTerminalsCommand;
use Dashed\DashedEcommercePaynl\Commands\SyncPayNLPaymentMethodsCommand;
use Dashed\DashedEcommercePaynl\Filament\Pages\Settings\PayNLSettingsPage;

class DashedEcommercePaynlServiceProvider extends PackageServiceProvider
{
    public static string $name = 'dashed-ecommerce-paynl';

    public function bootingPackage()
    {
        $this->app->booted(function () {
            $schedule = app(Schedule::class);
            $schedule->command(SyncPayNLPaymentMethodsCommand::class)
                ->everyFifteenMinutes();
            $schedule->command(SyncPayNLPinTerminalsCommand::class)
                ->everyFifteenMinutes();
        });
    }

    public function configurePackage(Package $package): void
    {
        cms()->registerSettingsPage(PayNLSettingsPage::class, 'PayNL', 'banknotes', 'Link PayNL aan je webshop');

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
                SyncPayNLPinTerminalsCommand::class,
            ]);

        cms()->builder('plugins', [
            new DashedEcommercePaynlPlugin(),
        ]);
    }
}
