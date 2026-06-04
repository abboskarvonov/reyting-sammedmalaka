<?php

use App\Exports\StudentImportTemplate;
use App\Http\Controllers\PublicStatsController;
use App\Http\Controllers\StudentAuthController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\StudentRatingController;
use App\Http\Controllers\TeacherQrController;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;

// Public stats
Route::get('/', [PublicStatsController::class, 'index'])->name('public.stats');
Route::get('/ratings', [PublicStatsController::class, 'ratings'])->name('public.ratings');
Route::get('/attendance', [PublicStatsController::class, 'attendance'])->name('public.attendance');
Route::get('/tasks', [PublicStatsController::class, 'tasks'])->name('public.tasks');

// Student auth
Route::prefix('student')->name('student.')->group(function () {
    Route::get('login', [StudentAuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [StudentAuthController::class, 'login'])->name('login.post');
    Route::post('logout', [StudentAuthController::class, 'logout'])->name('logout');

    Route::middleware('student')->group(function () {
        Route::get('dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
        Route::get('rate/{teacher}', [StudentRatingController::class, 'show'])->name('rate.show');
        Route::post('rate/{teacher}', [StudentRatingController::class, 'store'])->name('rate.store');
    });
});

// Admin: tinglovchilar import shabloni (faqat autentifikatsiyadan o'tganlar)
Route::get('/admin/tinglovchilar/shablon', function () {
    return Excel::download(new StudentImportTemplate(), 'tinglovchilar_shablon.xlsx');
})->middleware(['web', 'auth'])->name('students.import-template');

// Teacher QR pages (public — students scan from printed QR)
Route::prefix('qr')->name('teacher.')->group(function () {
    Route::get('{token}', [TeacherQrController::class, 'show'])->name('qr');
    Route::get('{token}/image', [TeacherQrController::class, 'qrImage'])->name('qr.image');
});
