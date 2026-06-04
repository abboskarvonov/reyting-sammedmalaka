@extends('layouts.app')

@section('title', "QR — {{ $teacher->user->name }}")

@section('content')
<div class="max-w-md mx-auto text-center">
    <div class="bg-white rounded-xl shadow p-8">
        <h1 class="text-2xl font-bold mb-1">{{ $teacher->user->name }}</h1>
        <p class="text-gray-500 text-sm mb-6">{{ $teacher->department ?? $teacher->position ?? '' }}</p>

        <div class="flex justify-center mb-6">
            <img src="{{ route('teacher.qr.image', $teacher->qr_token) }}"
                 alt="QR kod"
                 class="w-56 h-56 border border-gray-200 rounded-lg p-2">
        </div>

        <p class="text-sm text-gray-500 mb-4">
            QR kodni skanerlang yoki quyidagi havoladan o'ting:
        </p>
        <a href="{{ $ratingUrl }}" class="text-blue-600 text-sm break-all hover:underline">
            {{ $ratingUrl }}
        </a>

        <div class="mt-6 border-t pt-4">
            <p class="text-xs text-gray-400">Yo'nalishlar:</p>
            <div class="flex flex-wrap gap-2 mt-2 justify-center">
                @foreach($teacher->directions as $direction)
                    <span class="bg-blue-100 text-blue-700 text-xs px-2 py-1 rounded-full">
                        {{ $direction->name }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
