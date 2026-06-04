<?php

namespace App\Filament\Resources\Groups\RelationManagers;

use App\Models\Direction;
use App\Models\Teacher;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TeacherAssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'teacherAssignments';

    protected static ?string $title = "O'qituvchi biriktirish";

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('teacher_id')
                ->label("O'qituvchi")
                ->options(
                    Teacher::active()
                        ->with('user')
                        ->get()
                        ->mapWithKeys(fn ($t) => [$t->id => $t->user->name])
                )
                ->searchable()
                ->required(),

            Select::make('direction_id')
                ->label("Yo'nalish")
                ->options(Direction::orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('teacher.user.name')
                    ->label("O'qituvchi")
                    ->searchable()
                    ->sortable(),

                TextColumn::make('direction.name')
                    ->label("Yo'nalish")
                    ->badge()
                    ->color('primary'),

                TextColumn::make('direction.unique_key')
                    ->label('Kalit')
                    ->badge()
                    ->color('gray'),
            ])
            ->headerActions([
                CreateAction::make()->label("O'qituvchi biriktirish"),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->emptyStateHeading("Hali o'qituvchi biriktirilmagan")
            ->emptyStateDescription("'O'qituvchi biriktirish' tugmasi orqali qo'shing");
    }
}
