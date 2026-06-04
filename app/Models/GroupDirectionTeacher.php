<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupDirectionTeacher extends Model
{
    protected $table = 'group_direction_teacher';

    protected $fillable = [
        'group_id',
        'direction_id',
        // teacher_id bu jadvalda yo'q — o'qituvchi teacher_directions orqali yo'nalishga biriktiriladi
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function direction(): BelongsTo
    {
        return $this->belongsTo(Direction::class);
    }
}
