<?php

namespace App\Filament\Resources\Ratings\Tables;

use App\Models\Rating;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RatingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Sana')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('teacher.user.name')
                    ->label("O'qituvchi")
                    ->searchable(),
                TextColumn::make('direction.name')
                    ->label("Yo'nalish")
                    ->searchable(),
                TextColumn::make('student.group.name')
                    ->label('Guruh')
                    ->badge(),
                TextColumn::make('total_score')
                    ->label('Ball')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 4.5 => 'success',
                        $state >= 3.5 => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('academic_year')
                    ->label("O'quv yili"),
                TextColumn::make('semester')
                    ->label('Semestr')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state . '-semestr'),
            ])
            ->filters([
                SelectFilter::make('teacher_id')
                    ->label("O'qituvchi")
                    ->relationship('teacher.user', 'name'),
                SelectFilter::make('direction_id')
                    ->label("Yo'nalish")
                    ->relationship('direction', 'name'),
                SelectFilter::make('academic_year')
                    ->label("O'quv yili")
                    ->options(fn () => Rating::distinct()->orderByDesc('academic_year')->pluck('academic_year', 'academic_year')),
                SelectFilter::make('semester')
                    ->label('Semestr')
                    ->options(['1' => '1-semestr', '2' => '2-semestr']),
            ])
            ->recordActions([])
            ->toolbarActions([])
            ->defaultSort('created_at', 'desc');
    }
}
