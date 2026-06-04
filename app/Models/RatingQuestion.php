<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RatingQuestion extends Model
{
    use HasFactory;

    protected $fillable = ['question', 'max_score', 'order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function answers(): HasMany
    {
        return $this->hasMany(RatingAnswer::class, 'question_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('order');
    }
}
