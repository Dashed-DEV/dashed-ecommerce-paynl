<?php

namespace Dashed\DashedEcommercePaynl\Filament\Pages\Settings;

use Filament\Pages\Page;
use Filament\Forms\Components\Tabs;
use Dashed\DashedCore\Classes\Sites;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Placeholder;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedEcommercePaynl\Classes\PayNL;

class PayNLSettingsPage extends Page
{
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $title = 'PayNL';

    protected static string $view = 'dashed-core::settings.pages.default-settings';
    public array $data = [];

    public function mount(): void
    {
        $formData = [];
        $sites = Sites::getSites();
        foreach ($sites as $site) {
            $formData["paynl_at_hash_{$site['id']}"] = Customsetting::get('paynl_at_hash', $site['id']);
            $formData["paynl_sl_code_{$site['id']}"] = Customsetting::get('paynl_sl_code', $site['id']);
            $formData["paynl_test_mode_{$site['id']}"] = Customsetting::get('paynl_test_mode', $site['id'], false) ? true : false;
            $formData["paynl_connected_{$site['id']}"] = Customsetting::get('paynl_connected', $site['id']);
        }

        $this->form->fill($formData);
    }

    protected function getFormSchema(): array
    {
        $sites = Sites::getSites();
        $tabGroups = [];

        $tabs = [];
        foreach ($sites as $site) {
            $schema = [
                Placeholder::make('label')
                    ->label("PayNL voor {$site['name']}")
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 2,
                    ]),
                Placeholder::make('label')
                    ->label("PayNL is " . (! Customsetting::get('paynl_connected', $site['id'], 0) ? 'niet' : '') . ' geconnect')
                    ->content(Customsetting::get('paynl_connection_error', $site['id'], ''))
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 2,
                    ]),
                TextInput::make("paynl_at_hash_{$site['id']}")
                    ->label('PayNL AT hash')
                    ->maxLength(255),
                TextInput::make("paynl_sl_code_{$site['id']}")
                    ->label('PayNL SL code')
                    ->maxLength(255),
                Toggle::make("paynl_test_mode_{$site['id']}")
                    ->label('Testmodus activeren'),
            ];

            $tabs[] = Tab::make($site['id'])
                ->label(ucfirst($site['name']))
                ->schema($schema)
                ->columns([
                    'default' => 1,
                    'lg' => 2,
                ]);
        }
        $tabGroups[] = Tabs::make('Sites')
            ->tabs($tabs);

        return $tabGroups;
    }

    public function getFormStatePath(): ?string
    {
        return 'data';
    }

    public function submit()
    {
        $sites = Sites::getSites();

        foreach ($sites as $site) {
            Customsetting::set('paynl_at_hash', $this->form->getState()["paynl_at_hash_{$site['id']}"], $site['id']);
            Customsetting::set('paynl_sl_code', $this->form->getState()["paynl_sl_code_{$site['id']}"], $site['id']);
            Customsetting::set('paynl_test_mode', $this->form->getState()["paynl_test_mode_{$site['id']}"], $site['id']);
            Customsetting::set('paynl_connected', PayNL::isConnected($site['id']), $site['id']);

            if (Customsetting::get('paynl_connected', $site['id'])) {
                PayNL::syncPaymentMethods($site['id']);
                PayNL::syncPinTerminals();
            }
        }

        Notification::make()
            ->title('De PayNL instellingen zijn opgeslagen')
            ->success()
            ->send();

        return redirect(PayNLSettingsPage::getUrl());
    }
}
