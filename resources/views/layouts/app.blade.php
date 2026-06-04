<!DOCTYPE html>
<html lang="uz">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Xodimlarni baholash tizimi')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>

<body class="bg-slate-50 text-gray-900 min-h-screen flex flex-col">

    {{-- NAV --}}
    <nav class="sticky top-0 z-50 bg-white border-b border-slate-100" style="box-shadow:0 2px 8px rgba(15,23,42,.08)">
        <div class="max-w-5xl mx-auto px-6 h-20 flex items-center justify-between gap-4">

            {{-- Logo --}}
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

            {{-- Nav links --}}
            <div class="flex items-center gap-2">
                <a href="{{ route('public.stats') }}"
                    class="px-3 py-1.5 rounded-lg text-gray-500 hover:text-blue-700 hover:bg-blue-50 font-medium transition-colors text-[13px]">
                    <i class="fas fa-chart-bar mr-1.5 text-[11px]"></i>Statistika
                </a>

                @if(session('student_id'))
                    <a href="{{ route('student.dashboard') }}"
                        class="px-3 py-1.5 rounded-lg text-gray-500 hover:text-blue-700 hover:bg-blue-50 font-medium transition-colors text-[13px]">
                        <i class="fas fa-table-columns mr-1.5 text-[11px]"></i>Dashboard
                    </a>
                    <form method="POST" action="{{ route('student.logout') }}" class="inline m-0">
                        @csrf
                        <button class="px-3 py-1.5 rounded-lg text-gray-500 hover:text-red-600 hover:bg-red-50 font-medium transition-colors text-[13px] cursor-pointer">
                            <i class="fas fa-right-from-bracket mr-1.5 text-[11px]"></i>Chiqish
                        </button>
                    </form>
                @else
                    <a href="{{ route('student.login') }}"
                        class="inline-flex items-center gap-2 px-5 py-2.5 text-white text-[13px] font-semibold rounded-[10px] transition-opacity hover:opacity-90"
                        style="background:linear-gradient(135deg,#1E3A5F,#2563EB)">
                        <i class="fas fa-right-to-bracket text-[12px]"></i>Kirish
                    </a>
                @endif
            </div>
        </div>
    </nav>

    {{-- Page header (optional) --}}
    @yield('page-header')

    {{-- Alerts --}}
    @if(session('success') || session('error'))
        <div class="max-w-5xl mx-auto w-full px-6 pt-5">
            @if(session('success'))
                <div class="flex items-center gap-3 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-[13px]">
                    <i class="fas fa-circle-check text-emerald-500"></i>{{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="flex items-center gap-3 p-4 bg-red-50 border border-red-200 text-red-800 rounded-xl text-[13px]">
                    <i class="fas fa-circle-exclamation text-red-500"></i>{{ session('error') }}
                </div>
            @endif
        </div>
    @endif

    <main class="flex-1 max-w-5xl mx-auto w-full px-6 py-8">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="bg-gray-900 text-white/40 py-6 text-center mt-auto">
        <p class="text-[13px] font-semibold text-white/80 m-0 mb-1">
            Respublika o'rta tibbiyot va farmaseft xodimlar malakasini oshirish va ularni ixtisoslashtirish markazi Samarqand filiali
        </p>
        <p class="text-[11px] m-0">© {{ date('Y') }} · Barcha huquqlar himoyalangan</p>
    </footer>

    @stack('scripts')
</body>

</html>
