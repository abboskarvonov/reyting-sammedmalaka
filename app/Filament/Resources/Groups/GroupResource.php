<?php

namespace App\Filament\Resources\Groups;

use App\Filament\Resources\Groups\Pages\CreateGroup;
use App\Filament\Resources\Groups\Pages\EditGroup;
use App\Filament\Resources\Groups\Pages\ListGroups;
use App\Filament\Resources\Groups\Schemas\GroupForm;
use App\Filament\Resources\Groups\Tables\GroupsTable;
use App\Models\Group;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class GroupResource extends Resource
{
    protected static ?string $model = Group::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?string $navigationLabel = 'Guruhlar';

    protected static ?string $modelLabel = 'Guruh';

    protected static ?string $pluralModelLabel = 'Guruhlar';

    protected static UnitEnum|string|null $navigationGroup = "Ta'lim";

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return GroupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GroupsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGroups::route('/'),
            'create' => CreateGroup::route('/create'),
            'edit' => EditGroup::route('/{record}/edit'),
        ];
    }
}
