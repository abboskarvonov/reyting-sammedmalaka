@extends('layouts.app')

@section('title', 'Dashboard')

@section('page-header')
    <div class="border-b border-slate-100" style="background:linear-gradient(135deg,#1E3A5F 0%,#1e4d8c 60%,#2563EB 100%)">
        <div class="max-w-5xl mx-auto px-6 py-7 flex items-center justify-between flex-wrap gap-3">
            <div>
                <h1 class="text-[24px] font-extrabold text-white m-0 leading-tight">
                    Salom, {{ session('student_name') }}!
                </h1>
                <p class="text-[13px] text-white/60 mt-1.5 m-0">
                    Guruh: <span class="text-white/80 font-medium">{{ $student->group->name ?? '—' }}</span>
                    &nbsp;·&nbsp;
                    <i class="fas fa-calendar-alt text-[11px] mr-0.5"></i>
                    <span class="text-white/80 font-medium">{{ $semesterStart->format('d.m.Y') }}</span>
                    &nbsp;—&nbsp;
                    <span class="text-white/80 font-medium">{{ $semesterEnd->format('d.m.Y') }}</span>
                </p>
            </div>
            <div class="flex items-center gap-2 text-[12px] text-white/70 bg-white/10 px-3 py-1.5 rounded-full">
                <i class="fas fa-graduation-cap text-[11px]"></i>
                Tinglovchi kabineti
            </div>
        </div>
    </div>
@endsection

@section('content')

@if($teachers->isEmpty())
    <div class="bg-white rounded-2xl p-12 text-center" style="box-shadow:0 2px 12px rgba(15,23,42,.07)">
        <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-chalkboard-user text-slate-400 text-2xl"></i>
        </div>
        <p class="text-[15px] font-semibold text-gray-700 m-0">Guruhingizga biriktirilgan o'qituvchilar topilmadi</p>
        <p class="text-[13px] text-gray-400 mt-1 m-0">Administrator bilan bog'laning</p>
    </div>
@else
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($teachers as $teacher)
            @php
                $allRated = $teacher->directions->every(fn($dir) =>
                    \App\Models\Rating::where([
                        'teacher_id'   => $teacher->id,
                        'direction_id' => $dir->id,
                        'student_id'   => $student->id,
                        'academic_year'=> $year,
                        'semester'     => $semester,
                    ])->exists()
                );
            @endphp

            <div class="bg-white rounded-2xl overflow-hidden flex flex-col" style="box-shadow:0 2px 12px rgba(15,23,42,.07)">
                {{-- Card header --}}
                <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 text-white font-bold text-[15px]"
                        style="background:linear-gradient(135deg,#1E3A5F,#2563EB)">
                        {{ mb_strtoupper(mb_substr($teacher->user->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-[14px] font-bold text-gray-900 m-0 truncate">{{ $teacher->user->name }}</p>
                        <p class="text-[12px] text-gray-400 m-0 truncate">{{ $teacher->department ?? '—' }}</p>
                    </div>
                    @if($allRated)
                        <span class="ml-auto shrink-0 text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">
                            <i class="fas fa-check mr-0.5"></i>Bajarildi
                        </span>
                    @endif
                </div>

                {{-- Directions --}}
                <div class="px-5 py-3 flex flex-col gap-2.5 flex-1">
                    @foreach($teacher->directions as $direction)
                        @php
                            $rated = \App\Models\Rating::where([
                                'teacher_id'   => $teacher->id,
                                'direction_id' => $direction->id,
                                'student_id'   => $student->id,
                                'academic_year'=> $year,
                                'semester'     => $semester,
                            ])->exists();
                        @endphp

                        <div class="flex items-center justify-between py-2 border-b border-slate-50 last:border-0">
                            <div class="flex items-center gap-2 min-w-0">
                                <i class="fas fa-bookmark text-[10px] text-blue-400 shrink-0"></i>
                                <span class="text-[13px] text-gray-700 font-medium truncate">{{ $direction->name }}</span>
                            </div>
                            @if($rated)
                                <span class="shrink-0 text-[11px] font-semibold text-emerald-600 flex items-center gap-1">
                                    <i class="fas fa-circle-check text-[10px]"></i>Baholangan
                                </span>
                            @else
                                <a href="{{ route('student.rate.show', [$teacher, 'direction_id' => $direction->id]) }}"
                                   class="shrink-0 inline-flex items-center gap-1.5 text-[12px] font-semibold text-white px-3 py-1.5 rounded-lg transition-opacity hover:opacity-90"
                                   style="background:linear-gradient(135deg,#1E3A5F,#2563EB)">
                                    <i class="fas fa-star text-[10px]"></i>Baholash
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endif

@endsection
