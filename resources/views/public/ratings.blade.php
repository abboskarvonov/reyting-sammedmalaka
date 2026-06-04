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
    ])->values()->toArray();

    /* Teacher full data for modal (dirs only) */
    $teacherFullData = $allTeachers->mapWithKeys(fn($t) => [
        $t->id => [
            'name'  => $t->user->name,
            'tasks' => [],
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

{{-- ═══════ PAGE HEADER ═══════ --}}
@section('page-header')
    <div class="border-b border-slate-100" style="background:linear-gradient(135deg,#1E3A5F 0%,#1e4d8c 60%,#2563EB 100%)">
        <div class="px-8 py-7 flex items-center justify-between flex-wrap gap-3">
            <div>
                <h1 class="text-[26px] font-extrabold text-white m-0 leading-tight">O'qituvchi reytingi</h1>
                <p class="text-[13px] text-white/60 mt-1.5 m-0">Baholash natijalari · yo'nalishlar bo'yicha tahlil</p>
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

    {{-- ══════ KPI cards ══════ --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 *:min-w-0 *:overflow-hidden mb-5">

        {{-- Jami xodimlar --}}
        <div class="card px-5.5 py-5">
            <div class="flex items-start justify-between mb-2.5">
                <p class="text-xs font-medium text-gray-500 m-0">Jami xodimlar</p>
                <span class="w-7.5 h-7.5 rounded-lg bg-indigo-50 flex items-center justify-center text-[13px] text-blue-500 shrink-0">
                    <i class="fas fa-users"></i>
                </span>
            </div>
            <p class="text-[32px] font-extrabold text-gray-900 m-0 mb-2 leading-none tabular-nums">{{ $totalTeachers }}</p>
            <span class="tp tp-n"><i class="fas fa-circle" style="font-size:5px"></i>Faol o'qituvchi</span>
        </div>

        {{-- O'rtacha ball --}}
        <div class="card px-5.5 py-5">
            <div class="flex items-start justify-between mb-2.5">
                <p class="text-xs font-medium text-gray-500 m-0">O'rtacha ball</p>
                <span class="w-7.5 h-7.5 rounded-lg bg-yellow-100 flex items-center justify-center text-[13px] text-yellow-600 shrink-0">
                    <i class="fas fa-star"></i>
                </span>
            </div>
            <p class="text-[32px] font-extrabold text-gray-900 m-0 mb-2 leading-none tabular-nums">{{ $avgR ?: '—' }}</p>
            <div class="flex items-center gap-1.25">
                <div class="flex gap-0.5">
                    @for ($s = 1; $s <= 5; $s++)
                        <i class="fas fa-star text-[10px]" style="color:{{ $s <= round($avgR) ? '#F59E0B' : '#E5E7EB' }}"></i>
                    @endfor
                </div>
                <span class="text-[10px] text-gray-400">5 ballik</span>
            </div>
        </div>

        {{-- Jami baholashlar --}}
        <div class="card px-5.5 py-5">
            <div class="flex items-start justify-between mb-2.5">
                <p class="text-xs font-medium text-gray-500 m-0">Jami baholashlar</p>
                <span class="w-7.5 h-7.5 rounded-lg bg-emerald-50 flex items-center justify-center text-[13px] text-emerald-600 shrink-0">
                    <i class="fas fa-chart-bar"></i>
                </span>
            </div>
            <p class="text-[32px] font-extrabold text-gray-900 m-0 mb-2 leading-none tabular-nums">{{ $totalRatings }}</p>
            <span class="tp tp-g"><i class="fas fa-circle" style="font-size:5px"></i>Tinglovchilar tomonidan</span>
        </div>

    </div>

    {{-- ══════ Row 2: Teacher chart | Direction progress bars ══════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 *:min-w-0 *:overflow-hidden mb-5">

        {{-- O'qituvchilar reytingi chart --}}
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
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-sm bg-emerald-500 inline-block"></span>≥ 4.5</span>
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-sm bg-amber-400 inline-block"></span>≥ 3.5</span>
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-sm bg-red-400 inline-block"></span>&lt; 3.5</span>
                </div>
            </div>
            @if ($directionStats->count() > 0)
                <div class="flex flex-col gap-3 flex-1">
                    @foreach ($directionStats as $dir)
                        @php
                            $sc   = round((float) $dir->ratings_avg_total_score, 2);
                            $pct  = min(100, round($sc / 5 * 100));
                            $col  = $sc >= 4.5 ? '#10B981' : ($sc >= 3.5 ? '#F59E0B' : '#EF4444');
                            $tcol = $sc >= 4.5 ? '#16A34A' : ($sc >= 3.5 ? '#CA8A04' : '#DC2626');
                        @endphp
                        <div class="flex items-center gap-3 py-1.5 border-b border-gray-50 last:border-0">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1.5">
                                    <p class="text-[13px] font-semibold text-gray-800 m-0 truncate pr-2">{{ $dir->name }}</p>
                                    <span class="text-[11px] text-gray-400 shrink-0">{{ $dir->ratings_count }} ta</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                        <div class="h-full rounded-full transition-[width] duration-700 ease-out"
                                            style="width:{{ $pct }}%;background:{{ $col }}"></div>
                                    </div>
                                    <span class="text-[13px] font-extrabold tabular-nums shrink-0 min-w-8 text-right"
                                        style="color:{{ $tcol }}">{{ number_format($sc, 2) }}</span>
                                    <button
                                        @click="openDirModal({{ $dir->id }})"
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

    {{-- ══════ Full teacher table with filters ══════ --}}
    <div class="card overflow-hidden">

        {{-- Header --}}
        <div class="px-5.5 py-3.5 border-b border-gray-50 flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2 flex-1 min-w-40">
                <span class="w-7 h-7 rounded-[7px] bg-indigo-50 flex items-center justify-center text-[11px] text-blue-500">
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
                        <th class="px-4 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide w-10">#</th>
                        <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide">O'qituvchi</th>
                        <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-wide hide-md">Yo'nalishlar</th>
                        <th class="px-4 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Ball</th>
                        <th class="px-4 py-2.5 text-center text-[10px] font-semibold text-gray-400 uppercase tracking-wide hide-sm">Baholashlar</th>
                        <th class="px-4 py-2.5 w-14"></th>
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
                                    :class="r.score >= 4.5 ? 'sb-g' : r.score >= 3.5 ? 'sb-a' : r.score > 0 ? 'sb-r' : 'sb-n'"
                                    x-text="r.score>0?r.score.toFixed(2)+' ★':'—'"></span>
                            </td>
                            <td class="px-4 py-2.75 text-center hide-sm">
                                <span class="text-xs text-gray-500" x-text="r.cnt+' ta'"></span>
                            </td>
                            <td class="px-4 py-2.75">
                                <button @click="openModal(r.id, 'dirs')" title="Yo'nalishlar bo'yicha"
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

    {{-- ══════ Teacher Modal (tabs: tasks + dirs) ══════ --}}
    <div x-show="modal" x-cloak @keydown.escape.window="modal=false"
        class="fixed inset-0 z-[300] backdrop-blur-sm"
        style="background:rgba(15,23,42,.5)">
        <div class="flex items-center justify-center w-full h-full p-5"
            @click.self="modal=false">
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
                            <span :style="`margin-left:5px;padding:1px 7px;border-radius:20px;font-size:10px;${modalTab==='tasks'?'background:#DBEAFE;color:#2563EB':'background:#F3F4F6;color:#9CA3AF'}`"
                                x-text="modalTasks.length"></span>
                        </button>
                        <button @click="modalTab='dirs'"
                            :style="`padding:8px 16px;font-size:12px;font-weight:600;border:none;cursor:pointer;background:transparent;border-bottom:2px solid transparent;margin-bottom:-2px;transition:all .15s;${modalTab==='dirs'?'color:#2563EB;border-bottom-color:#2563EB;':'color:#9CA3AF;'}`">
                            <span class="mr-1.25">🎯</span>
                            Yo'nalishlar bo'yicha ball
                            <span :style="`margin-left:5px;padding:1px 7px;border-radius:20px;font-size:10px;${modalTab==='dirs'?'background:#DBEAFE;color:#2563EB':'background:#F3F4F6;color:#9CA3AF'}`"
                                x-text="modalDirs.length"></span>
                        </button>
                    </div>
                </div>

                {{-- TAB: Topshiriqlar --}}
                <div x-show="modalTab==='tasks'" class="overflow-y-auto flex-1">
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
                        <div :style="`display:flex;align-items:flex-start;gap:12px;padding:13px 22px;${i<modalTasks.length-1?'border-bottom:1px solid #F9FAFB':''}`">
                            <div :style="`width:32px;height:32px;border-radius:9px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:15px;background:${task.status==='completed'?'#DCFCE7':'#FEE2E2'}`">
                                <span x-text="task.status==='completed'?'✅':'⏳'"></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2 flex-wrap">
                                    <p class="text-[13px] font-semibold text-gray-900 m-0 flex-1" x-text="task.title"></p>
                                    <span :style="`font-size:11px;font-weight:600;padding:2px 9px;border-radius:20px;flex-shrink:0;${task.status==='completed'?'background:#DCFCE7;color:#16A34A':'background:#FEE2E2;color:#DC2626'}`"
                                        x-text="task.status==='completed'?'Bajarilgan':'Kutilmoqda'"></span>
                                </div>
                                <div class="flex items-center gap-2.5 mt-1.25 flex-wrap">
                                    <span x-show="task.done_at" class="text-[11px] text-emerald-500 flex items-center gap-0.75">
                                        <span>✓</span><span x-text="task.done_at"></span>
                                    </span>
                                    <span x-show="task.due && task.status!=='completed'" class="text-[11px] text-amber-500 flex items-center gap-0.75">
                                        <span>📅</span><span x-text="task.due"></span>
                                    </span>
                                    <span x-show="task.priority && task.priority!=='medium'"
                                        :style="`font-size:10px;font-weight:600;padding:1px 7px;border-radius:20px;${task.priority==='high'?'background:#FEE2E2;color:#DC2626':task.priority==='urgent'?'background:#FEF3C7;color:#D97706':'background:#F3F4F6;color:#6B7280'}`"
                                        x-text="task.priority==='high'?'🔴 Yuqori':task.priority==='urgent'?'🔥 Shoshilinch':'Oddiy'"></span>
                                </div>
                                <p x-show="task.note" class="text-[11px] text-gray-400 mt-1.25 m-0 italic leading-snug" x-text="'💬 '+task.note"></p>
                            </div>
                        </div>
                    </template>
                    <div x-show="modalTasks.length===0" class="p-12 text-center text-gray-400">
                        <div class="text-[28px] mb-2 opacity-30">📋</div>
                        <p class="text-[13px] m-0">Topshiriqlar tayinlanmagan</p>
                    </div>
                </div>

                {{-- TAB: Yo'nalishlar bo'yicha ball --}}
                <div x-show="modalTab==='dirs'" class="overflow-y-auto flex-1">
                    <div class="px-5.5 py-3 bg-neutral-50 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] text-gray-500">
                                Baholangan yo'nalishlar: <strong class="text-gray-700" x-text="modalDirs.length"></strong> ta
                            </span>
                            <span class="text-[11px] font-bold text-gray-900"
                                x-text="modalDirs.length?(modalDirs.reduce((s,d)=>s+d.score,0)/modalDirs.length).toFixed(2)+' o\'rt.':'—'"></span>
                        </div>
                    </div>
                    <template x-for="(dir,i) in modalDirs" :key="i">
                        <div :style="`display:flex;align-items:center;gap:14px;padding:14px 22px;${i<modalDirs.length-1?'border-bottom:1px solid #F9FAFB':''}`">
                            <div class="w-6.5 h-6.5 rounded-[7px] shrink-0 bg-indigo-50 flex items-center justify-center text-[11px] font-bold text-blue-500"
                                x-text="i+1"></div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[13px] font-semibold text-gray-900 m-0 truncate" x-text="dir.name"></p>
                                <p class="text-[11px] text-gray-400 mt-0.5 m-0" x-text="dir.count+' ta baholash'"></p>
                            </div>
                            <div class="shrink-0 text-right min-w-25">
                                <div class="flex items-center gap-2 justify-end">
                                    <div class="w-17.5 h-1.25 rounded-full bg-slate-100 overflow-hidden">
                                        <div :style="`height:100%;border-radius:99px;width:${dir.score/5*100}%;background:${dir.score>=4.5?'#10B981':dir.score>=3.5?'#F59E0B':'#EF4444'};transition:width .6s ease`">
                                        </div>
                                    </div>
                                    <span :style="`font-size:14px;font-weight:800;font-variant-numeric:tabular-nums;color:${dir.score>=4.5?'#16A34A':dir.score>=3.5?'#CA8A04':'#DC2626'}`"
                                        x-text="dir.score.toFixed(2)"></span>
                                </div>
                                <div class="flex justify-end gap-px mt-1">
                                    <template x-for="s in 5" :key="s">
                                        <span :style="`font-size:9px;color:${s<=Math.round(dir.score)?'#F59E0B':'#E5E7EB'}`">★</span>
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
        </div>
    </div>{{-- /teacher modal --}}

    {{-- ══════ Direction Modal ══════ --}}
    <div x-show="dirModal" x-cloak @keydown.escape.window="dirModal=false"
        class="fixed inset-0 z-[400] backdrop-blur-sm"
        style="background:rgba(15,23,42,.5)">
        <div class="flex items-center justify-center w-full h-full p-5"
            @click.self="dirModal=false">
            <div class="bg-white rounded-2xl w-full max-w-lg max-h-[85vh] overflow-hidden flex flex-col"
                style="box-shadow:0 24px 60px rgba(15,23,42,.2)">

                {{-- Header --}}
                <div class="px-5.5 py-4.5 border-b border-gray-100 flex items-start justify-between gap-3 shrink-0">
                    <div>
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="w-6 h-6 rounded-lg bg-indigo-50 flex items-center justify-center text-blue-500 text-[11px] shrink-0">
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
                        <div :style="`display:flex;align-items:center;gap:14px;padding:14px 22px;${i<dirModalTeachers.length-1?'border-bottom:1px solid #F9FAFB':''}`">
                            <div class="w-9 h-9 rounded-[10px] shrink-0 flex items-center justify-center text-white text-[12px] font-bold"
                                style="background:linear-gradient(135deg,#1E3A5F,#3B82F6)"
                                x-text="t.name.split(' ').map(w=>w[0]).join('').slice(0,2).toUpperCase()">
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[13px] font-semibold text-gray-900 m-0 truncate" x-text="t.name"></p>
                                <p class="text-[11px] text-gray-400 m-0" x-text="t.cnt+' ta baholash'"></p>
                            </div>
                            <div class="shrink-0 text-right min-w-28">
                                <div class="flex items-center gap-2 justify-end mb-1">
                                    <div class="w-16 h-1.25 rounded-full bg-slate-100 overflow-hidden">
                                        <div :style="`height:100%;border-radius:99px;width:${t.score/5*100}%;background:${t.score>=4.5?'#10B981':t.score>=3.5?'#F59E0B':'#EF4444'};transition:width .6s ease`"></div>
                                    </div>
                                    <span class="text-[14px] font-extrabold tabular-nums"
                                        :style="`color:${t.score>=4.5?'#16A34A':t.score>=3.5?'#CA8A04':'#DC2626'}`"
                                        x-text="t.score.toFixed(2)"></span>
                                </div>
                                <div class="flex justify-end gap-px">
                                    <template x-for="s in 5" :key="s">
                                        <span :style="`font-size:9px;color:${s<=Math.round(t.score)?'#F59E0B':'#E5E7EB'}`">★</span>
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
    TD: { labels: @json($tL), scores: @json($tS), counts: @json($tC) },
    ROWS: @json($rows),
    TEACHER_FULL: @json($teacherFullData),
    DIR_FULL: @json($dirTeacherData),
};
</script>
@endpush
