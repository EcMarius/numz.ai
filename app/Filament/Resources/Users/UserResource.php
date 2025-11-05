<?php

namespace App\Filament\Resources\Users;

use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static BackedEnum|string|null $navigationIcon = 'phosphor-users-duotone';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(191),
                TextInput::make('username')
                    ->required()
                    ->maxLength(191),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(191),
                FileUpload::make('avatar')
                    ->image()
                    ->directory('avatars')
                    ->visibility('public')
                    ->avatar()
                    ->imageEditor()
                    ->circleCropper()
                    ->maxSize(2048)
                    ->helperText('Upload a profile picture (optional)'),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create'),
                Select::make('roles')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->preload()
                    ->searchable()
                    ->required(),
                DateTimePicker::make('trial_ends_at'),
                TextInput::make('verification_code')
                    ->maxLength(191),
                Toggle::make('verified'),
                Toggle::make('has_smart_search')
                    ->label('Force Smart Search (AI-based)')
                    ->helperText('When enabled, this user will use AI-based lead relevance checking regardless of their plan settings. This provides better quality leads but consumes more AI tokens.'),

                // Admin Rate Limit Bypass Toggles
                Toggle::make('bypass_post_sync_limit')
                    ->label('Bypass Post Management Sync Cooldown')
                    ->helperText('Removes the 3-minute cooldown between post syncs. User can sync posts anytime.'),
                Toggle::make('bypass_campaign_sync_limit')
                    ->label('Bypass Campaign Manual Sync Limit')
                    ->helperText('Allows unlimited manual campaign syncs, ignoring the monthly quota limit.'),
                Toggle::make('bypass_ai_reply_limit')
                    ->label('Bypass AI Reply Generation Limit')
                    ->helperText('Allows unlimited AI reply generations, ignoring the monthly quota limit.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                ImageColumn::make('avatar')
                    ->circular()
                    ->defaultImageUrl(fn () => url(setting('site.default_profile_photo', 'storage/demo/default.png'))),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                Action::make('Impersonate')
                    ->url(fn ($record) => route('impersonate', $record))
                    ->visible(fn ($record) => auth()->user()->id !== $record->id),
                Action::make('clearSyncCooldowns')
                    ->label('Clear Sync Cooldowns')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Clear All Sync Cooldowns?')
                    ->modalDescription('This will reset all sync cooldowns for this user (post management, campaigns, etc.)')
                    ->action(function (User $record) {
                        // Clear post management cooldown from cache
                        $cacheKey = 'user_' . $record->id . '_last_post_sync';
                        \Illuminate\Support\Facades\Cache::forget($cacheKey);

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Sync Cooldowns Cleared')
                            ->body('All sync cooldowns have been reset for ' . $record->name)
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
