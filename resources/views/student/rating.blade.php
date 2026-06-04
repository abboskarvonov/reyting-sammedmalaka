@extends('layouts.app')

@section('title', "Baholash — {{ $teacher->user->name }}")

@section('page-header')
    <div class="border-b border-slate-100" style="background:linear-gradient(135deg,#1E3A5F 0%,#1e4d8c 60%,#2563EB 100%)">
        <div class="max-w-5xl mx-auto px-6 py-7 flex items-center justify-between flex-wrap gap-3">
            <div>
                <h1 class="text-[24px] font-extrabold text-white m-0 leading-tight">O'qituvchini baholash</h1>
                <p class="text-[13px] text-white/60 mt-1.5 m-0">
                    <span class="text-white/80 font-medium">{{ $teacher->user->name }}</span>
                    &nbsp;·&nbsp;
                    Yo'nalish: <span class="text-white/80 font-medium">{{ $direction->name }}</span>
                </p>
            </div>
            <a href="{{ route('student.dashboard') }}"
                class="inline-flex items-center gap-2 px-4 py-2 text-[13px] font-semibold text-white/80 bg-white/10 hover:bg-white/20 rounded-xl transition-colors">
                <i class="fas fa-arrow-left text-[11px]"></i>Orqaga
            </a>
        </div>
    </div>
@endsection

@section('content')
<div class="max-w-2xl mx-auto">
    <form method="POST" action="{{ route('student.rate.store', $teacher) }}">
        @csrf
        <input type="hidden" name="direction_id" value="{{ $direction->id }}">

        {{-- Questions --}}
        <div class="flex flex-col gap-4 mb-5">
            @foreach($questions as $index => $q)
                <div class="bg-white rounded-2xl p-6" style="box-shadow:0 2px 12px rgba(15,23,42,.07)">
                    <div class="flex items-start gap-3 mb-5">
                        <span class="shrink-0 w-7 h-7 rounded-lg flex items-center justify-center text-[12px] font-bold text-white"
                            style="background:linear-gradient(135deg,#1E3A5F,#2563EB)">
                            {{ $index + 1 }}
                        </span>
                        <p class="text-[14px] font-semibold text-gray-800 m-0 leading-snug">{{ $q->question }}</p>
                    </div>

                    <div class="flex gap-3 justify-center">
                        @for($i = 1; $i <= 5; $i++)
                            <label class="flex flex-col items-center gap-1.5 cursor-pointer group">
                                <input
                                    type="radio"
                                    name="scores[{{ $q->id }}]"
                                    value="{{ $i }}"
                                    class="sr-only peer"
                                    required
                                >
                                <span class="w-11 h-11 rounded-xl border-2 border-slate-200 flex items-center justify-center text-[15px] font-bold text-gray-400 transition-all peer-checked:border-blue-600 peer-checked:bg-blue-600 peer-checked:text-white group-hover:border-blue-400 group-hover:text-blue-500">
                                    {{ $i }}
                                </span>
                                <span class="text-[10px] font-medium {{ $i >= 4 ? 'text-emerald-500' : ($i <= 2 ? 'text-red-400' : 'text-amber-500') }}">
                                    @if($i === 1) Juda yomon
                                    @elseif($i === 2) Yomon
                                    @elseif($i === 3) O'rta
                                    @elseif($i === 4) Yaxshi
                                    @else A'lo
                                    @endif
                                </span>
                            </label>
                        @endfor
                    </div>

                    @error("scores.{$q->id}")
                        <p class="mt-3 text-[12px] text-red-600 flex items-center gap-1.5">
                            <i class="fas fa-circle-exclamation text-[10px]"></i>{{ $message }}
                        </p>
                    @enderror
                </div>
            @endforeach
        </div>

        {{-- Comment --}}
        <div class="bg-white rounded-2xl p-6 mb-5" style="box-shadow:0 2px 12px rgba(15,23,42,.07)">
            <label class="block text-[13px] font-semibold text-gray-600 mb-2">
                <i class="fas fa-comment-dots text-blue-400 mr-1.5"></i>Izoh (ixtiyoriy)
            </label>
            <textarea
                name="comment"
                rows="3"
                maxlength="500"
                class="w-full bg-slate-50 border-2 border-slate-200 rounded-xl px-4 py-3 text-[13px] text-gray-700 outline-none focus:border-blue-500 focus:bg-white transition-all resize-none placeholder-gray-300"
                placeholder="Qo'shimcha fikr yoki taklif..."
            ></textarea>
        </div>

        {{-- Actions --}}
        <div class="flex gap-3">
            <button type="submit"
                class="flex-1 text-white font-bold py-3.5 rounded-xl transition-opacity hover:opacity-90 text-[14px] flex items-center justify-center gap-2"
                style="background:linear-gradient(135deg,#1E3A5F,#2563EB)">
                <i class="fas fa-paper-plane text-[12px]"></i>Yuborish
            </button>
            <a href="{{ route('student.dashboard') }}"
               class="flex-1 text-center border-2 border-slate-200 hover:border-slate-300 hover:bg-slate-50 font-bold py-3.5 rounded-xl transition-colors text-[14px] text-gray-600 flex items-center justify-center gap-2">
                <i class="fas fa-xmark text-[12px]"></i>Bekor qilish
            </a>
        </div>
    </form>
</div>
@endsection
