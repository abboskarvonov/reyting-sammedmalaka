# 04 — Autentifikatsiya Tizimi

## Kirish turlari

| Foydalanuvchi        | Kirish usuli  | Route            |
| -------------------- | ------------- | ---------------- |
| Admin / Teacher      | Email + Parol | `/login`         |
| Student (Tinglovchi) | ID-kod        | `/student/login` |
| Guest                | —             | Ochiq sahifalar  |

---

## Route tuzilmasi

```php
// routes/web.php

// ==== Ochiq sahifalar (autentifikatsiya talab qilinmaydi) ====
Route::get('/', [PublicStatsController::class, 'index'])->name('public.stats');
Route::get('/stats/teacher/{teacher}', [PublicStatsController::class, 'teacher'])->name('public.teacher');
Route::get('/stats/subject/{subject}', [PublicStatsController::class, 'subject'])->name('public.subject');

// ==== QR-kod sahifasi (ochiq) ====
Route::get('/rate/{token}', [RatingController::class, 'showQr'])->name('rating.qr');

// ==== Admin/Teacher autentifikatsiya ====
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ==== Tinglovchi autentifikatsiya ====
Route::middleware('guest:student')->group(function () {
    Route::get('/student/login', [StudentAuthController::class, 'showLogin'])->name('student.login');
    Route::post('/student/login', [StudentAuthController::class, 'login']);
});
Route::post('/student/logout', [StudentAuthController::class, 'logout'])->name('student.logout');

// ==== Admin panel ====
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // O'qituvchilar
    Route::resource('teachers', AdminTeacherController::class);
    Route::post('teachers/{teacher}/archive', [AdminTeacherController::class, 'archive'])->name('teachers.archive');

    // Fanlar
    Route::resource('subjects', AdminSubjectController::class);

    // Guruhlar
    Route::resource('groups', AdminGroupController::class);
    Route::post('groups/{group}/assign', [AdminGroupController::class, 'assignSubjectTeacher'])->name('groups.assign');

    // Tinglovchilar
    Route::resource('students', AdminStudentController::class);
    Route::post('students/import', [AdminStudentController::class, 'import'])->name('students.import');

    // Topshiriqlar
    Route::resource('tasks', AdminTaskController::class);
    Route::post('tasks/{task}/assign', [AdminTaskController::class, 'assignToTeachers'])->name('tasks.assign');

    // Davomat
    Route::resource('attendances', AdminAttendanceController::class);
    Route::get('attendances/bulk', [AdminAttendanceController::class, 'bulkCreate'])->name('attendances.bulk');
    Route::post('attendances/bulk', [AdminAttendanceController::class, 'bulkStore']);

    // Baholash natijalari
    Route::get('ratings', [AdminRatingController::class, 'index'])->name('ratings.index');
    Route::get('ratings/export', [AdminRatingController::class, 'export'])->name('ratings.export');
});

// ==== Teacher panel ====
Route::middleware(['auth', 'role:teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('dashboard');
    Route::get('/tasks', [TeacherTaskController::class, 'index'])->name('tasks.index');
    Route::patch('/tasks/{assignment}', [TeacherTaskController::class, 'update'])->name('tasks.update');
    Route::get('/attendance', [TeacherAttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/qr-code', [TeacherQrController::class, 'show'])->name('qr.show');
});

// ==== Tinglovchi baholash ====
Route::middleware('auth:student')->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
    Route::get('/rate/{teacher}/{subject}', [StudentRatingController::class, 'show'])->name('rate.show');
    Route::post('/rate/{teacher}/{subject}', [StudentRatingController::class, 'store'])->name('rate.store');
});
```

---

## Middleware

### `EnsureRole` middleware

```php
// app/Http/Middleware/EnsureRole.php
class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!auth()->check() || auth()->user()->role !== $role) {
            abort(403, 'Ruxsat yo\'q');
        }

        return $next($request);
    }
}

// bootstrap/app.php ga qo'shish
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => EnsureRole::class,
    ]);
})
```

---

## AuthController (Admin/Teacher)

```php
// app/Http/Controllers/Auth/AuthController.php
class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!auth()->attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email yoki parol noto\'g\'ri.']);
        }

        if (!auth()->user()->is_active) {
            auth()->logout();
            return back()->withErrors(['email' => 'Hisobingiz faol emas.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(
            auth()->user()->isAdmin() ? route('admin.dashboard') : route('teacher.dashboard')
        );
    }

    public function logout(Request $request): RedirectResponse
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
```

---

## StudentAuthController

```php
// app/Http/Controllers/Auth/StudentAuthController.php
class StudentAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.student-login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'string'],
        ]);

        $student = Student::where('student_id', $data['student_id'])
            ->where('is_active', true)
            ->first();

        if (!$student) {
            return back()->withErrors(['student_id' => 'Bunday ID-kod topilmadi.']);
        }

        // Sessiyaga tinglovchi ma'lumotini saqlash
        session(['student_id' => $student->id, 'student' => $student]);

        return redirect()->route('student.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(['student_id', 'student']);
        return redirect()->route('student.login');
    }
}
```

---

## `EnsureStudent` middleware

```php
// app/Http/Middleware/EnsureStudent.php
class EnsureStudent
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session()->has('student_id')) {
            return redirect()->route('student.login')
                ->withErrors(['auth' => 'Iltimos, avval tizimga kiring.']);
        }

        return $next($request);
    }
}
```

---

## Login sahifasi (Blade)

```blade
{{-- resources/views/auth/login.blade.php --}}
<form method="POST" action="{{ route('login') }}">
    @csrf
    <div>
        <label>Email</label>
        <input type="email" name="email" value="{{ old('email') }}" required autofocus>
        @error('email') <p>{{ $message }}</p> @enderror
    </div>
    <div>
        <label>Parol</label>
        <input type="password" name="password" required>
    </div>
    <div>
        <label>
            <input type="checkbox" name="remember"> Eslab qol
        </label>
    </div>
    <button type="submit">Kirish</button>
</form>
```

---

## Tinglovchi Login sahifasi (QR-kod orqali)

```blade
{{-- resources/views/auth/student-login.blade.php --}}
{{-- QR-kod skanerlanganda token URL orqali keladi, ID kiritiladi --}}
<form method="POST" action="{{ route('student.login') }}">
    @csrf
    @if(request('token'))
        <input type="hidden" name="qr_token" value="{{ request('token') }}">
    @endif
    <div>
        <label>Tinglovchi ID-kodingiz</label>
        <input type="text" name="student_id"
               placeholder="Masalan: TLV-2024-001"
               required autofocus>
        @error('student_id') <p>{{ $message }}</p> @enderror
    </div>
    <button type="submit">Baholashga o'tish</button>
</form>
```

---

## Parolni tiklash (faqat admin/teacher uchun)

Laravel standart `php artisan make:auth` yoki Breeze orqali parolni tiklash sozlanadi.

```bash
composer require laravel/breeze --dev
php artisan breeze:install blade
# keyin faqat kerakli qismlarni qoldirish
```
