@props([
    'icon',
    'label',
    'value',
    'suffix'     => '',
    'trendLabel' => '',
    'trendUp'    => true,
    'barPct'     => 0,
    'iconBg'     => 'rgba(0,74,198,.1)',
    'iconColor'  => '#004ac6',
    'barColor'   => '#004ac6',
])

<div class="kpi-card">
    <div class="flex justify-between items-start mb-4">
        <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0"
             style="background:{{ $iconBg }}">
            <span class="material-symbols-outlined" style="font-size:20px;color:{{ $iconColor }}">{{ $icon }}</span>
        </div>
        @if($trendLabel)
            <div class="flex items-center gap-1 text-xs font-semibold"
                 style="color:{{ $trendUp ? '#006c49' : '#ba1a1a' }}">
                <span class="material-symbols-outlined" style="font-size:14px">{{ $trendUp ? 'trending_up' : 'trending_down' }}</span>
                <span>{{ $trendLabel }}</span>
            </div>
        @endif
    </div>
    <p class="text-xs font-medium m-0" style="color:#434655">{{ $label }}</p>
    <h3 class="text-[40px] font-bold m-0 mt-1 leading-none" style="font-family:'Hanken Grotesk',sans-serif;color:#0b1c30">
        {{ $value }}@if($suffix)<span class="text-xl font-semibold" style="color:#434655">{{ $suffix }}</span>@endif
    </h3>
    <div class="mt-4 h-1 w-full rounded-full overflow-hidden" style="background:#e5eeff">
        <div class="h-full rounded-full chart-bar-animate"
             style="background:{{ $barColor }};width:{{ min(100, $barPct) }}%"></div>
    </div>
</div>
