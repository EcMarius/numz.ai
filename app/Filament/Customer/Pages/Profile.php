<?php

namespace App\Filament\Customer\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;

class Profile extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationGroup = 'Account';

    protected static ?int $navigationSort = 8;

    protected static string $view = 'filament.customer.pages.profile';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'name' => auth()->user()->name,
            'email' => auth()->user()->email,
            'phone' => auth()->user()->phone,
            'company' => auth()->user()->company,
            'address' => auth()->user()->address,
            'city' => auth()->user()->city,
            'state' => auth()->user()->state,
            'postal_code' => auth()->user()->postal_code,
            'country' => auth()->user()->country,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Personal Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label('Phone Number')
                            ->tel()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('company')
                            ->label('Company Name')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Address Information')
                    ->schema([
                        Forms\Components\Textarea::make('address')
                            ->label('Street Address')
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('city')
                            ->label('City')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('state')
                            ->label('State/Province')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('postal_code')
                            ->label('Postal Code')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('country')
                            ->label('Country')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Change Password')
                    ->schema([
                        Forms\Components\TextInput::make('current_password')
                            ->label('Current Password')
                            ->password()
                            ->currentPassword()
                            ->revealable(),

                        Forms\Components\TextInput::make('password')
                            ->label('New Password')
                            ->password()
                            ->confirmed()
                            ->revealable()
                            ->minLength(8),

                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('Confirm New Password')
                            ->password()
                            ->revealable(),
                    ])
                    ->columns(3)
                    ->description('Leave blank if you don\'t want to change your password'),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $user = auth()->user();

        // Update profile data
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'company' => $data['company'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'country' => $data['country'] ?? null,
        ]);

        // Update password if provided
        if (!empty($data['password'])) {
            $user->update([
                'password' => Hash::make($data['password']),
            ]);
        }

        Notification::make()
            ->title('Profile Updated')
            ->body('Your profile has been updated successfully.')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('save')
                ->label('Save Changes')
                ->submit('save')
                ->color('primary'),
        ];
    }
}
