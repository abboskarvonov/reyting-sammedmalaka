<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'starts_at', 'ends_at', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'date',
        'ends_at'   => 'date',
    ];

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    /** Guruh biriktirilgan yo'nalishlar */
    public function directions(): BelongsToMany
    {
        return $this->belongsToMany(Direction::class, 'group_direction_teacher')
            ->withTimestamps();
    }

    /** RelationManager uchun: guruh-yo'nalish yozuvlari */
    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(GroupDirectionTeacher::class);
    }

    /**
     * Faqat faol va o'qish davri hali tugamagan guruhlar.
     * ends_at sanasi o'tib ketsa — guruh avtomatik "active emas" hisoblanadi.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where('ends_at', '>=', now()->toDateString());
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->ends_at !== null && $this->ends_at->lt(now()->startOfDay());
    }
}
