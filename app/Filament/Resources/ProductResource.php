<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Models\EmailTemplate;
use App\Models\ModuleConfiguration;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Product Details')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Details')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\Section::make('Basic Information')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', \Illuminate\Support\Str::slug($state)))
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('slug')
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(ignoreRecord: true)
                                            ->columnSpan(1),

                                        Forms\Components\Select::make('type')
                                            ->options([
                                                'hosting' => 'Hosting',
                                                'domain' => 'Domain',
                                                'ssl' => 'SSL Certificate',
                                                'addon' => 'Add-on',
                                                'service' => 'Service',
                                                'license' => 'License',
                                                'vps' => 'VPS',
                                                'dedicated' => 'Dedicated Server',
                                            ])
                                            ->required()
                                            ->default('service')
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('category')
                                            ->label('Product Group/Category')
                                            ->maxLength(255)
                                            ->datalist([
                                                'Web Hosting',
                                                'Cloud Hosting',
                                                'Email Hosting',
                                                'Domains',
                                                'SSL Certificates',
                                                'Software Licenses',
                                            ])
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('sku')
                                            ->label('SKU')
                                            ->maxLength(255)
                                            ->unique(ignoreRecord: true)
                                            ->columnSpan(2),

                                        Forms\Components\RichEditor::make('description')
                                            ->label('Product Description')
                                            ->toolbarButtons([
                                                'bold',
                                                'italic',
                                                'underline',
                                                'strike',
                                                'bulletList',
                                                'orderedList',
                                                'h2',
                                                'h3',
                                                'link',
                                                'undo',
                                                'redo',
                                            ])
                                            ->columnSpanFull(),

                                        Forms\Components\TagsInput::make('features')
                                            ->label('Product Features')
                                            ->placeholder('Add feature and press enter')
                                            ->helperText('Key features displayed to customers')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Visibility & Status')
                                    ->schema([
                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Active (Visible to Customers)')
                                            ->default(true)
                                            ->helperText('Enable to show this product on your store'),

                                        Forms\Components\Toggle::make('is_featured')
                                            ->label('Featured Product')
                                            ->default(false)
                                            ->helperText('Featured products appear prominently on homepage'),

                                        Forms\Components\Toggle::make('is_hidden')
                                            ->label('Hidden Product')
                                            ->default(false)
                                            ->helperText('Hidden products only accessible via direct link'),

                                        Forms\Components\TextInput::make('sort_order')
                                            ->label('Display Order')
                                            ->numeric()
                                            ->default(0)
                                            ->helperText('Lower numbers appear first'),
                                    ])
                                    ->columns(4),
                            ]),

                        Forms\Components\Tabs\Tab::make('Pricing')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Forms\Components\Section::make('Base Pricing')
                                    ->schema([
                                        Forms\Components\TextInput::make('price')
                                            ->label('Default Price')
                                            ->numeric()
                                            ->prefix('$')
                                            ->required()
                                            ->default(0)
                                            ->helperText('Base price for monthly billing'),

                                        Forms\Components\TextInput::make('setup_fee')
                                            ->label('One-Time Setup Fee')
                                            ->numeric()
                                            ->prefix('$')
                                            ->default(0),

                                        Forms\Components\Select::make('billing_cycles')
                                            ->label('Available Billing Cycles')
                                            ->options([
                                                'free' => 'Free',
                                                'one_time' => 'One Time Payment',
                                                'monthly' => 'Monthly',
                                                'quarterly' => 'Quarterly (3 months)',
                                                'semi_annually' => 'Semi-Annually (6 months)',
                                                'annually' => 'Annually (12 months)',
                                                'biennially' => 'Biennially (24 months)',
                                                'triennially' => 'Triennially (36 months)',
                                            ])
                                            ->multiple()
                                            ->default(['monthly', 'annually'])
                                            ->helperText('Select which billing periods customers can choose'),
                                    ])
                                    ->columns(3),

                                Forms\Components\Section::make('Pricing Tiers')
                                    ->description('Set custom pricing for each billing cycle. If not set, the default price will be used.')
                                    ->schema([
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('pricing_tiers.monthly')
                                                    ->label('Monthly Price')
                                                    ->numeric()
                                                    ->prefix('$'),

                                                Forms\Components\TextInput::make('pricing_tiers.quarterly')
                                                    ->label('Quarterly Price (Total)')
                                                    ->numeric()
                                                    ->prefix('$'),

                                                Forms\Components\TextInput::make('pricing_tiers.semi_annually')
                                                    ->label('Semi-Annual Price (Total)')
                                                    ->numeric()
                                                    ->prefix('$'),

                                                Forms\Components\TextInput::make('pricing_tiers.annually')
                                                    ->label('Annual Price (Total)')
                                                    ->numeric()
                                                    ->prefix('$'),

                                                Forms\Components\TextInput::make('pricing_tiers.biennially')
                                                    ->label('Biennial Price (Total)')
                                                    ->numeric()
                                                    ->prefix('$'),

                                                Forms\Components\TextInput::make('pricing_tiers.triennially')
                                                    ->label('Triennial Price (Total)')
                                                    ->numeric()
                                                    ->prefix('$'),

                                                Forms\Components\TextInput::make('pricing_tiers.one_time')
                                                    ->label('One-Time Payment')
                                                    ->numeric()
                                                    ->prefix('$'),
                                            ]),
                                    ]),

                                Forms\Components\Section::make('Stock Management')
                                    ->schema([
                                        Forms\Components\TextInput::make('stock_quantity')
                                            ->numeric()
                                            ->label('Stock Quantity')
                                            ->helperText('Leave empty for unlimited stock'),

                                        Forms\Components\Select::make('stock_status')
                                            ->options([
                                                'in_stock' => 'In Stock',
                                                'low_stock' => 'Low Stock',
                                                'out_of_stock' => 'Out of Stock',
                                                'on_backorder' => 'On Backorder',
                                            ])
                                            ->default('in_stock')
                                            ->required(),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('Module Settings')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Forms\Components\Section::make('Server & Provisioning')
                                    ->schema([
                                        Forms\Components\Select::make('server_id')
                                            ->label('Default Server')
                                            ->relationship('server', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->helperText('Server where new services will be provisioned'),

                                        Forms\Components\Select::make('module_name')
                                            ->label('Provisioning Module')
                                            ->options([
                                                'cpanel' => 'cPanel',
                                                'plesk' => 'Plesk',
                                                'directadmin' => 'DirectAdmin',
                                                'virtualizor' => 'Virtualizor',
                                                'proxmox' => 'Proxmox',
                                                'custom' => 'Custom Module',
                                            ])
                                            ->searchable()
                                            ->helperText('Module used to provision this product'),

                                        Forms\Components\Toggle::make('auto_setup')
                                            ->label('Automatic Provisioning')
                                            ->default(false)
                                            ->helperText('Automatically provision service after payment'),

                                        Forms\Components\Toggle::make('requires_domain')
                                            ->label('Requires Domain')
                                            ->default(false)
                                            ->helperText('Customer must provide domain during order'),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Module Configuration')
                                    ->description('Configuration options passed to the provisioning module')
                                    ->schema([
                                        Forms\Components\KeyValue::make('configuration_options')
                                            ->label('Module Settings')
                                            ->keyLabel('Option Name')
                                            ->valueLabel('Option Value')
                                            ->helperText('Module-specific configuration (e.g., package name, disk quota, bandwidth)')
                                            ->columnSpanFull()
                                            ->reorderable(),
                                    ]),

                                Forms\Components\Section::make('Welcome Email')
                                    ->schema([
                                        Forms\Components\Select::make('welcome_email_template_id')
                                            ->label('Welcome Email Template')
                                            ->relationship('welcomeEmailTemplate', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->helperText('Email sent to customer after service activation'),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Addons & Options')
                            ->icon('heroicon-o-puzzle-piece')
                            ->schema([
                                Forms\Components\Section::make('Configurable Options')
                                    ->description('Options customers can configure during purchase')
                                    ->schema([
                                        Forms\Components\Repeater::make('configurable_options')
                                            ->schema([
                                                Forms\Components\TextInput::make('name')
                                                    ->required()
                                                    ->label('Option Name')
                                                    ->placeholder('e.g., Additional Storage'),

                                                Forms\Components\Select::make('type')
                                                    ->options([
                                                        'dropdown' => 'Dropdown',
                                                        'quantity' => 'Quantity',
                                                        'radio' => 'Radio Buttons',
                                                        'checkbox' => 'Checkbox',
                                                    ])
                                                    ->required()
                                                    ->default('dropdown'),

                                                Forms\Components\KeyValue::make('options')
                                                    ->label('Available Choices')
                                                    ->keyLabel('Choice')
                                                    ->valueLabel('Price (addon)')
                                                    ->helperText('Enter 0 for no additional cost'),

                                                Forms\Components\Toggle::make('required')
                                                    ->default(false),
                                            ])
                                            ->columns(2)
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                                    ]),

                                Forms\Components\Section::make('Product Addons')
                                    ->description('Related products that can be purchased alongside this product')
                                    ->schema([
                                        Forms\Components\Repeater::make('product_addons')
                                            ->schema([
                                                Forms\Components\Select::make('addon_product_id')
                                                    ->label('Addon Product')
                                                    ->relationship('addonProducts', 'name')
                                                    ->searchable()
                                                    ->required(),

                                                Forms\Components\Toggle::make('is_required')
                                                    ->label('Required')
                                                    ->default(false),

                                                Forms\Components\TextInput::make('discount_percentage')
                                                    ->label('Discount %')
                                                    ->numeric()
                                                    ->suffix('%')
                                                    ->minValue(0)
                                                    ->maxValue(100),
                                            ])
                                            ->columns(3)
                                            ->columnSpanFull()
                                            ->collapsible(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Product $record): string => $record->sku ? "SKU: {$record->sku}" : ''),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'hosting' => 'primary',
                        'domain' => 'success',
                        'ssl' => 'warning',
                        'addon' => 'info',
                        'vps' => 'purple',
                        'dedicated' => 'orange',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('category')
                    ->label('Group')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('price')
                    ->money('USD')
                    ->sortable()
                    ->description(fn (Product $record): string => $record->setup_fee > 0 ? "Setup: $" . number_format($record->setup_fee, 2) : ''),

                Tables\Columns\TextColumn::make('stock_status')
                    ->label('Stock')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'in_stock' => 'success',
                        'low_stock' => 'warning',
                        'out_of_stock' => 'danger',
                        'on_backorder' => 'info',
                        default => 'gray',
                    })
                    ->description(fn (Product $record): string => $record->stock_quantity ? "{$record->stock_quantity} available" : 'Unlimited'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_hidden')
                    ->label('Hidden')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('orders_count')
                    ->counts('orders')
                    ->label('Orders')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('server.name')
                    ->label('Server')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'hosting' => 'Hosting',
                        'domain' => 'Domain',
                        'ssl' => 'SSL Certificate',
                        'addon' => 'Add-on',
                        'service' => 'Service',
                        'license' => 'License',
                        'vps' => 'VPS',
                        'dedicated' => 'Dedicated Server',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('category')
                    ->label('Product Group')
                    ->options(fn () => Product::distinct()->pluck('category', 'category')->filter()->toArray())
                    ->multiple(),

                Tables\Filters\SelectFilter::make('stock_status')
                    ->options([
                        'in_stock' => 'In Stock',
                        'low_stock' => 'Low Stock',
                        'out_of_stock' => 'Out of Stock',
                        'on_backorder' => 'On Backorder',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All products')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured')
                    ->placeholder('All products')
                    ->trueLabel('Featured only')
                    ->falseLabel('Not featured'),

                Tables\Filters\TernaryFilter::make('is_hidden')
                    ->label('Hidden')
                    ->placeholder('All products')
                    ->trueLabel('Hidden only')
                    ->falseLabel('Public only'),

                Tables\Filters\SelectFilter::make('server_id')
                    ->relationship('server', 'name')
                    ->label('Server')
                    ->multiple(),

                Tables\Filters\Filter::make('has_stock')
                    ->label('Out of Stock')
                    ->query(fn ($query) => $query->where('stock_status', 'out_of_stock')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('duplicate')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('gray')
                        ->action(function (Product $record) {
                            $newProduct = $record->replicate();
                            $newProduct->name = $record->name . ' (Copy)';
                            $newProduct->slug = \Illuminate\Support\Str::slug($newProduct->name) . '-' . uniqid();
                            $newProduct->is_active = false;
                            $newProduct->save();

                            \Filament\Notifications\Notification::make()
                                ->title('Product duplicated')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('toggle_active')
                        ->label(fn (Product $record) => $record->is_active ? 'Deactivate' : 'Activate')
                        ->icon(fn (Product $record) => $record->is_active ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                        ->color(fn (Product $record) => $record->is_active ? 'warning' : 'success')
                        ->action(function (Product $record) {
                            $record->update(['is_active' => !$record->is_active]);
                            \Filament\Notifications\Notification::make()
                                ->title($record->is_active ? 'Product activated' : 'Product deactivated')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('mark_featured')
                        ->icon('heroicon-o-star')
                        ->color('warning')
                        ->action(fn ($records) => $records->each->update(['is_featured' => true]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Product Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('type')
                            ->badge(),

                        Infolists\Components\TextEntry::make('category')
                            ->label('Product Group'),

                        Infolists\Components\TextEntry::make('sku')
                            ->label('SKU')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('description')
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Pricing')
                    ->schema([
                        Infolists\Components\TextEntry::make('price')
                            ->money('USD'),

                        Infolists\Components\TextEntry::make('setup_fee')
                            ->money('USD'),

                        Infolists\Components\TextEntry::make('billing_cycles')
                            ->badge()
                            ->separator(','),

                        Infolists\Components\KeyValueEntry::make('pricing_tiers')
                            ->label('Pricing by Cycle')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Stock & Status')
                    ->schema([
                        Infolists\Components\TextEntry::make('stock_quantity')
                            ->default('Unlimited'),

                        Infolists\Components\TextEntry::make('stock_status')
                            ->badge(),

                        Infolists\Components\IconEntry::make('is_active')
                            ->label('Active')
                            ->boolean(),

                        Infolists\Components\IconEntry::make('is_featured')
                            ->label('Featured')
                            ->boolean(),

                        Infolists\Components\IconEntry::make('is_hidden')
                            ->label('Hidden')
                            ->boolean(),

                        Infolists\Components\TextEntry::make('sort_order')
                            ->label('Display Order'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Features')
                    ->schema([
                        Infolists\Components\TextEntry::make('features')
                            ->badge()
                            ->separator(',')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('Configuration')
                    ->schema([
                        Infolists\Components\TextEntry::make('server.name')
                            ->label('Server'),

                        Infolists\Components\TextEntry::make('module_name')
                            ->label('Module'),

                        Infolists\Components\IconEntry::make('auto_setup')
                            ->label('Auto Setup')
                            ->boolean(),

                        Infolists\Components\IconEntry::make('requires_domain')
                            ->label('Requires Domain')
                            ->boolean(),

                        Infolists\Components\KeyValueEntry::make('configuration_options')
                            ->label('Module Configuration')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Infolists\Components\Section::make('Statistics')
                    ->schema([
                        Infolists\Components\TextEntry::make('orders_count')
                            ->label('Total Orders'),

                        Infolists\Components\TextEntry::make('subscriptions_count')
                            ->label('Active Subscriptions'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->dateTime(),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->dateTime(),
                    ])
                    ->columns(4)
                    ->collapsible(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
            'view' => Pages\ViewProduct::route('/{record}'),
        ];
    }
}
