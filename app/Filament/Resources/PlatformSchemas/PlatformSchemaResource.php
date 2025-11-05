<?php

namespace App\Filament\Resources\PlatformSchemas;

use App\Filament\Resources\PlatformSchemas\Pages\CreatePlatformSchema;
use App\Filament\Resources\PlatformSchemas\Pages\EditPlatformSchema;
use App\Filament\Resources\PlatformSchemas\Pages\ListPlatformSchemas;
use App\Filament\Resources\PlatformSchemas\Schemas\PlatformSchemaForm;
use App\Filament\Resources\PlatformSchemas\Tables\PlatformSchemaTable;
use App\Models\PlatformSchema;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class PlatformSchemaResource extends Resource
{
    protected static ?string $model = PlatformSchema::class;

    protected static string|BackedEnum|null $navigationIcon = 'phosphor-code-duotone';

    protected static UnitEnum|string|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Platform Schemas';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return PlatformSchemaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlatformSchemaTable::configure($table);
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
            'index' => ListPlatformSchemas::route('/'),
            'create' => CreatePlatformSchema::route('/create'),
            'edit' => EditPlatformSchema::route('/{record}/edit'),
        ];
    }
}
