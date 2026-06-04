<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RatingAnswer extends Model
{
    use HasFactory;

    protected $fillable = ['rating_id', 'question_id', 'score'];

    public function rating(): BelongsTo
    {
        return $this->belongsTo(Rating::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(RatingQuestion::class, 'question_id');
    }
}
