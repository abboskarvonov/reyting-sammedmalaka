<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rating extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id', 'direction_id', 'student_id',
        'academic_year', 'semester', 'total_score', 'comment', 'ip_address',
    ];

    protected $casts = ['total_score' => 'decimal:2'];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function direction(): BelongsTo
    {
        return $this->belongsTo(Direction::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(RatingAnswer::class);
    }

    public function recalculateTotalScore(): void
    {
        $this->total_score = $this->answers()->avg('score') ?? 0;
        $this->save();
    }
}
