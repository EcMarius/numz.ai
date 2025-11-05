<?php
    use Filament\Forms\Components\TextInput;
    use Livewire\Volt\Component;
    use function Laravel\Folio\{middleware, name};
    use Filament\Forms\Concerns\InteractsWithForms;
    use Filament\Forms\Contracts\HasForms;
    use Filament\Actions\Concerns\InteractsWithActions;
    use Filament\Actions\Contracts\HasActions;
    use Filament\Forms\Form;
    use Filament\Schemas\Schema;
    use Filament\Notifications\Notification;
    use Filament\Tables;
    use Filament\Tables\Table;
    use Filament\Tables\Actions\Action;
    use Filament\Tables\Columns\TextColumn;
    use Filament\Actions\DeleteAction;
    use Filament\Actions\EditAction;
    use Filament\Actions\ViewAction;

    use Illuminate\Support\Str;
    use Wave\ApiKey;
    
    middleware('auth');
    name('settings.api');

	new class extends Component implements HasForms, HasActions, Tables\Contracts\HasTable
	{
        use InteractsWithForms, InteractsWithActions, Tables\Concerns\InteractsWithTable;
        
        // variables for (b)rowing keys
        public $keys = [];
        
        public ?array $data = [];

        public function mount(): void
        {
            $this->form->fill();
            $this->refreshKeys();
        }

        public function form(Schema $schema): Schema
        {
            return $schema
                ->components([
                    TextInput::make('key')
                        ->label('Create a new API Key')
                        ->required()
                ])
                ->statePath('data');
        }

        public function add(){

            $state = $this->form->getState();
            $this->validate();

            $apiKey = auth()->user()->createApiKey(Str::slug($state['key']));

            Notification::make()
                ->title('Successfully created new API Key')
                ->success()
                ->send();

            $this->form->fill();

            $this->refreshKeys();
        }

        public function table(Table $table): Table
        {
            return $table->query(Wave\ApiKey::query()->where('user_id', auth()->user()->id))
                ->columns([
                    TextColumn::make('name'),
                    TextColumn::make('created_at')->label('Created'),
                ])
                ->actions([
                    ViewAction::make()
                        ->slideOver()
                        ->modalWidth('md')
                        ->form([
                            TextInput::make('name')
                                ->label('Key Name')
                                ->disabled(),
                            TextInput::make('key')
                                ->label('API Key')
                                ->helperText('Copy this key to use in your API requests.')
                                ->copyable()
                                ->disabled()
                                ->extraInputAttributes(['class' => 'font-mono text-sm']),
                        ]),
                    EditAction::make()
                        ->slideOver()
                        ->modalWidth('md')
                        ->form([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            // ...
                        ]),
                    DeleteAction::make(),
            ]);
        }

        public function refreshKeys(){
            $this->keys = auth()->user()->apiKeys;
        }


	}

?>

<x-layouts.app>
    @volt('settings.api') 
        <div class="relative">
            <x-app.settings-layout
                title="API Keys"
                description="Manage your API Keys"
            >
                <div class="flex flex-col">
                    <!-- API Documentation Link -->
                    <div class="w-full max-w-lg mb-6 p-4 bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div class="flex-1">
                                <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-1">
                                    API Documentation Available
                                </h3>
                                <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-3">
                                    Learn how to use our API to manage campaigns, leads, and sync operations programmatically.
                                </p>
                                <div class="flex items-center space-x-3">
                                    <a href="/docs" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-zinc-900 hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-100 rounded-md transition-colors duration-150">
                                        <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        View Docs
                                    </a>
                                    <a href="/docs.postman" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 border border-zinc-300 dark:border-zinc-700 rounded-md transition-colors duration-150">
                                        <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                        </svg>
                                        Postman
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form wire:submit="add" class="w-full max-w-lg">
                        {{ $this->form }}
                        <div class="w-full pt-6 text-right">
                            <x-button type="submit">Create New Key</x-button>
                        </div>
                    </form>
                    <hr class="my-8 border-zinc-200">
                    <x-elements.label class="block text-sm font-medium leading-5 text-zinc-700">Current API Keys</x-elements.label>
                    <div class="pt-5">
                        {{ $this->table }}
                    </div>
                </div>
            </x-app.settings-layout>
        </div>
    @endvolt
</x-layouts.app>
