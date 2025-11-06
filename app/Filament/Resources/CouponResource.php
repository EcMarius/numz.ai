<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages;
use App\Models\Coupon;
use App\Models\HostingProduct;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Billing';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->label('Coupon Code')
                            ->hint('Leave blank to auto-generate')
                            ->helperText('Unique coupon code that customers will enter'),

                        Forms\Components\Select::make('type')
                            ->required()
                            ->options([
                                'percentage' => 'Percentage Discount',
                                'fixed' => 'Fixed Amount Discount',
                                'credits' => 'Credit Grant',
                            ])
                            ->default('percentage')
                            ->reactive(),

                        Forms\Components\TextInput::make('value')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->suffix(fn ($get) => match ($get('type')) {
                                'percentage' => '%',
                                'fixed', 'credits' => '$',
                                default => '',
                            }),

                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Active'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Usage Limits')
                    ->schema([
                        Forms\Components\TextInput::make('max_uses')
                            ->numeric()
                            ->minValue(1)
                            ->label('Maximum Total Uses')
                            ->helperText('Leave blank for unlimited uses'),

                        Forms\Components\TextInput::make('max_uses_per_user')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required()
                            ->label('Maximum Uses Per User'),

                        Forms\Components\TextInput::make('uses_count')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->label('Current Usage Count'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Date Range')
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Start Date')
                            ->helperText('Leave blank to start immediately'),

                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Expiration Date')
                            ->helperText('Leave blank for no expiration'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Product Restrictions')
                    ->schema([
                        Forms\Components\Select::make('product_ids')
                            ->multiple()
                            ->options(HostingProduct::pluck('name', 'id'))
                            ->label('Applies to Products')
                            ->helperText('Leave blank to apply to all products'),

                        Forms\Components\Select::make('excluded_product_ids')
                            ->multiple()
                            ->options(HostingProduct::pluck('name', 'id'))
                            ->label('Excluded Products'),

                        Forms\Components\TextInput::make('minimum_order_amount')
                            ->numeric()
                            ->prefix('$')
                            ->label('Minimum Order Amount')
                            ->helperText('Minimum cart total required to use this coupon'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Order Type Restrictions')
                    ->schema([
                        Forms\Components\Toggle::make('applies_to_new_orders')
                            ->default(true)
                            ->label('Applies to New Orders'),

                        Forms\Components\Toggle::make('applies_to_renewals')
                            ->default(true)
                            ->label('Applies to Renewals'),

                        Forms\Components\Toggle::make('is_recurring')
                            ->default(false)
                            ->label('Recurring Discount')
                            ->helperText('Apply discount to all future renewals'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('User Restrictions')
                    ->schema([
                        Forms\Components\Toggle::make('first_order_only')
                            ->default(false)
                            ->label('First Order Only'),

                        Forms\Components\TagsInput::make('allowed_email_domains')
                            ->label('Allowed Email Domains')
                            ->helperText('e.g., @company.com, @university.edu')
                            ->placeholder('Add email domain'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Stacking Options')
                    ->schema([
                        Forms\Components\Toggle::make('can_stack')
                            ->default(false)
                            ->label('Can Stack with Other Coupons')
                            ->reactive(),

                        Forms\Components\Select::make('stack_with_coupon_ids')
                            ->multiple()
                            ->options(Coupon::pluck('code', 'id'))
                            ->label('Specific Coupons to Stack With')
                            ->helperText('Leave blank to allow stacking with any coupon')
                            ->visible(fn ($get) => $get('can_stack')),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Metadata')
                    ->schema([
                        Forms\Components\TextInput::make('created_by')
                            ->maxLength(255)
                            ->label('Created By')
                            ->default(fn () => auth()->user()?->name),

                        Forms\Components\KeyValue::make('metadata')
                            ->label('Additional Metadata')
                            ->helperText('Store notes, campaign info, etc.'),
                    ])
                    ->columns(1)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'success' => 'percentage',
                        'warning' => 'fixed',
                        'primary' => 'credits',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'percentage' => 'Percentage',
                        'fixed' => 'Fixed',
                        'credits' => 'Credits',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('value')
                    ->formatStateUsing(function ($record) {
                        return $record->formatted_value;
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('uses_count')
                    ->label('Uses')
                    ->formatStateUsing(function ($record) {
                        $max = $record->max_uses ? "/{$record->max_uses}" : '';
                        return $record->uses_count . $max;
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->sortable(),

                Tables\Columns\TextColumn::make('starts_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'percentage' => 'Percentage',
                        'fixed' => 'Fixed',
                        'credits' => 'Credits',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Filters\Filter::make('active_now')
                    ->label('Active Now')
                    ->query(fn (Builder $query) => $query->active()),

                Tables\Filters\Filter::make('expired')
                    ->label('Expired')
                    ->query(fn (Builder $query) => $query->expired()),

                Tables\Filters\Filter::make('exhausted')
                    ->label('Exhausted (Max Uses Reached)')
                    ->query(fn (Builder $query) => $query->exhausted()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function (Coupon $record) {
                        $newCoupon = $record->replicate();
                        $newCoupon->code = $record->code . '_COPY';
                        $newCoupon->uses_count = 0;
                        $newCoupon->save();
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
            'view' => Pages\ViewCoupon::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::active()->count();
    }
}
