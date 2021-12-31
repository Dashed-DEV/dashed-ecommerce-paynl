<?php

namespace Qubiqx\QcommerceEcommercePaynl\Filament\Pages\Settings;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Qubiqx\QcommerceCore\Classes\Sites;
use Qubiqx\QcommerceCore\Models\Customsetting;
use Qubiqx\QcommerceEcommerceWebwinkelkeur\Classes\Webwinkelkeur;

class PayNLSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $title = 'PayNL';

    protected static string $view = 'qcommerce-core::settings.pages.default-settings';

    public function mount(): void
    {
        $formData = [];
        $sites = Sites::getSites();
        foreach ($sites as $site) {
            $formData["paynl_at_hash_{$site['id']}"] = Customsetting::get('paynl_at_hash', $site['id']);
            $formData["paynl_sl_code_{$site['id']}"] = Customsetting::get('paynl_sl_code', $site['id']);
            $formData["paynl_test_mode_{$site['id']}"] = Customsetting::get('paynl_test_mode', $site['id']);
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
                    ->label("Webwinkelkeur is " . (! Customsetting::get('webwinkelkeur_connected', $site['id'], 0) ? 'niet' : '') . ' geconnect')
                    ->content(Customsetting::get('webwinkelkeur_connection_error', $site['id'], ''))
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 2,
                    ]),
                TextInput::make("webwinkelkeur_client_id_{$site['id']}")
                    ->label('Webwinkelkeur Client ID')
                    ->rules([
                        'max:255',
                    ]),
                TextInput::make("webwinkelkeur_auth_token_{$site['id']}")
                    ->label('Webwinkelkeur Auth Token')
                    ->rules([
                        'max:255',
                    ]),
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

    public function submit()
    {
        $sites = Sites::getSites();

        foreach ($sites as $site) {
            Customsetting::set('webwinkelkeur_client_id', $this->form->getState()["webwinkelkeur_client_id_{$site['id']}"], $site['id']);
            Customsetting::set('webwinkelkeur_auth_token', $this->form->getState()["webwinkelkeur_auth_token_{$site['id']}"], $site['id']);
            Customsetting::set('webwinkelkeur_connected', Webwinkelkeur::isConnected($site['id']), $site['id']);
        }

        $this->notify('success', 'De Webwinkelkeur instellingen zijn opgeslagen');

        return redirect(WebwinkelkeurSettingsPage::getUrl());
    }
}
