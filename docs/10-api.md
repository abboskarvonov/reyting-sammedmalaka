# 10 — API Endpointlar

## Asosiy maqsad

API endpointlar asosan:

- Mobile ilovalar uchun
- Tashqi tizimlar bilan integratsiya uchun
- SPA (Single Page App) uchun (kelajakda Vue/React)

---

## Autentifikatsiya

API Laravel Sanctum token-based auth ishlatadi.

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

---

## Endpointlar ro'yxati

### Auth

| Method | URL           | Tavsif              | Auth |
| ------ | ------------- | ------------------- | ---- |
| POST   | `/api/login`  | Token olish         | —    |
| POST   | `/api/logout` | Tokenni o'chirish   | ✓    |
| GET    | `/api/me`     | Joriy foydalanuvchi | ✓    |

### O'qituvchilar

| Method | URL                             | Tavsif                | Auth    |
| ------ | ------------------------------- | --------------------- | ------- |
| GET    | `/api/teachers`                 | Ro'yxat + reyting     | —       |
| GET    | `/api/teachers/{id}`            | Bitta o'qituvchi      | —       |
| GET    | `/api/teachers/{id}/stats`      | Baholash statistikasi | —       |
| GET    | `/api/teachers/{id}/attendance` | Davomat statistikasi  | ✓ admin |
| GET    | `/api/teachers/{id}/tasks`      | Topshiriqlar holati   | ✓ admin |

### Baholash

| Method | URL            | Tavsif                 | Auth      |
| ------ | -------------- | ---------------------- | --------- |
| GET    | `/api/ratings` | Ro'yxat (filter bilan) | ✓ admin   |
| POST   | `/api/ratings` | Yangi baho yuborish    | ✓ student |

### Statistika (ochiq)

| Method | URL                   | Tavsif                 | Auth |
| ------ | --------------------- | ---------------------- | ---- |
| GET    | `/api/stats/overview` | Umumiy ko'rsatkichlar  | —    |
| GET    | `/api/stats/teachers` | O'qituvchilar reytingi | —    |
| GET    | `/api/stats/subjects` | Fanlar statistikasi    | —    |

---

## API Routes

```php
// routes/api.php
use App\Http\Controllers\Api;

Route::prefix('v1')->group(function () {

    // ==== Ochiq endpointlar ====
    Route::get('/stats/overview', [Api\StatsController::class, 'overview']);
    Route::get('/stats/teachers', [Api\StatsController::class, 'teachers']);
    Route::get('/stats/subjects', [Api\StatsController::class, 'subjects']);
    Route::get('/teachers', [Api\TeacherController::class, 'index']);
    Route::get('/teachers/{teacher}', [Api\TeacherController::class, 'show']);
    Route::get('/teachers/{teacher}/stats', [Api\TeacherController::class, 'stats']);

    // ==== Autentifikatsiya ====
    Route::post('/login', [Api\AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/student/login', [Api\AuthController::class, 'studentLogin'])->middleware('throttle:10,1');

    // ==== Himoyalangan endpointlar ====
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [Api\AuthController::class, 'logout']);
        Route::get('/me', [Api\AuthController::class, 'me']);

        // Admin
        Route::middleware('role:admin')->group(function () {
            Route::get('/teachers/{teacher}/attendance', [Api\TeacherController::class, 'attendance']);
            Route::get('/teachers/{teacher}/tasks', [Api\TeacherController::class, 'tasks']);
            Route::get('/ratings', [Api\RatingController::class, 'index']);
        });

        // Tinglovchi baholash
        Route::post('/ratings', [Api\RatingController::class, 'store']);
    });
});
```

---

## API Controllers

### Auth Controller

```php
// app/Http/Controllers/Api/AuthController.php
class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!auth()->attempt($credentials)) {
            return response()->json([
                'message' => 'Email yoki parol noto\'g\'ri.'
            ], 401);
        }

        $user  = auth()->user();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'   => $user->id,
                'name' => $user->name,
                'role' => $user->role,
            ],
        ]);
    }

    public function studentLogin(Request $request): JsonResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'string'],
        ]);

        $student = Student::where('student_id', $data['student_id'])
            ->where('is_active', true)
            ->first();

        if (!$student) {
            return response()->json(['message' => 'ID-kod topilmadi.'], 404);
        }

        return response()->json([
            'student' => [
                'id'         => $student->id,
                'student_id' => $student->student_id,
                'full_name'  => $student->full_name,
                'group'      => $student->group->name,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Chiqildi.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user()->only('id', 'name', 'email', 'role'));
    }
}
```

---

### Teacher API Controller

```php
// app/Http/Controllers/Api/TeacherController.php
class TeacherController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $teachers = Teacher::active()
            ->with('user:id,name')
            ->withAvg('ratings', 'total_score')
            ->withCount('ratings')
            ->when($request->department, fn($q) => $q->where('department', $request->department))
            ->orderByDesc('ratings_avg_total_score')
            ->paginate(20);

        return response()->json($teachers);
    }

    public function show(Teacher $teacher): JsonResponse
    {
        $teacher->load(['user:id,name', 'subjects:id,name,code']);

        return response()->json([
            'id'          => $teacher->id,
            'name'        => $teacher->user->name,
            'employee_id' => $teacher->employee_id,
            'position'    => $teacher->position,
            'department'  => $teacher->department,
            'subjects'    => $teacher->subjects,
            'avg_rating'  => round($teacher->ratings()->avg('total_score') ?? 0, 2),
            'total_ratings' => $teacher->ratings()->count(),
        ]);
    }

    public function stats(Teacher $teacher, Request $request): JsonResponse
    {
        $year     = $request->year     ?? config('app.academic_year');
        $semester = $request->semester ?? config('app.semester');

        $subjectStats = Rating::where('teacher_id', $teacher->id)
            ->where('academic_year', $year)
            ->where('semester', $semester)
            ->with('subject:id,name')
            ->selectRaw('subject_id, AVG(total_score) as avg_score, COUNT(*) as count')
            ->groupBy('subject_id')
            ->get();

        return response()->json([
            'teacher'      => $teacher->user->name,
            'overall_avg'  => round($subjectStats->avg('avg_score') ?? 0, 2),
            'total_ratings' => $subjectStats->sum('count'),
            'by_subject'   => $subjectStats,
        ]);
    }

    public function attendance(Teacher $teacher, Request $request): JsonResponse
    {
        $period = $request->period ?? 'month';
        $stats  = Attendance::getStats($teacher->id, $period);

        return response()->json([
            'teacher' => $teacher->user->name,
            'period'  => $period,
            'stats'   => $stats,
        ]);
    }

    public function tasks(Teacher $teacher): JsonResponse
    {
        $assignments = $teacher->taskAssignments()
            ->with('task:id,title,due_date,priority')
            ->get();

        return response()->json([
            'teacher'         => $teacher->user->name,
            'total'           => $assignments->count(),
            'completed'       => $assignments->where('status', 'completed')->count(),
            'completion_rate' => $teacher->task_completion_rate,
            'tasks'           => $assignments,
        ]);
    }
}
```

---

### Rating API Controller

```php
// app/Http/Controllers/Api/RatingController.php
class RatingController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'teacher_id' => ['required', 'exists:teachers,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'scores'     => ['required', 'array'],
            'scores.*'   => ['required', 'integer', 'min:1', 'max:5'],
            'comment'    => ['nullable', 'string', 'max:500'],
        ]);

        $student = Student::findOrFail($validated['student_id']);

        $year     = config('app.academic_year');
        $semester = config('app.semester');

        if ($student->hasRated($validated['teacher_id'], $validated['subject_id'], $year, $semester)) {
            return response()->json([
                'message' => 'Siz bu fanni allaqachon baholagansiz.'
            ], 422);
        }

        DB::transaction(function () use ($validated, $year, $semester) {
            $rating = Rating::create([
                'teacher_id'    => $validated['teacher_id'],
                'subject_id'    => $validated['subject_id'],
                'student_id'    => $validated['student_id'],
                'academic_year' => $year,
                'semester'      => $semester,
                'comment'       => $validated['comment'] ?? null,
                'ip_address'    => request()->ip(),
            ]);

            foreach ($validated['scores'] as $questionId => $score) {
                RatingAnswer::create([
                    'rating_id'   => $rating->id,
                    'question_id' => $questionId,
                    'score'       => $score,
                ]);
            }

            $rating->recalculateTotalScore();
        });

        return response()->json(['message' => 'Baholash qabul qilindi.'], 201);
    }
}
```

---

## API Response formati (standart)

```json
// Muvaffaqiyatli javob
{
    "data": { ... },
    "message": "OK"
}

// Xato javob
{
    "message": "Xato xabari",
    "errors": {
        "field": ["Validatsiya xatosi"]
    }
}

// Sahifalangan javob
{
    "data": [ ... ],
    "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
    "meta": { "current_page": 1, "total": 50, "per_page": 20 }
}
```

---

## Rate Limiting

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->throttleApi();

    RateLimiter::for('api', function (Request $request) {
        return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });

    RateLimiter::for('login', function (Request $request) {
        return Limit::perMinute(10)->by($request->ip());
    });
})
```
