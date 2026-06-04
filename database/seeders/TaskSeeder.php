<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();

        // ── 1. Topshiriqlarni yaratish ────────────────────────────────
        $tasksData = [
            [
                'title'       => 'Dars ishlanmalarini yangilash',
                'description' => "Joriy semestr uchun dars ishlanmalarini yangilab topshirish.",
                'due_date'    => now()->addDays(10),
                'priority'    => 'high',
            ],
            [
                'title'       => 'Attestatsiya hujjatlarini tayyorlash',
                'description' => "Malaka oshirish attestatsiyasi uchun hujjat paketini tayyorlash.",
                'due_date'    => now()->addDays(20),
                'priority'    => 'high',
            ],
            [
                'title'       => 'Ilmiy maqola nashr qilish',
                'description' => "Joriy o'quv yilida kamida 1 ta ilmiy maqola nashr qilish.",
                'due_date'    => now()->addDays(60),
                'priority'    => 'medium',
            ],
            [
                'title'       => 'Seminar o\'tkazish',
                'description' => "Mutaxassislik fani bo'yicha tinglovchilar uchun ochiq seminar tashkil qilish.",
                'due_date'    => now()->addDays(15),
                'priority'    => 'medium',
            ],
            [
                'title'       => 'Yillik hisobot topshirish',
                'description' => "O'tgan yil faoliyati bo'yicha yozma hisobot tayyorlab topshirish.",
                'due_date'    => now()->subDays(5),
                'priority'    => 'low',
            ],
            [
                'title'       => 'Elektron resurs tayyorlash',
                'description' => "Dars uchun multimedia materiallar (slayd, video) tayyorlash.",
                'due_date'    => now()->addDays(30),
                'priority'    => 'medium',
            ],
            [
                'title'       => 'Test savollari bazasini to\'ldirish',
                'description' => "Har bir o'qituvchi kamida 50 ta test savoli kiritishi kerak.",
                'due_date'    => now()->addDays(25),
                'priority'    => 'high',
            ],
        ];

        $tasks = [];
        foreach ($tasksData as $data) {
            $tasks[] = Task::updateOrCreate(
                ['title' => $data['title']],
                array_merge($data, ['created_by' => $admin->id, 'is_active' => true])
            );
        }

        // ── 2. O'qituvchilarga turli sondagi topshiriqlar biriktirish ──
        // Har bir o'qituvchi turli topshiriqlar oladi va turlicha bajaradi
        $teachers = Teacher::with('user')->get();

        // email => [biriktirilgan task indekslari => holat]
        // 0-6 = task indeks, 'completed'/'pending'
        $assignments = [
            'dilnoza@smk.uz'  => [0 => 'completed', 1 => 'completed', 2 => 'completed', 3 => 'completed', 4 => 'completed', 5 => 'completed', 6 => 'pending'],
            'jasur@smk.uz'    => [0 => 'completed', 1 => 'completed', 2 => 'pending',   3 => 'completed', 4 => 'completed'],
            'malika@smk.uz'   => [0 => 'completed', 1 => 'pending',   2 => 'pending',   3 => 'completed', 5 => 'pending'],
            'bobur@smk.uz'    => [0 => 'completed', 1 => 'completed', 4 => 'pending',   6 => 'pending'],
            'sarvinoz@smk.uz' => [0 => 'pending',   1 => 'pending',   2 => 'pending'],
            'ulugbek@smk.uz'  => [0 => 'completed', 3 => 'completed', 5 => 'pending',   6 => 'pending'],
            'nilufar@smk.uz'  => [0 => 'completed', 1 => 'pending',   2 => 'pending',   4 => 'completed', 5 => 'completed'],
        ];

        foreach ($teachers as $teacher) {
            $email      = $teacher->user->email;
            $taskMatrix = $assignments[$email] ?? [0 => 'pending', 1 => 'pending'];

            foreach ($taskMatrix as $taskIndex => $status) {
                if (! isset($tasks[$taskIndex])) continue;

                TaskAssignment::updateOrInsert(
                    ['task_id' => $tasks[$taskIndex]->id, 'teacher_id' => $teacher->id],
                    [
                        'status'       => $status,
                        'note'         => $status === 'completed' ? 'Bajarildi va topshirildi.' : null,
                        'completed_at' => $status === 'completed' ? now()->subDays(rand(1, 10)) : null,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]
                );
            }
        }
    }
}
