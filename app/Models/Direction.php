<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Direction extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'unique_key'];

    /** Yo'nalishga biriktirilgan o'qituvchilar (teacher_directions) */
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'teacher_directions');
    }

    /** Yo'nalishga biriktirilgan guruhlar (group_direction_teacher) */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_direction_teacher')
            ->withTimestamps();
    }

    /** RelationManager uchun: biriktirilgan o'qituvchilar yozuvlari */
    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherDirection::class);
    }

    /** RelationManager uchun: biriktirilgan guruhlar yozuvlari */
    public function groupAssignments(): HasMany
    {
        return $this->hasMany(GroupDirectionTeacher::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function getAverageScoreAttribute(): float
    {
        return round($this->ratings()->avg('total_score') ?? 0, 2);
    }
}
