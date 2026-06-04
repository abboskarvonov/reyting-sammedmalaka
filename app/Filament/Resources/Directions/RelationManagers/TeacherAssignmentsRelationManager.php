<?php

namespace App\Filament\Resources\Directions\RelationManagers;

use App\Models\Teacher;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TeacherAssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'teacherAssignments';

    protected static ?string $title = "O'qituvchilar";

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('teacher_id')
                ->label("O'qituvchi")
                ->options(function () {
                    $assignedIds = $this->ownerRecord->teachers()->pluck('teachers.id')->toArray();

                    return Teacher::active()
                        ->with('user')
                        ->whereNotIn('id', $assignedIds)
                        ->get()
                        ->mapWithKeys(fn ($t) => [
                            $t->id => $t->user->name
                                . ($t->position ? ' — ' . $t->position : ''),
                        ]);
                })
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
                TextColumn::make('teacher.position')
                    ->label('Lavozim')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('teacher.department')
                    ->label("Bo'lim")
                    ->searchable(),
            ])
            ->headerActions([
                CreateAction::make()->label("O'qituvchi biriktirish"),
            ])
            ->actions([
                DeleteAction::make()->label('Olib tashlash'),
            ])
            ->bulkActions([
                DeleteBulkAction::make()->label('Tanlanganlarni olib tashlash'),
            ])
            ->emptyStateHeading("Hali o'qituvchi biriktirilmagan")
            ->emptyStateDescription("'O'qituvchi biriktirish' tugmasi orqali qo'shing");
    }
}
