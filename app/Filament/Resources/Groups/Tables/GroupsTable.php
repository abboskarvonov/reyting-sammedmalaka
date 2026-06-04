<?php

namespace App\Filament\Resources\Groups\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GroupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Guruh')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label('Kod')
                    ->badge(),
                TextColumn::make('starts_at')
                    ->label('Boshlanish')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Tugash')
                    ->date('d.m.Y')
                    ->sortable()
                    // Muddati o'tganlarda qizil rang
                    ->color(fn ($record) => $record->is_expired ? 'danger' : null),
                TextColumn::make('students_count')
                    ->label('Tinglovchilar')
                    ->counts('students')
                    ->sortable(),
                // Haqiqiy faollik: is_active=true VA ends_at >= bugun
                IconColumn::make('is_active')
                    ->label('Faol')
                    ->state(fn ($record) => $record->is_active && ! $record->is_expired)
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Belgi bo\'yicha faol'),
                Filter::make('active_now')
                    ->label('Hozir faol (davr bo\'yicha)')
                    ->query(fn (Builder $query) => $query
                        ->where('is_active', true)
                        ->where('ends_at', '>=', now()->toDateString())
                    ),
                Filter::make('expired')
                    ->label('Muddati o\'tganlar')
                    ->query(fn (Builder $query) => $query
                        ->where('ends_at', '<', now()->toDateString())
                    ),
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
            ->defaultSort('starts_at', 'desc');
    }
}
