<!DOCTYPE html>
<html lang="uz">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', "Statistika portali — RO'TFXMOUIM Samarqand filiali")</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@600;700&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/css/stats.css', 'resources/js/stats.js', 'resources/js/app.js'])
</head>

<body class="min-h-screen" x-data="{ open: true }" style="background:#f8f9ff;color:#0b1c30;-webkit-font-smoothing:antialiased">

    {{-- ═══════ SIDEBAR ═══════ --}}
    <aside :style="{width: open ? '256px' : '64px'}"
           style="background:#eff4ff;transition:width .25s ease"
           class="h-screen fixed left-0 top-0 flex flex-col py-8 z-50 shadow-sm overflow-hidden">

        {{-- Logo --}}
        <div class="mb-8 flex items-center gap-3 overflow-hidden"
             :class="open ? 'px-6' : 'px-0 justify-center'">
            <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0" style="background:#004ac6">
                <span class="material-symbols-outlined text-white" style="font-size:18px">school</span>
            </div>
            <div class="overflow-hidden" x-show="open">
                <h1 class="text-sm font-bold leading-tight m-0" style="font-family:'Hanken Grotesk',sans-serif;color:#004ac6;white-space:normal;line-height:1.25">RO'TFXMOUIM Samarqand filiali</h1>
                <p class="text-[11px] m-0 mt-0.5 whitespace-nowrap" style="color:#434655;opacity:.7">Statistika portali</p>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 px-2 space-y-0.5">

            <a href="{{ route('public.stats') }}"
               :class="open ? 'gap-3 px-3' : 'justify-center px-0'"
               :title="open ? '' : 'Dashboard'"
               class="flex items-center py-2.5 rounded-lg transition-colors text-sm no-underline overflow-hidden {{ request()->routeIs('public.stats') ? 'font-bold' : '' }}"
               style="{{ request()->routeIs('public.stats') ? 'color:#004ac6;background:rgba(0,74,198,.1)' : 'color:#434655' }}"
               @unless(request()->routeIs('public.stats'))
               onmouseover="this.style.background='#dce9ff'" onmouseout="this.style.background=''"
               @endunless>
                <span class="material-symbols-outlined shrink-0" style="font-size:22px">dashboard</span>
                <span x-show="open" class="whitespace-nowrap">Dashboard</span>
            </a>

            <a href="{{ route('public.ratings') }}"
               :class="open ? 'gap-3 px-3' : 'justify-center px-0'"
               :title="open ? '' : 'O\'qituvchilar'"
               class="flex items-center py-2.5 rounded-lg transition-colors text-sm no-underline overflow-hidden {{ request()->routeIs('public.ratings') ? 'font-bold' : '' }}"
               style="{{ request()->routeIs('public.ratings') ? 'color:#004ac6;background:rgba(0,74,198,.1)' : 'color:#434655' }}"
               @unless(request()->routeIs('public.ratings'))
               onmouseover="this.style.background='#dce9ff'" onmouseout="this.style.background=''"
               @endunless>
                <span class="material-symbols-outlined shrink-0" style="font-size:22px">groups</span>
                <span x-show="open" class="whitespace-nowrap">O'qituvchilar</span>
            </a>

            <a href="{{ route('public.attendance') }}"
               :class="open ? 'gap-3 px-3' : 'justify-center px-0'"
               :title="open ? '' : 'Davomat'"
               class="flex items-center py-2.5 rounded-lg transition-colors text-sm no-underline overflow-hidden {{ request()->routeIs('public.attendance') ? 'font-bold' : '' }}"
               style="{{ request()->routeIs('public.attendance') ? 'color:#004ac6;background:rgba(0,74,198,.1)' : 'color:#434655' }}"
               @unless(request()->routeIs('public.attendance'))
               onmouseover="this.style.background='#dce9ff'" onmouseout="this.style.background=''"
               @endunless>
                <span class="material-symbols-outlined shrink-0" style="font-size:22px">calendar_today</span>
                <span x-show="open" class="whitespace-nowrap">Davomat</span>
            </a>

            <a href="{{ route('public.tasks') }}"
               :class="open ? 'gap-3 px-3' : 'justify-center px-0'"
               :title="open ? '' : 'Topshiriqlar'"
               class="flex items-center py-2.5 rounded-lg transition-colors text-sm no-underline overflow-hidden {{ request()->routeIs('public.tasks') ? 'font-bold' : '' }}"
               style="{{ request()->routeIs('public.tasks') ? 'color:#004ac6;background:rgba(0,74,198,.1)' : 'color:#434655' }}"
               @unless(request()->routeIs('public.tasks'))
               onmouseover="this.style.background='#dce9ff'" onmouseout="this.style.background=''"
               @endunless>
                <span class="material-symbols-outlined shrink-0" style="font-size:22px">assignment</span>
                <span x-show="open" class="whitespace-nowrap">Topshiriqlar</span>
            </a>

        </nav>

        {{-- Student login --}}
        <div class="mt-auto pt-6" :class="open ? 'px-3' : 'px-2'" style="border-top:1px solid rgba(195,198,215,.35)">
            <a href="{{ route('student.login') }}"
               :class="open ? 'gap-3 px-3' : 'justify-center px-0'"
               :title="open ? '' : 'Tinglovchi kirishi'"
               class="flex items-center py-3 rounded-lg no-underline transition-colors"
               onmouseover="this.style.background='#dce9ff'" onmouseout="this.style.background=''">
                <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0" style="background:#dce9ff">
                    <span class="material-symbols-outlined" style="font-size:18px;color:#004ac6">account_circle</span>
                </div>
                <div class="min-w-0 overflow-hidden" x-show="open">
                    <p class="text-xs font-bold m-0 leading-tight whitespace-nowrap" style="color:#0b1c30">Tinglovchi kirishi</p>
                    <p class="text-[10px] m-0 mt-0.5 whitespace-nowrap" style="color:#434655">ID-kod bilan</p>
                </div>
            </a>
        </div>

    </aside>

    {{-- ═══════ MAIN WRAPPER ═══════ --}}
    <div :style="{marginLeft: open ? '256px' : '64px'}"
         style="transition:margin-left .25s ease"
         class="flex flex-col min-h-screen">

        {{-- ═══════ HEADER ═══════ --}}
        <header class="flex justify-between items-center h-16 px-6 sticky top-0 z-40 shadow-sm" style="background:#f8f9ff">

            {{-- Sidebar toggle --}}
            <button @click="open = !open"
                    class="w-9 h-9 rounded-lg flex items-center justify-center border-0 cursor-pointer"
                    style="background:#eff4ff;color:#004ac6"
                    onmouseover="this.style.background='#dce9ff'" onmouseout="this.style.background='#eff4ff'">
                <span class="material-symbols-outlined" style="font-size:20px" x-text="open ? 'menu_open' : 'menu'"></span>
            </button>

            {{-- Right side info --}}
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-full" style="background:rgba(0,108,73,.1)">
                    <span class="w-2 h-2 rounded-full animate-pulse inline-block" style="background:#006c49"></span>
                    <span class="text-[11px] font-semibold" style="color:#006c49">Jonli ma'lumot</span>
                </div>
            </div>

        </header>

        {{-- ═══════ MAIN CONTENT ═══════ --}}
        <main class="flex-1 px-8 py-6 pb-16">
            @yield('content')
        </main>

        {{-- ═══════ FOOTER ═══════ --}}
        <footer class="py-6 text-center" style="background:#0b1c30;border-top:2px solid #004ac6">
            <div class="flex items-center justify-center gap-2.5 mb-2">
                <div class="w-6 h-6 rounded-md flex items-center justify-center shrink-0" style="background:#004ac6">
                    <span class="material-symbols-outlined text-white" style="font-size:14px">school</span>
                </div>
                <p class="text-[13px] font-semibold m-0" style="color:rgba(255,255,255,.85)">RO'TFXMOUIM Samarqand filiali</p>
            </div>
            <p class="text-[11px] m-0 px-4" style="color:rgba(255,255,255,.35)">Respublika o'rta tibbiyot va farmaseft xodimlar malakasini oshirish va ularni ixtisoslashtirish markazi Samarqand filiali · © {{ date('Y') }}</p>
        </footer>

    </div>

    @stack('scripts')
</body>

</html>
