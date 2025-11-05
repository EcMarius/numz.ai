<?php

namespace App\Filament\Pages;

use App\Models\ModuleSetting;
use App\Numz\Modules\PaymentGateways\StripeGateway;
use App\Numz\Modules\PaymentGateways\PayPalGateway;
use App\Numz\Modules\PaymentGateways\PaysafecardGateway;
use App\Numz\Modules\PaymentGateways\CoinbaseGateway;
use App\Numz\Modules\PaymentGateways\TwoCheckoutGateway;
use App\Numz\Modules\PaymentGateways\RazorpayGateway;
use App\Numz\Modules\Registrars\DomainNameAPIRegistrar;
use App\Numz\Modules\Provisioning\OneProviderProvisioning;
use App\Numz\Modules\Integrations\TawkToIntegration;
use App\Numz\Modules\Integrations\SocialAuthModule;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use BackedEnum;

class ModuleSettings extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Module Settings';

    protected static ?string $title = 'Module Settings';

    protected static ?int $navigationSort = 99;

    protected static string $view = 'filament.pages.module-settings';

    public static function getNavigationGroup(): ?string
    {
        return 'NUMZ.AI';
    }

    public ?array $data = [];

    protected function getModules(): array
    {
        return [
            'payment_gateway' => [
                'label' => 'Payment Gateways',
                'modules' => [
                    'stripe' => new StripeGateway(),
                    'paypal' => new PayPalGateway(),
                    'paysafecard' => new PaysafecardGateway(),
                    'coinbase' => new CoinbaseGateway(),
                    'twocheckout' => new TwoCheckoutGateway(),
                    'razorpay' => new RazorpayGateway(),
                ],
            ],
            'registrar' => [
                'label' => 'Domain Registrars',
                'modules' => [
                    'domainnameapi' => new DomainNameAPIRegistrar(),
                ],
            ],
            'provisioning' => [
                'label' => 'Provisioning Modules',
                'modules' => [
                    'oneprovider' => new OneProviderProvisioning(),
                ],
            ],
            'integration' => [
                'label' => 'Integrations',
                'modules' => [
                    'tawkto' => new TawkToIntegration(),
                    'socialauth' => new SocialAuthModule(),
                ],
            ],
        ];
    }

    public function mount(): void
    {
        $this->form->fill($this->getFormData());
    }

    protected function getFormData(): array
    {
        $data = [];
        $modules = $this->getModules();

        foreach ($modules as $moduleType => $group) {
            foreach ($group['modules'] as $moduleName => $module) {
                $config = $module->getConfig();
                $key = "{$moduleType}_{$moduleName}";

                // Get enabled status
                $data["{$key}_enabled"] = ModuleSetting::get($moduleType, $moduleName, 'enabled', false) === 'true';

                // Get settings values
                if (isset($config['settings'])) {
                    foreach ($config['settings'] as $setting) {
                        $settingKey = "{$key}_{$setting['key']}";
                        $data[$settingKey] = ModuleSetting::get($moduleType, $moduleName, $setting['key'], '');
                    }
                }
            }
        }

        return $data;
    }

    public function form(Form $form): Form
    {
        $modules = $this->getModules();
        $tabs = [];

        foreach ($modules as $moduleType => $group) {
            $sections = [];

            foreach ($group['modules'] as $moduleName => $module) {
                $config = $module->getConfig();
                $key = "{$moduleType}_{$moduleName}";

                $fields = [
                    Toggle::make("{$key}_enabled")
                        ->label('Enabled')
                        ->inline(false)
                        ->default(false),
                ];

                // Add settings fields
                if (isset($config['settings'])) {
                    foreach ($config['settings'] as $setting) {
                        $settingKey = "{$key}_{$setting['key']}";

                        $field = match ($setting['type']) {
                            'password' => TextInput::make($settingKey)
                                ->label($setting['label'])
                                ->password()
                                ->revealable()
                                ->placeholder('Enter ' . strtolower($setting['label'])),
                            'checkbox', 'boolean' => Toggle::make($settingKey)
                                ->label($setting['label'])
                                ->inline(false),
                            default => TextInput::make($settingKey)
                                ->label($setting['label'])
                                ->placeholder('Enter ' . strtolower($setting['label'])),
                        };

                        $fields[] = $field;
                    }
                }

                $sections[] = Section::make($config['name'])
                    ->description($config['description'] ?? null)
                    ->schema($fields)
                    ->collapsible()
                    ->columns(1);
            }

            $tabs[] = Tabs\Tab::make($group['label'])
                ->schema($sections);
        }

        return $form
            ->schema([
                Tabs::make('Module Types')
                    ->tabs($tabs)
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $modules = $this->getModules();

        foreach ($modules as $moduleType => $group) {
            foreach ($group['modules'] as $moduleName => $module) {
                $config = $module->getConfig();
                $key = "{$moduleType}_{$moduleName}";

                // Save enabled status
                $enabledKey = "{$key}_enabled";
                if (array_key_exists($enabledKey, $data)) {
                    ModuleSetting::updateOrCreate(
                        [
                            'module_type' => $moduleType,
                            'module_name' => $moduleName,
                            'key' => 'enabled',
                        ],
                        [
                            'value' => $data[$enabledKey] ? 'true' : 'false',
                            'encrypted' => false,
                        ]
                    );
                }

                // Save settings
                if (isset($config['settings'])) {
                    foreach ($config['settings'] as $setting) {
                        $settingKey = "{$key}_{$setting['key']}";

                        if (array_key_exists($settingKey, $data)) {
                            $value = $data[$settingKey];

                            // Convert boolean values to strings
                            if (is_bool($value)) {
                                $value = $value ? 'true' : 'false';
                            }

                            ModuleSetting::updateOrCreate(
                                [
                                    'module_type' => $moduleType,
                                    'module_name' => $moduleName,
                                    'key' => $setting['key'],
                                ],
                                [
                                    'value' => $value,
                                    'encrypted' => $setting['encrypted'] ?? false,
                                ]
                            );
                        }
                    }
                }
            }
        }

        Notification::make()
            ->title('Settings saved successfully')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Save Settings')
                ->submit('save'),
        ];
    }
}
