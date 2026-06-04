<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class AttendanceChartWidget extends ChartWidget
{
    protected ?string $heading = 'Davomat statistikasi (joriy oy)';
    protected static ?int $sort = 6;
    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $now    = Carbon::now();
        $start  = $now->copy()->startOfMonth();
        $end    = $now->copy()->endOfMonth();

        $rows = Attendance::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $labels = ['Vaqtida', 'Kechikkan', 'Uzrli', 'Kelmagani'];
        $keys   = ['on_time', 'late', 'excused', 'absent'];
        $colors = ['#22c55e', '#f59e0b', '#3b82f6', '#ef4444'];

        return [
            'datasets' => [
                [
                    'data'            => array_map(fn($k) => $rows[$k] ?? 0, $keys),
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
