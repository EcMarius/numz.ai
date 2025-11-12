<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class WHMCSSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $view = 'filament.pages.whmcs-settings';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'WHMCS Settings';

    protected static ?int $navigationSort = 99;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->getSettings());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Tabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('General')
                            ->icon('heroicon-o-cog')
                            ->schema([
                                Forms\Components\Section::make('Currency Settings')
                                    ->description('Configure default currency and formatting')
                                    ->schema([
                                        Forms\Components\Select::make('currency_default')
                                            ->label('Default Currency')
                                            ->options([
                                                'USD' => 'US Dollar ($)',
                                                'EUR' => 'Euro (€)',
                                                'GBP' => 'British Pound (£)',
                                                'AUD' => 'Australian Dollar (A$)',
                                                'CAD' => 'Canadian Dollar (C$)',
                                                'INR' => 'Indian Rupee (₹)',
                                            ])
                                            ->required()
                                            ->default('USD'),

                                        Forms\Components\TextInput::make('currency_prefix')
                                            ->label('Currency Prefix')
                                            ->default('$')
                                            ->maxLength(5),

                                        Forms\Components\TextInput::make('currency_suffix')
                                            ->label('Currency Suffix')
                                            ->maxLength(5),

                                        Forms\Components\TextInput::make('currency_decimals')
                                            ->label('Decimal Places')
                                            ->numeric()
                                            ->default(2)
                                            ->minValue(0)
                                            ->maxValue(4),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Date & Time')
                                    ->description('Date and time formatting')
                                    ->schema([
                                        Forms\Components\TextInput::make('date_format')
                                            ->label('Date Format')
                                            ->default('d/m/Y')
                                            ->helperText('PHP date format (e.g., d/m/Y, m/d/Y, Y-m-d)'),

                                        Forms\Components\TextInput::make('datetime_format')
                                            ->label('DateTime Format')
                                            ->default('d/m/Y H:i:s')
                                            ->helperText('PHP datetime format'),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('Invoicing')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Forms\Components\Section::make('Invoice Generation')
                                    ->schema([
                                        Forms\Components\Toggle::make('invoicing_auto_create')
                                            ->label('Auto-Generate Recurring Invoices')
                                            ->helperText('Automatically create invoices for recurring services')
                                            ->default(true),

                                        Forms\Components\TextInput::make('invoicing_grace_days')
                                            ->label('Grace Period (Days)')
                                            ->helperText('Days before due date to generate invoice')
                                            ->numeric()
                                            ->default(7)
                                            ->minValue(0)
                                            ->maxValue(90),

                                        Forms\Components\TagsInput::make('invoicing_reminder_days')
                                            ->label('Payment Reminder Days')
                                            ->helperText('Days before due date to send reminders (e.g., 7, 3, 1)')
                                            ->default(['7', '3', '1'])
                                            ->separator(','),

                                        Forms\Components\TagsInput::make('invoicing_overdue_days')
                                            ->label('Overdue Notice Days')
                                            ->helperText('Days after due date to send overdue notices')
                                            ->default(['1', '3', '7'])
                                            ->separator(','),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('Provisioning')
                            ->icon('heroicon-o-server')
                            ->schema([
                                Forms\Components\Section::make('Automatic Provisioning')
                                    ->schema([
                                        Forms\Components\Toggle::make('provisioning_auto_create')
                                            ->label('Auto-Provision Services')
                                            ->helperText('Automatically provision services when invoice is paid')
                                            ->default(true),

                                        Forms\Components\Toggle::make('provisioning_auto_suspend')
                                            ->label('Auto-Suspend Overdue Services')
                                            ->helperText('Automatically suspend services when payment is overdue')
                                            ->default(true),

                                        Forms\Components\TextInput::make('provisioning_suspension_grace_days')
                                            ->label('Suspension Grace Period (Days)')
                                            ->helperText('Days after due date before suspension')
                                            ->numeric()
                                            ->default(3)
                                            ->minValue(0)
                                            ->maxValue(30),

                                        Forms\Components\Toggle::make('provisioning_auto_terminate')
                                            ->label('Auto-Terminate Suspended Services')
                                            ->helperText('Automatically terminate services after extended suspension')
                                            ->default(false),

                                        Forms\Components\TextInput::make('provisioning_termination_days')
                                            ->label('Termination Period (Days)')
                                            ->helperText('Days suspended before termination')
                                            ->numeric()
                                            ->default(30)
                                            ->minValue(1)
                                            ->maxValue(365)
                                            ->visible(fn (Forms\Get $get) => $get('provisioning_auto_terminate')),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('Domains')
                            ->icon('heroicon-o-globe-alt')
                            ->schema([
                                Forms\Components\Section::make('Domain Management')
                                    ->schema([
                                        Forms\Components\Toggle::make('domains_auto_renew')
                                            ->label('Enable Auto-Renew by Default')
                                            ->helperText('New domains will have auto-renew enabled')
                                            ->default(false),

                                        Forms\Components\Toggle::make('domains_sync_enabled')
                                            ->label('Enable Domain Sync')
                                            ->helperText('Synchronize domain status with registrars')
                                            ->default(true),

                                        Forms\Components\TextInput::make('domains_sync_interval')
                                            ->label('Sync Interval (Hours)')
                                            ->helperText('How often to sync with registrars')
                                            ->numeric()
                                            ->default(24)
                                            ->minValue(1)
                                            ->maxValue(168)
                                            ->visible(fn (Forms\Get $get) => $get('domains_sync_enabled')),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('Tax')
                            ->icon('heroicon-o-calculator')
                            ->schema([
                                Forms\Components\Section::make('Tax Configuration')
                                    ->schema([
                                        Forms\Components\Toggle::make('tax_enabled')
                                            ->label('Enable Tax')
                                            ->default(false)
                                            ->live(),

                                        Forms\Components\TextInput::make('tax_name')
                                            ->label('Tax Name')
                                            ->default('VAT')
                                            ->maxLength(50)
                                            ->visible(fn (Forms\Get $get) => $get('tax_enabled')),

                                        Forms\Components\TextInput::make('tax_rate')
                                            ->label('Tax Rate (%)')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->step(0.01)
                                            ->suffix('%')
                                            ->visible(fn (Forms\Get $get) => $get('tax_enabled')),

                                        Forms\Components\Toggle::make('tax_inclusive')
                                            ->label('Tax Inclusive Pricing')
                                            ->helperText('Prices include tax')
                                            ->default(false)
                                            ->visible(fn (Forms\Get $get) => $get('tax_enabled')),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('Email')
                            ->icon('heroicon-o-envelope')
                            ->schema([
                                Forms\Components\Section::make('Email Settings')
                                    ->schema([
                                        Forms\Components\TextInput::make('email_from_name')
                                            ->label('From Name')
                                            ->default(config('app.name'))
                                            ->maxLength(100),

                                        Forms\Components\TextInput::make('email_from_email')
                                            ->label('From Email')
                                            ->email()
                                            ->default(config('mail.from.address')),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Email Notifications')
                                    ->description('Enable/disable automatic email notifications')
                                    ->schema([
                                        Forms\Components\Toggle::make('email_send_account')
                                            ->label('Account Emails')
                                            ->helperText('Welcome, password reset, etc.')
                                            ->default(true),

                                        Forms\Components\Toggle::make('email_send_product')
                                            ->label('Product Emails')
                                            ->helperText('Product welcome, suspension, etc.')
                                            ->default(true),

                                        Forms\Components\Toggle::make('email_send_domain')
                                            ->label('Domain Emails')
                                            ->helperText('Domain registration, renewal, etc.')
                                            ->default(true),

                                        Forms\Components\Toggle::make('email_send_invoice')
                                            ->label('Invoice Emails')
                                            ->helperText('Invoice created, payment received, etc.')
                                            ->default(true),

                                        Forms\Components\Toggle::make('email_send_support')
                                            ->label('Support Emails')
                                            ->helperText('Ticket opened, reply, etc.')
                                            ->default(true),
                                    ])
                                    ->columns(3),
                            ]),

                        Forms\Components\Tabs\Tab::make('Client Area')
                            ->icon('heroicon-o-user-group')
                            ->schema([
                                Forms\Components\Section::make('Client Area Settings')
                                    ->schema([
                                        Forms\Components\TextInput::make('client_page_title')
                                            ->label('Page Title')
                                            ->default(config('app.name') . ' - Client Area')
                                            ->maxLength(100),

                                        Forms\Components\Select::make('client_default_theme')
                                            ->label('Default Theme')
                                            ->options([
                                                'twenty-one' => 'Twenty-One (Modern)',
                                                'six' => 'Six (Legacy)',
                                            ])
                                            ->default('twenty-one'),

                                        Forms\Components\Toggle::make('client_allow_registration')
                                            ->label('Allow Client Registration')
                                            ->default(true),

                                        Forms\Components\Toggle::make('client_require_verification')
                                            ->label('Require Email Verification')
                                            ->default(true),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('Security')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Forms\Components\Section::make('Security Settings')
                                    ->schema([
                                        Forms\Components\TextInput::make('security_password_strength')
                                            ->label('Minimum Password Strength')
                                            ->helperText('0-100 scale, higher is more secure')
                                            ->numeric()
                                            ->default(60)
                                            ->minValue(0)
                                            ->maxValue(100),

                                        Forms\Components\TextInput::make('security_session_timeout')
                                            ->label('Session Timeout (Seconds)')
                                            ->helperText('Auto-logout after inactivity')
                                            ->numeric()
                                            ->default(3600)
                                            ->minValue(300)
                                            ->maxValue(86400),

                                        Forms\Components\Toggle::make('security_two_factor')
                                            ->label('Enable Two-Factor Authentication')
                                            ->default(false),

                                        Forms\Components\Toggle::make('security_csrf_protection')
                                            ->label('CSRF Protection')
                                            ->default(true)
                                            ->disabled(),

                                        Forms\Components\Toggle::make('security_xss_protection')
                                            ->label('XSS Protection')
                                            ->default(true)
                                            ->disabled(),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('Cron')
                            ->icon('heroicon-o-clock')
                            ->schema([
                                Forms\Components\Section::make('Cron Configuration')
                                    ->description('Automated task configuration')
                                    ->schema([
                                        Forms\Components\Placeholder::make('cron_url')
                                            ->label('Cron URL')
                                            ->content(fn () => url('/cron.php?cron_key=' . $this->getCronKey()))
                                            ->helperText('Use this URL for your cron job'),

                                        Forms\Components\TextInput::make('cron_key')
                                            ->label('Cron Security Key')
                                            ->default(fn () => $this->getCronKey() ?: \Str::random(32))
                                            ->helperText('Key required to access cron.php via web'),

                                        Forms\Components\Textarea::make('cron_ip_whitelist')
                                            ->label('IP Whitelist')
                                            ->helperText('One IP address per line (leave empty to allow all)')
                                            ->rows(3),

                                        Forms\Components\Placeholder::make('cron_command')
                                            ->label('CLI Command')
                                            ->content('php artisan whmcs:cron --all')
                                            ->helperText('Alternative: run via command line'),
                                    ]),

                                Forms\Components\Section::make('Cron Schedule')
                                    ->description('Recommended cron schedule: */5 * * * * (every 5 minutes)')
                                    ->schema([
                                        Forms\Components\Placeholder::make('cron_setup')
                                            ->content(function () {
                                                return new \Illuminate\Support\HtmlString('
                                                    <div class="text-sm space-y-2">
                                                        <p><strong>Add this to your crontab:</strong></p>
                                                        <code class="block bg-gray-100 dark:bg-gray-800 p-2 rounded">
                                                            */5 * * * * cd ' . base_path() . ' && php artisan whmcs:cron --all >> /dev/null 2>&1
                                                        </code>
                                                        <p class="text-gray-600 dark:text-gray-400"><strong>Or via wget:</strong></p>
                                                        <code class="block bg-gray-100 dark:bg-gray-800 p-2 rounded">
                                                            */5 * * * * wget -q -O- "' . url('/cron.php?cron_key=' . $this->getCronKey()) . '" >> /dev/null 2>&1
                                                        </code>
                                                    </div>
                                                ');
                                            }),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }

            DB::table('tblconfiguration')->updateOrInsert(
                ['setting' => "whmcs.{$key}"],
                ['value' => $value, 'updated_at' => now()]
            );
        }

        Notification::make()
            ->title('Settings Saved')
            ->success()
            ->send();
    }

    protected function getSettings(): array
    {
        $settings = DB::table('tblconfiguration')
            ->where('setting', 'LIKE', 'whmcs.%')
            ->get();

        $data = [];
        foreach ($settings as $setting) {
            $key = str_replace('whmcs.', '', $setting->setting);

            // Try to decode JSON
            $value = $setting->value;
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $value = $decoded;
            }

            $data[$key] = $value;
        }

        // Merge with defaults
        $defaults = $this->getDefaultSettings();
        return array_merge($defaults, $data);
    }

    protected function getDefaultSettings(): array
    {
        $defaults = config('whmcs.defaults');

        $flat = [];
        foreach ($defaults as $group => $settings) {
            if (is_array($settings)) {
                foreach ($settings as $key => $value) {
                    $flat["{$group}_{$key}"] = $value;
                }
            } else {
                $flat[$group] = $settings;
            }
        }

        return $flat;
    }

    protected function getCronKey(): ?string
    {
        $key = DB::table('tblconfiguration')
            ->where('setting', 'whmcs.cron_key')
            ->value('value');

        if (!$key) {
            $key = \Str::random(32);
            DB::table('tblconfiguration')->insert([
                'setting' => 'whmcs.cron_key',
                'value' => $key,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $key;
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('save')
                ->label('Save Settings')
                ->action('save'),
        ];
    }
}
