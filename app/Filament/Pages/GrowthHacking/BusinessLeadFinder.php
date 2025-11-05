<?php

namespace App\Filament\Pages\GrowthHacking;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use App\Models\SmtpConfig;
use App\Models\GrowthHackingCampaign;
use App\Jobs\ProcessGrowthHackingCampaign;
use Illuminate\Support\Facades\Auth;

class BusinessLeadFinder extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-rocket-launch';

    protected static ?string $navigationLabel = 'Business Lead Finder';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): string
    {
        return 'Growth Hacking';
    }

    public ?array $data = [];

    public function getView(): string
    {
        return 'filament.pages.growth-hacking.business-lead-finder';
    }

    public function mount(): void
    {
        $this->form->fill([
            'email_method' => 'site_smtp',
            'auto_create_accounts' => true,
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Campaign Settings')
                ->description('Configure your growth hacking campaign')
                ->schema([
                    TextInput::make('name')
                        ->label('Campaign Name')
                        ->required()
                        ->placeholder('e.g., Web Agencies Q1 2025')
                        ->maxLength(255),

                    Textarea::make('description')
                        ->label('Description')
                        ->placeholder('Optional description of this campaign')
                        ->rows(2),
                ]),

            Section::make('Target Websites')
                ->description('Enter website URLs to analyze (one per line, max 100)')
                ->schema([
                    Textarea::make('website_urls')
                        ->label('Website URLs')
                        ->required()
                        ->placeholder("https://example.com\nhttps://another-example.com\nhttps://third-example.com")
                        ->rows(8)
                        ->helperText('Enter one URL per line. System will scrape each website and extract contact information.')
                        ->rules(['required', function ($attribute, $value, $fail) {
                            $urls = array_filter(array_map('trim', explode("\n", $value)));
                            if (count($urls) > 100) {
                                $fail('Maximum 100 URLs allowed per campaign.');
                            }
                            foreach ($urls as $url) {
                                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                                    $fail("Invalid URL: {$url}");
                                }
                            }
                        }]),
                ]),

            Section::make('Email Configuration')
                ->description('Configure how emails will be sent to prospects')
                ->schema([
                    Select::make('email_method')
                        ->label('Email Method')
                        ->required()
                        ->options([
                            'site_smtp' => 'Site SMTP (from .env)',
                            'custom_smtp' => 'Custom SMTP Configuration',
                        ])
                        ->default('site_smtp')
                        ->reactive(),

                    Select::make('smtp_config_id')
                        ->label('SMTP Configuration')
                        ->options(SmtpConfig::where('is_active', true)->pluck('name', 'id'))
                        ->visible(fn ($get) => $get('email_method') === 'custom_smtp')
                        ->required(fn ($get) => $get('email_method') === 'custom_smtp')
                        ->helperText('Select a previously configured SMTP server'),

                    Toggle::make('auto_create_accounts')
                        ->label('Auto-create User Accounts')
                        ->helperText('Automatically create user accounts for prospects and send them login credentials')
                        ->default(true),
                ]),
        ];
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        try {
            // Create campaign
            $campaign = GrowthHackingCampaign::create([
                'admin_user_id' => Auth::id(),
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'website_urls' => $data['website_urls'],
                'email_method' => $data['email_method'],
                'smtp_config_id' => $data['smtp_config_id'] ?? null,
                'auto_create_accounts' => $data['auto_create_accounts'],
                'status' => 'processing',
            ]);

            // Dispatch background job to process campaign
            ProcessGrowthHackingCampaign::dispatch($campaign);

            Notification::make()
                ->success()
                ->title('Campaign Created!')
                ->body('Processing has started. You will be able to review prospects before sending emails.')
                ->send();

            // Redirect to campaigns list
            $this->redirect(route('filament.admin.resources.growth-hacking-campaigns.index'));

        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error Creating Campaign')
                ->body($e->getMessage())
                ->send();
        }
    }
}
