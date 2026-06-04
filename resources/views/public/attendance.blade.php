@extends('layouts.stats')

@section('title', 'Davomat statistikasi — SMK Samarqand')

{{-- ═══════ PAGE HEADER ═══════ --}}
@section('page-header')
    <div class="border-b border-slate-100" style="background:linear-gradient(135deg,#1E3A5F 0%,#1e4d8c 60%,#2563EB 100%)">
        <div class="px-8 py-7 flex items-center justify-between flex-wrap gap-3">
            <div>
                <h1 class="text-[26px] font-extrabold text-white m-0 leading-tight">Davomat statistikasi</h1>
                <p class="text-[13px] text-white/60 mt-1.5 m-0">O'qituvchilar davomati · oylik tahlil</p>
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
<div x-data="attApp()" x-init="init()" x-cloak>

    {{-- ══════ Attendance card (full-width) ══════ --}}
    <div class="card overflow-hidden">

        {{-- Header --}}
        <div class="px-5.5 py-4 border-b border-gray-100 flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 rounded-[7px] bg-blue-100 flex items-center justify-center text-blue-600 text-[11px] shrink-0">
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
                    <span class="flex items-center gap-0.75"><span class="w-2 h-2 rounded-[2px] bg-emerald-500 inline-block"></span>O'z vaqtida</span>
                    <span class="flex items-center gap-0.75"><span class="w-2 h-2 rounded-[2px] bg-amber-400 inline-block"></span>Kech</span>
                    <span class="flex items-center gap-0.75"><span class="w-2 h-2 rounded-[2px] bg-blue-500 inline-block"></span>Uzrli</span>
                    <span class="flex items-center gap-0.75"><span class="w-2 h-2 rounded-[2px] bg-red-500 inline-block"></span>Kelmagan</span>
                </div>
            </div>
        </div>

        {{-- 4 summary stat boxes (Alpine reactive) --}}
        <div style="display:grid;grid-template-columns:repeat(4,1fr);border-bottom:1px solid #F3F4F6">
            <div class="px-5 py-3.5 border-r border-gray-100">
                <p class="text-[11px] text-gray-500 m-0 mb-1.25">O'z vaqtida</p>
                <p class="text-2xl font-extrabold text-gray-900 m-0 mb-1.25 leading-none tabular-nums" x-text="summary.on_time"></p>
                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-[20px]" style="background:#DCFCE7;color:#16A34A"
                    x-text="(summary.total > 0 ? Math.round(summary.on_time/summary.total*100) : 0)+'%'"></span>
            </div>
            <div class="px-5 py-3.5 border-r border-gray-100">
                <p class="text-[11px] text-gray-500 m-0 mb-1.25">Kech keldi</p>
                <p class="text-2xl font-extrabold text-gray-900 m-0 mb-1.25 leading-none tabular-nums" x-text="summary.late"></p>
                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-[20px]" style="background:#FEF9C3;color:#CA8A04"
                    x-text="(summary.total > 0 ? Math.round(summary.late/summary.total*100) : 0)+'%'"></span>
            </div>
            <div class="px-5 py-3.5 border-r border-gray-100">
                <p class="text-[11px] text-gray-500 m-0 mb-1.25">Uzrli sabab</p>
                <p class="text-2xl font-extrabold text-gray-900 m-0 mb-1.25 leading-none tabular-nums" x-text="summary.excused"></p>
                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-[20px]" style="background:#DBEAFE;color:#2563EB"
                    x-text="(summary.total > 0 ? Math.round(summary.excused/summary.total*100) : 0)+'%'"></span>
            </div>
            <div class="px-5 py-3.5">
                <p class="text-[11px] text-gray-500 m-0 mb-1.25">Kelmagan</p>
                <p class="text-2xl font-extrabold text-gray-900 m-0 mb-1.25 leading-none tabular-nums" x-text="summary.absent"></p>
                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-[20px]" style="background:#FEE2E2;color:#DC2626"
                    x-text="(summary.total > 0 ? Math.round(summary.absent/summary.total*100) : 0)+'%'"></span>
            </div>
        </div>

        {{-- Per-teacher attendance bars --}}
        <div x-show="hasData" class="px-5.5 py-2">
            <template x-for="(name, i) in (currentData?.labels ?? [])" :key="i">
                <div class="flex items-center gap-3 py-2.5 border-b border-gray-50 last:border-0">
                    <p class="text-[12px] font-medium text-gray-700 m-0 shrink-0 truncate" style="width:150px" x-text="name"></p>
                    <div class="flex-1 rounded-lg overflow-hidden flex" style="height:22px;background:#F1F5F9;min-width:80px">
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
                    <span class="text-[11px] text-gray-400 shrink-0 tabular-nums min-w-12 text-right" x-text="currentData.tot[i]+' kun'"></span>
                </div>
            </template>
        </div>
        <div x-show="!hasData" class="p-8 text-center text-gray-400">
            <i class="fas fa-calendar-xmark text-2xl opacity-25 block mb-2"></i>
            <p class="text-xs m-0" x-text="''+currentLabel+' uchun davomat ma\'lumotlari yo\'q'"></p>
        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
window.STATS_DATA = {
    ATTEND_ALL: @json($attendanceAllMonths),
    DEFAULT_MONTH: @json($defaultMonthKey),
};
</script>
@endpush
