<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'employee_id', 'phone', 'position',
        'department', 'photo', 'qr_token', 'is_archived',
    ];

    protected $casts = ['is_archived' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * O'qituvchi biriktirilgan yo'nalishlar (teacher_directions).
     * Guruh biriktirilishiga aloqasi yo'q — yo'nalish orqali guruh talabalariga ko'rinadi.
     */
    public function directions(): BelongsToMany
    {
        return $this->belongsToMany(Direction::class, 'teacher_directions');
    }

    public function taskAssignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class);
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_assignments')
            ->withPivot(['status', 'completion_percent', 'note', 'completed_at'])
            ->withTimestamps();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function getTaskCompletionRateAttribute(): float
    {
        $total = $this->taskAssignments()->count();
        if ($total === 0) return 0;
        $completed = $this->taskAssignments()->where('status', 'completed')->count();
        return round(($completed / $total) * 100, 1);
    }

    public function getAverageRatingAttribute(): float
    {
        return round($this->ratings()->avg('total_score') ?? 0, 2);
    }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }
}
