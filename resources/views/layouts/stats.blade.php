<!DOCTYPE html>
<html lang="uz">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Statistika paneli — SMK Samarqand')</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/css/stats.css', 'resources/js/stats.js', 'resources/js/app.js'])
</head>

<body class="bg-slate-50 min-h-screen flex flex-col">

    {{-- ═══════ NAV ═══════ --}}
    <nav class="sticky top-0 z-50 bg-white border-b border-slate-100" style="box-shadow:0 2px 8px rgba(15,23,42,.08)">
        <div class="px-8 h-20 flex items-center justify-between gap-4">
            {{-- Logo (link to home) --}}
            <a href="{{ route('public.stats') }}" class="flex items-center gap-3 shrink-0 no-underline">
                <div class="w-12 h-12 rounded-[12px] flex items-center justify-center shrink-0"
                    style="background:linear-gradient(135deg,#1E3A5F,#3B82F6)">
                    <svg viewBox="0 0 16 16" class="w-5 h-5 fill-white">
                        <rect x="6" y="0" width="4" height="16" rx="1" />
                        <rect x="0" y="6" width="16" height="4" rx="1" />
                    </svg>
                </div>
                <div>
                    <p class="text-[16px] font-bold text-gray-900 leading-tight m-0">SMK Samarqand</p>
                    <p class="text-[11px] text-gray-400 leading-tight m-0 mt-0.5">Xodimlarni baholash tizimi</p>
                </div>
            </a>
            {{-- Center nav --}}
            <div class="flex items-center gap-1">
                @php $navBase = 'flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-[13px] font-semibold no-underline transition-colors'; @endphp
                <a href="{{ route('public.ratings') }}"
                    class="{{ $navBase }} {{ request()->routeIs('public.ratings') ? 'bg-blue-50 text-blue-700' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50' }}">
                    <i class="fas fa-star text-[11px]"></i>O'qituvchi reytingi
                </a>
                <a href="{{ route('public.attendance') }}"
                    class="{{ $navBase }} {{ request()->routeIs('public.attendance') ? 'bg-blue-50 text-blue-700' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50' }}">
                    <i class="fas fa-calendar-check text-[11px]"></i>Davomat
                </a>
                <a href="{{ route('public.tasks') }}"
                    class="{{ $navBase }} {{ request()->routeIs('public.tasks') ? 'bg-blue-50 text-blue-700' : 'text-gray-500 hover:text-gray-900 hover:bg-gray-50' }}">
                    <i class="fas fa-clipboard-check text-[11px]"></i>Topshiriqlar
                </a>
            </div>
            {{-- Login button --}}
            <div class="flex items-center gap-2.5 shrink-0">
                <a href="{{ route('student.login') }}"
                    class="inline-flex items-center gap-2 px-5 py-2.5 text-white text-[13px] font-semibold rounded-[10px] no-underline transition-opacity hover:opacity-90"
                    style="background:linear-gradient(135deg,#1E3A5F,#2563EB)">
                    <i class="fas fa-right-to-bracket text-[12px]"></i>Tinglovchi kirishi
                </a>
            </div>
        </div>
    </nav>

    {{-- ═══════ PAGE HEADER (optional) ═══════ --}}
    @yield('page-header')

    {{-- ═══════ MAIN ═══════ --}}
    <main class="flex-1 px-8 py-6 pb-14">
        @yield('content')
    </main>

    {{-- ═══════ FOOTER ═══════ --}}
    <footer class="bg-gray-900 text-white/40 py-6 text-center">
        <p class="text-[13px] font-semibold text-white/85 m-0 mb-1">
            Respublika o`rta tibbiyot va farmaseft xodimlar malakasini oshirish va ularni ixtisoslashtirish markazi
            Samarqand filiali baholash tizimi statistikasi
        </p>
        <p class="text-[11px] m-0">© {{ date('Y') }} · Barcha huquqlar himoyalangan</p>
    </footer>

    {{-- ═══════ INJECTED DATA & SCRIPTS ═══════ --}}
    @stack('scripts')

</body>

</html>
