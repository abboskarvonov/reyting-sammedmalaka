<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Direction;
use App\Models\Rating;
use App\Models\TaskAssignment;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PublicStatsController extends Controller
{
    public function index()
    {
        $year     = config('app.academic_year');
        $semester = config('app.semester');

        $totalTeachers = Teacher::active()->count();

        $totalRatings = Rating::where('academic_year', $year)
            ->where('semester', $semester)
            ->distinct()
            ->count('student_id');

        $avgScore = Rating::where('academic_year', $year)
            ->where('semester', $semester)
            ->avg('total_score');

        // Top 10 o'qituvchi — leaderboard uchun
        $topTeachers = Teacher::active()
            ->with('user')
            ->withAvg(['ratings' => fn ($q) => $q
                ->where('academic_year', $year)
                ->where('semester', $semester)
            ], 'total_score')
            ->addSelect([
                'ratings_count' => DB::table('ratings')
                    ->selectRaw('COUNT(DISTINCT student_id)')
                    ->whereColumn('ratings.teacher_id', 'teachers.id')
                    ->where('academic_year', $year)
                    ->where('semester', $semester),
            ])
            ->having('ratings_avg_total_score', '>', 0)
            ->orderByDesc('ratings_avg_total_score')
            ->limit(10)
            ->get();

        // Barcha faol o'qituvchilar — grafik + jadval uchun
        $allTeachers = Teacher::active()
            ->with(['user', 'directions', 'taskAssignments.task'])
            ->withAvg(['ratings' => fn ($q) => $q
                ->where('academic_year', $year)
                ->where('semester', $semester)
            ], 'total_score')
            ->addSelect([
                'ratings_count' => DB::table('ratings')
                    ->selectRaw('COUNT(DISTINCT student_id)')
                    ->whereColumn('ratings.teacher_id', 'teachers.id')
                    ->where('academic_year', $year)
                    ->where('semester', $semester),
            ])
            ->withCount(['taskAssignments as tasks_total'])
            ->withCount(['taskAssignments as tasks_completed' => fn ($q) => $q
                ->where('status', 'completed')
            ])
            ->orderByDesc('ratings_avg_total_score')
            ->get();

        // Yo'nalishlar statistikasi
        $directionStats = Direction::withAvg(['ratings' => fn ($q) => $q
                ->where('academic_year', $year)
                ->where('semester', $semester)
            ], 'total_score')
            ->addSelect([
                'ratings_count' => DB::table('ratings')
                    ->selectRaw('COUNT(DISTINCT student_id)')
                    ->whereColumn('ratings.direction_id', 'directions.id')
                    ->where('academic_year', $year)
                    ->where('semester', $semester),
            ])
            ->having('ratings_avg_total_score', '>', 0)
            ->orderByDesc('ratings_avg_total_score')
            ->get();

        // O'qituvchi × yo'nalish kesimida o'rtacha ball (teacher modal uchun)
        $teacherDirRatings = Rating::where('academic_year', $year)
            ->where('semester', $semester)
            ->join('directions', 'ratings.direction_id', '=', 'directions.id')
            ->select(
                'ratings.teacher_id',
                'directions.name as dir_name',
                DB::raw('ROUND(AVG(ratings.total_score), 2) as avg_score'),
                DB::raw('COUNT(*) as cnt')
            )
            ->groupBy('ratings.teacher_id', 'directions.id', 'directions.name')
            ->get()
            ->groupBy('teacher_id');

        // Yo'nalish × o'qituvchi kesimida o'rtacha ball (direction modal uchun)
        $dirTeacherRatings = Rating::where('academic_year', $year)
            ->where('semester', $semester)
            ->join('directions', 'ratings.direction_id', '=', 'directions.id')
            ->join('teachers', 'ratings.teacher_id', '=', 'teachers.id')
            ->join('users', 'teachers.user_id', '=', 'users.id')
            ->select(
                'ratings.direction_id',
                'users.name as teacher_name',
                DB::raw('ROUND(AVG(ratings.total_score), 2) as avg_score'),
                DB::raw('COUNT(*) as cnt')
            )
            ->groupBy('ratings.direction_id', 'ratings.teacher_id', 'users.name')
            ->orderByDesc('avg_score')
            ->get()
            ->groupBy('direction_id');

        // Umumiy topshiriq statistikasi
        $totalTaskAssignments    = TaskAssignment::count();
        $completedTaskAssignments = TaskAssignment::where('status', 'completed')->count();
        $taskCompletionRate      = $totalTaskAssignments > 0
            ? round($completedTaskAssignments / $totalTaskAssignments * 100, 1)
            : 0;

        // Davomat — mavjud oylar ro'yxati
        $availableMonths = Attendance::query()
            ->selectRaw('DATE_FORMAT(date, "%Y-%m") as month_key')
            ->distinct()
            ->orderByDesc('month_key')
            ->pluck('month_key');

        $currentMonthKey = Carbon::now()->format('Y-m');
        $defaultMonthKey = $availableMonths->first() ?? $currentMonthKey;

        // Har bir oy uchun summary + per-teacher ma'lumotlar
        $attendanceAllMonths = [];
        foreach ($availableMonths as $mk) {
            $start = $mk . '-01';
            $end   = Carbon::parse($start)->endOfMonth()->toDateString();

            $summary = Attendance::whereBetween('date', [$start, $end])
                ->selectRaw('status, COUNT(*) as cnt')
                ->groupBy('status')
                ->pluck('cnt', 'status');

            $rows = Attendance::whereBetween('date', [$start, $end])
                ->join('teachers', 'attendances.teacher_id', '=', 'teachers.id')
                ->join('users', 'teachers.user_id', '=', 'users.id')
                ->select(
                    'users.name',
                    DB::raw('COUNT(*) as total'),
                    DB::raw('SUM(CASE WHEN status="on_time"  THEN 1 ELSE 0 END) as on_time_cnt'),
                    DB::raw('SUM(CASE WHEN status="late"     THEN 1 ELSE 0 END) as late_cnt'),
                    DB::raw('SUM(CASE WHEN status="excused"  THEN 1 ELSE 0 END) as excused_cnt'),
                    DB::raw('SUM(CASE WHEN status="absent"   THEN 1 ELSE 0 END) as absent_cnt')
                )
                ->groupBy('attendances.teacher_id', 'users.name')
                ->get()
                ->filter(fn($r) => $r->total > 0)
                ->sortByDesc(fn($r) => $r->on_time_cnt / $r->total)
                ->values();

            $tot = (int) $summary->sum();
            $attendanceAllMonths[$mk] = [
                'label'   => Carbon::parse($start)->locale('uz')->isoFormat('MMMM YYYY'),
                'summary' => [
                    'on_time' => (int) $summary->get('on_time', 0),
                    'late'    => (int) $summary->get('late', 0),
                    'excused' => (int) $summary->get('excused', 0),
                    'absent'  => (int) $summary->get('absent', 0),
                    'total'   => $tot,
                ],
                'labels'  => $rows->pluck('name')->toArray(),
                'ot'      => $rows->map(fn($r) => $r->total ? (int) round($r->on_time_cnt / $r->total * 100) : 0)->toArray(),
                'la'      => $rows->map(fn($r) => $r->total ? (int) round($r->late_cnt    / $r->total * 100) : 0)->toArray(),
                'ex'      => $rows->map(fn($r) => $r->total ? (int) round($r->excused_cnt / $r->total * 100) : 0)->toArray(),
                'ab'      => $rows->map(fn($r) => $r->total ? (int) round($r->absent_cnt  / $r->total * 100) : 0)->toArray(),
                'tot'     => $rows->pluck('total')->map(fn($v) => (int) $v)->toArray(),
                'don'     => $rows->pluck('on_time_cnt')->map(fn($v) => (int) $v)->toArray(),
                'lan'     => $rows->pluck('late_cnt')->map(fn($v) => (int) $v)->toArray(),
                'exn'     => $rows->pluck('excused_cnt')->map(fn($v) => (int) $v)->toArray(),
                'abn'     => $rows->pluck('absent_cnt')->map(fn($v) => (int) $v)->toArray(),
            ];
        }

        // Joriy oy (summary boxes va chart uchun default)
        $defData        = $attendanceAllMonths[$defaultMonthKey] ?? ['summary' => ['on_time' => 0, 'late' => 0, 'excused' => 0, 'absent' => 0, 'total' => 0]];
        $attendanceStats = collect($defData['summary']);
        $onTimePct       = $defData['summary']['total'] > 0
            ? round($defData['summary']['on_time'] / $defData['summary']['total'] * 100, 1)
            : 0;

        // Per-teacher (sidebar widget uchun, joriy oy)
        $now   = Carbon::now();
        $month = $now->copy()->startOfMonth()->toDateString();
        $end   = $now->copy()->endOfMonth()->toDateString();
        $teacherAttendance = Attendance::query()
            ->whereBetween('date', [$month, $end])
            ->select(
                'teacher_id',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status="on_time"  THEN 1 ELSE 0 END) as on_time_cnt'),
                DB::raw('SUM(CASE WHEN status="late"     THEN 1 ELSE 0 END) as late_cnt'),
                DB::raw('SUM(CASE WHEN status="excused"  THEN 1 ELSE 0 END) as excused_cnt'),
                DB::raw('SUM(CASE WHEN status="absent"   THEN 1 ELSE 0 END) as absent_cnt')
            )
            ->groupBy('teacher_id')
            ->get()
            ->keyBy('teacher_id');

        return view('public.stats', compact(
            'topTeachers', 'allTeachers', 'directionStats', 'attendanceStats',
            'year', 'semester',
            'totalTeachers', 'totalRatings', 'avgScore', 'onTimePct',
            'taskCompletionRate', 'teacherDirRatings', 'dirTeacherRatings', 'teacherAttendance',
            'attendanceAllMonths', 'defaultMonthKey'
        ));
    }

    public function ratings()
    {
        $year     = config('app.academic_year');
        $semester = config('app.semester');

        $totalTeachers = Teacher::active()->count();

        $totalRatings = Rating::where('academic_year', $year)
            ->where('semester', $semester)
            ->distinct()
            ->count('student_id');

        $avgScore = Rating::where('academic_year', $year)
            ->where('semester', $semester)
            ->avg('total_score');

        $allTeachers = Teacher::active()
            ->with(['user', 'directions', 'taskAssignments.task'])
            ->withAvg(['ratings' => fn ($q) => $q
                ->where('academic_year', $year)
                ->where('semester', $semester)
            ], 'total_score')
            ->addSelect([
                'ratings_count' => DB::table('ratings')
                    ->selectRaw('COUNT(DISTINCT student_id)')
                    ->whereColumn('ratings.teacher_id', 'teachers.id')
                    ->where('academic_year', $year)
                    ->where('semester', $semester),
            ])
            ->withCount(['taskAssignments as tasks_total'])
            ->withCount(['taskAssignments as tasks_completed' => fn ($q) => $q
                ->where('status', 'completed')
            ])
            ->orderByDesc('ratings_avg_total_score')
            ->get();

        $directionStats = Direction::withAvg(['ratings' => fn ($q) => $q
                ->where('academic_year', $year)
                ->where('semester', $semester)
            ], 'total_score')
            ->addSelect([
                'ratings_count' => DB::table('ratings')
                    ->selectRaw('COUNT(DISTINCT student_id)')
                    ->whereColumn('ratings.direction_id', 'directions.id')
                    ->where('academic_year', $year)
                    ->where('semester', $semester),
            ])
            ->having('ratings_avg_total_score', '>', 0)
            ->orderByDesc('ratings_avg_total_score')
            ->get();

        $teacherDirRatings = Rating::where('academic_year', $year)
            ->where('semester', $semester)
            ->join('directions', 'ratings.direction_id', '=', 'directions.id')
            ->select(
                'ratings.teacher_id',
                'directions.name as dir_name',
                DB::raw('ROUND(AVG(ratings.total_score), 2) as avg_score'),
                DB::raw('COUNT(*) as cnt')
            )
            ->groupBy('ratings.teacher_id', 'directions.id', 'directions.name')
            ->get()
            ->groupBy('teacher_id');

        $dirTeacherRatings = Rating::where('academic_year', $year)
            ->where('semester', $semester)
            ->join('directions', 'ratings.direction_id', '=', 'directions.id')
            ->join('teachers', 'ratings.teacher_id', '=', 'teachers.id')
            ->join('users', 'teachers.user_id', '=', 'users.id')
            ->select(
                'ratings.direction_id',
                'users.name as teacher_name',
                DB::raw('ROUND(AVG(ratings.total_score), 2) as avg_score'),
                DB::raw('COUNT(*) as cnt')
            )
            ->groupBy('ratings.direction_id', 'ratings.teacher_id', 'users.name')
            ->orderByDesc('avg_score')
            ->get()
            ->groupBy('direction_id');

        return view('public.ratings', compact(
            'allTeachers', 'directionStats', 'teacherDirRatings', 'dirTeacherRatings',
            'totalTeachers', 'totalRatings', 'avgScore'
        ));
    }

    public function attendance()
    {
        $availableMonths = Attendance::query()
            ->selectRaw('DATE_FORMAT(date, "%Y-%m") as month_key')
            ->distinct()
            ->orderByDesc('month_key')
            ->pluck('month_key');

        $currentMonthKey = Carbon::now()->format('Y-m');
        $defaultMonthKey = $availableMonths->first() ?? $currentMonthKey;

        $attendanceAllMonths = [];
        foreach ($availableMonths as $mk) {
            $start = $mk . '-01';
            $end   = Carbon::parse($start)->endOfMonth()->toDateString();

            $summary = Attendance::whereBetween('date', [$start, $end])
                ->selectRaw('status, COUNT(*) as cnt')
                ->groupBy('status')
                ->pluck('cnt', 'status');

            $rows = Attendance::whereBetween('date', [$start, $end])
                ->join('teachers', 'attendances.teacher_id', '=', 'teachers.id')
                ->join('users', 'teachers.user_id', '=', 'users.id')
                ->select(
                    'users.name',
                    DB::raw('COUNT(*) as total'),
                    DB::raw('SUM(CASE WHEN status="on_time"  THEN 1 ELSE 0 END) as on_time_cnt'),
                    DB::raw('SUM(CASE WHEN status="late"     THEN 1 ELSE 0 END) as late_cnt'),
                    DB::raw('SUM(CASE WHEN status="excused"  THEN 1 ELSE 0 END) as excused_cnt'),
                    DB::raw('SUM(CASE WHEN status="absent"   THEN 1 ELSE 0 END) as absent_cnt')
                )
                ->groupBy('attendances.teacher_id', 'users.name')
                ->get()
                ->filter(fn($r) => $r->total > 0)
                ->sortByDesc(fn($r) => $r->on_time_cnt / $r->total)
                ->values();

            $tot = (int) $summary->sum();
            $attendanceAllMonths[$mk] = [
                'label'   => Carbon::parse($start)->locale('uz')->isoFormat('MMMM YYYY'),
                'summary' => [
                    'on_time' => (int) $summary->get('on_time', 0),
                    'late'    => (int) $summary->get('late', 0),
                    'excused' => (int) $summary->get('excused', 0),
                    'absent'  => (int) $summary->get('absent', 0),
                    'total'   => $tot,
                ],
                'labels'  => $rows->pluck('name')->toArray(),
                'ot'      => $rows->map(fn($r) => $r->total ? (int) round($r->on_time_cnt / $r->total * 100) : 0)->toArray(),
                'la'      => $rows->map(fn($r) => $r->total ? (int) round($r->late_cnt    / $r->total * 100) : 0)->toArray(),
                'ex'      => $rows->map(fn($r) => $r->total ? (int) round($r->excused_cnt / $r->total * 100) : 0)->toArray(),
                'ab'      => $rows->map(fn($r) => $r->total ? (int) round($r->absent_cnt  / $r->total * 100) : 0)->toArray(),
                'tot'     => $rows->pluck('total')->map(fn($v) => (int) $v)->toArray(),
                'don'     => $rows->pluck('on_time_cnt')->map(fn($v) => (int) $v)->toArray(),
                'lan'     => $rows->pluck('late_cnt')->map(fn($v) => (int) $v)->toArray(),
                'exn'     => $rows->pluck('excused_cnt')->map(fn($v) => (int) $v)->toArray(),
                'abn'     => $rows->pluck('absent_cnt')->map(fn($v) => (int) $v)->toArray(),
            ];
        }

        return view('public.attendance', compact('attendanceAllMonths', 'defaultMonthKey'));
    }

    public function tasks()
    {
        $allTeachers = Teacher::active()
            ->with(['user', 'taskAssignments.task'])
            ->withCount(['taskAssignments as tasks_total'])
            ->withCount(['taskAssignments as tasks_completed' => fn ($q) => $q
                ->where('status', 'completed')
            ])
            ->get();

        $totalTaskAssignments    = TaskAssignment::count();
        $completedTaskAssignments = TaskAssignment::where('status', 'completed')->count();
        $taskCompletionRate      = $totalTaskAssignments > 0
            ? round($completedTaskAssignments / $totalTaskAssignments * 100, 1)
            : 0;

        $aTo = (int) $allTeachers->sum(fn($t) => $t->tasks_total ?? 0);
        $aDn = (int) $allTeachers->sum(fn($t) => $t->tasks_completed ?? 0);
        $aPn = $aTo - $aDn;
        $aPc = $aTo > 0 ? (int) round(($aDn / $aTo) * 100) : 0;

        $tkF = $allTeachers->filter(fn($t) => ($t->tasks_total ?? 0) > 0)->values();

        $teacherFullData = $allTeachers
            ->mapWithKeys(fn($t) => [
                $t->id => [
                    'name'  => $t->user->name,
                    'tasks' => ($t->tasks_total ?? 0) > 0
                        ? $t->taskAssignments
                            ->map(fn($a) => [
                                'title'    => $a->task?->title ?? '—',
                                'status'   => $a->status,
                                'priority' => $a->task?->priority ?? 'medium',
                                'due'      => $a->task?->due_date
                                    ? \Carbon\Carbon::parse($a->task->due_date)->format('d.m.Y')
                                    : null,
                                'done_at'  => $a->completed_at
                                    ? \Carbon\Carbon::parse($a->completed_at)->format('d.m.Y')
                                    : null,
                                'note'     => $a->note ?? null,
                            ])
                            ->sortBy(fn($x) => $x['status'] === 'completed' ? 1 : 0)
                            ->values()
                            ->toArray()
                        : [],
                ],
            ])
            ->toArray();

        return view('public.tasks', compact(
            'tkF', 'aTo', 'aDn', 'aPn', 'aPc', 'taskCompletionRate', 'teacherFullData'
        ));
    }
}
