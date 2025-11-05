<?php

namespace App\Filament\Resources\Testimonials\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Testimonials\TestimonialResource;
use Filament\Resources\Pages\ListRecords;

class ListTestimonials extends ListRecords
{
    protected static string $resource = TestimonialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
