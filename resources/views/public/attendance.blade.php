@extends('layouts.stats')

@section('title', 'Davomat statistikasi — SMK Samarqand')

@section('content')
<div x-data="attApp()" x-cloak>

    {{-- ══ PAGE HEADER ══ --}}
    <section class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
        <div>
            <h2 class="text-[36px] font-bold leading-tight m-0" style="font-family:'Hanken Grotesk',sans-serif;color:#0b1c30">Davomat statistikasi</h2>
            <p class="text-sm mt-1 m-0" style="color:#434655">O'qituvchilar davomati · oylik tahlil</p>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            <select x-model="selectedMonth" @change="setMonth(selectedMonth)"
                    class="rounded-lg px-3 py-2 text-xs font-semibold outline-none cursor-pointer border-none"
                    style="background:#fff;color:#0b1c30;border:1px solid rgba(195,198,215,.4);box-shadow:0 1px 3px rgba(0,0,0,.05)"
                    onfocus="this.style.boxShadow='0 0 0 2px rgba(0,74,198,.2)'"
                    onblur="this.style.boxShadow='0 1px 3px rgba(0,0,0,.05)'">
                <template x-for="mk in monthKeys" :key="mk">
                    <option :value="mk" x-text="allData[mk].label"></option>
                </template>
            </select>
            <div class="flex items-center gap-2 px-4 py-2 rounded-full border" style="background:#fff;border-color:rgba(195,198,215,.3);box-shadow:0 1px 3px rgba(0,0,0,.05)">
                <span class="w-2 h-2 rounded-full animate-pulse inline-block" style="background:#006c49"></span>
                <span class="text-xs" style="color:#434655" x-text="currentLabel"></span>
            </div>
        </div>
    </section>

    {{-- ══ STATUS CARDS (border-l-4) ══ --}}
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">

        {{-- O'z vaqtida (green) --}}
        <div class="kpi-card" style="border-left:4px solid #006c49">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background:rgba(108,248,187,.2)">
                    <span class="material-symbols-outlined" style="font-size:20px;color:#006c49">check_circle</span>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full" style="background:#dcfce7;color:#006c49"
                      x-text="(summary.total > 0 ? Math.round(summary.on_time/summary.total*100) : 0)+'%'"></span>
            </div>
            <p class="text-[10px] font-semibold uppercase tracking-wider m-0 mb-1" style="color:#434655">O'z vaqtida</p>
            <h3 class="text-[40px] font-bold m-0 leading-none" style="font-family:'Hanken Grotesk',sans-serif;color:#0b1c30"
                x-text="summary.on_time"></h3>
            <p class="text-[11px] mt-2 m-0" style="color:#434655">O'tgan oyga nisbatan yaxshi</p>
        </div>

        {{-- Kech keldi (amber) --}}
        <div class="kpi-card" style="border-left:4px solid #784b00">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background:rgba(255,221,184,.3)">
                    <span class="material-symbols-outlined" style="font-size:20px;color:#784b00">schedule</span>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full" style="background:#fef3c7;color:#784b00"
                      x-text="(summary.total > 0 ? Math.round(summary.late/summary.total*100) : 0)+'%'"></span>
            </div>
            <p class="text-[10px] font-semibold uppercase tracking-wider m-0 mb-1" style="color:#434655">Kech keldi</p>
            <h3 class="text-[40px] font-bold m-0 leading-none" style="font-family:'Hanken Grotesk',sans-serif;color:#0b1c30"
                x-text="summary.late"></h3>
            <p class="text-[11px] mt-2 m-0" style="color:#434655">Asosan birinchi smenada</p>
        </div>

        {{-- Uzrli (blue) --}}
        <div class="kpi-card" style="border-left:4px solid #004ac6">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background:rgba(0,74,198,.1)">
                    <span class="material-symbols-outlined" style="font-size:20px;color:#004ac6">info</span>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full" style="background:#dbeafe;color:#2563eb"
                      x-text="(summary.total > 0 ? Math.round(summary.excused/summary.total*100) : 0)+'%'"></span>
            </div>
            <p class="text-[10px] font-semibold uppercase tracking-wider m-0 mb-1" style="color:#434655">Uzrli sabab</p>
            <h3 class="text-[40px] font-bold m-0 leading-none" style="font-family:'Hanken Grotesk',sans-serif;color:#0b1c30"
                x-text="summary.excused"></h3>
            <p class="text-[11px] mt-2 m-0" style="color:#434655">Tibbiy ma'lumotnomalar bilan</p>
        </div>

        {{-- Kelmagan (red) --}}
        <div class="kpi-card" style="border-left:4px solid #ba1a1a">
            <div class="flex justify-between items-start mb-4">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background:rgba(186,26,26,.1)">
                    <span class="material-symbols-outlined" style="font-size:20px;color:#ba1a1a">cancel</span>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full" style="background:#fee2e2;color:#ba1a1a"
                      x-text="(summary.total > 0 ? Math.round(summary.absent/summary.total*100) : 0)+'%'"></span>
            </div>
            <p class="text-[10px] font-semibold uppercase tracking-wider m-0 mb-1" style="color:#434655">Kelmagan</p>
            <h3 class="text-[40px] font-bold m-0 leading-none" style="font-family:'Hanken Grotesk',sans-serif;color:#0b1c30"
                x-text="summary.absent"></h3>
            <p class="text-[11px] mt-2 m-0" style="color:#434655">Nazorat talab etiladi</p>
        </div>

    </section>

    {{-- ══ PER-TEACHER BARS ══ --}}
    <section>
        <div class="stats-card overflow-hidden">

            {{-- Header --}}
            <div class="px-6 py-4 flex items-center justify-between flex-wrap gap-3" style="border-bottom:1px solid rgba(195,198,215,.2)">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:rgba(0,74,198,.1)">
                        <span class="material-symbols-outlined" style="font-size:18px;color:#004ac6">bar_chart</span>
                    </div>
                    <div>
                        <h4 class="text-[15px] font-semibold m-0" style="font-family:'Hanken Grotesk',sans-serif;color:#0b1c30">Shaxsiy ko'rsatkichlar</h4>
                        <p class="text-[11px] m-0" style="color:#434655" x-text="currentLabel"></p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3 text-[10px]" style="color:#434655">
                    <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-sm inline-block" style="background:#006c49"></span>O'z vaqtida</span>
                    <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-sm inline-block" style="background:#784b00"></span>Kech</span>
                    <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-sm inline-block" style="background:#004ac6"></span>Uzrli</span>
                    <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-sm inline-block" style="background:#ba1a1a"></span>Kelmagan</span>
                </div>
            </div>

            {{-- Bars --}}
            <div x-show="hasData" class="px-6 py-3">
                <template x-for="(name, i) in (currentData?.labels ?? [])" :key="i">
                    <div class="flex items-center gap-3 py-3 ldiv last:border-0">
                        <div class="shrink-0" style="width:180px">
                            <p class="text-[13px] font-semibold m-0 truncate" style="color:#0b1c30" x-text="name"></p>
                            <p class="text-[10px] m-0" style="color:#434655" x-text="currentData.tot[i]+' kun'"></p>
                        </div>
                        <div class="flex-1 rounded-lg overflow-hidden flex" style="height:24px;background:#eff4ff;min-width:80px">
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
                        <div class="shrink-0 text-right" style="min-width:60px">
                            <span class="text-[11px] font-semibold tabular-nums" style="color:#0b1c30"
                                  x-text="(currentData.tot[i]>0?Math.round(currentData.don[i]/currentData.tot[i]*100):0)+'%'"></span>
                            <p class="text-[10px] m-0" style="color:#434655">vaqtida</p>
                        </div>
                    </div>
                </template>
            </div>

            <div x-show="!hasData" class="px-6 py-12 text-center" style="color:#434655">
                <span class="material-symbols-outlined block mb-2" style="font-size:40px;opacity:.2">calendar_off</span>
                <p class="text-sm m-0" x-text="''+currentLabel+' uchun davomat ma\'lumotlari yo\'q'"></p>
            </div>

        </div>
    </section>

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
