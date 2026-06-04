<?php

namespace App\Filament\Resources\Ratings;

use App\Filament\Resources\Ratings\Pages\ListRatings;
use App\Filament\Resources\Ratings\Schemas\RatingForm;
use App\Filament\Resources\Ratings\Tables\RatingsTable;
use App\Models\Rating;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class RatingResource extends Resource
{
    protected static ?string $model = Rating::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationLabel = 'Baholash natijalari';

    protected static ?string $modelLabel = 'Baholash';

    protected static ?string $pluralModelLabel = 'Baholashlar';

    protected static UnitEnum|string|null $navigationGroup = 'Baholash';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return RatingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RatingsTable::configure($table);
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
            'index' => ListRatings::route('/'),
        ];
    }
}
