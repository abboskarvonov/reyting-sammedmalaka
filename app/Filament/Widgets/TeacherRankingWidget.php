<?php

namespace App\Filament\Widgets;

use App\Models\Teacher;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TeacherRankingWidget extends TableWidget
{
    protected static ?string $heading = "O'qituvchilar reytingi (Top 10)";
    protected static ?int    $sort    = 2;
    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        $year     = config('app.academic_year');
        $semester = config('app.semester');

        return $table
            ->query(fn (): Builder => Teacher::active()
                ->with('user')
                ->withAvg(['ratings' => fn ($q) => $q
                    ->where('academic_year', $year)
                    ->where('semester', $semester)
                ], 'total_score')
                ->addSelect([
                    'ratings_count' => DB::table('ratings')
                        ->selectRaw('COUNT(DISTINCT student_id)')
                        ->whereColumn('ratings.teacher_id', 'teachers.id')
                        ->where('academic_year', $year)
                        ->where('semester', $semester),
                ])
                ->having('ratings_avg_total_score', '>', 0)
                ->orderByDesc('ratings_avg_total_score')
                ->limit(10)
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label("O'qituvchi")
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('department')
                    ->label("Bo'lim")
                    ->default('—'),
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
                    ->alignCenter(),
            ])
            ->paginated(false)
            ->modelLabel("o'qituvchi")
            ->pluralModelLabel("o'qituvchilar")
            ->emptyStateHeading('Baholash ma\'lumotlari yo\'q')
            ->emptyStateDescription('Ushbu semestr uchun o\'qituvchilar hali baholanmagan')
            ->emptyStateIcon('heroicon-o-academic-cap');
    }
}
