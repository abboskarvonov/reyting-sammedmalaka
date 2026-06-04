@extends('layouts.stats')

@section('title', 'Topshiriqlar statistikasi — SMK Samarqand')

@php
    $tkRows = $tkF->map(fn($t) => [
        'id'    => (int) $t->id,
        'name'  => $t->user->name,
        'dept'  => $t->department ?? '—',
        'total' => (int) $t->tasks_total,
        'done'  => (int) $t->tasks_completed,
        'pct'   => $t->tasks_total > 0 ? (int) round($t->tasks_completed / $t->tasks_total * 100) : 0,
    ])->values()->toArray();
@endphp

{{-- ═══════ PAGE HEADER ═══════ --}}
@section('page-header')
    <div class="border-b border-slate-100" style="background:linear-gradient(135deg,#1E3A5F 0%,#1e4d8c 60%,#2563EB 100%)">
        <div class="px-8 py-7 flex items-center justify-between flex-wrap gap-3">
            <div>
                <h1 class="text-[26px] font-extrabold text-white m-0 leading-tight">Topshiriqlar statistikasi</h1>
                <p class="text-[13px] text-white/60 mt-1.5 m-0">Topshiriq bajarilishi · o'qituvchilar bo'yicha tahlil</p>
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
<div x-data="tApp()" x-init="$nextTick(initCharts)" x-cloak>

    {{-- ══════ KPI cards ══════ --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 *:min-w-0 *:overflow-hidden mb-5">

        {{-- Jami topshiriqlar --}}
        <div class="card px-5.5 py-5">
            <div class="flex items-start justify-between mb-2.5">
                <p class="text-xs font-medium text-gray-500 m-0">Jami topshiriqlar</p>
                <span class="w-7.5 h-7.5 rounded-lg bg-indigo-50 flex items-center justify-center text-[13px] text-blue-500 shrink-0">
                    <i class="fas fa-clipboard-list"></i>
                </span>
            </div>
            <p class="text-[32px] font-extrabold text-gray-900 m-0 mb-2 leading-none tabular-nums">{{ $aTo }}</p>
            <span class="tp tp-n"><i class="fas fa-circle" style="font-size:5px"></i>Tayinlangan</span>
        </div>

        {{-- Bajarilgan --}}
        <div class="card px-5.5 py-5">
            <div class="flex items-start justify-between mb-2.5">
                <p class="text-xs font-medium text-gray-500 m-0">Bajarilgan</p>
                <span class="w-7.5 h-7.5 rounded-lg bg-emerald-50 flex items-center justify-center text-[13px] text-emerald-600 shrink-0">
                    <i class="fas fa-circle-check"></i>
                </span>
            </div>
            <p class="text-[32px] font-extrabold text-gray-900 m-0 mb-2 leading-none tabular-nums">{{ $aDn }}</p>
            <span class="tp tp-g"><i class="fas fa-arrow-up" style="font-size:8px"></i>{{ $aPc }}% bajarildi</span>
        </div>

        {{-- Bajarilish foizi --}}
        <div class="card px-5.5 py-5">
            <div class="flex items-start justify-between mb-2.5">
                <p class="text-xs font-medium text-gray-500 m-0">Bajarilish foizi</p>
                <span class="w-7.5 h-7.5 rounded-lg bg-blue-100 flex items-center justify-center text-[13px] text-blue-600 shrink-0">
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

    {{-- ══════ Row 2: Donut | Per-teacher list ══════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 *:min-w-0 *:overflow-hidden mb-5">

        {{-- Donut card --}}
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
                        <p class="text-2xl font-extrabold text-gray-900 leading-none m-0 tabular-nums">{{ $aPc }}%</p>
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

        {{-- Per-teacher list with search --}}
        <div class="card p-5.5">
            <div class="mb-3.5 flex items-center justify-between flex-wrap gap-2">
                <div>
                    <h2 class="text-sm font-bold text-gray-900 m-0">O'qituvchi bo'yicha</h2>
                    <p class="text-[11px] text-gray-400 mt-0.75 m-0">Topshiriq bajarilish holati</p>
                </div>
                {{-- Search --}}
                <div class="flex items-center gap-1.5 bg-slate-50 border border-gray-200 rounded-lg px-2.5 py-1.5">
                    <i class="fas fa-search text-[10px] text-gray-400"></i>
                    <input type="search" x-model="fs" placeholder="Qidirish..."
                        class="bg-transparent border-0 outline-none text-xs text-gray-700 w-32.5">
                </div>
            </div>

            <div class="flex flex-col">
                <template x-for="(r, i) in filtered" :key="r.id">
                    <div class="flex items-center gap-2.5 py-2.25 border-b border-gray-50 last:border-0">
                        <p class="text-xs font-medium text-gray-700 m-0 flex-1 min-w-0 truncate" x-text="r.name"></p>
                        <div class="w-18 h-1.25 rounded-full bg-slate-100 overflow-hidden shrink-0">
                            <div class="h-full rounded-full transition-[width] duration-600 ease-out"
                                :style="`width:${r.pct}%;background:${r.pct>=100?'#10B981':r.pct>=60?'#34D399':r.pct>=30?'#F59E0B':'#EF4444'}`">
                            </div>
                        </div>
                        <span class="text-[11px] text-gray-500 shrink-0 tabular-nums min-w-7 text-right"
                            x-text="r.done+'/'+r.total"></span>
                        <button @click="openModal(r.id)" title="Batafsil"
                            class="shrink-0 w-7 h-7 rounded-[7px] bg-indigo-50 text-blue-500 border-0 cursor-pointer flex items-center justify-center transition-colors duration-150"
                            onmouseover="this.style.background='#DBEAFE'"
                            onmouseout="this.style.background='#EEF2FF'">
                            <i class="fas fa-chart-simple text-[11px]"></i>
                        </button>
                    </div>
                </template>
                <div x-show="filtered.length===0" class="py-8 text-center text-gray-400">
                    <i class="fas fa-clipboard-list text-[28px] opacity-20 block mb-2"></i>
                    <p class="text-xs m-0" x-text="fs?'Natija topilmadi':'Topshiriq ma\'lumotlari yo\'q'"></p>
                </div>
            </div>
        </div>

    </div>

    {{-- ══════ Task Modal ══════ --}}
    <div x-show="modal" x-cloak @keydown.escape.window="modal=false"
        class="fixed inset-0 z-[300] backdrop-blur-sm"
        style="background:rgba(15,23,42,.5)">
        <div class="flex items-center justify-center w-full h-full p-5"
            @click.self="modal=false">
            <div class="bg-white rounded-2xl w-full max-w-xl max-h-[88vh] overflow-hidden flex flex-col"
                style="box-shadow:0 24px 60px rgba(15,23,42,.2)">

                {{-- Modal Header --}}
                <div class="px-5.5 py-4.5 border-b border-gray-100 flex items-start justify-between gap-3 shrink-0">
                    <div>
                        <h3 class="text-[15px] font-bold text-gray-900 m-0" x-text="modalTeacher"></h3>
                        <p class="text-xs text-gray-400 mt-0.75 m-0">Topshiriqlar ro'yxati</p>
                    </div>
                    <button @click="modal=false"
                        class="shrink-0 w-7.5 h-7.5 rounded-lg bg-gray-100 border-0 cursor-pointer text-[15px] text-gray-500 flex items-center justify-center leading-none"
                        onmouseover="this.style.background='#E5E7EB'"
                        onmouseout="this.style.background='#F3F4F6'">✕</button>
                </div>

                {{-- Task summary bar --}}
                <div class="px-5.5 py-3 bg-neutral-50 border-b border-gray-100 shrink-0">
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

                {{-- Tasks list --}}
                <div class="overflow-y-auto flex-1">
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

            </div>
        </div>
    </div>{{-- /modal --}}

</div>
@endsection

@push('scripts')
<script>
window.STATS_DATA = {
    DONE_N: {{ $aDn }},
    PEND_N: {{ $aPn }},
    TK_ROWS: @json($tkRows),
    TEACHER_FULL: @json($teacherFullData),
};
</script>
@endpush
