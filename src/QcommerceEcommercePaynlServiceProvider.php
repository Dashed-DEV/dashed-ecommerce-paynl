<?php

namespace Qubiqx\QcommerceEcommercePaynl;

use Filament\PluginServiceProvider;
use Qubiqx\QcommerceEcommercePaynl\Filament\Pages\Settings\PayNLSettingsPage;
use Spatie\LaravelPackageTools\Package;

class QcommerceEcommercePaynlServiceProvider extends PluginServiceProvider
{
    public static string $name = 'qcommerce-ecommerce-paynl';

    public function configurePackage(Package $package): void
    {
        cms()->builder(
            'settingPages',
            array_merge(cms()->builder('settingPages'), [
                'paynl' => [
                    'name' => 'PayNL',
                    'description' => 'Link de betaalmethodes van PayNL',
                    'icon' => 'money',
                    'page' => PayNLSettingsPage::class,
                ],
            ])
        );

        $package
            ->name('qcommerce-ecommerce-paynl');
    }

    protected function getPages(): array
    {
        return array_merge(parent::getPages(), [
            PayNLSettingsPage::class,
        ]);
    }
}
