<?php

namespace App\Filament\Resources\Plans;

use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use App\Services\StripeService;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use App\Filament\Resources\Plans\Pages\ListPlans;
use App\Filament\Resources\Plans\Pages\CreatePlan;
use App\Filament\Resources\Plans\Pages\EditPlan;
use App\Filament\Resources\PlanResource\Pages;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;
use Wave\Plan;
use Wave\Plugins\EvenLeads\Models\Setting;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static BackedEnum|string|null $navigationIcon = 'phosphor-credit-card-duotone';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Plan Details')
                    ->description('Below are the basic details for each plan including name, description, and features')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(191)
                            ->columnSpan(2),
                        Textarea::make('description')
                            ->columnSpan([
                                'default' => 2,
                                'lg' => 1,
                            ]),
                        TagsInput::make('features')
                            ->reorderable()
                            ->separator(',')
                            ->placeholder('New feature')
                            ->columnSpan([
                                'default' => 2,
                                'lg' => 1,
                            ]),
                    ])->columns(2),
                Section::make('Plan Pricing')
                    ->description('Add the pricing details for your plans below')
                    ->schema([
                        TextInput::make('monthly_price_id')
                            ->label('Monthly Price ID')
                            ->hint('Stripe/Paddle ID')
                            ->maxLength(191),
                        TextInput::make('monthly_price')
                            ->maxLength(191),
                        TextInput::make('yearly_price_id')
                            ->label('Yearly Price ID')
                            ->hint('Stripe/Paddle ID')
                            ->maxLength(191),
                        TextInput::make('yearly_price')
                            ->maxLength(191),
                        TextInput::make('onetime_price_id')
                            ->label('One-time Price ID')
                            ->hint('Stripe/Paddle ID')
                            ->maxLength(191),
                        TextInput::make('onetime_price')
                            ->maxLength(191),
                    ])->columns(2),
                Section::make('Plan Status')
                    ->description('Make the plan default or active/inactive')
                    ->schema([
                        Toggle::make('active')
                            ->required(),
                        Toggle::make('default')
                            ->required(),
                        Toggle::make('is_on_request')
                            ->label('On Request (Enterprise)')
                            ->helperText('Mark this plan as "on request" - users must contact you to subscribe')
                            ->default(false),
                        Toggle::make('is_seated_plan')
                            ->label('Seated Plan')
                            ->helperText('Enable per-seat pricing. Users purchase seats for team members. Requires organization setup.')
                            ->default(false),
                    ])->columns(4),
                Section::make('EvenLeads Limits')
                    ->description('Configure EvenLeads plugin limits for this plan. Use -1 for unlimited.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('custom_properties.evenleads.campaigns')
                            ->label('Campaigns')
                            ->numeric()
                            ->default(-1)
                            ->hint('Max campaigns user can create (-1 = unlimited)')
                            ->required(),
                        TextInput::make('custom_properties.evenleads.keywords_per_campaign')
                            ->label('Keywords Per Campaign')
                            ->numeric()
                            ->default(-1)
                            ->hint('Max keywords per campaign (-1 = unlimited)')
                            ->required(),
                        TextInput::make('custom_properties.evenleads.manual_syncs_per_month')
                            ->label('Manual Syncs Per Month')
                            ->numeric()
                            ->default(-1)
                            ->hint('Manual sync limit per month (-1 = unlimited)')
                            ->required(),
                        TextInput::make('custom_properties.evenleads.ai_replies_per_month')
                            ->label('AI Replies Per Month')
                            ->numeric()
                            ->default(-1)
                            ->hint('AI reply generation limit (-1 = unlimited)')
                            ->required(),
                        TextInput::make('custom_properties.evenleads.leads_per_sync')
                            ->label('Leads Per Sync')
                            ->numeric()
                            ->default(60)
                            ->hint('Leads gathered in one sync')
                            ->required(),
                        Toggle::make('custom_properties.evenleads.soft_limit_leads')
                            ->label('Soft Limit for Leads')
                            ->hint('If enabled, actual leads may vary ±2 (e.g., 58-62 instead of exactly 60)')
                            ->default(true),
                        TextInput::make('custom_properties.evenleads.leads_storage')
                            ->label('Total Leads Storage')
                            ->numeric()
                            ->default(-1)
                            ->hint('Total leads user can store (-1 = unlimited)')
                            ->required(),
                        TextInput::make('custom_properties.evenleads.automated_sync_interval_minutes')
                            ->label('Auto Sync Interval (minutes)')
                            ->numeric()
                            ->default(1440)
                            ->hint('Minutes between automated syncs (1440 = 24 hours)')
                            ->required(),
                        Toggle::make('custom_properties.evenleads.ai_chat_access')
                            ->label('AI Chat Access')
                            ->hint('Enable access to AI Chat feature (Coming Soon)')
                            ->default(false),
                        Toggle::make('custom_properties.evenleads.smart_lead_retrieval')
                            ->label('Smart Lead Retrieval (AI)')
                            ->hint('Use AI to filter and validate lead relevance before adding to campaign. Uses the default AI model from EvenLeads settings.')
                            ->helperText('When enabled, each potential lead is analyzed by AI to ensure it\'s truly relevant to the campaign offering. This reduces noise but increases AI usage.')
                            ->default(false),
                        Toggle::make('custom_properties.evenleads.ai_post_management')
                            ->label('AI Post Management')
                            ->hint('Enable AI-powered post analytics, sentiment analysis, tips, and comment management features')
                            ->helperText('Grants access to AI features for managing social media posts, analyzing engagement, and generating insights. Uses AI reply quota.')
                            ->default(false),
                        Toggle::make('custom_properties.evenleads.follow_up_enabled')
                            ->label('Automated Follow-Ups')
                            ->hint('Enable automated follow-up messages to non-responding leads')
                            ->helperText('Allows users to automatically send follow-up messages to leads who haven\'t responded after a specified time period.')
                            ->default(false),
                    ]),
                Section::make('Social Account Management')
                    ->description('Configure social account connection limits for this plan')
                    ->collapsed()
                    ->schema([
                        TextInput::make('max_accounts_per_platform')
                            ->label('Max Accounts Per Platform')
                            ->numeric()
                            ->default(20)
                            ->minValue(1)
                            ->maxValue(100)
                            ->hint('Maximum social media accounts per platform users can connect')
                            ->helperText('Users will be able to connect up to this many accounts for each platform (Reddit, Facebook, Twitter, etc.)')
                            ->required(),
                    ]),
                Section::make('AI Configuration')
                    ->description('Configure AI models (OpenAI & Claude) available to users on this plan')
                    ->collapsed()
                    ->schema([
                        Select::make('custom_properties.evenleads.ai_models')
                            ->label('Available AI Models')
                            ->multiple()
                            ->searchable()
                            ->options(fn () => \Wave\Plugins\EvenLeads\Models\Setting::getAvailableAIModels())
                            ->hint('Select one or more models. Users will be able to choose from these models.')
                            ->helperText('Select which AI models users on this plan can access for lead analysis. Models list is synced from EvenLeads settings.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                BooleanColumn::make('active')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('syncToStripe')
                        ->label('Sync to Stripe')
                        ->icon('heroicon-o-arrow-path')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->modalHeading('Sync Selected Plans to Stripe')
                        ->modalDescription('This will create or update products in Stripe for all selected plans.')
                        ->action(function (Collection $records, StripeService $stripeService) {
                            if (!$stripeService->isConfigured()) {
                                Notification::make()
                                    ->warning()
                                    ->title('Stripe Not Configured')
                                    ->body('Please configure Stripe API credentials in EvenLeads settings first.')
                                    ->send();
                                return;
                            }

                            $successCount = 0;
                            $errors = [];

                            foreach ($records as $plan) {
                                try {
                                    $result = $stripeService->syncPlanToStripe($plan, false);
                                    if ($result['success']) {
                                        $successCount++;
                                    } else {
                                        $errors[] = $plan->name . ': ' . implode(', ', $result['errors']);
                                    }
                                } catch (\Exception $e) {
                                    $errors[] = $plan->name . ': ' . $e->getMessage();
                                }
                            }

                            if ($successCount > 0) {
                                Notification::make()
                                    ->success()
                                    ->title('Plans Synced')
                                    ->body("{$successCount} plan(s) synced successfully to Stripe.")
                                    ->send();
                            }

                            if (!empty($errors)) {
                                Notification::make()
                                    ->danger()
                                    ->title('Some Plans Failed')
                                    ->body(implode("\n", $errors))
                                    ->send();
                            }
                        }),
                    BulkAction::make('refreshPrices')
                        ->label('Refresh Stripe Prices')
                        ->icon('heroicon-o-currency-dollar')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Refresh Stripe Prices')
                        ->modalDescription('This will refresh price IDs for all selected plans in Stripe.')
                        ->action(function (Collection $records, StripeService $stripeService) {
                            if (!$stripeService->isConfigured()) {
                                Notification::make()
                                    ->warning()
                                    ->title('Stripe Not Configured')
                                    ->body('Please configure Stripe API credentials in EvenLeads settings first.')
                                    ->send();
                                return;
                            }

                            $successCount = 0;
                            $errors = [];

                            foreach ($records as $plan) {
                                try {
                                    $result = $stripeService->syncPlanToStripe($plan, true);
                                    if ($result['success']) {
                                        $successCount++;
                                    } else {
                                        $errors[] = $plan->name . ': ' . implode(', ', $result['errors']);
                                    }
                                } catch (\Exception $e) {
                                    $errors[] = $plan->name . ': ' . $e->getMessage();
                                }
                            }

                            if ($successCount > 0) {
                                Notification::make()
                                    ->success()
                                    ->title('Prices Refreshed')
                                    ->body("{$successCount} plan(s) refreshed successfully in Stripe.")
                                    ->send();
                            }

                            if (!empty($errors)) {
                                Notification::make()
                                    ->danger()
                                    ->title('Some Plans Failed')
                                    ->body(implode("\n", $errors))
                                    ->send();
                            }
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlans::route('/'),
            'create' => CreatePlan::route('/create'),
            'edit' => EditPlan::route('/{record}/edit'),
        ];
    }
}
