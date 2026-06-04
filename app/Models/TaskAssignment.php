<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id', 'teacher_id', 'status', 'note', 'completed_at',
    ];

    protected $casts = ['completed_at' => 'datetime'];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /** "Bajardi" deb belgilash */
    public function markCompleted(?string $note = null): void
    {
        $this->update([
            'status'       => 'completed',
            'completed_at' => now(),
            'note'         => $note ?? $this->note,
        ]);
    }

    /** "Bajarmadi" deb qaytarish */
    public function markPending(): void
    {
        $this->update([
            'status'       => 'pending',
            'completed_at' => null,
        ]);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
