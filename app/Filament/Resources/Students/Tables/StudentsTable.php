<?php

namespace App\Filament\Resources\Students\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class StudentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student_id')
                    ->label('ID-kod')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('full_name')
                    ->label('Ism-familiya')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('group.name')
                    ->label('Guruh')
                    ->badge()
                    ->sortable(),
                // O'qish sanasi — guruhdan olinadi
                TextColumn::make('group.starts_at')
                    ->label('Boshlanish')
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('group.ends_at')
                    ->label('Tugash')
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable()
                    ->color(fn ($record) => $record->group?->is_expired ? 'danger' : null),
                TextColumn::make('muassasa_nomi')
                    ->label('Muassasa')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('phone')
                    ->label('Telefon')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('diplom_raqam')
                    ->label('Diplom')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('passport_seriya_raqam')
                    ->label('Pasport')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('pinfl')
                    ->label('PINFL')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ratings_count')
                    ->label('Baholashlar')
                    ->counts('ratings')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Faol')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('group_id')
                    ->label('Guruh')
                    ->relationship('group', 'name'),
                SelectFilter::make('muassasa_nomi')
                    ->label('Muassasa')
                    ->options(fn () => \App\Models\Student::query()
                        ->whereNotNull('muassasa_nomi')
                        ->distinct()
                        ->orderBy('muassasa_nomi')
                        ->pluck('muassasa_nomi', 'muassasa_nomi')
                    ),
                TernaryFilter::make('is_active')
                    ->label('Faol'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('full_name');
    }
}
