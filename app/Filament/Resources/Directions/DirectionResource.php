<?php

namespace App\Filament\Resources\Directions;

use App\Filament\Resources\Directions\Pages\CreateDirection;
use App\Filament\Resources\Directions\Pages\EditDirection;
use App\Filament\Resources\Directions\Pages\ListDirections;
use App\Filament\Resources\Directions\RelationManagers\GroupAssignmentsRelationManager;
use App\Filament\Resources\Directions\RelationManagers\TeacherAssignmentsRelationManager;
use App\Filament\Resources\Directions\Schemas\DirectionForm;
use App\Filament\Resources\Directions\Tables\DirectionsTable;
use App\Models\Direction;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class DirectionResource extends Resource
{
    protected static ?string $model = Direction::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Yo\'nalishlar';

    protected static ?string $modelLabel = 'Yo\'nalish';

    protected static ?string $pluralModelLabel = 'Yo\'nalishlar';

    protected static UnitEnum|string|null $navigationGroup = "Ta'lim";

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return DirectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DirectionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            TeacherAssignmentsRelationManager::class,
            GroupAssignmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListDirections::route('/'),
            'create' => CreateDirection::route('/create'),
            'edit'   => EditDirection::route('/{record}/edit'),
        ];
    }
}
