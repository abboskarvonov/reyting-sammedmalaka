<?php

namespace App\Filament\Resources\Attendances\Tables;

use App\Models\Teacher;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AttendancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('teacher.user.name')
                    ->label("O'qituvchi")
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('teacher.user', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                    })
                    ->sortable(),
                TextColumn::make('date')
                    ->label('Sana')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Holat')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'on_time' => "O'z vaqtida",
                        'late' => 'Kechikdi',
                        'excused' => 'Sababli',
                        'absent' => 'Sababsiz',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'on_time' => 'success',
                        'late' => 'warning',
                        'excused' => 'info',
                        'absent' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('check_in_time')
                    ->label('Kirish vaqti')
                    ->time('H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('late_minutes')
                    ->label('Kechikish (daq.)')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('teacher_id')
                    ->label("O'qituvchi")
                    ->schema([
                        Select::make('teacher_id')
                            ->label("O'qituvchi ismi bo'yicha")
                            ->placeholder("Ism-familya kiriting...")
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => Teacher::with('user')
                                ->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                                ->get()
                                ->pluck('user.name', 'id')
                                ->toArray()
                            ),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['teacher_id'] ?? null,
                        fn ($q, $id) => $q->where('teacher_id', $id)
                    )),
                SelectFilter::make('status')
                    ->label('Holat')
                    ->options([
                        'on_time' => "O'z vaqtida",
                        'late' => 'Kechikdi',
                        'excused' => 'Sababli',
                        'absent' => 'Sababsiz',
                    ]),
                Filter::make('date_range')
                    ->label('Sana oralig\'i')
                    ->form([
                        DatePicker::make('date_from')->label('Dan'),
                        DatePicker::make('date_until')->label('Gacha'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['date_from'], fn ($q, $date) => $q->whereDate('date', '>=', $date))
                            ->when($data['date_until'], fn ($q, $date) => $q->whereDate('date', '<=', $date));
                    }),
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
            ->defaultSort('date', 'desc');
    }
}
