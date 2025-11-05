<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Support\Enums\Width;
use Filament\Pages\Page;

class Media extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-photo';

    protected static ?int $navigationSort = 5;

    public function getView(): string
    {
        return 'wave::media.index';
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }
}
