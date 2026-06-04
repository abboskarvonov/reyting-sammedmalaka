@extends('layouts.app')

@section('title', 'Tinglovchi kirishi')

@section('page-header')
    <div class="border-b border-slate-100" style="background:linear-gradient(135deg,#1E3A5F 0%,#1e4d8c 60%,#2563EB 100%)">
        <div class="max-w-5xl mx-auto px-6 py-7">
            <h1 class="text-[24px] font-extrabold text-white m-0 leading-tight">Tinglovchi kirishi</h1>
            <p class="text-[13px] text-white/60 mt-1.5 m-0">O'qituvchilarni baholash tizimiga kirish</p>
        </div>
    </div>
@endsection

@section('content')
<div class="max-w-md mx-auto">
    <div class="bg-white rounded-2xl overflow-hidden" style="box-shadow:0 4px 24px rgba(15,23,42,.10)">

        {{-- Top accent --}}
        <div class="h-1.5" style="background:linear-gradient(90deg,#1E3A5F,#2563EB,#3B82F6)"></div>

        <div class="p-8">
            {{-- Icon --}}
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-6"
                style="background:linear-gradient(135deg,#EFF6FF,#DBEAFE)">
                <i class="fas fa-id-card text-blue-600 text-xl"></i>
            </div>

            <h2 class="text-[18px] font-bold text-gray-900 m-0 mb-1">Tizimga kirish</h2>
            <p class="text-[13px] text-gray-400 m-0 mb-6">ID-kodingizni kiriting</p>

            <form method="POST" action="{{ route('student.login.post') }}">
                @csrf
                <div class="mb-5">
                    <label for="student_id" class="block text-[12px] font-semibold text-gray-500 uppercase tracking-wider mb-2">
                        Tinglovchi ID-kodi
                    </label>
                    <input
                        type="text"
                        id="student_id"
                        name="student_id"
                        value="{{ old('student_id') }}"
                        placeholder="Masalan: TLV-2024-001"
                        class="w-full bg-slate-50 border-2 border-slate-200 rounded-xl px-4 py-3 text-[14px] font-semibold text-gray-900 outline-none focus:border-blue-500 focus:bg-white transition-all placeholder-gray-300 tracking-wider
                               @error('student_id') border-red-400 bg-red-50 @enderror"
                        autofocus
                    >
                    @error('student_id')
                        <p class="mt-2 text-[12px] text-red-600 font-medium flex items-center gap-1.5">
                            <i class="fas fa-circle-exclamation text-[10px]"></i>{{ $message }}
                        </p>
                    @enderror
                </div>

                <button type="submit"
                    class="w-full text-white font-bold py-3 rounded-xl transition-opacity hover:opacity-90 text-[14px] flex items-center justify-center gap-2"
                    style="background:linear-gradient(135deg,#1E3A5F,#2563EB)">
                    <i class="fas fa-right-to-bracket text-[12px]"></i>
                    Tizimga kirish
                </button>
            </form>

            <p class="text-center text-[12px] text-gray-400 mt-6 m-0">
                <i class="fas fa-info-circle text-blue-400 mr-1"></i>
                ID-kodingizni o'quv yurtingizdan yoki o'qituvchingizdan oling
            </p>
        </div>
    </div>
</div>
@endsection
