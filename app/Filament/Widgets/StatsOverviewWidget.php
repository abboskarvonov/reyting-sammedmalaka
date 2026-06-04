<?php

namespace App\Filament\Widgets;

use App\Models\Direction;
use App\Models\Rating;
use App\Models\Student;
use App\Models\TaskAssignment;
use App\Models\Teacher;
use Filament\Widgets\StatsOverviewWidget as BaseStatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseStatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $year     = config('app.academic_year');
        $semester = config('app.semester');

        $totalTasks     = TaskAssignment::count();
        $completedTasks = TaskAssignment::where('status', 'completed')->count();
        $completionRate = $totalTasks > 0
            ? round($completedTasks / $totalTasks * 100) . '%'
            : '0%';

        $uniqueRaters = Rating::where('academic_year', $year)
            ->where('semester', $semester)
            ->distinct()
            ->count('student_id');

        $avgScore = Rating::where('academic_year', $year)
            ->where('semester', $semester)
            ->avg('total_score');

        return [
            Stat::make("O'qituvchilar", Teacher::active()->count())
                ->description('Faol xodimlar')
                ->icon('heroicon-o-academic-cap')
                ->color('primary'),
            Stat::make('Tinglovchilar', Student::where('is_active', true)->count())
                ->description('Faol tinglovchilar')
                ->icon('heroicon-o-users')
                ->color('info'),
            Stat::make('Baholashlar', $uniqueRaters)
                ->description('Baholangan tinglovchilar')
                ->icon('heroicon-o-star')
                ->color('success'),
            Stat::make("O'rtacha ball", $avgScore ? number_format($avgScore, 2) : '—')
                ->description('5 ballik tizim')
                ->icon('heroicon-o-chart-bar')
                ->color('warning'),
            Stat::make('Topshiriq bajarilishi', $completionRate)
                ->description("{$completedTasks} / {$totalTasks} ta")
                ->icon('heroicon-o-clipboard-document-check')
                ->color('success'),
            Stat::make("Yo'nalishlar", Direction::count())
                ->description('Jami yo\'nalishlar')
                ->icon('heroicon-o-squares-2x2')
                ->color('primary'),
        ];
    }
}
