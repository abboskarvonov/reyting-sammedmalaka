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

@section('content')
<div x-data="tApp()" x-init="$nextTick(initCharts)" x-cloak>

    {{-- ══ PAGE HEADER ══ --}}
    <section class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
        <div>
            <h2 class="text-[36px] font-bold leading-tight m-0" style="font-family:'Hanken Grotesk',sans-serif;color:#0b1c30">Topshiriqlar holati</h2>
            <p class="text-sm mt-1 m-0" style="color:#434655">Topshiriq bajarilishi va o'qituvchilar bo'yicha tahlil</p>
        </div>
        <div class="flex items-center gap-2 px-4 py-2 rounded-full border" style="background:#fff;border-color:rgba(195,198,215,.3);box-shadow:0 1px 3px rgba(0,0,0,.05)">
            <span class="w-2 h-2 rounded-full animate-pulse inline-block" style="background:#006c49"></span>
            <span class="text-xs" style="color:#434655">Jonli ma'lumot</span>
        </div>
    </section>

    {{-- ══ KPI CARDS ══ --}}
    <section class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-6">

        <x-public.kpi-card
            icon="assignment"
            label="Jami topshiriqlar"
            :value="$aTo"
            trend-label="Tayinlangan"
            :bar-pct="100"
        />

        <x-public.kpi-card
            icon="task_alt"
            label="Bajarilgan"
            :value="$aDn"
            :trend-label="$aPc . '%'"
            icon-bg="rgba(108,248,187,.25)"
            icon-color="#006c49"
            bar-color="#006c49"
            :bar-pct="$aPc"
        />

        <x-public.kpi-card
            icon="percent"
            label="Bajarilish foizi"
            :value="$taskCompletionRate"
            suffix="%"
            :trend-label="$taskCompletionRate >= 70 ? 'Yaxshi' : ($taskCompletionRate >= 40 ? 'O\'rta' : 'Past')"
            :trend-up="$taskCompletionRate >= 40"
            icon-bg="rgba(0,74,198,.1)"
            bar-color="#004ac6"
            :bar-pct="$taskCompletionRate"
        />

    </section>

    {{-- ══ BENTO GRID: Donut + Performers ══ --}}
    <section class="grid grid-cols-1 lg:grid-cols-12 gap-5 mb-6">

        {{-- Donut chart (5 cols) --}}
        <div class="lg:col-span-5 stats-card p-6 flex flex-col items-center text-center">
            <div class="w-full flex justify-start mb-6">
                <h4 class="text-lg font-semibold m-0" style="font-family:'Hanken Grotesk',sans-serif;color:#0b1c30">Umumiy bajarilish</h4>
            </div>

            {{-- Donut --}}
            <div class="relative shrink-0 mb-6" style="width:160px;height:160px">
                <canvas id="dn" width="160" height="160"></canvas>
                <div class="dlabel">
                    <p class="text-[28px] font-bold leading-none m-0" style="color:#0b1c30">{{ $aPc }}%</p>
                    <p class="text-[10px] mt-1 m-0" style="color:#434655">Bajarilgan</p>
                </div>
            </div>

            {{-- Legend --}}
            <div class="grid grid-cols-2 gap-6 w-full mt-2">
                <div class="flex items-center gap-3 justify-center">
                    <div class="w-3 h-3 rounded-full shrink-0" style="background:#006c49"></div>
                    <div class="text-left">
                        <p class="text-[10px] uppercase tracking-wider m-0 mb-0.5" style="color:#434655">Bajarilgan</p>
                        <p class="text-xl font-bold m-0" style="color:#0b1c30">{{ $aDn }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 justify-center">
                    <div class="w-3 h-3 rounded-full shrink-0" style="background:#ba1a1a"></div>
                    <div class="text-left">
                        <p class="text-[10px] uppercase tracking-wider m-0 mb-0.5" style="color:#434655">Kutilmoqda</p>
                        <p class="text-xl font-bold m-0" style="color:#0b1c30">{{ $aPn }}</p>
                    </div>
                </div>
            </div>

            {{-- Overall bar --}}
            <div class="w-full mt-5 h-1.5 rounded-full overflow-hidden" style="background:#eff4ff">
                <div class="h-full rounded-full" style="background:#006c49;width:{{ $aPc }}%"></div>
            </div>
        </div>

        {{-- Per-teacher performers (7 cols) --}}
        <div class="lg:col-span-7 stats-card p-6">
            <div class="flex justify-between items-center mb-5">
                <div>
                    <h4 class="text-lg font-semibold m-0" style="font-family:'Hanken Grotesk',sans-serif;color:#0b1c30">O'qituvchilar bo'yicha</h4>
                    <p class="text-xs mt-1 m-0" style="color:#434655">Topshiriq bajarilish holati</p>
                </div>
                <div class="flex items-center gap-1.5 rounded-lg px-3 py-1.5" style="background:#eff4ff">
                    <span class="material-symbols-outlined" style="font-size:14px;color:#434655">search</span>
                    <input type="search" x-model="fs" placeholder="Qidirish..."
                           class="bg-transparent border-0 outline-none text-xs w-28" style="color:#0b1c30">
                </div>
            </div>

            <div class="flex flex-col">
                <template x-for="(r, i) in filtered" :key="r.id">
                    <div class="flex items-center gap-3 py-3 ldiv last:border-0">
                        <div class="w-8 h-8 rounded-lg shrink-0 flex items-center justify-center text-white text-[11px] font-bold"
                             style="background:linear-gradient(135deg,#004ac6,#2563eb)"
                             x-text="r.name.split(' ').map(w=>w[0]).join('').slice(0,2).toUpperCase()">
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[13px] font-semibold m-0 truncate" style="color:#0b1c30" x-text="r.name"></p>
                            <p class="text-[11px] m-0 mt-0.5" style="color:#434655" x-text="r.dept"></p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0 min-w-28">
                            <div class="w-20 h-1.5 rounded-full overflow-hidden" style="background:#eff4ff">
                                <div class="h-full rounded-full"
                                     :style="`width:${r.pct}%;background:${r.pct>=60?'#006c49':r.pct>=30?'#784b00':'#ba1a1a'}`"></div>
                            </div>
                            <span class="text-[11px] font-semibold tabular-nums"
                                  :style="`color:${r.pct>=60?'#006c49':r.pct>=30?'#784b00':'#ba1a1a'}`"
                                  x-text="`${r.done}/${r.total}`"></span>
                        </div>
                        <button @click="openModal(r.id)" title="Batafsil"
                                class="shrink-0 w-7 h-7 rounded-lg border-0 cursor-pointer flex items-center justify-center"
                                style="background:#eff4ff;color:#004ac6"
                                onmouseover="this.style.background='#e5eeff'"
                                onmouseout="this.style.background='#eff4ff'">
                            <span class="material-symbols-outlined" style="font-size:16px">analytics</span>
                        </button>
                    </div>
                </template>
                <div x-show="filtered.length===0" class="py-10 text-center" style="color:#434655">
                    <span class="material-symbols-outlined block mb-2" style="font-size:36px;opacity:.2">assignment</span>
                    <p class="text-xs m-0" x-text="fs?'Natija topilmadi':'Topshiriq ma\'lumotlari yo\'q'"></p>
                </div>
            </div>
        </div>

    </section>

    {{-- ══ MODAL ══ --}}
    <x-public.task-modal />

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
