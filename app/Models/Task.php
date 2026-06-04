<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['title', 'description', 'created_by', 'due_date', 'priority', 'is_active'];

    protected $casts = [
        'due_date'  => 'date',
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class);
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'task_assignments')
            ->withPivot(['status', 'note', 'completed_at'])
            ->withTimestamps();
    }

    /** Bajarilgan topshiriqlar soni */
    public function getCompletedCountAttribute(): int
    {
        return $this->assignments()->where('status', 'completed')->count();
    }

    /** Bajarilmagan topshiriqlar soni */
    public function getPendingCountAttribute(): int
    {
        return $this->assignments()->where('status', 'pending')->count();
    }

    /** Bajarilish foizi */
    public function getCompletionRateAttribute(): float
    {
        $total = $this->assignments()->count();
        if ($total === 0) return 0;
        return round(($this->completed_count / $total) * 100, 1);
    }
}
