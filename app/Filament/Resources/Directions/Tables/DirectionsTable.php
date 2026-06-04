<?php

namespace App\Filament\Resources\Directions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DirectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Yo\'nalish')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('unique_key')
                    ->label('Kalit')
                    ->badge()
                    ->color('primary'),
                TextColumn::make('teachers_count')
                    ->label('O\'qituvchilar')
                    ->counts('teachers')
                    ->sortable(),
                TextColumn::make('ratings_avg_total_score')
                    ->label('O\'rtacha ball')
                    ->avg('ratings', 'total_score')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 4.5 => 'success',
                        $state >= 3.5 => 'warning',
                        $state > 0   => 'danger',
                        default       => 'gray',
                    })
                    ->default('—'),
                TextColumn::make('created_at')
                    ->label('Yaratilgan')
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }
}
