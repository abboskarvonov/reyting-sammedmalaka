<?php

namespace App\Filament\Resources\Teachers\Pages;

use App\Filament\Resources\Teachers\TeacherResource;
use App\Models\Teacher;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CreateTeacher extends CreateRecord
{
    protected static string $resource = TeacherResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $user = User::create([
            'name'      => $this->data['name'],
            'email'     => 'teacher_' . strtolower(str_replace(['-', ' '], '', $data['employee_id'])) . '_' . Str::random(4) . '@system.local',
            'password'  => bcrypt(Str::random(16)),
            'role'      => 'teacher',
            'is_active' => true,
        ]);

        return Teacher::create([
            'user_id'     => $user->id,
            'employee_id' => $data['employee_id'],
            'position'    => $data['position'] ?? null,
            'department'  => $data['department'] ?? null,
            'phone'       => $data['phone'] ?? null,
            'qr_token'    => Str::uuid()->toString(),
            'is_archived' => $data['is_archived'] ?? false,
        ]);
    }
}
