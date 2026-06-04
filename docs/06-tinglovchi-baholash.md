# 06 — Tinglovchi Baholash Sahifasi

## Oqim (Flow)

```
QR skanerlanadi / havola bosiladi
         │
         ▼
Tinglovchi ID kiritadi (/student/login)
         │
         ▼
Dashboard: o'qigan fanlari ro'yxati
         │
         ▼
Fan tanlaydi → Baholash sahifasi
         │
         ▼
Savollarni javoblaydi (1-5 ball)
         │
         ▼
Yuboradi → "Baholashingiz qabul qilindi"
         │
         ▼
Qayta baholash bloklangan ✓
```

---

## Student Dashboard Controller

```php
// app/Http/Controllers/Student/StudentDashboardController.php
class StudentDashboardController extends Controller
{
    public function index(): View
    {
        $student = $this->getStudent();

        $year     = config('app.academic_year', '2024-2025');
        $semester = config('app.semester', '1');

        // Guruhida o'tiladigan barcha fanlar + o'qituvchilar
        $assignments = GroupSubjectTeacher::where('group_id', $student->group_id)
            ->where('academic_year', $year)
            ->where('semester', $semester)
            ->with(['subject', 'teacher.user'])
            ->get()
            ->map(function ($gst) use ($student, $year, $semester) {
                $gst->already_rated = $student->hasRated(
                    $gst->teacher_id, $gst->subject_id, $year, $semester
                );
                return $gst;
            });

        return view('student.dashboard', [
            'student'     => $student,
            'assignments' => $assignments,
            'ratedCount'  => $assignments->where('already_rated', true)->count(),
            'totalCount'  => $assignments->count(),
        ]);
    }

    private function getStudent(): Student
    {
        return Student::findOrFail(session('student_id'));
    }
}
```

---

## Rating Controller (Baholash)

```php
// app/Http/Controllers/Student/StudentRatingController.php
class StudentRatingController extends Controller
{
    public function show(Teacher $teacher, Subject $subject): View
    {
        $student  = Student::findOrFail(session('student_id'));
        $year     = config('app.academic_year');
        $semester = config('app.semester');

        // Allaqachon baholagan bo'lsa bloklash
        if ($student->hasRated($teacher->id, $subject->id, $year, $semester)) {
            return redirect()->route('student.dashboard')
                ->with('info', 'Siz bu fanni allaqachon baholagansiz.');
        }

        // Tinglovchi bu fanni o'qiyaptimi?
        $assignment = GroupSubjectTeacher::where('group_id', $student->group_id)
            ->where('teacher_id', $teacher->id)
            ->where('subject_id', $subject->id)
            ->where('academic_year', $year)
            ->where('semester', $semester)
            ->firstOrFail();

        $questions = RatingQuestion::where('is_active', true)
            ->orderBy('order')
            ->get();

        return view('student.rating.show', compact(
            'student', 'teacher', 'subject', 'assignment', 'questions'
        ));
    }

    public function store(Request $request, Teacher $teacher, Subject $subject): RedirectResponse
    {
        $student  = Student::findOrFail(session('student_id'));
        $year     = config('app.academic_year');
        $semester = config('app.semester');

        // Qayta yuborishdan himoya
        if ($student->hasRated($teacher->id, $subject->id, $year, $semester)) {
            return redirect()->route('student.dashboard')
                ->with('warning', 'Siz bu fanni allaqachon baholagansiz.');
        }

        $questions = RatingQuestion::where('is_active', true)->get();

        $rules = [];
        foreach ($questions as $question) {
            $rules["scores.{$question->id}"] = [
                'required', 'integer',
                "min:1", "max:{$question->max_score}"
            ];
        }

        $validated = $request->validate(array_merge($rules, [
            'comment' => ['nullable', 'string', 'max:500'],
        ]));

        DB::transaction(function () use ($validated, $student, $teacher, $subject, $year, $semester, $questions) {
            $rating = Rating::create([
                'teacher_id'    => $teacher->id,
                'subject_id'    => $subject->id,
                'student_id'    => $student->id,
                'academic_year' => $year,
                'semester'      => $semester,
                'comment'       => $validated['comment'] ?? null,
                'ip_address'    => request()->ip(),
            ]);

            foreach ($questions as $question) {
                RatingAnswer::create([
                    'rating_id'   => $rating->id,
                    'question_id' => $question->id,
                    'score'       => $validated['scores'][$question->id],
                ]);
            }

            $rating->recalculateTotalScore();
        });

        return redirect()->route('student.dashboard')
            ->with('success', "«{$subject->name}» fani bo'yicha baholashingiz qabul qilindi!");
    }
}
```

---

## Baholash sahifasi (Blade)

```blade
{{-- resources/views/student/rating/show.blade.php --}}
<x-student-layout>
    <div class="max-w-2xl mx-auto py-8 px-4">

        {{-- O'qituvchi va fan haqida --}}
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <h1 class="text-2xl font-bold text-gray-900">
                {{ $subject->name }}
            </h1>
            <p class="text-gray-500 mt-1">
                O'qituvchi: <span class="font-semibold">{{ $teacher->user->name }}</span>
            </p>
        </div>

        <form method="POST" action="{{ route('student.rate.store', [$teacher, $subject]) }}">
            @csrf

            {{-- Savollar --}}
            @foreach ($questions as $question)
                <div class="bg-white rounded-xl shadow p-6 mb-4">
                    <p class="font-medium text-gray-800 mb-4">
                        {{ $loop->iteration }}. {{ $question->question }}
                    </p>

                    {{-- Yulduz reytingi --}}
                    <div class="flex gap-3" x-data="{ selected: 0 }">
                        @for ($i = 1; $i <= $question->max_score; $i++)
                            <label class="cursor-pointer">
                                <input type="radio"
                                       name="scores[{{ $question->id }}]"
                                       value="{{ $i }}"
                                       class="sr-only"
                                       x-on:change="selected = {{ $i }}"
                                       required>
                                <span class="text-3xl"
                                      :class="selected >= {{ $i }} ? 'text-yellow-400' : 'text-gray-300'">
                                    ★
                                </span>
                            </label>
                        @endfor
                    </div>

                    @error("scores.{$question->id}")
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            {{-- Izoh --}}
            <div class="bg-white rounded-xl shadow p-6 mb-6">
                <label class="font-medium text-gray-800">
                    Qo'shimcha izoh (ixtiyoriy)
                </label>
                <textarea name="comment"
                          rows="3"
                          maxlength="500"
                          placeholder="O'qituvchi haqida fikringizni yozing..."
                          class="w-full mt-2 border rounded-lg p-3 resize-none">{{ old('comment') }}</textarea>
            </div>

            <button type="submit"
                    class="w-full bg-blue-600 text-white py-3 rounded-xl font-semibold hover:bg-blue-700">
                Baholashni yuborish
            </button>
        </form>
    </div>
</x-student-layout>
```

---

## Dashboard sahifasi (Blade)

```blade
{{-- resources/views/student/dashboard.blade.php --}}
<x-student-layout>
    <div class="max-w-3xl mx-auto py-8 px-4">

        {{-- Xush kelibsiz --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold">Assalomu alaykum, {{ $student->full_name }}!</h1>
            <p class="text-gray-500">Guruh: {{ $student->group->name }}</p>
        </div>

        {{-- Progress --}}
        <div class="bg-white rounded-xl shadow p-5 mb-6">
            <div class="flex justify-between items-center mb-2">
                <span class="font-semibold">Baholash holati</span>
                <span class="text-sm text-gray-500">
                    {{ $ratedCount }} / {{ $totalCount }} baholandi
                </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
                <div class="bg-green-500 h-3 rounded-full transition-all"
                     style="width: {{ $totalCount > 0 ? ($ratedCount / $totalCount * 100) : 0 }}%">
                </div>
            </div>
        </div>

        {{-- Fanlar ro'yxati --}}
        <div class="space-y-3">
            @foreach ($assignments as $assignment)
                <div class="bg-white rounded-xl shadow p-5 flex items-center justify-between">
                    <div>
                        <p class="font-semibold">{{ $assignment->subject->name }}</p>
                        <p class="text-sm text-gray-500">
                            O'qituvchi: {{ $assignment->teacher->user->name }}
                        </p>
                    </div>
                    @if ($assignment->already_rated)
                        <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm font-medium">
                            ✓ Baholangan
                        </span>
                    @else
                        <a href="{{ route('student.rate.show', [$assignment->teacher, $assignment->subject]) }}"
                           class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700">
                            Baholash
                        </a>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</x-student-layout>
```

---

## Xavfsizlik choralari

| Xatar | Yechim |
|-------|--------|
| Qayta baholash | DB unique constraint + `hasRated()` tekshiruv |
| Boshqa guruh fani | `GroupSubjectTeacher` orqali tegishliligi tekshiriladi |
| ID spoofing | Session-based auth, har so'rovda tekshiriladi |
| CSRF | `@csrf` token barcha formlarda |
| XSS | Blade `{{ }}` avtomatik escape qiladi |
| Spam yuborish | Rate limiting middleware qo'shiladi |

```php
// Rate limiting (routes/web.php)
Route::middleware(['auth:student', 'throttle:30,1'])->group(function () {
    Route::post('/student/rate/{teacher}/{subject}', ...);
});
```
