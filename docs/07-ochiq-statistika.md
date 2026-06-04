# 07 — Ochiq Statistika Sahifasi

## Tavsif

Login talab qilinmaydigan sahifa — istalgan kishi ko'rishi mumkin. O'qituvchilar va fanlar bo'yicha umumiy baholash statistikasini interaktiv grafik va jadval ko'rinishida taqdim etadi.

---

## Sahifalar

| URL                   | Tavsif                             |
| --------------------- | ---------------------------------- |
| `/`                   | Asosiy statistika: reyting jadvali |
| `/stats/teacher/{id}` | Bitta o'qituvchi chuqur tahlili    |
| `/stats/subject/{id}` | Bitta fan bo'yicha statistika      |

---

## PublicStatsController

```php
// app/Http/Controllers/Public/PublicStatsController.php
class PublicStatsController extends Controller
{
    public function index(Request $request): View
    {
        $year     = $request->year     ?? config('app.academic_year');
        $semester = $request->semester ?? config('app.semester');
        $groupId  = $request->group_id;

        // O'qituvchilar reytingi
        $teachers = Teacher::active()
            ->with(['user', 'subjects'])
            ->withAvg([
                'ratings as avg_score' => fn($q) =>
                    $q->when($year,     fn($r) => $r->where('academic_year', $year))
                      ->when($semester, fn($r) => $r->where('semester', $semester))
                      ->when($groupId,  fn($r) => $r->whereHas('student', fn($s) =>
                          $s->where('group_id', $groupId)
                      ))
            ], 'total_score')
            ->withCount([
                'ratings as rating_count' => fn($q) =>
                    $q->when($year,     fn($r) => $r->where('academic_year', $year))
                      ->when($semester, fn($r) => $r->where('semester', $semester))
            ])
            ->orderByDesc('avg_score')
            ->get();

        // Fanlar bo'yicha statistika
        $subjects = Subject::where('is_active', true)
            ->withAvg([
                'ratings as avg_score' => fn($q) =>
                    $q->when($year,     fn($r) => $r->where('academic_year', $year))
                      ->when($semester, fn($r) => $r->where('semester', $semester))
            ], 'total_score')
            ->withCount([
                'ratings as rating_count' => fn($q) =>
                    $q->when($year, fn($r) => $r->where('academic_year', $year))
            ])
            ->orderByDesc('avg_score')
            ->get();

        return view('public.stats.index', [
            'teachers'  => $teachers,
            'subjects'  => $subjects,
            'groups'    => Group::where('is_active', true)->get(),
            'years'     => $this->getAcademicYears(),
            'filters'   => compact('year', 'semester', 'groupId'),
            'chartData' => $this->buildChartData($teachers),
        ]);
    }

    public function teacher(Teacher $teacher, Request $request): View
    {
        $year     = $request->year     ?? config('app.academic_year');
        $semester = $request->semester ?? config('app.semester');

        // O'qituvchining har bir fan bo'yicha o'rtacha bali
        $subjectStats = Rating::where('teacher_id', $teacher->id)
            ->where('academic_year', $year)
            ->where('semester', $semester)
            ->with('subject')
            ->selectRaw('subject_id, AVG(total_score) as avg_score, COUNT(*) as count')
            ->groupBy('subject_id')
            ->get();

        // Har bir savol bo'yicha o'rtacha ball
        $questionStats = RatingAnswer::whereHas('rating', fn($q) =>
                $q->where('teacher_id', $teacher->id)
                  ->where('academic_year', $year)
                  ->where('semester', $semester)
            )
            ->with('question')
            ->selectRaw('question_id, AVG(score) as avg_score')
            ->groupBy('question_id')
            ->get();

        // Oy bo'yicha dinamika
        $monthlyTrend = Rating::where('teacher_id', $teacher->id)
            ->where('academic_year', $year)
            ->selectRaw('MONTH(created_at) as month, AVG(total_score) as avg')
            ->groupByRaw('MONTH(created_at)')
            ->orderBy('month')
            ->get();

        return view('public.stats.teacher', [
            'teacher'       => $teacher->load('user', 'subjects'),
            'overallAvg'    => $subjectStats->avg('avg_score'),
            'totalRatings'  => $subjectStats->sum('count'),
            'subjectStats'  => $subjectStats,
            'questionStats' => $questionStats,
            'monthlyTrend'  => $monthlyTrend,
            'filters'       => compact('year', 'semester'),
            'years'         => $this->getAcademicYears(),
        ]);
    }

    public function subject(Subject $subject, Request $request): View
    {
        $year     = $request->year     ?? config('app.academic_year');
        $semester = $request->semester ?? config('app.semester');

        // Bu fanni o'qituvchilar bo'yicha tahlil
        $teacherStats = Rating::where('subject_id', $subject->id)
            ->where('academic_year', $year)
            ->where('semester', $semester)
            ->with('teacher.user')
            ->selectRaw('teacher_id, AVG(total_score) as avg_score, COUNT(*) as count')
            ->groupBy('teacher_id')
            ->orderByDesc('avg_score')
            ->get();

        return view('public.stats.subject', [
            'subject'      => $subject,
            'teacherStats' => $teacherStats,
            'overallAvg'   => $teacherStats->avg('avg_score'),
            'filters'      => compact('year', 'semester'),
            'years'        => $this->getAcademicYears(),
        ]);
    }

    private function getAcademicYears(): array
    {
        return Rating::distinct()
            ->orderByDesc('academic_year')
            ->pluck('academic_year')
            ->toArray();
    }

    private function buildChartData($teachers): array
    {
        return [
            'labels' => $teachers->pluck('user.name')->take(10)->toArray(),
            'scores' => $teachers->pluck('avg_score')->take(10)->map(fn($v) =>
                round($v ?? 0, 2)
            )->toArray(),
        ];
    }
}
```

---

## Asosiy sahifa (Blade)

```blade
{{-- resources/views/public/stats/index.blade.php --}}
<x-public-layout>
    <div class="max-w-6xl mx-auto py-10 px-4">

        <h1 class="text-3xl font-bold text-gray-900 mb-2">Baholash Statistikasi</h1>
        <p class="text-gray-500 mb-8">Ochiq ma'lumotlar — tizimga kirish talab qilinmaydi</p>

        {{-- Filter --}}
        <form method="GET" class="bg-white rounded-xl shadow p-5 mb-8 flex flex-wrap gap-4">
            <div>
                <label class="text-sm font-medium text-gray-700">O'quv yili</label>
                <select name="year" class="mt-1 block border rounded-lg px-3 py-2">
                    @foreach ($years as $y)
                        <option value="{{ $y }}" @selected($filters['year'] === $y)>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Semestr</label>
                <select name="semester" class="mt-1 block border rounded-lg px-3 py-2">
                    <option value="1" @selected($filters['semester'] === '1')>1-semestr</option>
                    <option value="2" @selected($filters['semester'] === '2')>2-semestr</option>
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Guruh</label>
                <select name="group_id" class="mt-1 block border rounded-lg px-3 py-2">
                    <option value="">Barcha guruhlar</option>
                    @foreach ($groups as $group)
                        <option value="{{ $group->id }}" @selected($filters['groupId'] == $group->id)>
                            {{ $group->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="self-end">
                <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700">
                    Filtrlash
                </button>
            </div>
        </form>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

            {{-- O'qituvchilar reytingi jadvali --}}
            <div class="bg-white rounded-xl shadow p-6">
                <h2 class="text-xl font-bold mb-4">O'qituvchilar Reytingi</h2>
                <div class="space-y-3">
                    @foreach ($teachers->take(10) as $i => $teacher)
                        <a href="{{ route('public.teacher', $teacher) }}"
                           class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition">
                            <div class="flex items-center gap-3">
                                <span class="w-7 h-7 flex items-center justify-center rounded-full
                                    {{ $i < 3 ? 'bg-yellow-400 text-white font-bold' : 'bg-gray-100 text-gray-600' }}
                                    text-sm">
                                    {{ $i + 1 }}
                                </span>
                                <div>
                                    <p class="font-medium">{{ $teacher->user->name }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ $teacher->subjects->pluck('name')->join(', ') }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-blue-600">
                                    {{ number_format($teacher->avg_score ?? 0, 1) }} / 5.0
                                </p>
                                <p class="text-xs text-gray-400">{{ $teacher->rating_count }} baho</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Grafik (Chart.js) --}}
            <div class="bg-white rounded-xl shadow p-6">
                <h2 class="text-xl font-bold mb-4">Top 10 O'qituvchilar</h2>
                <canvas id="teacherChart" height="300"></canvas>
            </div>
        </div>

        {{-- Fanlar jadvali --}}
        <div class="bg-white rounded-xl shadow p-6 mt-8">
            <h2 class="text-xl font-bold mb-4">Fanlar Bo'yicha Statistika</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left p-3">Fan nomi</th>
                            <th class="text-center p-3">O'rtacha ball</th>
                            <th class="text-center p-3">Baholashlar soni</th>
                            <th class="text-center p-3">Holati</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($subjects as $subject)
                            <tr class="hover:bg-gray-50">
                                <td class="p-3">
                                    <a href="{{ route('public.subject', $subject) }}"
                                       class="font-medium text-blue-600 hover:underline">
                                        {{ $subject->name }}
                                    </a>
                                </td>
                                <td class="p-3 text-center">
                                    <span class="font-bold {{ $subject->avg_score >= 4 ? 'text-green-600' : ($subject->avg_score >= 3 ? 'text-yellow-600' : 'text-red-600') }}">
                                        {{ number_format($subject->avg_score ?? 0, 2) }}
                                    </span>
                                </td>
                                <td class="p-3 text-center text-gray-500">
                                    {{ $subject->rating_count }}
                                </td>
                                <td class="p-3 text-center">
                                    @if ($subject->avg_score >= 4.5)
                                        <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded-full text-xs">A'lo</span>
                                    @elseif ($subject->avg_score >= 3.5)
                                        <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full text-xs">Yaxshi</span>
                                    @elseif ($subject->avg_score >= 2.5)
                                        <span class="bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full text-xs">Qoniqarli</span>
                                    @else
                                        <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded-full text-xs">Zaif</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Chart.js --}}
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('teacherChart');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($chartData['labels']),
                datasets: [{
                    label: "O'rtacha ball",
                    data: @json($chartData['scores']),
                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1,
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { min: 0, max: 5, ticks: { stepSize: 1 } }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    </script>
    @endpush
</x-public-layout>
```

---

## O'qituvchi chuqur tahlil sahifasi (Blade tuzilmasi)

```blade
{{-- resources/views/public/stats/teacher.blade.php --}}

{{-- 1. Asosiy ko'rsatkichlar kartalar --}}
{{-- Umumiy ball, baholashlar soni, fanlar soni --}}

{{-- 2. Fan bo'yicha tahlil (bar chart) --}}
{{-- Har bir fan uchun o'rtacha ball, Radar chart --}}

{{-- 3. Savol bo'yicha tahlil (table + progress bar) --}}
{{-- Har bir savol uchun o'rtacha ball --}}

{{-- 4. Vaqt bo'yicha dinamika (line chart) --}}
{{-- Oylik trend: baholash qanday o'zgargan --}}
```

---

## Kesh (Redis) — performance uchun

```php
// PublicStatsController da kesh qo'llash
public function index(Request $request): View
{
    $cacheKey = "public_stats_{$request->year}_{$request->semester}_{$request->group_id}";

    $data = Cache::remember($cacheKey, now()->addHours(2), function () use ($request) {
        return [
            'teachers' => $this->getTeacherRankings($request),
            'subjects' => $this->getSubjectStats($request),
        ];
    });

    // ...
}

// Cache yaroqsizlantirish (yangi baho kirilganda)
// app/Observers/RatingObserver.php
public function created(Rating $rating): void
{
    Cache::tags(['public_stats'])->flush();
}
```
