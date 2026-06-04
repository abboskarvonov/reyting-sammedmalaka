<?php

namespace App\Filament\Resources\Tasks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Topshiriq nomi')
                    ->searchable()
                    ->sortable()
                    ->limit(45),

                TextColumn::make('priority')
                    ->label('Muhimlik')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'low'    => 'Past',
                        'medium' => "O'rta",
                        'high'   => 'Yuqori',
                        default  => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'low'    => 'success',
                        'medium' => 'warning',
                        'high'   => 'danger',
                        default  => 'gray',
                    }),

                TextColumn::make('due_date')
                    ->label('Muddat')
                    ->date('d.m.Y')
                    ->sortable()
                    ->color(fn ($record) => $record->due_date?->isPast() ? 'danger' : null),

                // Biriktirilgan o'qituvchilar soni
                TextColumn::make('assignments_count')
                    ->label('Biriktirilgan')
                    ->counts('assignments')
                    ->badge()
                    ->color('info')
                    ->suffix(" o'q."),

                // Bajardi / Bajarmadi
                TextColumn::make('completed_count')
                    ->label('Bajardi')
                    ->getStateUsing(fn ($record) => $record->assignments()->where('status', 'completed')->count()
                        . ' / '
                        . $record->assignments()->count()
                    )
                    ->badge()
                    ->color(fn ($record) => $record->assignments()->count() > 0
                        && $record->assignments()->where('status', 'completed')->count() === $record->assignments()->count()
                        ? 'success' : 'warning'
                    ),
            ])
            ->filters([
                SelectFilter::make('priority')
                    ->label('Muhimlik')
                    ->options([
                        'low'    => 'Past',
                        'medium' => "O'rta",
                        'high'   => 'Yuqori',
                    ]),
            ])
            ->recordActions([
                EditAction::make()->label('Boshqarish'),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('due_date');
    }
}
