<?php

namespace App\Filament\Resources\Directions\RelationManagers;

use App\Models\Group;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GroupAssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'groupAssignments';

    protected static ?string $title = 'Guruhlar';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('group_id')
                ->label('Guruh')
                ->options(function () {
                    // Faqat faol va o'qish davri hali tugamagan guruhlar
                    return Group::active()
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn ($g) => [
                            $g->id => $g->name
                                . ' ('
                                . $g->starts_at->format('d.m.Y')
                                . ' — '
                                . $g->ends_at->format('d.m.Y')
                                . ')',
                        ]);
                })
                ->searchable()
                ->required()
                ->helperText("Faqat faol va muddati o'tmagan guruhlar ko'rsatiladi"),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('group.name')
                    ->label('Guruh')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('group.starts_at')
                    ->label('Boshlanish')
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('group.ends_at')
                    ->label('Tugash')
                    ->date('d.m.Y')
                    ->sortable()
                    ->color(fn ($record) => $record->group?->is_expired ? 'danger' : 'success'),

                IconColumn::make('group.is_active')
                    ->label('Faol')
                    ->state(fn ($record) => $record->group
                        && $record->group->is_active
                        && ! $record->group->is_expired
                    )
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()->label('Guruh biriktirish'),
            ])
            ->actions([
                DeleteAction::make()->label('Olib tashlash'),
            ])
            ->bulkActions([
                DeleteBulkAction::make()->label('Tanlanganlarni olib tashlash'),
            ])
            ->emptyStateHeading('Hali guruh biriktirilmagan')
            ->emptyStateDescription("'Guruh biriktirish' tugmasi orqali qo'shing");
    }
}
