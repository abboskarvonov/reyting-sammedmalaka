<?php

namespace App\Filament\Resources\Teachers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TeachersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Ism-familya')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employee_id')
                    ->label('ID')
                    ->searchable(),
                TextColumn::make('department')
                    ->label("Bo'lim")
                    ->searchable(),
                TextColumn::make('directions.name')
                    ->label("Yo'nalishlar")
                    ->badge()
                    ->separator(','),
                TextColumn::make('ratings_avg_total_score')
                    ->label("O'rtacha ball")
                    ->avg('ratings', 'total_score')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 4.5 => 'success',
                        $state >= 3.5 => 'warning',
                        default       => 'danger',
                    })
                    ->default('—'),
                TextColumn::make('task_completion_rate')
                    ->label('Topshiriq %')
                    ->getStateUsing(fn ($record) => $record->task_completion_rate . '%')
                    ->badge()
                    ->color('info'),
                IconColumn::make('is_archived')
                    ->label('Arxiv')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('department')
                    ->label("Bo'lim")
                    ->options(fn () => \App\Models\Teacher::query()
                        ->whereNotNull('department')
                        ->distinct()
                        ->pluck('department', 'department')
                    ),
                TernaryFilter::make('is_archived')
                    ->label('Arxivlangan'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('ratings_avg_total_score', 'desc');
    }
}
