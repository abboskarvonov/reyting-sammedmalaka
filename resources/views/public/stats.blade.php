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

    /* Avatar gradients */
    $aGs = [
        'linear-gradient(135deg,#004ac6,#2563eb)',
        'linear-gradient(135deg,#0EA5E9,#06B6D4)',
        'linear-gradient(135deg,#006c49,#2d9e6b)',
        'linear-gradient(135deg,#8B5CF6,#A78BFA)',
        'linear-gradient(135deg,#784b00,#a86800)',
    ];
@endphp

@section('content')
    <div x-data="sApp()" x-init="$nextTick(initCharts)" x-cloak>

        {{-- ══ PAGE HEADER ══ --}}
        <section class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
            <div>
                <h2 class="text-[36px] font-bold leading-tight m-0" style="font-family:'Hanken Grotesk',sans-serif;color:#0b1c30">Ochiq statistika</h2>
                <p class="text-sm mt-1 m-0" style="color:#434655">Akademik ko'rsatkichlar va xodimlar faoliyati tahlili</p>
            </div>
            <div class="flex items-center gap-2 px-4 py-2 rounded-full border" style="background:#fff;border-color:rgba(195,198,215,.3);box-shadow:0 1px 3px rgba(0,0,0,.05)">
                <span class="w-2 h-2 rounded-full animate-pulse inline-block" style="background:#006c49"></span>
                <span class="text-xs" style="color:#434655">Jonli ma'lumot</span>
            </div>
        </section>

        {{-- ══ KPI CARDS (4) ══ --}}
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">

            <x-public.kpi-card
                icon="badge"
                label="Jami xodimlar"
                :value="$totalTeachers"
                trend-label="Faol"
                :bar-pct="75"
                border-color="#004ac6"
            />

            <x-public.kpi-card
                icon="star"
                label="O'rtacha ball"
                :value="$avgR ?: '—'"
                trend-label="Ball"
                icon-bg="rgba(108,248,187,.25)"
                icon-color="#006c49"
                bar-color="#006c49"
                :bar-pct="$avgR > 0 ? min(100, round($avgR / 5 * 100)) : 0"
                border-color="#006c49"
            />

            <x-public.kpi-card
                icon="task_alt"
                label="Topshiriqlar bajarilishi"
                :value="$taskCompletionRate"
                suffix="%"
                :trend-label="$taskCompletionRate >= 60 ? 'Yaxshi' : 'Past'"
                :trend-up="$taskCompletionRate >= 60"
                icon-bg="rgba(255,221,184,.4)"
                icon-color="#784b00"
                bar-color="#784b00"
                :bar-pct="$taskCompletionRate"
                border-color="#784b00"
            />

            <x-public.kpi-card
                icon="calendar_month"
                label="O'z vaqtida davomat"
                :value="$onTimePct"
                suffix="%"
                :trend-label="$onTimePct . '%'"
                :bar-pct="$onTimePct"
                border-color="#ba1a1a"
            />

        </section>

        {{-- ══ 3-COLUMN: TOP-5 + YO'NALISHLAR + TOPSHIRIQLAR ══ --}}
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">

            {{-- Top-5 Teacher Ranking --}}
            <div class="stats-card p-6">
                <div class="flex justify-between items-center mb-6">
                    <h4 class="text-lg font-semibold m-0" style="font-family:'Hanken Grotesk',sans-serif;color:#0b1c30">
                        Top 5 o'qituvchi
                    </h4>
                    <a href="{{ route('public.ratings') }}"
                       class="text-xs font-semibold flex items-center gap-1 no-underline"
                       style="color:#004ac6">
                        Barchasi
                        <span class="material-symbols-outlined" style="font-size:14px">arrow_forward</span>
                    </a>
                </div>

                @if ($topTeachers->count() > 0)
                    <div class="space-y-5">
                        @foreach ($topTeachers->take(5) as $rk => $tch)
                            @php
                                $sc = round((float) $tch->ratings_avg_total_score, 1);
                                $barPct = min(100, round($sc / 5 * 100));
                                $ini = collect(explode(' ', $tch->user->name))
                                    ->map(fn($w) => strtoupper(substr($w, 0, 1)))
                                    ->take(2)
                                    ->implode('');
                            @endphp
                            <div class="space-y-2">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0"
                                             style="background:{{ $aGs[$rk] ?? $aGs[0] }}">
                                            {{ $ini }}
                                        </div>
                                        <span class="text-sm font-semibold" style="color:#0b1c30">{{ $tch->user->name }}</span>
                                    </div>
                                    <span class="text-xs font-bold" style="color:#004ac6">{{ number_format($sc, 1) }}</span>
                                </div>
                                <div class="h-2.5 w-full rounded-full overflow-hidden" style="background:#eff4ff">
                                    <div class="h-full rounded-full chart-bar-animate" style="background:#004ac6;width:{{ $barPct }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-12" style="color:#434655">
                        <span class="material-symbols-outlined mb-3" style="font-size:44px;opacity:.2">trophy</span>
                        <p class="text-sm m-0">Hali baholash ma'lumotlari yo'q</p>
                    </div>
                @endif
            </div>

            {{-- Direction Stats --}}
            <div class="stats-card p-6">
                <h4 class="text-lg font-semibold m-0 mb-6" style="font-family:'Hanken Grotesk',sans-serif;color:#0b1c30">
                    Yo'nalishlar statistikasi
                </h4>

                @if ($directionStats->count() > 0)
                    <div class="space-y-5">
                        @foreach ($directionStats->take(6) as $dir)
                            @php
                                $sc = round((float) $dir->ratings_avg_total_score, 2);
                                $pct = min(100, round($sc / 5 * 100));
                                $isGood = $sc >= 3.5;
                            @endphp
                            <div class="flex flex-col gap-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-semibold" style="color:#0b1c30">{{ $dir->name }}</span>
                                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full"
                                          style="{{ $isGood ? 'background:rgba(108,248,187,.25);color:#006c49' : 'background:rgba(186,26,26,.1);color:#ba1a1a' }}">
                                        {{ $isGood ? 'Faol' : 'Past' }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="flex-1 h-2 rounded-full overflow-hidden" style="background:#eff4ff">
                                        <div class="h-full rounded-full chart-bar-animate"
                                             style="background:{{ $isGood ? '#006c49' : 'rgba(186,26,26,.7)' }};width:{{ $pct }}%"></div>
                                    </div>
                                    <span class="text-xs font-bold min-w-8 text-right" style="color:#434655">{{ $pct }}%</span>
                                    <button @click="openDirModal({{ $dir->id }})"
                                            title="O'qituvchilar ko'rish"
                                            class="shrink-0 w-7 h-7 rounded-lg border-0 cursor-pointer flex items-center justify-center transition-colors"
                                            style="background:#eff4ff;color:#004ac6"
                                            onmouseover="this.style.background='#e5eeff'"
                                            onmouseout="this.style.background='#eff4ff'">
                                        <span class="material-symbols-outlined" style="font-size:16px">groups</span>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center py-12" style="color:#434655">
                        <span class="material-symbols-outlined mb-3" style="font-size:44px;opacity:.2">account_tree</span>
                        <p class="text-sm m-0">Yo'nalish ma'lumotlari yo'q</p>
                    </div>
                @endif

                <div class="mt-6 p-4 rounded-xl border" style="background:rgba(0,74,198,.04);border-color:rgba(0,74,198,.1)">
                    <div class="flex gap-3">
                        <span class="material-symbols-outlined shrink-0" style="font-size:20px;color:#004ac6">lightbulb</span>
                        <p class="text-xs leading-relaxed m-0" style="color:#434655">
                            <span class="font-bold" style="color:#0b1c30">Tavsiya:</span>
                            Past ko'rsatkichli yo'nalishlar uchun qo'shimcha monitoring tavsiya etiladi.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Task Status: Donut + Per-teacher --}}
            <div class="stats-card p-6 flex flex-col">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0" style="background:rgba(108,248,187,.2)">
                        <span class="material-symbols-outlined" style="font-size:18px;color:#006c49">task_alt</span>
                    </div>
                    <div>
                        <h4 class="text-[15px] font-semibold m-0" style="font-family:'Hanken Grotesk',sans-serif;color:#0b1c30">Topshiriqlar holati</h4>
                        <p class="text-[11px] mt-0.5 m-0" style="color:#434655">Umumiy bajarilish ko'rsatkichi</p>
                    </div>
                </div>

                {{-- Donut --}}
                <div class="flex justify-center mb-5">
                    <div class="relative shrink-0" style="width:140px;height:140px">
                        <canvas id="dn" width="140" height="140"></canvas>
                        <div class="dlabel">
                            <p class="text-2xl font-bold leading-none m-0" style="color:#0b1c30">{{ $aPc }}%</p>
                            <p class="text-[10px] mt-1 m-0" style="color:#434655">Bajarilgan</p>
                        </div>
                    </div>
                </div>

                {{-- Legend --}}
                <div class="flex flex-col gap-3 mb-5">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full inline-block shrink-0" style="background:#006c49"></span>
                            <span class="text-xs" style="color:#434655">Bajarilgan</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-base font-bold" style="color:#0b1c30">{{ $aDn }}</span>
                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full" style="background:#dcfce7;color:#006c49">{{ $aPc }}%</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full inline-block shrink-0" style="background:#ba1a1a"></span>
                            <span class="text-xs" style="color:#434655">Kutilmoqda</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-base font-bold" style="color:#0b1c30">{{ $aPn }}</span>
                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full" style="background:#fee2e2;color:#ba1a1a">{{ 100 - $aPc }}%</span>
                        </div>
                    </div>
                    <div class="h-1.5 rounded-full overflow-hidden mt-1" style="background:#eff4ff">
                        <div class="h-full rounded-full" style="background:#006c49;width:{{ $aPc }}%"></div>
                    </div>
                </div>

                {{-- Per-teacher tasks --}}
                @if (count($tkL) > 0)
                    <div class="flex flex-col flex-1 pt-4" style="border-top:1px solid rgba(195,198,215,.2)">
                        <h5 class="text-[10px] font-bold uppercase tracking-wide mb-3 m-0" style="color:#434655">O'qituvchi bo'yicha</h5>
                        @foreach ($tkF as $tkIdx => $tkT)
                            @php
                                $tkPct = $tkT->tasks_total > 0 ? (int) round(($tkT->tasks_completed / $tkT->tasks_total) * 100) : 0;
                                $tkBarCol = $tkPct >= 60 ? '#006c49' : ($tkPct >= 30 ? '#784b00' : '#ba1a1a');
                            @endphp
                            <div class="flex items-center gap-2.5 py-2 {{ $tkIdx < count($tkF) - 1 ? 'ldiv' : '' }}">
                                <p class="text-xs font-medium m-0 flex-1 min-w-0 truncate" style="color:#0b1c30">{{ $tkT->user->name }}</p>
                                <div class="w-16 h-1.5 rounded-full overflow-hidden shrink-0" style="background:#eff4ff">
                                    <div class="h-full rounded-full" style="background:{{ $tkBarCol }};width:{{ $tkPct }}%"></div>
                                </div>
                                <span class="text-[11px] shrink-0 min-w-8 text-right tabular-nums" style="color:#434655">
                                    {{ $tkT->tasks_completed }}/{{ $tkT->tasks_total }}
                                </span>
                                <button @click="openModal({{ $tkT->id }})" title="Batafsil"
                                        class="shrink-0 w-7 h-7 rounded-lg border-0 cursor-pointer flex items-center justify-center transition-colors"
                                        style="background:#eff4ff;color:#004ac6"
                                        onmouseover="this.style.background='#e5eeff'"
                                        onmouseout="this.style.background='#eff4ff'">
                                    <span class="material-symbols-outlined" style="font-size:16px">analytics</span>
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex-1 flex flex-col items-center justify-center py-6 pt-4" style="border-top:1px solid rgba(195,198,215,.2);color:#434655">
                        <span class="material-symbols-outlined mb-2" style="font-size:36px;opacity:.2">assignment</span>
                        <p class="text-xs m-0">Topshiriq ma'lumotlari yo'q</p>
                    </div>
                @endif
            </div>

        </section>

        {{-- ══ DAVOMAT ══ --}}
        <section class="mb-6">
            <div class="stats-card overflow-hidden" x-data="attApp()" x-cloak>

                {{-- Header --}}
                <div class="px-6 py-4 flex items-center justify-between flex-wrap gap-3" style="border-bottom:1px solid rgba(195,198,215,.2)">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:rgba(0,74,198,.1)">
                            <span class="material-symbols-outlined" style="font-size:18px;color:#004ac6">calendar_month</span>
                        </div>
                        <div>
                            <h4 class="text-[15px] font-semibold m-0" style="font-family:'Hanken Grotesk',sans-serif;color:#0b1c30">Davomat statistikasi</h4>
                            <p class="text-[11px] m-0" style="color:#434655" x-text="currentLabel"></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <select x-model="selectedMonth" @change="setMonth(selectedMonth)"
                                class="rounded-lg px-3 py-1.5 text-xs font-semibold outline-none cursor-pointer border-none"
                                style="background:#eff4ff;color:#0b1c30"
                                onfocus="this.style.boxShadow='0 0 0 2px rgba(0,74,198,.2)'"
                                onblur="this.style.boxShadow=''">
                            <template x-for="mk in monthKeys" :key="mk">
                                <option :value="mk" x-text="allData[mk].label"></option>
                            </template>
                        </select>
                        <div class="flex flex-wrap gap-3 text-[10px] pl-3" style="border-left:1px solid rgba(195,198,215,.3);color:#434655">
                            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-sm inline-block" style="background:#006c49"></span>O'z vaqtida</span>
                            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-sm inline-block" style="background:#784b00"></span>Kech</span>
                            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-sm inline-block" style="background:#004ac6"></span>Uzrli</span>
                            <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-sm inline-block" style="background:#ba1a1a"></span>Kelmagan</span>
                        </div>
                    </div>
                </div>

                {{-- 4 summary boxes --}}
                <div class="grid grid-cols-2 md:grid-cols-4" style="border-bottom:1px solid rgba(195,198,215,.2)">
                    <div class="px-5 py-4" style="border-right:1px solid rgba(195,198,215,.2)">
                        <p class="text-[11px] m-0 mb-1.5" style="color:#434655">O'z vaqtida</p>
                        <p class="text-2xl font-bold m-0 mb-1.5 leading-none tabular-nums" style="color:#0b1c30" x-text="summary.on_time"></p>
                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full" style="background:#dcfce7;color:#006c49"
                              x-text="(summary.total > 0 ? Math.round(summary.on_time/summary.total*100) : 0)+'%'"></span>
                    </div>
                    <div class="px-5 py-4" style="border-right:1px solid rgba(195,198,215,.2)">
                        <p class="text-[11px] m-0 mb-1.5" style="color:#434655">Kech keldi</p>
                        <p class="text-2xl font-bold m-0 mb-1.5 leading-none tabular-nums" style="color:#0b1c30" x-text="summary.late"></p>
                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full" style="background:#fef3c7;color:#784b00"
                              x-text="(summary.total > 0 ? Math.round(summary.late/summary.total*100) : 0)+'%'"></span>
                    </div>
                    <div class="px-5 py-4" style="border-right:1px solid rgba(195,198,215,.2)">
                        <p class="text-[11px] m-0 mb-1.5" style="color:#434655">Uzrli sabab</p>
                        <p class="text-2xl font-bold m-0 mb-1.5 leading-none tabular-nums" style="color:#0b1c30" x-text="summary.excused"></p>
                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full" style="background:#dbeafe;color:#004ac6"
                              x-text="(summary.total > 0 ? Math.round(summary.excused/summary.total*100) : 0)+'%'"></span>
                    </div>
                    <div class="px-5 py-4">
                        <p class="text-[11px] m-0 mb-1.5" style="color:#434655">Kelmagan</p>
                        <p class="text-2xl font-bold m-0 mb-1.5 leading-none tabular-nums" style="color:#0b1c30" x-text="summary.absent"></p>
                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full" style="background:#fee2e2;color:#ba1a1a"
                              x-text="(summary.total > 0 ? Math.round(summary.absent/summary.total*100) : 0)+'%'"></span>
                    </div>
                </div>

                {{-- Per-teacher attendance bars --}}
                <div x-show="hasData" class="px-6 py-3">
                    <template x-for="(name, i) in (currentData?.labels ?? [])" :key="i">
                        <div class="flex items-center gap-3 py-2.5 ldiv last:border-0">
                            <p class="text-xs font-medium m-0 shrink-0 truncate" style="width:160px;color:#0b1c30" x-text="name"></p>
                            <div class="flex-1 rounded-lg overflow-hidden flex" style="height:22px;background:#eff4ff;min-width:80px">
                                <template x-if="currentData.don[i] > 0">
                                    <div class="h-full flex items-center justify-center text-white text-[10px] font-bold leading-none"
                                         :style="`flex:${currentData.don[i]};background:rgba(0,108,73,.9)`"
                                         x-text="currentData.don[i]"></div>
                                </template>
                                <template x-if="currentData.lan[i] > 0">
                                    <div class="h-full flex items-center justify-center text-white text-[10px] font-bold leading-none"
                                         :style="`flex:${currentData.lan[i]};background:rgba(120,75,0,.9)`"
                                         x-text="currentData.lan[i]"></div>
                                </template>
                                <template x-if="currentData.exn[i] > 0">
                                    <div class="h-full flex items-center justify-center text-white text-[10px] font-bold leading-none"
                                         :style="`flex:${currentData.exn[i]};background:rgba(0,74,198,.9)`"
                                         x-text="currentData.exn[i]"></div>
                                </template>
                                <template x-if="currentData.abn[i] > 0">
                                    <div class="h-full flex items-center justify-center text-white text-[10px] font-bold leading-none"
                                         :style="`flex:${currentData.abn[i]};background:rgba(186,26,26,.85)`"
                                         x-text="currentData.abn[i]"></div>
                                </template>
                            </div>
                            <span class="text-[11px] shrink-0 tabular-nums min-w-12 text-right" style="color:#434655"
                                  x-text="currentData.tot[i]+' kun'"></span>
                        </div>
                    </template>
                </div>
                <div x-show="!hasData" class="px-6 py-10 text-center" style="color:#434655">
                    <span class="material-symbols-outlined mb-2 block" style="font-size:40px;opacity:.2">calendar_off</span>
                    <p class="text-sm m-0" x-text="''+currentLabel+' uchun davomat ma\'lumotlari yo\'q'"></p>
                </div>
            </div>
        </section>


        {{-- ══ MODALS ══ --}}
        <x-public.teacher-modal />
        <x-public.direction-modal />

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
