<?php

namespace Database\Seeders;

use App\Models\Direction;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TeacherSeeder extends Seeder
{
    public function run(): void
    {
        $teachers = [
            [
                'name'        => 'Dilnoza Yusupova',
                'email'       => 'dilnoza@smk.uz',
                'employee_id' => 'EMP-001',
                'position'    => 'Kardiolog',
                'department'  => 'Klinik bo\'lim',
                'directions'  => ['KRD'],
            ],
            [
                'name'        => 'Jasur Mirzayev',
                'email'       => 'jasur@smk.uz',
                'employee_id' => 'EMP-002',
                'position'    => 'Jarroh',
                'department'  => 'Jarrohlik bo\'limi',
                'directions'  => ['JRR'],
            ],
            [
                'name'        => 'Malika Toshmatova',
                'email'       => 'malika@smk.uz',
                'employee_id' => 'EMP-003',
                'position'    => 'Pediatr',
                'department'  => 'Pediatriya bo\'limi',
                'directions'  => ['PDT'],
            ],
            [
                'name'        => 'Bobur Qodirov',
                'email'       => 'bobur@smk.uz',
                'employee_id' => 'EMP-004',
                'position'    => 'Laborant',
                'department'  => 'Laboratoriya',
                'directions'  => ['LBR'],
            ],
            [
                'name'        => 'Sarvinoz Nazarova',
                'email'       => 'sarvinoz@smk.uz',
                'employee_id' => 'EMP-005',
                'position'    => 'Stomatolog',
                'department'  => 'Stomatologiya bo\'limi',
                'directions'  => ['STM'],
            ],
            [
                'name'        => 'Ulugbek Rashidov',
                'email'       => 'ulugbek@smk.uz',
                'employee_id' => 'EMP-006',
                'position'    => 'Nevropatolog',
                'department'  => 'Nevrologiya bo\'limi',
                'directions'  => ['NVR'],
            ],
            [
                'name'        => 'Nilufar Xolmatova',
                'email'       => 'nilufar@smk.uz',
                'employee_id' => 'EMP-007',
                'position'    => 'Ginekolog',
                'department'  => 'Ginekologiya bo\'limi',
                'directions'  => ['GNK'],
            ],
        ];

        foreach ($teachers as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'      => $data['name'],
                    'password'  => bcrypt('password'),
                    'role'      => 'teacher',
                    'is_active' => true,
                ]
            );

            $teacher = Teacher::updateOrCreate(
                ['employee_id' => $data['employee_id']],
                [
                    'user_id'     => $user->id,
                    'position'    => $data['position'],
                    'department'  => $data['department'],
                    'qr_token'    => Str::uuid()->toString(),
                    'is_archived' => false,
                ]
            );

            // teacher_directions pivot
            $directionIds = Direction::whereIn('unique_key', $data['directions'])->pluck('id');
            $teacher->directions()->syncWithoutDetaching($directionIds);
        }
    }
}
