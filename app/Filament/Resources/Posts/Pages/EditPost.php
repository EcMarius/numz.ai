<?php

namespace App\Filament\Resources\Posts\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use App\Filament\Resources\Posts\PostResource;
use App\Services\BlogAIService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    public function getView(): string
    {
        return 'filament.resources.posts.pages.edit-post';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_with_ai')
                ->label('Generate with AI')
                ->icon('heroicon-o-sparkles')
                ->modalHeading('Generate Content with AI')
                ->modalDescription('Enter a prompt to generate blog content using AI')
                ->modalSubmitActionLabel('Generate')
                ->form([
                    Select::make('model')
                        ->label('AI Model')
                        ->options(function () {
                            $aiService = new BlogAIService();
                            $models = $aiService->getAvailableModels();
                            return array_combine($models, $models);
                        })
                        ->default(function () {
                            $aiService = new BlogAIService();
                            return $aiService->getDefaultModel();
                        })
                        ->required(),
                    Textarea::make('prompt')
                        ->label('What do you want to generate?')
                        ->placeholder('e.g., "Write a comprehensive guide about Reddit lead generation strategies including..."')
                        ->rows(5)
                        ->required(),
                ])
                ->action(function (array $data) {
                    try {
                        $aiService = new BlogAIService();
                        $context = [
                            'title' => $this->record->title,
                            'excerpt' => $this->record->excerpt,
                            'keywords' => $this->record->meta_keywords,
                        ];

                        $content = $aiService->generateContent(
                            $data['prompt'],
                            $data['model'],
                            $context
                        );

                        // Update the body field
                        $this->record->update(['body' => $content]);

                        Notification::make()
                            ->title('Content generated successfully!')
                            ->success()
                            ->send();

                        // Refresh the page to show new content
                        return redirect()->to($this->getResource()::getUrl('edit', ['record' => $this->record]));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Failed to generate content')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            DeleteAction::make(),
        ];
    }
}
