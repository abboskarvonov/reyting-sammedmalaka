@extends('layouts.stats')

@section('title', "O'qituvchi reytingi — SMK Samarqand")

@php
    /* Teacher chart */
    $cT = $allTeachers
        ->filter(fn($t) => ($t->ratings_avg_total_score ?? 0) > 0)
        ->sortByDesc('ratings_avg_total_score')
        ->values();
    $tL = $cT->map(fn($t) => $t->user->name)->toArray();
    $tS = $cT->map(fn($t) => round((float) $t->ratings_avg_total_score, 2))->toArray();
    $tC = $cT->map(fn($t) => (int) $t->ratings_count)->toArray();
    $avgR = round($avgScore ?? 0, 1);

    /* Table rows */
    $rows = $allTeachers->map(fn($t) => [
        'id'    => (int) $t->id,
        'name'  => $t->user->name,
        'dept'  => $t->department ?? '—',
        'dirs'  => $t->directions->pluck('name')->implode(', ') ?: '—',
        'score' => round((float) ($t->ratings_avg_total_score ?? 0), 2),
        'cnt'   => (int) ($t->ratings_count ?? 0),
        'tTot'  => (int) ($t->tasks_total ?? 0),
        'tDn'   => (int) ($t->tasks_completed ?? 0),
        'tPct'  => ($t->tasks_total ?? 0) > 0
            ? (int) round(($t->tasks_completed / $t->tasks_total) * 100)
            : 0,
    ])->values()->toArray();

    /* Teacher full data for modal (dirs only) */
    $teacherFullData = $allTeachers->mapWithKeys(fn($t) => [
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
            'dirs'  => isset($teacherDirRatings[$t->id])
                ? $teacherDirRatings[$t->id]
                    ->map(fn($r) => [
                        'name'  => $r->dir_name,
                        'score' => (float) $r->avg_score,
                        'count' => (int) $r->cnt,
                    ])
                    ->sortByDesc('score')
                    ->values()
                    ->toArray()
                : [],
        ],
    ])->toArray();

    /* Direction modal data */
    $dirTeacherData = [];
    foreach ($directionStats as $dir) {
        $teachers = $dirTeacherRatings->get($dir->id, collect());
        $dirTeacherData[$dir->id] = [
            'name'     => $dir->name,
            'score'    => round((float) $dir->ratings_avg_total_score, 2),
            'count'    => (int) $dir->ratings_count,
            'teachers' => $teachers->map(fn($t) => [
                'name'  => $t->teacher_name,
                'score' => (float) $t->avg_score,
                'cnt'   => (int) $t->cnt,
            ])->values()->toArray(),
        ];
    }
@endphp

@section('content')
<div x-data="sApp()" x-init="$nextTick(initCharts)" x-cloak>

    {{-- ══ PAGE HEADER ══ --}}
    <section class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
        <div>
            <h2 class="text-[36px] font-bold leading-tight m-0" style="font-family:'Hanken Grotesk',sans-serif;color:#0b1c30">O'qituvchilar reytingi</h2>
            <p class="text-sm mt-1 m-0" style="color:#434655">Baholash natijalari va yo'nalishlar bo'yicha tahlil</p>
        </div>
        <div class="flex items-center gap-2 px-4 py-2 rounded-full border" style="background:#fff;border-color:rgba(195,198,215,.3);box-shadow:0 1px 3px rgba(0,0,0,.05)">
            <span class="w-2 h-2 rounded-full animate-pulse inline-block" style="background:#006c49"></span>
            <span class="text-xs" style="color:#434655">Jonli ma'lumot</span>
        </div>
    </section>

    {{-- ══ KPI CARDS ══ --}}
    <section class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-6">

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

    </section>

    {{-- ══ CHART ROW ══ --}}
    <section class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">

        {{-- Teacher rating chart --}}
        <div class="stats-card p-6">
            <div class="flex items-start justify-between flex-wrap gap-2 mb-5">
                <div>
                    <h4 class="text-lg font-semibold m-0" style="font-family:'Hanken Grotesk',sans-serif;color:#0b1c30">O'qituvchilar reytingi</h4>
                    <p class="text-xs mt-1 m-0" style="color:#434655">
                        @if (count($tL))
                            <strong style="color:#0b1c30">{{ count($tL) }}</strong> ta o'qituvchi baholangan
                        @else
                            Hali baholash ma'lumotlari yo'q
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-3 text-[11px]" style="color:#434655">
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm inline-block" style="background:#006c49"></span>A'lo ≥ 4.5</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm inline-block" style="background:#784b00"></span>Yaxshi ≥ 3.5</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm inline-block" style="background:#ba1a1a"></span>Past</span>
                </div>
            </div>
            @if (count($tL) > 0)
                <div class="cw" id="twrap"><canvas id="tc"></canvas></div>
            @else
                <div class="h-48 flex flex-col items-center justify-center" style="color:#434655">
                    <span class="material-symbols-outlined mb-2" style="font-size:40px;opacity:.2">bar_chart</span>
                    <p class="text-sm m-0">Hali baholash ma'lumotlari yo'q</p>
                </div>
            @endif
        </div>

        {{-- Direction stats --}}
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
                                <span class="text-xs font-bold min-w-8 text-right" style="color:#434655">{{ number_format($sc, 2) }}</span>
                                <button @click="openDirModal({{ $dir->id }})"
                                        title="O'qituvchilar ko'rish"
                                        class="shrink-0 w-7 h-7 rounded-lg border-0 cursor-pointer flex items-center justify-center"
                                        style="background:#eff4ff;color:#004ac6"
                                        onmouseover="this.style.background='#e5eeff'"
                                        onmouseout="this.style.background='#eff4ff'">
                                    <span class="material-symbols-outlined" style="font-size:14px">groups</span>
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
        </div>

    </section>

    {{-- ══ TEACHER TABLE ══ --}}
    <section>
        <div class="stats-card overflow-hidden">

            {{-- Header --}}
            <div class="px-6 py-4 flex flex-wrap items-center gap-3" style="border-bottom:1px solid rgba(195,198,215,.2)">
                <div class="flex items-center gap-2 flex-1 min-w-40">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:rgba(0,74,198,.1)">
                        <span class="material-symbols-outlined" style="font-size:18px;color:#004ac6">table_chart</span>
                    </div>
                    <h4 class="text-[15px] font-semibold m-0" style="font-family:'Hanken Grotesk',sans-serif;color:#0b1c30">Barcha o'qituvchilar</h4>
                </div>
                <div class="flex items-center gap-1.5 rounded-lg px-3 py-1.5" style="background:#eff4ff">
                    <span class="material-symbols-outlined" style="font-size:14px;color:#434655">search</span>
                    <input type="search" x-model="fs" placeholder="Qidirish..."
                           class="bg-transparent border-0 outline-none text-xs w-32" style="color:#0b1c30">
                </div>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full" style="border-collapse:collapse">
                    <thead>
                        <tr style="background:#eff4ff;border-bottom:1px solid rgba(195,198,215,.2)">
                            <th class="px-4 py-3 text-center text-[10px] font-semibold uppercase tracking-wide w-10" style="color:#434655">#</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-wide" style="color:#434655">O'qituvchi</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-wide hide-md" style="color:#434655">Yo'nalishlar</th>
                            <th class="px-4 py-3 text-center text-[10px] font-semibold uppercase tracking-wide" style="color:#434655">O'rtacha ball</th>
                            <th class="px-4 py-3 text-center text-[10px] font-semibold uppercase tracking-wide hide-sm" style="color:#434655">Sharhlar</th>
                            <th class="px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-wide" style="color:#434655">Topshiriqlar</th>
                            <th class="px-4 py-3 text-center text-[10px] font-semibold uppercase tracking-wide" style="color:#434655">Holati</th>
                            <th class="px-4 py-3 w-12"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(r,i) in filtered" :key="r.id">
                            <tr style="border-bottom:1px solid rgba(195,198,215,.1)"
                                onmouseover="this.style.background='rgba(239,244,255,.5)'"
                                onmouseout="this.style.background=''">
                                <td class="px-4 py-3 text-center">
                                    <span class="text-[11px] font-semibold" style="color:#434655" x-text="i+1"></span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-lg shrink-0 flex items-center justify-center text-white text-[11px] font-bold"
                                             style="background:linear-gradient(135deg,#004ac6,#2563eb)"
                                             x-text="r.name.split(' ').map(w=>w[0]).join('').slice(0,2).toUpperCase()">
                                        </div>
                                        <div>
                                            <p class="text-[13px] font-semibold m-0" style="color:#0b1c30" x-text="r.name"></p>
                                            <p class="text-[11px] m-0" style="color:#434655" x-text="r.dept"></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 hide-md">
                                    <span class="text-xs" style="color:#434655" x-text="r.dirs"></span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <span class="text-[15px] font-bold tabular-nums"
                                              :style="`color:${r.score>=4.5?'#006c49':r.score>=3.5?'#784b00':r.score>0?'#ba1a1a':'#9ca3af'}`"
                                              x-text="r.score>0?r.score.toFixed(2):'—'"></span>
                                        <span x-show="r.score>0" class="material-symbols-outlined" style="font-size:14px;color:#784b00;font-variation-settings:'FILL' 1">star</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center hide-sm">
                                    <span class="text-xs" style="color:#434655" x-text="r.cnt+' ta'"></span>
                                </td>
                                <td class="px-4 py-3">
                                    <template x-if="r.tTot>0">
                                        <div class="flex items-center gap-2 min-w-20">
                                            <div class="flex-1 h-1.5 rounded-full overflow-hidden" style="background:#eff4ff">
                                                <div class="h-full rounded-full"
                                                     :style="`width:${r.tPct}%;background:${r.tPct>=60?'#006c49':r.tPct>=30?'#784b00':'#ba1a1a'}`"></div>
                                            </div>
                                            <span class="text-[11px] font-semibold whitespace-nowrap tabular-nums"
                                                  :style="`color:${r.tPct>=60?'#006c49':r.tPct>=30?'#784b00':'#ba1a1a'}`"
                                                  x-text="`${r.tDn}/${r.tTot}`"></span>
                                        </div>
                                    </template>
                                    <template x-if="r.tTot===0">
                                        <span class="text-xs" style="color:#434655">—</span>
                                    </template>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-[10px] font-bold px-2 py-1 rounded"
                                          :style="`${r.score>=4.5?'background:#dcfce7;color:#006c49':r.score>=3.5?'background:#fef3c7;color:#784b00':r.score>0?'background:#fee2e2;color:#ba1a1a':'background:#f3f4f6;color:#9ca3af'}`"
                                          x-text="r.score>=4.5?'A\'lo':r.score>=3.5?'Yaxshi':r.score>0?'Qoniqarli':'Baholanmagan'"></span>
                                </td>
                                <td class="px-4 py-3">
                                    <button @click="openModal(r.id)" title="Batafsil"
                                            class="w-7 h-7 rounded-lg border-0 cursor-pointer flex items-center justify-center"
                                            style="background:#eff4ff;color:#004ac6"
                                            onmouseover="this.style.background='#e5eeff'"
                                            onmouseout="this.style.background='#eff4ff'">
                                        <span class="material-symbols-outlined" style="font-size:16px">analytics</span>
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filtered.length===0">
                            <td colspan="8" class="p-10 text-center" style="color:#434655">
                                <span class="material-symbols-outlined block mb-2" style="font-size:36px;opacity:.25">info</span>
                                Ma'lumot topilmadi
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-3 flex flex-wrap items-center justify-between gap-2" style="border-top:1px solid rgba(195,198,215,.2);background:#eff4ff">
                <p class="text-[11px] m-0" style="color:#434655">
                    <span class="font-semibold" style="color:#0b1c30" x-text="filtered.length"></span> ta o'qituvchi
                    <span x-show="fs" style="color:#004ac6"> (filtrlangan)</span>
                </p>
                <div class="flex items-center gap-3 text-[10px]" style="color:#434655">
                    <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded inline-block" style="background:#006c49"></span>A'lo ≥ 4.5</span>
                    <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded inline-block" style="background:#784b00"></span>Yaxshi ≥ 3.5</span>
                    <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded inline-block" style="background:#ba1a1a"></span>Qoniqarli</span>
                </div>
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
    TD: { labels: @json($tL), scores: @json($tS), counts: @json($tC) },
    ROWS: @json($rows),
    TEACHER_FULL: @json($teacherFullData),
    DIR_FULL: @json($dirTeacherData),
};
</script>
@endpush
