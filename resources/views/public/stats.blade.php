@extends('layouts.stats')

@section('title', 'Statistika paneli — SMK Samarqand')

@php
    /* Teacher chart */
    $cT = $allTeachers
        ->filter(fn($t) => ($t->ratings_avg_total_score ?? 0) > 0)
        ->sortByDesc('ratings_avg_total_score')
        ->values();
    $tL = $cT->map(fn($t) => $t->user->name)->toArray();
    $tS = $cT->map(fn($t) => round((float) $t->ratings_avg_total_score, 2))->toArray();
    $tC = $cT->map(fn($t) => (int) $t->ratings_count)->toArray();

    /* Direction chart */
    $dL = $directionStats->map(fn($d) => $d->name)->toArray();
    $dS = $directionStats->map(fn($d) => round((float) ($d->ratings_avg_total_score ?? 0), 2))->toArray();
    $dC = $directionStats->map(fn($d) => (int) ($d->ratings_count ?? 0))->toArray();

    /* Task stacked */
    $tkF = $allTeachers->filter(fn($t) => ($t->tasks_total ?? 0) > 0)->values();
    $tkL = $tkF->map(fn($t) => $t->user->name)->toArray();
    $tkCo = $tkF
        ->map(fn($t) => $t->tasks_total > 0 ? (int) round(($t->tasks_completed / $t->tasks_total) * 100) : 0)
        ->toArray();
    $tkPe = $tkF
        ->map(
            fn($t) => $t->tasks_total > 0
                ? (int) round((($t->tasks_total - $t->tasks_completed) / $t->tasks_total) * 100)
                : 100,
        )
        ->toArray();
    $tkTo = $tkF->map(fn($t) => (int) $t->tasks_total)->toArray();
    $tkDn = $tkF->map(fn($t) => (int) $t->tasks_completed)->toArray();

    /* Modal uchun: o'qituvchi → topshiriqlar + yo'nalishlar bo'yicha ball */
$teacherFullData = $allTeachers
    ->mapWithKeys(
        fn($t) => [
            $t->id => [
                'name' => $t->user->name,
                'tasks' =>
                    ($t->tasks_total ?? 0) > 0
                        ? $t->taskAssignments
                            ->map(
                                fn($a) => [
                                    'title' => $a->task?->title ?? '—',
                                    'status' => $a->status,
                                    'priority' => $a->task?->priority ?? 'medium',
                                    'due' => $a->task?->due_date
                                        ? \Carbon\Carbon::parse($a->task->due_date)->format('d.m.Y')
                                        : null,
                                    'done_at' => $a->completed_at
                                        ? \Carbon\Carbon::parse($a->completed_at)->format('d.m.Y')
                                        : null,
                                    'note' => $a->note ?? null,
                                ],
                            )
                            ->sortBy(fn($x) => $x['status'] === 'completed' ? 1 : 0)
                            ->values()
                            ->toArray()
                        : [],
                'dirs' => isset($teacherDirRatings[$t->id])
                    ? $teacherDirRatings[$t->id]
                        ->map(
                            fn($r) => [
                                'name' => $r->dir_name,
                                'score' => (float) $r->avg_score,
                                'count' => (int) $r->cnt,
                            ],
                        )
                        ->sortByDesc('score')
                        ->values()
                        ->toArray()
                    : [],
            ],
        ],
    )
    ->toArray();

/* Donut overall */
$aTo = (int) $allTeachers->sum(fn($t) => $t->tasks_total ?? 0);
$aDn = (int) $allTeachers->sum(fn($t) => $t->tasks_completed ?? 0);
$aPn = $aTo - $aDn;
$aPc = $aTo > 0 ? (int) round(($aDn / $aTo) * 100) : 0;

/* Table */
$rows = $allTeachers
    ->map(
        fn($t) => [
            'id' => (int) $t->id,
            'name' => $t->user->name,
            'dept' => $t->department ?? '—',
            'dirs' => $t->directions->pluck('name')->implode(', ') ?? '—',
            'score' => round((float) ($t->ratings_avg_total_score ?? 0), 1),
            'cnt' => (int) ($t->ratings_count ?? 0),
            'tTot' => (int) ($t->tasks_total ?? 0),
            'tDn' => (int) ($t->tasks_completed ?? 0),
            'tPct' =>
                ($t->tasks_total ?? 0) > 0
                    ? (int) round((($t->tasks_total - $t->tasks_completed) / $t->tasks_total) * 100)
                    : 0,
        ],
    )
    ->values()
    ->toArray();

$avgR = round($avgScore ?? 0, 1);

/* Davomat chart */
$attTotal = (int) $attendanceStats->sum();
$attOnTime = (int) $attendanceStats->get('on_time', 0);
$attLate = (int) $attendanceStats->get('late', 0);
$attExcused = (int) $attendanceStats->get('excused', 0);
$attAbsent = (int) $attendanceStats->get('absent', 0);

$aRows = $allTeachers
    ->map(
        fn($t) => [
            'name' => $t->user->name,
            'total' => (int) ($teacherAttendance[$t->id]->total ?? 0),
            'on_time' => (int) ($teacherAttendance[$t->id]->on_time_cnt ?? 0),
            'late' => (int) ($teacherAttendance[$t->id]->late_cnt ?? 0),
            'excused' => (int) ($teacherAttendance[$t->id]->excused_cnt ?? 0),
            'absent' => (int) ($teacherAttendance[$t->id]->absent_cnt ?? 0),
        ],
    )
    ->filter(fn($r) => $r['total'] > 0)
    ->sortByDesc(fn($r) => $r['total'] > 0 ? $r['on_time'] / $r['total'] : 0)
    ->values();

$aL = $aRows->map(fn($r) => $r['name'])->toArray();
$aOT = $aRows->map(fn($r) => $r['total'] > 0 ? (int) round(($r['on_time'] / $r['total']) * 100) : 0)->toArray();
$aLA = $aRows->map(fn($r) => $r['total'] > 0 ? (int) round(($r['late'] / $r['total']) * 100) : 0)->toArray();
$aEX = $aRows->map(fn($r) => $r['total'] > 0 ? (int) round(($r['excused'] / $r['total']) * 100) : 0)->toArray();
$aAB = $aRows->map(fn($r) => $r['total'] > 0 ? (int) round(($r['absent'] / $r['total']) * 100) : 0)->toArray();
$aTO = $aRows->map(fn($r) => (int) $r['total'])->toArray();
$aDN = $aRows->map(fn($r) => (int) $r['on_time'])->toArray();
$aLN = $aRows->map(fn($r) => (int) $r['late'])->toArray();
$aEN = $aRows->map(fn($r) => (int) $r['excused'])->toArray();
$aAN = $aRows->map(fn($r) => (int) $r['absent'])->toArray();

$currentMonth = \Carbon\Carbon::now()->locale('uz')->isoFormat('MMMM YYYY');

/* Direction modal uchun: direction_id => [{teacher_name, avg_score, cnt}] */
$dirTeacherData = [];
foreach ($directionStats as $dir) {
    $teachers = $dirTeacherRatings->get($dir->id, collect());
    $dirTeacherData[$dir->id] = [
        'name' => $dir->name,
        'score' => round((float) $dir->ratings_avg_total_score, 2),
        'count' => (int) $dir->ratings_count,
        'teachers' => $teachers
            ->map(
                fn($t) => [
                    'name' => $t->teacher_name,
                    'score' => (float) $t->avg_score,
                    'cnt' => (int) $t->cnt,
                    ],
                )
                ->values()
                ->toArray(),
        ];
    }
@endphp

{{-- ═══════ PAGE HEADER ═══════ --}}
@section('page-header')
    <div class="border-b border-slate-100" style="background:linear-gradient(135deg,#1E3A5F 0%,#1e4d8c 60%,#2563EB 100%)">
        <div class="px-8 py-7 flex items-center justify-between flex-wrap gap-3">
            <div>
                <h1 class="text-[26px] font-extrabold text-white m-0 leading-tight">Ochiq statistika</h1>
                <p class="text-[13px] text-white/60 mt-1.5 m-0">O'qituvchilar faoliyati · baholash natijalari · topshiriqlar
                    tahlili</p>
            </div>
            <div class="flex items-center gap-2 text-[12px] text-white/70 bg-white/10 px-3 py-1.5 rounded-full">
                <span class="w-2 h-2 rounded-full bg-emerald-400 inline-block animate-pulse"></span>
                Jonli ma'lumot
            </div>
        </div>
    </div>
@endsection

{{-- ═══════ CONTENT ═══════ --}}
@section('content')
    <div x-data="sApp()" x-init="$nextTick(initCharts)" x-cloak>

        {{-- ══════════════════════════════════════════════════════
         ROW 1 — 3 KPI cards
         ══════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 *:min-w-0 *:overflow-hidden mb-5">

            {{-- Jami xodimlar --}}
            <div class="card px-5.5 py-5">
                <div class="flex items-start justify-between mb-2.5">
                    <p class="text-xs font-medium text-gray-500 m-0">Jami xodimlar</p>
                    <span
                        class="w-7.5 h-7.5 rounded-lg bg-indigo-50 flex items-center justify-center text-[13px] text-blue-500 shrink-0">
                        <i class="fas fa-users"></i>
                    </span>
                </div>
                <p class="text-[32px] font-extrabold text-gray-900 m-0 mb-2 leading-none tabular-nums">{{ $totalTeachers }}
                </p>
                <span class="tp tp-n"><i class="fas fa-circle" style="font-size:5px"></i>Faol o'qituvchi</span>
            </div>

            {{-- O'rtacha ball --}}
            <div class="card px-5.5 py-5">
                <div class="flex items-start justify-between mb-2.5">
                    <p class="text-xs font-medium text-gray-500 m-0">O'rtacha ball</p>
                    <span
                        class="w-7.5 h-7.5 rounded-lg bg-yellow-100 flex items-center justify-center text-[13px] text-yellow-600 shrink-0">
                        <i class="fas fa-star"></i>
                    </span>
                </div>
                <p class="text-[32px] font-extrabold text-gray-900 m-0 mb-2 leading-none tabular-nums">{{ $avgR ?: '—' }}
                </p>
                <div class="flex items-center gap-1.25">
                    <div class="flex gap-0.5">
                        @for ($s = 1; $s <= 5; $s++)
                            <i class="fas fa-star text-[10px]"
                                style="color:{{ $s <= round($avgR) ? '#F59E0B' : '#E5E7EB' }}"></i>
                        @endfor
                    </div>
                    <span class="text-[10px] text-gray-400">5 ballik</span>
                </div>
            </div>

            {{-- Topshiriqlar --}}
            <div class="card px-5.5 py-5">
                <div class="flex items-start justify-between mb-2.5">
                    <p class="text-xs font-medium text-gray-500 m-0">Topshiriqlar</p>
                    <span
                        class="w-7.5 h-7.5 rounded-lg bg-blue-100 flex items-center justify-center text-[13px] text-blue-600 shrink-0">
                        <i class="fas fa-clipboard-check"></i>
                    </span>
                </div>
                <p class="text-[32px] font-extrabold text-gray-900 m-0 mb-2 leading-none tabular-nums">
                    {{ $taskCompletionRate }}<span class="text-lg font-semibold text-gray-500">%</span>
                </p>
                @if ($taskCompletionRate >= 70)
                    <span class="tp tp-g"><i class="fas fa-arrow-up" style="font-size:8px"></i>Yaxshi</span>
                @elseif($taskCompletionRate >= 40)
                    <span class="tp tp-n"><i class="fas fa-minus" style="font-size:8px"></i>O'rta</span>
                @else
                    <span class="tp tp-r"><i class="fas fa-arrow-down" style="font-size:8px"></i>Past</span>
                @endif
            </div>

        </div>

        {{-- ══════════════════════════════════════════════════════
         ROW 2 — Teacher chart | Direction chart
         ══════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 *:min-w-0 *:overflow-hidden mb-5">

            {{-- O'qituvchilar reytingi --}}
            <div class="card px-6.5 py-5.5">
                <div class="flex items-start justify-between flex-wrap gap-2 mb-4.5">
                    <div>
                        <h2 class="text-[15px] font-bold text-gray-900 m-0">O'qituvchilar reytingi</h2>
                        <p class="text-xs text-gray-400 mt-1 m-0">
                            @if (count($tL))
                                <strong class="text-gray-700">{{ count($tL) }}</strong> ta o'qituvchi baholangan
                            @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-3 text-[11px] text-gray-500">
                        <span class="flex items-center gap-1">
                            <span class="w-2.5 h-2.5 rounded-[3px] bg-emerald-500 inline-block"></span>A'lo ≥ 4.5
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="w-2.5 h-2.5 rounded-[3px] bg-amber-400 inline-block"></span>Yaxshi ≥ 3.5
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="w-2.5 h-2.5 rounded-[3px] bg-red-400 inline-block"></span>Qoniqarli &lt; 3.5
                        </span>
                    </div>
                </div>
                @if (count($tL) > 0)
                    <div class="cw" id="twrap"><canvas id="tc"></canvas></div>
                @else
                    <div class="h-50 flex flex-col items-center justify-center text-gray-400">
                        <i class="fas fa-chart-bar text-[32px] opacity-20 mb-2.5"></i>
                        <p class="text-[13px] m-0">Hali baholash ma'lumotlari yo'q</p>
                    </div>
                @endif
            </div>

            {{-- Yo'nalishlar statistikasi --}}
            <div class="card px-6.5 py-5.5 flex flex-col">
                <div class="flex items-start justify-between flex-wrap gap-2 mb-4">
                    <div>
                        <h2 class="text-[15px] font-bold text-gray-900 m-0">Yo'nalishlar statistikasi</h2>
                        <p class="text-xs text-gray-400 mt-1 m-0">Yo'nalish bo'yicha o'rtacha baholash natijasi</p>
                    </div>
                    <div class="flex items-center gap-3 text-[11px] text-gray-500">
                        <span class="flex items-center gap-1"><span
                                class="w-2 h-2 rounded-sm bg-emerald-500 inline-block"></span>≥ 4.5</span>
                        <span class="flex items-center gap-1"><span
                                class="w-2 h-2 rounded-sm bg-amber-400 inline-block"></span>≥ 3.5</span>
                        <span class="flex items-center gap-1"><span
                                class="w-2 h-2 rounded-sm bg-red-400 inline-block"></span>&lt; 3.5</span>
                    </div>
                </div>
                @if ($directionStats->count() > 0)
                    <div class="flex flex-col gap-3 flex-1">
                        @foreach ($directionStats as $dir)
                            @php
                                $sc = round((float) $dir->ratings_avg_total_score, 2);
                                $pct = min(100, round(($sc / 5) * 100));
                                $col = $sc >= 4.5 ? '#10B981' : ($sc >= 3.5 ? '#F59E0B' : '#EF4444');
                                $tcol = $sc >= 4.5 ? '#16A34A' : ($sc >= 3.5 ? '#CA8A04' : '#DC2626');
                            @endphp
                            <div class="flex items-center gap-3 py-1.5 border-b border-gray-50 last:border-0">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between mb-1.5">
                                        <p class="text-[13px] font-semibold text-gray-800 m-0 truncate pr-2">
                                            {{ $dir->name }}</p>
                                        <span class="text-[11px] text-gray-400 shrink-0">{{ $dir->ratings_count }} ta</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                            <div class="h-full rounded-full transition-[width] duration-700 ease-out"
                                                style="width:{{ $pct }}%;background:{{ $col }}"></div>
                                        </div>
                                        <span class="text-[13px] font-extrabold tabular-nums shrink-0 min-w-8 text-right"
                                            style="color:{{ $tcol }}">{{ number_format($sc, 2) }}</span>
                                        <button @click="openDirModal({{ $dir->id }})"
                                            title="Yo'nalish bo'yicha o'qituvchilar"
                                            class="shrink-0 w-7 h-7 rounded-lg bg-indigo-50 text-blue-500 border-0 cursor-pointer flex items-center justify-center transition-colors duration-150"
                                            onmouseover="this.style.background='#DBEAFE'"
                                            onmouseout="this.style.background='#EEF2FF'">
                                            <i class="fas fa-users text-[11px]"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex-1 h-50 flex flex-col items-center justify-center text-gray-400">
                        <i class="fas fa-sitemap text-[32px] opacity-20 mb-2.5"></i>
                        <p class="text-[13px] m-0">Yo'nalish ma'lumotlari yo'q</p>
                    </div>
                @endif
            </div>

        </div>

        {{-- ══════════════════════════════════════════════════════
         ROW 3 — Top 5 | Donut | Teacher tasks
         ══════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 *:min-w-0 *:overflow-hidden mb-5">

            {{-- ── Card 1: Top 5 O'qituvchi ──────────────────────────────── --}}
            <div class="card p-5.5 flex flex-col">
                <div class="mb-4">
                    <h2 class="text-sm font-bold text-gray-900 m-0">Top 5 O'qituvchi</h2>
                    <p class="text-[11px] text-gray-400 mt-0.75 m-0">Eng yuqori baholash natijalari</p>
                </div>
                @if ($topTeachers->count() > 0)
                    <div class="flex-1">
                        @foreach ($topTeachers->take(5) as $rk => $tch)
                            @php
                                $sc = round((float) $tch->ratings_avg_total_score, 1);
                                $ini = collect(explode(' ', $tch->user->name))
                                    ->map(fn($w) => strtoupper(substr($w, 0, 1)))
                                    ->take(2)
                                    ->implode('');
                                $aGs = [
                                    'linear-gradient(135deg,#1E3A5F,#3B82F6)',
                                    'linear-gradient(135deg,#0EA5E9,#06B6D4)',
                                    'linear-gradient(135deg,#10B981,#34D399)',
                                    'linear-gradient(135deg,#8B5CF6,#A78BFA)',
                                    'linear-gradient(135deg,#F59E0B,#FBBF24)',
                                ];
                                $rGs = [
                                    'linear-gradient(135deg,#F59E0B,#D97706)',
                                    'linear-gradient(135deg,#9CA3AF,#6B7280)',
                                    'linear-gradient(135deg,#B45309,#92400E)',
                                ];
                                $sCol = $sc >= 4.5 ? '#16A34A' : ($sc >= 3.5 ? '#CA8A04' : '#DC2626');
                            @endphp
                            <div class="{{ $rk < 4 ? 'ldiv' : '' }} flex items-center gap-3 py-2.75">
                                <div class="w-5.5 h-5.5 rounded shrink-0 flex items-center justify-center text-[10px] font-bold"
                                    style="{{ $rk < 3 ? 'background:' . $rGs[$rk] . ';color:#fff' : 'background:#F3F4F6;color:#6B7280' }}">
                                    {{ $rk + 1 }}
                                </div>
                                <div class="w-10 h-10 rounded-[10px] shrink-0 flex items-center justify-center text-white text-[13px] font-bold"
                                    style="background:{{ $aGs[$rk] ?? $aGs[0] }}">
                                    {{ $ini }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-[13px] font-semibold text-gray-900 m-0 truncate">{{ $tch->user->name }}
                                    </p>
                                    <p class="text-[11px] text-gray-400 mt-px m-0">{{ $tch->department ?? '—' }}</p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-base font-extrabold m-0 leading-none tabular-nums"
                                        style="color:{{ $sCol }}">
                                        {{ number_format($sc, 1) }}
                                    </p>
                                    <div class="flex justify-end gap-px mt-0.75">
                                        @for ($s = 1; $s <= 5; $s++)
                                            <i class="fas fa-star text-[8px]"
                                                style="color:{{ $s <= round($sc) ? '#F59E0B' : '#E5E7EB' }}"></i>
                                        @endfor
                                    </div>
                                    <p class="text-[10px] text-gray-400 mt-0.5 m-0 text-right">{{ $tch->ratings_count }}
                                        ta</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex-1 flex flex-col items-center justify-center py-8 text-gray-400 text-center">
                        <i class="fas fa-trophy text-[28px] opacity-20 block mb-2"></i>
                        <p class="text-xs m-0">Ma'lumot yo'q</p>
                    </div>
                @endif
            </div>

            {{-- ── Card 2: Topshiriqlar holati (donut) ──────────────────── --}}
            <div class="card p-5.5 flex flex-col">
                <div class="mb-4">
                    <h2 class="text-sm font-bold text-gray-900 m-0">Topshiriqlar holati</h2>
                    <p class="text-[11px] text-gray-400 mt-0.75 m-0">Umumiy bajarilish ko'rsatkichi</p>
                </div>

                {{-- Donut --}}
                <div class="flex justify-center mb-5">
                    <div class="relative w-35 h-35 shrink-0">
                        <canvas id="dn" width="140" height="140"></canvas>
                        <div class="dlabel">
                            <p class="text-2xl font-extrabold text-gray-900 leading-none m-0 tabular-nums">
                                {{ $aPc }}%</p>
                            <p class="text-[10px] text-gray-400 mt-0.75 m-0">Bajarilgan</p>
                        </div>
                    </div>
                </div>

                {{-- Legend --}}
                <div class="flex flex-col gap-3">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-1.75">
                            <span class="w-2.25 h-2.25 rounded-full bg-emerald-500 inline-block shrink-0"></span>
                            <span class="text-xs text-gray-500">Bajarilgan</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-[15px] font-bold text-gray-900 tabular-nums">{{ $aDn }}</span>
                            <span class="tp tp-g" style="font-size:10px;padding:1px 7px">{{ $aPc }}%</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-1.75">
                            <span class="w-2.25 h-2.25 rounded-full bg-red-500 inline-block shrink-0"></span>
                            <span class="text-xs text-gray-500">Kutilmoqda</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-[15px] font-bold text-gray-900 tabular-nums">{{ $aPn }}</span>
                            <span class="tp tp-r" style="font-size:10px;padding:1px 7px">{{ 100 - $aPc }}%</span>
                        </div>
                    </div>
                    <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden mt-1">
                        <div class="h-full rounded-full transition-[width] duration-800 ease-out"
                            style="background:linear-gradient(90deg,#10B981,#34D399);width:{{ $aPc }}%"></div>
                    </div>
                </div>
            </div>

            {{-- ── Card 3: O'qituvchi bo'yicha topshiriqlar ─────────────── --}}
            <div class="card p-5.5 flex flex-col">
                <div class="mb-3.5">
                    <h2 class="text-sm font-bold text-gray-900 m-0">O'qituvchi bo'yicha</h2>
                    <p class="text-[11px] text-gray-400 mt-0.75 m-0">Topshiriq bajarilish holati</p>
                </div>
                @if (count($tkL) > 0)
                    <div class="flex flex-col flex-1">
                        @foreach ($tkF as $tkIdx => $tkT)
                            @php
                                $tkPct =
                                    $tkT->tasks_total > 0
                                        ? (int) round(($tkT->tasks_completed / $tkT->tasks_total) * 100)
                                        : 0;
                                $tkBarCol =
                                    $tkPct >= 100
                                        ? '#10B981'
                                        : ($tkPct >= 60
                                            ? '#34D399'
                                            : ($tkPct >= 30
                                                ? '#F59E0B'
                                                : '#EF4444'));
                            @endphp
                            <div
                                class="flex items-center gap-2.5 py-2.25 {{ $tkIdx < count($tkF) - 1 ? 'border-b border-gray-50' : '' }}">
                                <p class="text-xs font-medium text-gray-700 m-0 flex-1 min-w-0 truncate">
                                    {{ $tkT->user->name }}
                                </p>
                                <div class="w-18 h-1.25 rounded-full bg-slate-100 overflow-hidden shrink-0">
                                    <div class="h-full rounded-full transition-[width] duration-600 ease-out"
                                        style="background:{{ $tkBarCol }};width:{{ $tkPct }}%"></div>
                                </div>
                                <span class="text-[11px] text-gray-500 shrink-0 tabular-nums min-w-7 text-right">
                                    {{ $tkT->tasks_completed }}/{{ $tkT->tasks_total }}
                                </span>
                                <button @click="openModal({{ $tkT->id }})" title="Batafsil"
                                    class="shrink-0 w-7 h-7 rounded-[7px] bg-indigo-50 text-blue-500 border-0 cursor-pointer flex items-center justify-center transition-colors duration-150"
                                    onmouseover="this.style.background='#DBEAFE'"
                                    onmouseout="this.style.background='#EEF2FF'">
                                    <i class="fas fa-chart-simple text-[11px]"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex-1 flex flex-col items-center justify-center py-8 text-gray-400 text-center">
                        <i class="fas fa-clipboard-list text-[28px] opacity-20 block mb-2"></i>
                        <p class="text-xs m-0">Topshiriq ma'lumotlari yo'q</p>
                    </div>
                @endif
            </div>

        </div>

        {{-- ══════════════════════════════════════════════════════
         ROW 4 — Davomat statistikasi (full-width)
         ══════════════════════════════════════════════════ --}}
        <div class="card overflow-hidden mb-5" x-data="attApp()" x-init="init()">

            {{-- Header --}}
            <div class="px-5.5 py-4 border-b border-gray-100 flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-2">
                    <span
                        class="w-7 h-7 rounded-[7px] bg-blue-100 flex items-center justify-center text-blue-600 text-[11px] shrink-0">
                        <i class="fas fa-calendar-check"></i>
                    </span>
                    <div>
                        <h2 class="text-[13px] font-bold text-gray-900 m-0">Davomat statistikasi</h2>
                        <p class="text-[11px] text-gray-400 m-0" x-text="currentLabel"></p>
                    </div>
                </div>
                {{-- Oy filtri --}}
                <div class="flex items-center gap-2 flex-wrap">
                    <select x-model="selectedMonth" @change="setMonth(selectedMonth)"
                        class="bg-white border border-gray-200 rounded-lg px-3 py-1.5 text-[12px] font-semibold text-gray-700 outline-none cursor-pointer focus:border-blue-400 transition-colors">
                        <template x-for="mk in monthKeys" :key="mk">
                            <option :value="mk" x-text="allData[mk].label"></option>
                        </template>
                    </select>
                    <div class="flex flex-wrap gap-2 text-[10px] text-gray-500 border-l border-gray-100 pl-2 ml-1">
                        <span class="flex items-center gap-0.75"><span
                                class="w-2 h-2 rounded-xs bg-emerald-500 inline-block"></span>O'z vaqtida</span>
                        <span class="flex items-center gap-0.75"><span
                                class="w-2 h-2 rounded-xs bg-amber-400 inline-block"></span>Kech</span>
                        <span class="flex items-center gap-0.75"><span
                                class="w-2 h-2 rounded-xs bg-blue-500 inline-block"></span>Uzrli</span>
                        <span class="flex items-center gap-0.75"><span
                                class="w-2 h-2 rounded-xs bg-red-500 inline-block"></span>Kelmagan</span>
                    </div>
                </div>
            </div>

            {{-- 4 summary stat boxes (Alpine reactive) --}}
            <div style="display:grid;grid-template-columns:repeat(4,1fr);border-bottom:1px solid #F3F4F6">
                <div class="px-5 py-3.5 border-r border-gray-100">
                    <p class="text-[11px] text-gray-500 m-0 mb-1.25">O'z vaqtida</p>
                    <p class="text-2xl font-extrabold text-gray-900 m-0 mb-1.25 leading-none tabular-nums"
                        x-text="summary.on_time"></p>
                    <span class="text-[11px] font-semibold px-2 py-0.5 rounded-[20px]"
                        style="background:#DCFCE7;color:#16A34A"
                        x-text="(summary.total > 0 ? Math.round(summary.on_time/summary.total*100) : 0)+'%'"></span>
                </div>
                <div class="px-5 py-3.5 border-r border-gray-100">
                    <p class="text-[11px] text-gray-500 m-0 mb-1.25">Kech keldi</p>
                    <p class="text-2xl font-extrabold text-gray-900 m-0 mb-1.25 leading-none tabular-nums"
                        x-text="summary.late"></p>
                    <span class="text-[11px] font-semibold px-2 py-0.5 rounded-[20px]"
                        style="background:#FEF9C3;color:#CA8A04"
                        x-text="(summary.total > 0 ? Math.round(summary.late/summary.total*100) : 0)+'%'"></span>
                </div>
                <div class="px-5 py-3.5 border-r border-gray-100">
                    <p class="text-[11px] text-gray-500 m-0 mb-1.25">Uzrli sabab</p>
                    <p class="text-2xl font-extrabold text-gray-900 m-0 mb-1.25 leading-none tabular-nums"
                        x-text="summary.excused"></p>
                    <span class="text-[11px] font-semibold px-2 py-0.5 rounded-[20px]"
                        style="background:#DBEAFE;color:#2563EB"
                        x-text="(summary.total > 0 ? Math.round(summary.excused/summary.total*100) : 0)+'%'"></span>
                </div>
                <div class="px-5 py-3.5">
                    <p class="text-[11px] text-gray-500 m-0 mb-1.25">Kelmagan</p>
                    <p class="text-2xl font-extrabold text-gray-900 m-0 mb-1.25 leading-none tabular-nums"
                        x-text="summary.absent"></p>
                    <span class="text-[11px] font-semibold px-2 py-0.5 rounded-[20px]"
                        style="background:#FEE2E2;color:#DC2626"
                        x-text="(summary.total > 0 ? Math.round(summary.absent/summary.total*100) : 0)+'%'"></span>
                </div>
            </div>

            {{-- Per-teacher attendance bars --}}
            <div x-show="hasData" class="px-5.5 py-2">
                <template x-for="(name, i) in (currentData?.labels ?? [])" :key="i">
                    <div class="flex items-center gap-3 py-2.5 border-b border-gray-50 last:border-0">
                        <p class="text-[12px] font-medium text-gray-700 m-0 shrink-0 truncate" style="width:150px"
                            x-text="name"></p>
                        <div class="flex-1 rounded-lg overflow-hidden flex"
                            style="height:22px;background:#F1F5F9;min-width:80px">
                            <template x-if="currentData.don[i] > 0">
                                <div class="h-full flex items-center justify-center text-white text-[10px] font-bold leading-none"
                                    :style="`flex:${currentData.don[i]};background:rgba(16,185,129,.9)`"
                                    x-text="currentData.don[i]"></div>
                            </template>
                            <template x-if="currentData.lan[i] > 0">
                                <div class="h-full flex items-center justify-center text-white text-[10px] font-bold leading-none"
                                    :style="`flex:${currentData.lan[i]};background:rgba(245,158,11,.9)`"
                                    x-text="currentData.lan[i]"></div>
                            </template>
                            <template x-if="currentData.exn[i] > 0">
                                <div class="h-full flex items-center justify-center text-white text-[10px] font-bold leading-none"
                                    :style="`flex:${currentData.exn[i]};background:rgba(59,130,246,.9)`"
                                    x-text="currentData.exn[i]"></div>
                            </template>
                            <template x-if="currentData.abn[i] > 0">
                                <div class="h-full flex items-center justify-center text-white text-[10px] font-bold leading-none"
                                    :style="`flex:${currentData.abn[i]};background:rgba(239,68,68,.85)`"
                                    x-text="currentData.abn[i]"></div>
                            </template>
                        </div>
                        <span class="text-[11px] text-gray-400 shrink-0 tabular-nums min-w-12 text-right"
                            x-text="currentData.tot[i]+' kun'"></span>
                    </div>
                </template>
            </div>
            <div x-show="!hasData" class="p-8 text-center text-gray-400">
                <i class="fas fa-calendar-xmark text-2xl opacity-25 block mb-2"></i>
                <p class="text-xs m-0" x-text="''+currentLabel+' uchun davomat ma\'lumotlari yo\'q'"></p>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════
         ROW 5 — O'qituvchilar jadvali (full-width)
         ══════════════════════════════════════════════════ --}}
        <div class="card overflow-hidden">

            {{-- Header --}}
            <div class="px-5.5 py-3.5 border-b border-gray-50 flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2 flex-1 min-w-40">
                    <span
                        class="w-7 h-7 rounded-[7px] bg-indigo-50 flex items-center justify-center text-[11px] text-blue-500">
                        <i class="fas fa-table-list"></i>
                    </span>
                    <h2 class="text-[13px] font-bold text-gray-900 m-0">Barcha o'qituvchilar</h2>
                </div>
                <div class="flex flex-wrap gap-2">
                    <select x-model="fd"
                        class="bg-slate-50 border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs text-gray-700 outline-none cursor-pointer">
                        <option value="">Barcha yo'nalishlar</option>
                        @foreach ($directionStats as $dir)
                            <option value="{{ $dir->name }}">{{ $dir->name }}</option>
                        @endforeach
                    </select>
                    <div class="flex items-center gap-1.5 bg-slate-50 border border-gray-200 rounded-lg px-2.5 py-1.5">
                        <i class="fas fa-search text-[10px] text-gray-400"></i>
                        <input type="search" x-model="fs" placeholder="Qidirish..."
                            class="bg-transparent border-0 outline-none text-xs text-gray-700 w-32.5">
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full" style="border-collapse:collapse">
                    <thead>
                        <tr class="bg-neutral-50 border-b border-gray-100">
                            <th
                                class="px-4 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide w-10">
                                #</th>
                            <th
                                class="px-4 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide">
                                O'qituvchi</th>
                            <th
                                class="px-4 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide hide-md">
                                Yo'nalishlar</th>
                            <th
                                class="px-4 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide">
                                Ball</th>
                            <th
                                class="px-4 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide hide-sm">
                                Baholashlar</th>
                            <th
                                class="px-4 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide">
                                Topshiriqlar</th>
                            <th class="px-4 py-2.5 w-22.5"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(r,i) in filtered" :key="r.id">
                            <tr class="trow">
                                <td class="px-4 py-2.75 text-center">
                                    <span class="text-[11px] font-semibold text-gray-400" x-text="i+1"></span>
                                </td>
                                <td class="px-4 py-2.75">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8.5 h-8.5 rounded-[9px] shrink-0 flex items-center justify-center text-white text-[11px] font-bold"
                                            style="background:linear-gradient(135deg,#1E3A5F,#3B82F6)"
                                            x-text="r.name.split(' ').map(w=>w[0]).join('').slice(0,2).toUpperCase()">
                                        </div>
                                        <div>
                                            <p class="text-[13px] font-semibold text-gray-900 m-0" x-text="r.name"></p>
                                            <p class="text-[11px] text-gray-400 m-0" x-text="r.dept"></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-2.75 hide-md">
                                    <span class="text-xs text-gray-500" x-text="r.dirs"></span>
                                </td>
                                <td class="px-4 py-2.75 text-center">
                                    <span class="sb"
                                        :class="r.score >= 4.5 ? 'sb-g' : r.score >= 3.5 ? 'sb-a' : r.score > 0 ? 'sb-r' :
                                            'sb-n'"
                                        x-text="r.score>0?r.score.toFixed(1)+' ★':'—'"></span>
                                </td>
                                <td class="px-4 py-2.75 text-center hide-sm">
                                    <span class="text-xs text-gray-500" x-text="r.cnt+' ta'"></span>
                                </td>
                                <td class="px-4 py-2.75">
                                    <template x-if="r.tTot>0">
                                        <div class="flex items-center gap-2 min-w-22.5">
                                            <div class="tb flex-1">
                                                <div class="tf"
                                                    :style="`width:${100-r.tPct}%;background:${r.tPct===0?'#10B981':r.tPct<=30?'#34D399':r.tPct<=60?'#F59E0B':'#EF4444'}`">
                                                </div>
                                            </div>
                                            <span class="text-[11px] font-semibold whitespace-nowrap"
                                                :style="`color:${r.tPct===0?'#16A34A':r.tPct<=30?'#16A34A':r.tPct<=60?'#CA8A04':'#DC2626'}`"
                                                x-text="`${r.tDn}/${r.tTot}`"></span>
                                        </div>
                                    </template>
                                    <template x-if="r.tTot===0">
                                        <span class="text-xs text-gray-400">—</span>
                                    </template>
                                </td>
                                <td class="px-4 py-2.75">
                                    <button @click="openModal(r.id)" title="Batafsil"
                                        class="w-7.5 h-7.5 rounded-lg bg-indigo-50 text-blue-500 border-0 cursor-pointer flex items-center justify-center transition-colors duration-150"
                                        onmouseover="this.style.background='#DBEAFE'"
                                        onmouseout="this.style.background='#EEF2FF'">
                                        <i class="fas fa-chart-simple text-xs"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filtered.length===0">
                            <td colspan="6" class="p-9 text-center text-gray-400">
                                <i class="fas fa-circle-info text-[22px] opacity-30 block mb-1.5"></i>
                                Ma'lumot topilmadi
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Footer --}}
            <div class="px-5.5 py-2.5 border-t border-gray-50 flex flex-wrap items-center justify-between gap-2">
                <p class="text-[11px] text-gray-400 m-0">
                    <span class="font-semibold text-gray-900" x-text="filtered.length"></span> ta o'qituvchi
                    <span x-show="fs||fd" class="text-blue-500"> (filtrlangan)</span>
                </p>
                <div class="flex items-center gap-2.5 text-[10px] text-gray-500">
                    <span class="flex items-center gap-0.75">
                        <span class="w-1.75 h-1.75 rounded-full bg-emerald-500 inline-block"></span>A'lo ≥ 4.5
                    </span>
                    <span class="flex items-center gap-0.75">
                        <span class="w-1.75 h-1.75 rounded-full bg-amber-400 inline-block"></span>Yaxshi ≥ 3.5
                    </span>
                    <span class="flex items-center gap-0.75">
                        <span class="w-1.75 h-1.75 rounded-full bg-red-400 inline-block"></span>Qoniqarli
                    </span>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════
         MODAL — Batafsil ma'lumot
         ══════════════════════════════════════════════════ --}}
        <div x-show="modal" x-cloak @keydown.escape.window="modal=false" class="fixed inset-0 z-300 backdrop-blur-sm"
            style="background:rgba(15,23,42,.5)">
            <div class="flex items-center justify-center w-full h-full p-5" @click.self="modal=false">

                <div class="bg-white rounded-2xl w-full max-w-xl max-h-[88vh] overflow-hidden flex flex-col"
                    style="box-shadow:0 24px 60px rgba(15,23,42,.2)">

                    {{-- Modal Header --}}
                    <div class="px-5.5 pt-5 shrink-0">
                        <div class="flex items-start justify-between gap-3 mb-3.5">
                            <div>
                                <h3 class="text-[15px] font-bold text-gray-900 m-0" x-text="modalTeacher"></h3>
                                <p class="text-xs text-gray-400 mt-0.75 m-0">Batafsil ma'lumot</p>
                            </div>
                            <button @click="modal=false"
                                class="shrink-0 w-7.5 h-7.5 rounded-lg bg-gray-100 border-0 cursor-pointer text-[15px] text-gray-500 flex items-center justify-center leading-none"
                                onmouseover="this.style.background='#E5E7EB'"
                                onmouseout="this.style.background='#F3F4F6'">✕</button>
                        </div>

                        {{-- Tabs --}}
                        <div class="flex gap-0.5 border-b-2 border-gray-100">
                            <button @click="modalTab='tasks'"
                                :style="`padding:8px 16px;font-size:12px;font-weight:600;border:none;cursor:pointer;background:transparent;border-bottom:2px solid transparent;margin-bottom:-2px;transition:all .15s;${modalTab==='tasks'?'color:#2563EB;border-bottom-color:#2563EB;':'color:#9CA3AF;'}`">
                                <span class="mr-1.25">📋</span>
                                Topshiriqlar
                                <span
                                    :style="`margin-left:5px;padding:1px 7px;border-radius:20px;font-size:10px;${modalTab==='tasks'?'background:#DBEAFE;color:#2563EB':'background:#F3F4F6;color:#9CA3AF'}`"
                                    x-text="modalTasks.length"></span>
                            </button>
                            <button @click="modalTab='dirs'"
                                :style="`padding:8px 16px;font-size:12px;font-weight:600;border:none;cursor:pointer;background:transparent;border-bottom:2px solid transparent;margin-bottom:-2px;transition:all .15s;${modalTab==='dirs'?'color:#2563EB;border-bottom-color:#2563EB;':'color:#9CA3AF;'}`">
                                <span class="mr-1.25">🎯</span>
                                Yo'nalishlar bo'yicha ball
                                <span
                                    :style="`margin-left:5px;padding:1px 7px;border-radius:20px;font-size:10px;${modalTab==='dirs'?'background:#DBEAFE;color:#2563EB':'background:#F3F4F6;color:#9CA3AF'}`"
                                    x-text="modalDirs.length"></span>
                            </button>
                        </div>
                    </div>

                    {{-- ── TAB: Topshiriqlar ──────────────────────────────── --}}
                    <div x-show="modalTab==='tasks'" class="overflow-y-auto flex-1">

                        {{-- Task summary bar --}}
                        <div class="px-5.5 py-3 bg-neutral-50 border-b border-gray-100">
                            <div class="flex items-center justify-between mb-1.75">
                                <span class="text-[11px] text-gray-500">
                                    Bajarilgan: <strong class="text-emerald-500"
                                        x-text="modalTasks.filter(t=>t.status==='completed').length"></strong>
                                    / <span x-text="modalTasks.length"></span> ta
                                </span>
                                <span class="text-[11px] font-bold text-gray-900"
                                    x-text="modalTasks.length?Math.round(modalTasks.filter(t=>t.status==='completed').length/modalTasks.length*100)+'%':'0%'"></span>
                            </div>
                            <div class="h-1.25 rounded-full bg-red-100 overflow-hidden">
                                <div class="h-full rounded-full transition-[width] duration-700 ease-out"
                                    style="background:linear-gradient(90deg,#10B981,#34D399)"
                                    :style="`width:${modalTasks.length?Math.round(modalTasks.filter(t=>t.status==='completed').length/modalTasks.length*100):0}%`">
                                </div>
                            </div>
                        </div>

                        <template x-for="(task,i) in modalTasks" :key="i">
                            <div
                                :style="`display:flex;align-items:flex-start;gap:12px;padding:13px 22px;${i<modalTasks.length-1?'border-bottom:1px solid #F9FAFB':''}`">
                                <div
                                    :style="`width:32px;height:32px;border-radius:9px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:15px;background:${task.status==='completed'?'#DCFCE7':'#FEE2E2'}`">
                                    <span x-text="task.status==='completed'?'✅':'⏳'"></span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-2 flex-wrap">
                                        <p class="text-[13px] font-semibold text-gray-900 m-0 flex-1" x-text="task.title">
                                        </p>
                                        <span
                                            :style="`font-size:11px;font-weight:600;padding:2px 9px;border-radius:20px;flex-shrink:0;${task.status==='completed'?'background:#DCFCE7;color:#16A34A':'background:#FEE2E2;color:#DC2626'}`"
                                            x-text="task.status==='completed'?'Bajarilgan':'Kutilmoqda'"></span>
                                    </div>
                                    <div class="flex items-center gap-2.5 mt-1.25 flex-wrap">
                                        <span x-show="task.done_at"
                                            class="text-[11px] text-emerald-500 flex items-center gap-0.75">
                                            <span>✓</span><span x-text="task.done_at"></span>
                                        </span>
                                        <span x-show="task.due && task.status!=='completed'"
                                            class="text-[11px] text-amber-500 flex items-center gap-0.75">
                                            <span>📅</span><span x-text="task.due"></span>
                                        </span>
                                        <span x-show="task.priority && task.priority!=='medium'"
                                            :style="`font-size:10px;font-weight:600;padding:1px 7px;border-radius:20px;${task.priority==='high'?'background:#FEE2E2;color:#DC2626':task.priority==='urgent'?'background:#FEF3C7;color:#D97706':'background:#F3F4F6;color:#6B7280'}`"
                                            x-text="task.priority==='high'?'🔴 Yuqori':task.priority==='urgent'?'🔥 Shoshilinch':'Oddiy'"></span>
                                    </div>
                                    <p x-show="task.note"
                                        class="text-[11px] text-gray-400 mt-1.25 m-0 italic leading-snug"
                                        x-text="'💬 '+task.note"></p>
                                </div>
                            </div>
                        </template>
                        <div x-show="modalTasks.length===0" class="p-12 text-center text-gray-400">
                            <div class="text-[28px] mb-2 opacity-30">📋</div>
                            <p class="text-[13px] m-0">Topshiriqlar tayinlanmagan</p>
                        </div>
                    </div>

                    {{-- ── TAB: Yo'nalishlar bo'yicha ball ─────────────────── --}}
                    <div x-show="modalTab==='dirs'" class="overflow-y-auto flex-1">

                        {{-- Dir summary --}}
                        <div class="px-5.5 py-3 bg-neutral-50 border-b border-gray-100">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] text-gray-500">
                                    Baholangan yo'nalishlar: <strong class="text-gray-700"
                                        x-text="modalDirs.length"></strong> ta
                                </span>
                                <span class="text-[11px] font-bold text-gray-900"
                                    x-text="modalDirs.length?(modalDirs.reduce((s,d)=>s+d.score,0)/modalDirs.length).toFixed(2)+' o\'rt.':'—'"></span>
                            </div>
                        </div>

                        <template x-for="(dir,i) in modalDirs" :key="i">
                            <div
                                :style="`display:flex;align-items:center;gap:14px;padding:14px 22px;${i<modalDirs.length-1?'border-bottom:1px solid #F9FAFB':''}`">
                                {{-- Rank --}}
                                <div class="w-6.5 h-6.5 rounded-[7px] shrink-0 bg-indigo-50 flex items-center justify-center text-[11px] font-bold text-blue-500"
                                    x-text="i+1"></div>
                                {{-- Direction name --}}
                                <div class="flex-1 min-w-0">
                                    <p class="text-[13px] font-semibold text-gray-900 m-0 truncate" x-text="dir.name"></p>
                                    <p class="text-[11px] text-gray-400 mt-0.5 m-0" x-text="dir.count+' ta baholash'"></p>
                                </div>
                                {{-- Score + bar --}}
                                <div class="shrink-0 text-right min-w-25">
                                    <div class="flex items-center gap-2 justify-end">
                                        <div class="w-17.5 h-1.25 rounded-full bg-slate-100 overflow-hidden">
                                            <div
                                                :style="`height:100%;border-radius:99px;width:${dir.score/5*100}%;background:${dir.score>=4.5?'#10B981':dir.score>=3.5?'#F59E0B':'#EF4444'};transition:width .6s ease`">
                                            </div>
                                        </div>
                                        <span
                                            :style="`font-size:14px;font-weight:800;font-variant-numeric:tabular-nums;color:${dir.score>=4.5?'#16A34A':dir.score>=3.5?'#CA8A04':'#DC2626'}`"
                                            x-text="dir.score.toFixed(2)"></span>
                                    </div>
                                    <div class="flex justify-end gap-px mt-1">
                                        <template x-for="s in 5" :key="s">
                                            <span
                                                :style="`font-size:9px;color:${s<=Math.round(dir.score)?'#F59E0B':'#E5E7EB'}`">★</span>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div x-show="modalDirs.length===0" class="p-12 text-center text-gray-400">
                            <div class="text-[28px] mb-2 opacity-30">🎯</div>
                            <p class="text-[13px] m-0">Baholash ma'lumotlari yo'q</p>
                        </div>
                    </div>

                </div>
            </div>{{-- /flex centering wrapper --}}
        </div>{{-- /teacher modal overlay --}}

        {{-- ══════════════════════════════════════════════════════
         DIRECTION MODAL — Yo'nalish bo'yicha o'qituvchilar
         ══════════════════════════════════════════════════ --}}
        <div x-show="dirModal" x-cloak @keydown.escape.window="dirModal=false"
            class="fixed inset-0 z-400 backdrop-blur-sm" style="background:rgba(15,23,42,.5)">
            <div class="flex items-center justify-center w-full h-full p-5" @click.self="dirModal=false">

                <div class="bg-white rounded-2xl w-full max-w-lg max-h-[85vh] overflow-hidden flex flex-col"
                    style="box-shadow:0 24px 60px rgba(15,23,42,.2)">

                    {{-- Header --}}
                    <div class="px-5.5 py-4.5 border-b border-gray-100 flex items-start justify-between gap-3 shrink-0">
                        <div>
                            <div class="flex items-center gap-2 mb-0.5">
                                <span
                                    class="w-6 h-6 rounded-lg bg-indigo-50 flex items-center justify-center text-blue-500 text-[11px] shrink-0">
                                    <i class="fas fa-sitemap"></i>
                                </span>
                                <h3 class="text-[15px] font-bold text-gray-900 m-0" x-text="dirModalName"></h3>
                            </div>
                            <p class="text-[11px] text-gray-400 m-0 ml-8">
                                <span x-text="dirModalTeachers.length"></span> ta o'qituvchi baholangan
                            </p>
                        </div>
                        <button @click="dirModal=false"
                            class="shrink-0 w-7.5 h-7.5 rounded-lg bg-gray-100 border-0 cursor-pointer text-[15px] text-gray-500 flex items-center justify-center leading-none"
                            onmouseover="this.style.background='#E5E7EB'"
                            onmouseout="this.style.background='#F3F4F6'">✕</button>
                    </div>

                    {{-- Summary bar --}}
                    <div class="px-5.5 py-3 bg-neutral-50 border-b border-gray-100 shrink-0">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-[11px] text-gray-500">Yo'nalish o'rtacha balli</span>
                            <span class="text-[13px] font-extrabold tabular-nums"
                                :style="`color:${dirModalScore>=4.5?'#16A34A':dirModalScore>=3.5?'#CA8A04':'#DC2626'}`"
                                x-text="dirModalScore.toFixed(2)+' / 5.00'"></span>
                        </div>
                        <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full transition-[width] duration-700 ease-out"
                                :style="`width:${dirModalScore/5*100}%;background:${dirModalScore>=4.5?'#10B981':dirModalScore>=3.5?'#F59E0B':'#EF4444'}`">
                            </div>
                        </div>
                    </div>

                    {{-- Teachers list --}}
                    <div class="overflow-y-auto flex-1">
                        <template x-for="(t, i) in dirModalTeachers" :key="i">
                            <div
                                :style="`display:flex;align-items:center;gap:14px;padding:14px 22px;${i<dirModalTeachers.length-1?'border-bottom:1px solid #F9FAFB':''}`">
                                {{-- Avatar --}}
                                <div class="w-9 h-9 rounded-[10px] shrink-0 flex items-center justify-center text-white text-[12px] font-bold"
                                    style="background:linear-gradient(135deg,#1E3A5F,#3B82F6)"
                                    x-text="t.name.split(' ').map(w=>w[0]).join('').slice(0,2).toUpperCase()">
                                </div>
                                {{-- Name --}}
                                <div class="flex-1 min-w-0">
                                    <p class="text-[13px] font-semibold text-gray-900 m-0 truncate" x-text="t.name"></p>
                                    <p class="text-[11px] text-gray-400 m-0" x-text="t.cnt+' ta baholash'"></p>
                                </div>
                                {{-- Score + bar --}}
                                <div class="shrink-0 text-right min-w-28">
                                    <div class="flex items-center gap-2 justify-end mb-1">
                                        <div class="w-16 h-1.25 rounded-full bg-slate-100 overflow-hidden">
                                            <div
                                                :style="`height:100%;border-radius:99px;width:${t.score/5*100}%;background:${t.score>=4.5?'#10B981':t.score>=3.5?'#F59E0B':'#EF4444'};transition:width .6s ease`">
                                            </div>
                                        </div>
                                        <span class="text-[14px] font-extrabold tabular-nums"
                                            :style="`color:${t.score>=4.5?'#16A34A':t.score>=3.5?'#CA8A04':'#DC2626'}`"
                                            x-text="t.score.toFixed(2)"></span>
                                    </div>
                                    <div class="flex justify-end gap-px">
                                        <template x-for="s in 5" :key="s">
                                            <span
                                                :style="`font-size:9px;color:${s<=Math.round(t.score)?'#F59E0B':'#E5E7EB'}`">★</span>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div x-show="dirModalTeachers.length===0" class="p-12 text-center text-gray-400">
                            <i class="fas fa-users text-[28px] opacity-20 block mb-2"></i>
                            <p class="text-[13px] m-0">Bu yo'nalishda hali baholash yo'q</p>
                        </div>
                    </div>

                </div>
            </div>
        </div>{{-- /direction modal --}}

    </div>
@endsection

@push('scripts')
    <script>
        window.STATS_DATA = {
            TD: {
                labels: @json($tL),
                scores: @json($tS),
                counts: @json($tC)
            },
            DD: {
                labels: @json($dL),
                scores: @json($dS),
                counts: @json($dC)
            },
            TK: {
                labels: @json($tkL),
                comp: @json($tkCo),
                pend: @json($tkPe),
                tots: @json($tkTo),
                done: @json($tkDn)
            },
            ROWS: @json($rows),
            TEACHER_FULL: @json($teacherFullData),
            DIR_FULL: @json($dirTeacherData),
            ATTEND: {
                labels: @json($aL),
                ot: @json($aOT),
                la: @json($aLA),
                ex: @json($aEX),
                ab: @json($aAB),
                tot: @json($aTO),
                don: @json($aDN),
                lan: @json($aLN),
                exn: @json($aEN),
                abn: @json($aAN),
            },
            DONE_N: {{ $aDn }},
            PEND_N: {{ $aPn }},
            ATTEND_ALL: @json($attendanceAllMonths),
            DEFAULT_MONTH: @json($defaultMonthKey),
        };
    </script>
@endpush
