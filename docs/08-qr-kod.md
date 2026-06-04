# 08 — QR-kod Tizimi

## Qanday ishlaydi?

```
Admin → O'qituvchi profili yaratilinganda qr_token avtomatik generatsiya bo'ladi
             │
             ▼
Teacher → /teacher/qr-code sahifasida o'z QR kodini ko'radi va chop etadi
             │
             ▼
Tinglovchi → Telefon kamerasi bilan skanerlaydi
             │
             ▼
URL ochiladi: https://yourapp.com/rate/{token}
             │
             ▼
Tinglovchi ID kiritadi → Baholash sahifasi ochiladi
```

---

## Package o'rnatish

```bash
composer require simplesoftwareio/simple-qrcode
```

---

## QR-kod generatsiya (Service)

```php
// app/Services/QrCodeService.php
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    public function generate(Teacher $teacher): string
    {
        $url = route('rating.qr', ['token' => $teacher->qr_token]);

        return QrCode::format('svg')
            ->size(300)
            ->errorCorrection('H')       // yuqori xato tuzatish
            ->margin(2)
            ->generate($url);
    }

    public function generatePng(Teacher $teacher): string
    {
        $url = route('rating.qr', ['token' => $teacher->qr_token]);

        return QrCode::format('png')
            ->size(400)
            ->errorCorrection('H')
            ->margin(2)
            ->generate($url);
    }

    public function regenerateToken(Teacher $teacher): Teacher
    {
        $teacher->update(['qr_token' => Str::random(32)]);
        return $teacher;
    }
}
```

---

## QR sahifasini ko'rsatish (o'qituvchi uchun)

```php
// app/Http/Controllers/Teacher/TeacherQrController.php
class TeacherQrController extends Controller
{
    public function show(QrCodeService $qrService): View
    {
        $teacher = auth()->user()->teacher;

        return view('teacher.qr', [
            'teacher' => $teacher->load('user', 'subjects'),
            'qrSvg'   => $qrService->generate($teacher),
            'qrUrl'   => route('rating.qr', ['token' => $teacher->qr_token]),
        ]);
    }

    public function regenerate(QrCodeService $qrService): RedirectResponse
    {
        $teacher = auth()->user()->teacher;
        $qrService->regenerateToken($teacher);

        return back()->with('success', 'QR-kod yangilandi. Eski QR-kod endi ishlamaydi.');
    }

    public function download(QrCodeService $qrService): Response
    {
        $teacher = auth()->user()->teacher;
        $png     = $qrService->generatePng($teacher);

        return response($png)
            ->header('Content-Type', 'image/png')
            ->header('Content-Disposition',
                'attachment; filename="qr-' . $teacher->employee_id . '.png"'
            );
    }
}
```

---

## QR token orqali kirish (tinglovchi)

```php
// app/Http/Controllers/RatingController.php
class RatingController extends Controller
{
    public function showQr(string $token): View|RedirectResponse
    {
        $teacher = Teacher::where('qr_token', $token)
            ->where('is_archived', false)
            ->with(['user', 'subjects'])
            ->firstOrFail();

        // Agar tinglovchi sessiyada bo'lsa, to'g'ridan dashboard'ga
        if (session()->has('student_id')) {
            return redirect()->route('student.dashboard')
                ->with('info', "O'qituvchi: {$teacher->user->name}. Fanini tanlang.");
        }

        // Aks holda login sahifasiga token bilan redirect
        return redirect()->route('student.login')
            ->with('teacher', $teacher)
            ->withInput(['qr_token' => $token]);
    }
}
```

---

## O'qituvchi QR sahifasi (Blade)

```blade
{{-- resources/views/teacher/qr.blade.php --}}
<x-teacher-layout>
    <div class="max-w-lg mx-auto py-10 px-4 text-center">
        <h1 class="text-2xl font-bold mb-2">Mening QR-kodim</h1>
        <p class="text-gray-500 mb-6">
            Tinglovchilar ushbu QR-kodni skanerlaydi va baholashni amalga oshiradi
        </p>

        {{-- QR Kod --}}
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-6 inline-block">
            {!! $qrSvg !!}
        </div>

        {{-- Ma'lumotlar --}}
        <div class="bg-gray-50 rounded-xl p-4 mb-6 text-left space-y-2">
            <p><span class="font-medium">O'qituvchi:</span> {{ $teacher->user->name }}</p>
            <p><span class="font-medium">Fanlar:</span>
                {{ $teacher->subjects->pluck('name')->join(', ') ?: 'Belgilanmagan' }}
            </p>
            <p class="text-xs text-gray-400 break-all">
                <span class="font-medium">URL:</span> {{ $qrUrl }}
            </p>
        </div>

        {{-- Tugmalar --}}
        <div class="flex gap-3 justify-center">
            <a href="{{ route('teacher.qr.download') }}"
               class="bg-blue-600 text-white px-5 py-2.5 rounded-lg hover:bg-blue-700 font-medium">
                PNG yuklab olish
            </a>
            <form method="POST" action="{{ route('teacher.qr.regenerate') }}">
                @csrf
                <button type="submit"
                        onclick="return confirm('Yangi QR yaratilsa, eski QR ishlamay qoladi. Davom etasizmi?')"
                        class="bg-gray-200 text-gray-800 px-5 py-2.5 rounded-lg hover:bg-gray-300 font-medium">
                    Yangilash
                </button>
            </form>
        </div>

        {{-- Chop etish (print) --}}
        <div class="mt-4">
            <button onclick="window.print()"
                    class="text-gray-500 underline text-sm hover:text-gray-700">
                Chop etish (print)
            </button>
        </div>
    </div>
</x-teacher-layout>
```

---

## Admin — barcha o'qituvchilar QR kodlarini PDF'ga export

```php
// app/Http/Controllers/Admin/AdminTeacherController.php (qo'shimcha method)
public function exportQrCodes(QrCodeService $qrService): Response
{
    $teachers = Teacher::active()->with('user')->get();

    $pdf = Pdf::loadView('admin.teachers.qr-export', [
        'teachers' => $teachers,
        'qrCodes'  => $teachers->mapWithKeys(fn($t) => [
            $t->id => $qrService->generate($t)
        ]),
    ]);

    return $pdf->download('qr-kodlar-' . now()->format('Y-m-d') . '.pdf');
}
```

---

## Xavfsizlik

| Xatar                  | Yechim                                      |
| ---------------------- | ------------------------------------------- |
| Token taxmin qilish    | `Str::random(32)` — 32 belgili random token |
| Eski QR ishlatish      | Token regeneratsiya imkoniyati              |
| Arxivlangan o'qituvchi | `is_archived` tekshiruvi                    |
| Brute force            | Route throttle: `throttle:20,1`             |

```php
// QR route uchun throttle
Route::get('/rate/{token}', [RatingController::class, 'showQr'])
    ->middleware('throttle:20,1')
    ->name('rating.qr');
```

---

## Routes (qo'shimcha)

```php
// Teacher QR routes
Route::middleware(['auth', 'role:teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/qr-code', [TeacherQrController::class, 'show'])->name('qr.show');
    Route::post('/qr-code/regenerate', [TeacherQrController::class, 'regenerate'])->name('qr.regenerate');
    Route::get('/qr-code/download', [TeacherQrController::class, 'download'])->name('qr.download');
});

// Admin QR export
Route::get('admin/teachers/qr-export', [AdminTeacherController::class, 'exportQrCodes'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.teachers.qr-export');
```
