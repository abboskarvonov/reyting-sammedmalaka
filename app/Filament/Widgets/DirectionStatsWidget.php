<?php

namespace App\Filament\Widgets;

use App\Models\Direction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DirectionStatsWidget extends TableWidget
{
    protected static ?string $heading = "Yo'nalishlar reytingi";
    protected static ?int    $sort    = 3;
    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        $year     = config('app.academic_year');
        $semester = config('app.semester');

        return $table
            ->query(fn (): Builder => Direction::query()
                ->withAvg(['ratings' => fn ($q) => $q
                    ->where('academic_year', $year)
                    ->where('semester', $semester)
                ], 'total_score')
                ->addSelect([
                    'ratings_count' => DB::table('ratings')
                        ->selectRaw('COUNT(DISTINCT student_id)')
                        ->whereColumn('ratings.direction_id', 'directions.id')
                        ->where('academic_year', $year)
                        ->where('semester', $semester),
                ])
                ->having('ratings_avg_total_score', '>', 0)
                ->orderByDesc('ratings_avg_total_score')
            )
            ->columns([
                TextColumn::make('name')
                    ->label("Yo'nalish")
                    ->weight('bold')
                    ->grow(),
                TextColumn::make('ratings_avg_total_score')
                    ->label('Ball')
                    ->numeric(decimalPlaces: 2)
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 4.5 => 'success',
                        $state >= 3.5 => 'warning',
                        default       => 'danger',
                    })
                    ->alignCenter(),
                TextColumn::make('ratings_count')
                    ->label('Tinglovchi')
                    ->numeric()
                    ->badge()
                    ->color('info')
                    ->alignCenter(),
            ])
            ->paginated(false)
            ->modelLabel("yo'nalish")
            ->pluralModelLabel("yo'nalishlar")
            ->emptyStateHeading('Baholash ma\'lumotlari yo\'q')
            ->emptyStateDescription('Ushbu semestr uchun yo\'nalishlar hali baholanmagan')
            ->emptyStateIcon('heroicon-o-chart-bar');
    }
}
