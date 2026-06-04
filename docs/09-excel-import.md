# 09 — Excel Import / Export

## Package

```bash
composer require maatwebsite/excel
php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider" --tag=config
```

---

## Import: Tinglovchilar ro'yxati

### Excel fayl formati

```
| A            | B              | C             |
|--------------|----------------|---------------|
| id_kod       | ism_familya    | telefon       |
| TLV-2024-001 | Aliyev Botir   | +998901234567 |
| TLV-2024-002 | Rahimova Zulfiya| +998907654321 |
```

> **Muhim:** Birinchi qator sarlavha bo'lishi shart.

---

### StudentsImport

```php
// app/Imports/StudentsImport.php
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;

class StudentsImport implements
    ToModel, WithHeadingRow, WithValidation,
    WithBatchInserts, WithChunkReading,
    SkipsOnError
{
    use SkipsErrors;

    public function __construct(
        private int $groupId,
        public array $errors = []
    ) {}

    public function model(array $row): ?Student
    {
        // Bo'sh qatorlarni o'tkazib yuborish
        if (empty(trim($row['id_kod'] ?? ''))) {
            return null;
        }

        return new Student([
            'student_id' => strtoupper(trim($row['id_kod'])),
            'full_name'  => trim($row['ism_familya']),
            'group_id'   => $this->groupId,
            'phone'      => !empty($row['telefon']) ? trim($row['telefon']) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'id_kod'      => ['required', 'string', 'unique:students,student_id'],
            'ism_familya' => ['required', 'string', 'max:255'],
            'telefon'     => ['nullable', 'string', 'max:20'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'id_kod.required'    => ':row-qator: ID-kod bo\'sh bo\'lmasligi kerak.',
            'id_kod.unique'      => ':row-qator: :attribute allaqachon mavjud.',
            'ism_familya.required' => ':row-qator: Ism-familya kiritilishi shart.',
        ];
    }

    public function batchSize(): int  { return 100; }
    public function chunkSize(): int  { return 500; }
}
```

---

### Import Controller

```php
// app/Http/Controllers/Admin/AdminStudentController.php

public function importForm(): View
{
    return view('admin.students.import', [
        'groups' => Group::where('is_active', true)->get(),
    ]);
}

public function import(Request $request): RedirectResponse
{
    $request->validate([
        'file'     => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        'group_id' => ['required', 'exists:groups,id'],
    ]);

    $import = new StudentsImport($request->group_id);

    Excel::import($import, $request->file('file'));

    $errorCount = count($import->errors());

    if ($errorCount > 0) {
        return back()
            ->with('warning', "Import tugadi. {$errorCount} ta qator xato bilan o'tkazib yuborildi.")
            ->with('import_errors', $import->errors());
    }

    return redirect()->route('admin.students.index')
        ->with('success', 'Tinglovchilar muvaffaqiyatli import qilindi.');
}
```

---

## Export: Tinglovchilar ro'yxati

```php
// app/Exports/StudentsExport.php
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentsExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    public function __construct(private ?int $groupId = null) {}

    public function query(): Builder
    {
        return Student::with('group')
            ->when($this->groupId, fn($q) => $q->where('group_id', $this->groupId))
            ->orderBy('group_id')
            ->orderBy('full_name');
    }

    public function headings(): array
    {
        return ['ID-kod', 'Ism-familya', 'Guruh', 'Telefon', 'Holati'];
    }

    public function map($student): array
    {
        return [
            $student->student_id,
            $student->full_name,
            $student->group->name ?? '—',
            $student->phone ?? '—',
            $student->is_active ? 'Faol' : 'Nofaol',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

// Controller'da ishlatish:
public function export(Request $request): BinaryFileResponse
{
    $filename = 'tinglovchilar-' . now()->format('Y-m-d') . '.xlsx';
    return Excel::download(new StudentsExport($request->group_id), $filename);
}
```

---

## Export: Baholash natijalari

```php
// app/Exports/RatingsExport.php
class RatingsExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    public function __construct(private array $filters = []) {}

    public function query(): Builder
    {
        return Rating::with(['teacher.user', 'subject', 'student.group'])
            ->when($this->filters['teacher_id'] ?? null, fn($q, $v) =>
                $q->where('teacher_id', $v)
            )
            ->when($this->filters['subject_id'] ?? null, fn($q, $v) =>
                $q->where('subject_id', $v)
            )
            ->when($this->filters['academic_year'] ?? null, fn($q, $v) =>
                $q->where('academic_year', $v)
            )
            ->latest();
    }

    public function headings(): array
    {
        return [
            'Sana', 'O\'qituvchi', 'Fan', 'Tinglovchi guruh',
            'O\'rtacha ball', 'O\'quv yili', 'Semestr'
        ];
    }

    public function map($rating): array
    {
        return [
            $rating->created_at->format('d.m.Y'),
            $rating->teacher->user->name,
            $rating->subject->name,
            $rating->student->group->name ?? '—',
            number_format($rating->total_score, 2),
            $rating->academic_year,
            $rating->semester . '-semestr',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
```

---

## Export: Davomat statistikasi

```php
// app/Exports/AttendanceExport.php
class AttendanceExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private int $teacherId,
        private string $period = 'month',
        private ?Carbon $date = null
    ) {}

    public function collection(): Collection
    {
        $this->date ??= now();
        $query = Attendance::where('teacher_id', $this->teacherId)
            ->orderBy('date');

        return match ($this->period) {
            'year'  => $query->whereYear('date', $this->date->year)->get(),
            'month' => $query->whereYear('date', $this->date->year)
                            ->whereMonth('date', $this->date->month)->get(),
            'week'  => $query->whereBetween('date', [
                            $this->date->startOfWeek(),
                            $this->date->endOfWeek()
                        ])->get(),
            default => $query->get(),
        };
    }

    public function headings(): array
    {
        return ['Sana', 'Holat', 'Kirish vaqti', 'Kechikish (daqiqa)', 'Sabab'];
    }

    public function map($row): array
    {
        $status = match ($row->status) {
            'on_time' => "O'z vaqtida",
            'late'    => 'Kechikdi',
            'excused' => 'Sababli',
            'absent'  => 'Sababsiz',
        };

        return [
            $row->date->format('d.m.Y'),
            $status,
            $row->check_in_time ?? '—',
            $row->late_minutes ?: '—',
            $row->reason ?? '—',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
```

---

## Excel shablon yuklab olish

```php
// app/Http/Controllers/Admin/AdminStudentController.php
public function downloadTemplate(): BinaryFileResponse
{
    return Excel::download(new StudentsTemplateExport(), 'tinglovchi-shablon.xlsx');
}

// app/Exports/StudentsTemplateExport.php
class StudentsTemplateExport implements FromArray, WithHeadings, WithStyles
{
    public function array(): array
    {
        return [
            ['TLV-2024-001', 'Aliyev Botir Raximovich', '+998901234567'],
            ['TLV-2024-002', 'Rahimova Zulfiya Karimovna', '+998907654321'],
        ];
    }

    public function headings(): array
    {
        return ['id_kod', 'ism_familya', 'telefon'];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '2563EB']],
            ],
        ];
    }
}
```

---

## Import sahifasi (Blade)

```blade
{{-- resources/views/admin/students/import.blade.php --}}
<x-admin-layout>
    <div class="max-w-2xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">Tinglovchilarni Import Qilish</h1>

        {{-- Shablon yuklab olish --}}
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
            <p class="font-medium text-blue-800 mb-2">Excel shabloni</p>
            <p class="text-sm text-blue-600 mb-3">
                To'ldirish uchun shablonni yuklab oling.
                Sarlavhalar o'zgartirilmasin.
            </p>
            <a href="{{ route('admin.students.template') }}"
               class="inline-block bg-blue-600 text-white px-4 py-2 rounded-lg text-sm">
                Shablonni yuklab olish
            </a>
        </div>

        {{-- Import form --}}
        <form method="POST"
              action="{{ route('admin.students.import') }}"
              enctype="multipart/form-data"
              class="bg-white rounded-xl shadow p-6 space-y-4">
            @csrf

            <div>
                <label class="font-medium">Guruh *</label>
                <select name="group_id" required class="mt-1 w-full border rounded-lg px-3 py-2">
                    <option value="">— Guruhni tanlang —</option>
                    @foreach ($groups as $group)
                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                    @endforeach
                </select>
                @error('group_id') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="font-medium">Excel fayl *</label>
                <input type="file"
                       name="file"
                       accept=".xlsx,.xls,.csv"
                       required
                       class="mt-1 w-full border rounded-lg px-3 py-2">
                <p class="text-xs text-gray-400 mt-1">Maksimal: 5MB. Format: .xlsx, .xls, .csv</p>
                @error('file') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
            </div>

            <button type="submit"
                    class="w-full bg-green-600 text-white py-3 rounded-lg font-semibold hover:bg-green-700">
                Import qilish
            </button>
        </form>

        {{-- Import xatolari --}}
        @if (session('import_errors'))
            <div class="mt-4 bg-yellow-50 border border-yellow-200 rounded-xl p-4">
                <p class="font-medium text-yellow-800 mb-2">Xatoliklar:</p>
                <ul class="text-sm text-yellow-700 space-y-1 list-disc list-inside">
                    @foreach (session('import_errors') as $error)
                        <li>{{ $error->getMessage() }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</x-admin-layout>
```
