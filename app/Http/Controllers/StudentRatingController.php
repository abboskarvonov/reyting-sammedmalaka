<?php

namespace App\Http\Controllers;

use App\Models\Direction;
use App\Models\Rating;
use App\Models\RatingAnswer;
use App\Models\RatingQuestion;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentRatingController extends Controller
{
    public function show(Teacher $teacher, Request $request)
    {
        $student  = Student::findOrFail(session('student_id'));
        $year     = config('app.academic_year');
        $semester = config('app.semester');

        $directionId = $request->query('direction_id');

        if (!$directionId) {
            return redirect()->route('student.dashboard')
                ->with('error', "Yo'nalish tanlanmagan.");
        }

        if ($student->hasRated($teacher->id, (int) $directionId, $year, $semester)) {
            return redirect()->route('student.dashboard')
                ->with('error', "Siz bu o'qituvchini allaqachon baholagansiz.");
        }

        $direction = $teacher->directions()->find($directionId);

        if (!$direction) {
            return redirect()->route('student.dashboard')
                ->with('error', "Yo'nalish topilmadi.");
        }

        $questions = RatingQuestion::where('is_active', true)
            ->orderBy('order')
            ->get();

        return view('student.rating', compact('teacher', 'student', 'questions', 'direction', 'year', 'semester'));
    }

    public function store(Teacher $teacher, Request $request)
    {
        $student  = Student::findOrFail(session('student_id'));
        $year     = config('app.academic_year');
        $semester = config('app.semester');

        $directionId = (int) $request->input('direction_id');

        if ($student->hasRated($teacher->id, $directionId, $year, $semester)) {
            return redirect()->route('student.dashboard')
                ->with('error', "Siz bu o'qituvchini allaqachon baholagansiz.");
        }

        $questions = RatingQuestion::where('is_active', true)->get();

        $rules = ['direction_id' => ['required', 'exists:directions,id']];
        foreach ($questions as $q) {
            $rules["scores.{$q->id}"] = ['required', 'integer', 'min:1', 'max:5'];
        }
        $rules['comment'] = ['nullable', 'string', 'max:500'];

        $validated   = $request->validate($rules);
        $scores      = $validated['scores'];

        DB::transaction(function () use ($teacher, $student, $directionId, $year, $semester, $scores, $validated) {
            $totalScore = count($scores) > 0
                ? round(array_sum($scores) / count($scores), 2)
                : 0;

            $rating = Rating::create([
                'teacher_id'   => $teacher->id,
                'direction_id' => $directionId,
                'student_id'   => $student->id,
                'academic_year' => $year,
                'semester'     => $semester,
                'total_score'  => $totalScore,
                'comment'      => $validated['comment'] ?? null,
                'ip_address'   => request()->ip(),
            ]);

            foreach ($scores as $questionId => $score) {
                RatingAnswer::create([
                    'rating_id'   => $rating->id,
                    'question_id' => $questionId,
                    'score'       => $score,
                ]);
            }
        });

        return redirect()->route('student.dashboard')
            ->with('success', 'Baholash muvaffaqiyatli saqlandi!');
    }
}
