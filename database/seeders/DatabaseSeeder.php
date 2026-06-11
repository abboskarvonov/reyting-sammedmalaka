<?php

namespace Database\Seeders;

use App\Models\RatingQuestion;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Admin foydalanuvchi
        User::updateOrCreate(
            ['email' => 'admin@staff-rating.com'],
            [
                'name'      => 'Super Admin',
                'password'  => bcrypt('password'),
                'role'      => 'admin',
                'is_active' => true,
            ]
        );

        // 2. Baholash savollari
        $questions = [
            ["O'qituvchi mavzuni tushunarli va aniq tushuntira oladimi?",    1],
            ["O'qituvchi darsga yaxshi tayyorgarlik ko'rganmi?",             2],
            ["O'qituvchi tinglovchilarga hurmat bilan muomala qiladimi?",     3],
            ["Dars qiziqarli va interaktiv tarzda o'tildimi?",               4],
            ["O'qituvchi savollaringizga aniq va to'liq javob bera oladimi?", 5],
        ];

        foreach ($questions as [$question, $order]) {
            RatingQuestion::updateOrCreate(
                ['question' => $question],
                ['max_score' => 5, 'order' => $order, 'is_active' => true]
            );
        }

        // 3. Asosiy ma'lumotlar
        $this->call([
            AdminUserSeeder::class,
            GroupSeeder::class,
            SubjectSeeder::class,
            TeacherSeeder::class,
            StudentSeeder::class,
            GroupSubjectTeacherSeeder::class,
            TaskSeeder::class,
            AttendanceSeeder::class,
            RatingSeeder::class,
        ]);
    }
}
