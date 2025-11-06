<?php

namespace App\Filament\Pages;

use App\Models\SystemSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class Settings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $view = 'filament.pages.settings';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 10;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->getFormData());
    }

    protected function getFormData(): array
    {
        $settings = SystemSetting::all()->mapWithKeys(function ($setting) {
            return [$setting->key => $setting->getTypedValue()];
        });

        return $settings->toArray();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Settings')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('General')
                            ->icon('heroicon-o-cog')
                            ->schema([
                                Forms\Components\TextInput::make('company_name')
                                    ->label('Company Name')
                                    ->default('My Hosting Company'),
                                Forms\Components\TextInput::make('company_url')
                                    ->label('Company URL')
                                    ->url()
                                    ->default('https://example.com'),
                                Forms\Components\TextInput::make('support_email')
                                    ->label('Support Email')
                                    ->email()
                                    ->default('support@example.com'),
                                Forms\Components\TextInput::make('admin_email')
                                    ->label('Admin Email')
                                    ->email()
                                    ->default('admin@example.com'),
                                Forms\Components\Select::make('default_currency')
                                    ->label('Default Currency')
                                    ->options([
                                        'USD' => 'USD - US Dollar',
                                        'EUR' => 'EUR - Euro',
                                        'GBP' => 'GBP - British Pound',
                                        'CAD' => 'CAD - Canadian Dollar',
                                        'AUD' => 'AUD - Australian Dollar',
                                    ])
                                    ->default('USD'),
                                Forms\Components\Select::make('default_timezone')
                                    ->label('Default Timezone')
                                    ->options(collect(timezone_identifiers_list())->mapWithKeys(fn($tz) => [$tz => $tz]))
                                    ->searchable()
                                    ->default('UTC'),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('Billing')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Forms\Components\TextInput::make('invoice_prefix')
                                    ->label('Invoice Prefix')
                                    ->default('INV'),
                                Forms\Components\TextInput::make('invoice_due_days')
                                    ->label('Invoice Due Days')
                                    ->numeric()
                                    ->default(14),
                                Forms\Components\TextInput::make('auto_suspend_days')
                                    ->label('Auto Suspend After (days)')
                                    ->numeric()
                                    ->default(7)
                                    ->helperText('Suspend services after X days overdue'),
                                Forms\Components\TextInput::make('auto_terminate_days')
                                    ->label('Auto Terminate After (days)')
                                    ->numeric()
                                    ->default(30)
                                    ->helperText('Terminate services after X days overdue'),
                                Forms\Components\TextInput::make('late_fee_percentage')
                                    ->label('Late Fee Percentage')
                                    ->numeric()
                                    ->default(5)
                                    ->suffix('%'),
                                Forms\Components\Toggle::make('enable_credit_system')
                                    ->label('Enable Credit System')
                                    ->default(true),
                                Forms\Components\Toggle::make('allow_partial_payments')
                                    ->label('Allow Partial Payments')
                                    ->default(true),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('Email')
                            ->icon('heroicon-o-envelope')
                            ->schema([
                                Forms\Components\Select::make('mail_driver')
                                    ->label('Mail Driver')
                                    ->options([
                                        'smtp' => 'SMTP',
                                        'sendmail' => 'Sendmail',
                                        'mailgun' => 'Mailgun',
                                        'postmark' => 'Postmark',
                                        'ses' => 'Amazon SES',
                                    ])
                                    ->default('smtp'),
                                Forms\Components\TextInput::make('mail_from_address')
                                    ->label('From Email Address')
                                    ->email()
                                    ->default('noreply@example.com'),
                                Forms\Components\TextInput::make('mail_from_name')
                                    ->label('From Name')
                                    ->default('My Hosting Company'),
                                Forms\Components\Toggle::make('enable_email_notifications')
                                    ->label('Enable Email Notifications')
                                    ->default(true),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('AI & Automation')
                            ->icon('heroicon-o-cpu-chip')
                            ->schema([
                                Forms\Components\Toggle::make('enable_ai_features')
                                    ->label('Enable AI Features')
                                    ->default(false)
                                    ->reactive(),
                                Forms\Components\Select::make('ai_provider')
                                    ->label('AI Provider')
                                    ->options([
                                        'openai' => 'OpenAI',
                                        'anthropic' => 'Anthropic (Claude)',
                                    ])
                                    ->default('openai')
                                    ->visible(fn (callable $get) => $get('enable_ai_features')),
                                Forms\Components\Select::make('ai_model')
                                    ->label('AI Model')
                                    ->options([
                                        'gpt-4' => 'GPT-4',
                                        'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
                                        'claude-3-opus' => 'Claude 3 Opus',
                                        'claude-3-sonnet' => 'Claude 3 Sonnet',
                                    ])
                                    ->default('gpt-4')
                                    ->visible(fn (callable $get) => $get('enable_ai_features')),
                                Forms\Components\Toggle::make('enable_chatbot')
                                    ->label('Enable AI Chatbot')
                                    ->default(false),
                                Forms\Components\Toggle::make('enable_churn_prediction')
                                    ->label('Enable Churn Prediction')
                                    ->default(false),
                                Forms\Components\Toggle::make('enable_fraud_detection')
                                    ->label('Enable Fraud Detection')
                                    ->default(false),
                                Forms\Components\Toggle::make('enable_sentiment_analysis')
                                    ->label('Enable Sentiment Analysis')
                                    ->default(false),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('Security')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Forms\Components\Toggle::make('enforce_2fa')
                                    ->label('Enforce 2FA for Admins')
                                    ->default(false),
                                Forms\Components\Toggle::make('enable_ip_whitelist')
                                    ->label('Enable IP Whitelist')
                                    ->default(false),
                                Forms\Components\TextInput::make('session_lifetime')
                                    ->label('Session Lifetime (minutes)')
                                    ->numeric()
                                    ->default(120),
                                Forms\Components\TextInput::make('password_min_length')
                                    ->label('Minimum Password Length')
                                    ->numeric()
                                    ->default(8),
                                Forms\Components\Toggle::make('enable_audit_log')
                                    ->label('Enable Audit Log')
                                    ->default(true),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('Support')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->schema([
                                Forms\Components\Toggle::make('enable_support_tickets')
                                    ->label('Enable Support Tickets')
                                    ->default(true),
                                Forms\Components\Toggle::make('enable_live_chat')
                                    ->label('Enable Live Chat')
                                    ->default(false),
                                Forms\Components\Toggle::make('enable_knowledge_base')
                                    ->label('Enable Knowledge Base')
                                    ->default(true),
                                Forms\Components\TextInput::make('default_ticket_priority')
                                    ->label('Default Ticket Priority')
                                    ->default('normal'),
                            ])->columns(2),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            SystemSetting::set($key, $value, $this->inferGroup($key));
        }

        Notification::make()
            ->title('Settings saved successfully')
            ->success()
            ->send();
    }

    private function inferGroup(string $key): string
    {
        if (str_contains($key, 'invoice') || str_contains($key, 'billing') || str_contains($key, 'payment')) {
            return 'billing';
        }
        if (str_contains($key, 'mail') || str_contains($key, 'email')) {
            return 'email';
        }
        if (str_contains($key, 'ai') || str_contains($key, 'chatbot')) {
            return 'ai';
        }
        if (str_contains($key, 'security') || str_contains($key, '2fa') || str_contains($key, 'password')) {
            return 'security';
        }
        if (str_contains($key, 'support') || str_contains($key, 'ticket')) {
            return 'support';
        }

        return 'general';
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('save')
                ->label('Save Settings')
                ->submit('save'),
        ];
    }
}
