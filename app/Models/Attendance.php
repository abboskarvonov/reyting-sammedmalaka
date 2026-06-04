<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id', 'date', 'status', 'check_in_time',
        'expected_time', 'late_minutes', 'reason', 'recorded_by',
    ];

    protected $casts = ['date' => 'date'];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeForYear($query, int $year)
    {
        return $query->whereYear('date', $year);
    }

    public function scopeForMonth($query, int $year, int $month)
    {
        return $query->whereYear('date', $year)->whereMonth('date', $month);
    }

    public function scopeForWeek($query, Carbon $startOfWeek)
    {
        return $query->whereBetween('date', [
            $startOfWeek->copy()->startOfWeek(),
            $startOfWeek->copy()->endOfWeek(),
        ]);
    }

    public static function getStats(int $teacherId, string $period = 'month', ?Carbon $date = null): array
    {
        $date ??= now();
        $query = static::where('teacher_id', $teacherId);

        match ($period) {
            'year'  => $query->forYear($date->year),
            'month' => $query->forMonth($date->year, $date->month),
            'week'  => $query->forWeek($date),
        };

        return [
            'on_time' => (clone $query)->where('status', 'on_time')->count(),
            'late'    => (clone $query)->where('status', 'late')->count(),
            'excused' => (clone $query)->where('status', 'excused')->count(),
            'absent'  => (clone $query)->where('status', 'absent')->count(),
            'total'   => $query->count(),
        ];
    }
}
