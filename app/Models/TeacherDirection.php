<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherDirection extends Model
{
    protected $table = 'teacher_directions';

    protected $fillable = [
        'teacher_id',
        'direction_id',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function direction(): BelongsTo
    {
        return $this->belongsTo(Direction::class);
    }
}
