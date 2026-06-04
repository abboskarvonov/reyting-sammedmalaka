<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'full_name',
        'group_id',
        'phone',
        'muassasa_nomi',
        'diplom_raqam',
        'passport_seriya_raqam',
        'pinfl',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    /** O'qish boshlanish sanasi — guruhdan olinadi */
    public function getStartDateAttribute(): ?\Illuminate\Support\Carbon
    {
        return $this->group?->starts_at;
    }

    /** O'qish tugash sanasi — guruhdan olinadi */
    public function getEndDateAttribute(): ?\Illuminate\Support\Carbon
    {
        return $this->group?->ends_at;
    }

    public function hasRated(int $teacherId, int $directionId, string $year, string $semester): bool
    {
        return $this->ratings()
            ->where('teacher_id', $teacherId)
            ->where('direction_id', $directionId)
            ->where('academic_year', $year)
            ->where('semester', $semester)
            ->exists();
    }
}
