<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StudentDashboardController extends Controller
{
    public function index()
    {
        $student = Student::with('group')->findOrFail(session('student_id'));

        $year     = config('app.academic_year');
        $semester = config('app.semester');

        [$startYear, $endYear] = explode('-', $year);
        if ((int) $semester === 1) {
            $semesterStart = Carbon::create((int) $startYear, 9, 1);
            $semesterEnd   = Carbon::create((int) $endYear,   1, 31);
        } else {
            $semesterStart = Carbon::create((int) $endYear, 2, 1);
            $semesterEnd   = Carbon::create((int) $endYear, 6, 30);
        }

        // 1. Talabaning guruhiga biriktirilgan yo'nalishlar ID lari
        $directionIds = DB::table('group_direction_teacher')
            ->where('group_id', $student->group_id)
            ->pluck('direction_id');

        // 2. O'sha yo'nalishlarga biriktirilgan faol o'qituvchilar
        //    Har bir o'qituvchi uchun faqat guruhga tegishli yo'nalishlar yuklanadi
        $teachers = Teacher::active()
            ->whereHas('directions', fn ($q) => $q->whereIn('directions.id', $directionIds))
            ->with([
                'user',
                'directions' => fn ($q) => $q->whereIn('directions.id', $directionIds),
            ])
            ->get();

        return view('student.dashboard', compact('student', 'teachers', 'year', 'semester', 'semesterStart', 'semesterEnd'));
    }
}
