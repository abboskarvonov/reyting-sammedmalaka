<?php

namespace Database\Seeders;

use App\Models\Rating;
use App\Models\RatingAnswer;
use App\Models\RatingQuestion;
use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RatingSeeder extends Seeder
{
    public function run(): void
    {
        $year     = config('app.academic_year', '2024-2025');
        $semester = config('app.semester', '1');

        $questions = RatingQuestion::where('is_active', true)->get();
        if ($questions->isEmpty()) return;

        // O'qituvchi sifatiga mos ball diapazoni
        $scoreRanges = [
            'dilnoza@smk.uz'  => [4.7, 5.0],
            'jasur@smk.uz'    => [4.4, 4.9],
            'malika@smk.uz'   => [4.2, 4.8],
            'bobur@smk.uz'    => [3.8, 4.5],
            'sarvinoz@smk.uz' => [3.5, 4.3],
            'ulugbek@smk.uz'  => [3.2, 4.0],
            'nilufar@smk.uz'  => [3.8, 4.5],
        ];

        $teachers = Teacher::with(['user', 'directions'])->get();

        foreach ($teachers as $teacher) {
            $range = $scoreRanges[$teacher->user->email] ?? [3.5, 4.5];

            // O'qituvchi yo'nalishlari (teacher_directions)
            $directionIds = $teacher->directions->pluck('id');
            if ($directionIds->isEmpty()) continue;

            // O'sha yo'nalishlarga biriktirilgan guruhlar (group_direction_teacher)
            $groupIds = DB::table('group_direction_teacher')
                ->whereIn('direction_id', $directionIds)
                ->pluck('group_id')
                ->unique();

            // O'sha guruhlardagi talabalar
            $studentIds = DB::table('students')
                ->whereIn('group_id', $groupIds)
                ->whereNull('deleted_at')
                ->pluck('id')
                ->unique();

            foreach ($studentIds as $studentId) {
                foreach ($directionIds as $directionId) {
                    // O'sha yo'nalish shu talabaning guruhiga biriktirilganmi?
                    $studentGroupId = DB::table('students')->where('id', $studentId)->value('group_id');
                    $linked = DB::table('group_direction_teacher')
                        ->where('group_id', $studentGroupId)
                        ->where('direction_id', $directionId)
                        ->exists();
                    if (! $linked) continue;

                    // Allaqachon baholanganmi?
                    $exists = Rating::where([
                        'teacher_id'    => $teacher->id,
                        'direction_id'  => $directionId,
                        'student_id'    => $studentId,
                        'academic_year' => $year,
                        'semester'      => $semester,
                    ])->exists();

                    if ($exists) continue;

                    // ~70% talabalar baholaydi
                    if (rand(1, 100) > 70) continue;

                    $scores     = [];
                    $totalScore = 0;

                    foreach ($questions as $q) {
                        $score    = $this->randomScore($range[0], $range[1]);
                        $scores[] = ['question_id' => $q->id, 'score' => $score];
                        $totalScore += $score;
                    }

                    $avgScore = round($totalScore / count($questions), 2);

                    $rating = Rating::create([
                        'teacher_id'    => $teacher->id,
                        'direction_id'  => $directionId,
                        'student_id'    => $studentId,
                        'academic_year' => $year,
                        'semester'      => $semester,
                        'total_score'   => $avgScore,
                        'comment'       => rand(1, 3) === 1 ? $this->randomComment($avgScore) : null,
                        'ip_address'    => '127.0.0.1',
                    ]);

                    foreach ($scores as $s) {
                        RatingAnswer::create([
                            'rating_id'   => $rating->id,
                            'question_id' => $s['question_id'],
                            'score'       => $s['score'],
                        ]);
                    }
                }
            }
        }
    }

    private function randomScore(float $min, float $max): int
    {
        $float = $min + (($max - $min) * (rand(50, 100) / 100));
        return (int) round(min(5, max(1, $float)));
    }

    private function randomComment(float $score): string
    {
        $positive = [
            "Juda yaxshi dars o'tdi, ko'p narsa o'rgandim.",
            "O'qituvchi mavzuni tushunarli qilib tushuntirdi.",
            'Dars qiziqarli va interaktiv bo\'ldi.',
            "Savollarimga to'liq javob berdi, rahmat.",
        ];
        $neutral = [
            "Dars normal o'tdi.",
            "O'rtacha, yaxshilash mumkin.",
            "Ba'zi mavzular tushunarsiz qoldi.",
        ];
        $negative = [
            "Dars sekin o'tdi, ko'proq misol kerak.",
            "Tushuntirish aniqroq bo'lishi kerak.",
        ];

        if ($score >= 4.5) return $positive[array_rand($positive)];
        if ($score >= 3.5) return $neutral[array_rand($neutral)];
        return $negative[array_rand($negative)];
    }
}
