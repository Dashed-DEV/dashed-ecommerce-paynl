<?php

namespace Dashed\DashedEcommercePaynl;

use Dashed\DashedEcommercePaynl\Filament\Pages\Settings\PayNLSettingsPage;
use Filament\Contracts\Plugin;
use Filament\Panel;

class DashedEcommercePaynlPlugin implements Plugin
{
    public function getId(): string
    {
        return 'dashed-ecommerce-paynl';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->pages([
                PayNLSettingsPage::class,
            ]);
    }

    public function boot(Panel $panel): void
    {

    }
}
