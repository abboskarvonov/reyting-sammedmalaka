<?php

namespace App\Filament\Widgets;

use App\Models\Teacher;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class TaskStatsWidget extends TableWidget
{
    protected static ?string $heading = 'Topshiriqlar holati (o\'qituvchi bo\'yicha)';
    protected static ?int    $sort    = 5;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Teacher::active()
                ->with('user')
                ->whereHas('taskAssignments')
                ->withCount(['taskAssignments as tasks_total'])
                ->withCount(['taskAssignments as tasks_completed' => fn ($q) => $q
                    ->where('status', 'completed')
                ])
                ->orderByDesc('tasks_total')
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label("O'qituvchi")
                    ->searchable()
                    ->weight('bold')
                    ->grow(),
                TextColumn::make('tasks_total')
                    ->label('Jami')
                    ->numeric()
                    ->alignCenter(),
                TextColumn::make('tasks_completed')
                    ->label('Bajarilgan')
                    ->numeric()
                    ->badge()
                    ->color('success')
                    ->alignCenter(),
                TextColumn::make('tasks_pending')
                    ->label('Kutilmoqda')
                    ->getStateUsing(fn ($record) => ($record->tasks_total ?? 0) - ($record->tasks_completed ?? 0))
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'success')
                    ->alignCenter(),
                TextColumn::make('completion_pct')
                    ->label('Bajarilish %')
                    ->getStateUsing(fn ($record) => $record->tasks_total > 0
                        ? round($record->tasks_completed / $record->tasks_total * 100)
                        : 0
                    )
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 80 => 'success',
                        $state >= 50 => 'warning',
                        default      => 'danger',
                    })
                    ->alignCenter(),
            ])
            ->paginated(false)
            ->modelLabel("o'qituvchi")
            ->pluralModelLabel("o'qituvchilar")
            ->emptyStateHeading('Topshiriqlar mavjud emas')
            ->emptyStateDescription('Hali hech bir o\'qituvchiga topshiriq tayinlanmagan')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }
}
