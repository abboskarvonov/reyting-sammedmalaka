<?php

namespace App\Imports;

use App\Models\Group;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public int   $imported = 0;
    public int   $skipped  = 0;
    public array $errors   = [];

    /**
     * Kutilayotgan Excel ustun nomlari (1-qator sarlavhalar):
     *
     *  ism_familiya          → full_name
     *  id_code               → student_id
     *  muassasa_nomi         → muassasa_nomi
     *  telefon               → phone
     *  diplom_raqam          → diplom_raqam
     *  passport_seriya_raqam → passport_seriya_raqam
     *  PINFL                 → pinfl
     *  group                 → guruh nomi
     *  start_date            → group.starts_at  (guruh boshlanish sanasi)
     *  end_date              → group.ends_at    (guruh tugash sanasi)
     *
     * Sana formati: 01.05.2026
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            try {
                $arr = $row instanceof Collection ? $row->toArray() : (array) $row;
                $this->processRow($arr, $index + 2);
            } catch (\Throwable $e) {
                $this->skipped++;
                $this->errors[] = sprintf('%d-qator: %s', $index + 2, $e->getMessage());
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    private function processRow(array $row, int $lineNumber): void
    {
        $studentId = trim($row['id_code']       ?? '');
        $fullName  = trim($row['ism_familiya']  ?? '');
        $groupName = trim($row['group']         ?? '');

        if ($studentId === '' || $fullName === '' || $groupName === '') {
            $this->skipped++;
            $this->errors[] = "{$lineNumber}-qator: id_code, ism_familiya yoki group bo'sh.";
            return;
        }

        // start_date / end_date → faqat GURUH uchun ishlatiladi
        $startDate = $this->parseDate($row['start_date'] ?? null);
        $endDate   = $this->parseDate($row['end_date']   ?? null);

        // Guruhni top yoki yarat, sanalarini yangilab qo'y
        $group = $this->resolveGroup($groupName, $startDate, $endDate);

        Student::withTrashed()->updateOrCreate(
            ['student_id' => $studentId],
            [
                'full_name'             => $fullName,
                'group_id'              => $group->id,
                'phone'                 => $this->nullable($row['telefon']               ?? null),
                'muassasa_nomi'         => $this->nullable($row['muassasa_nomi']         ?? null),
                'diplom_raqam'          => $this->nullable($row['diplom_raqam']          ?? null),
                'passport_seriya_raqam' => $this->nullable($row['passport_seriya_raqam'] ?? null),
                'pinfl'                 => $this->nullable($row['pinfl']                 ?? null),
                'is_active'             => true,
                'deleted_at'            => null,
            ]
        );

        $this->imported++;
    }

    // ─────────────────────────────────────────────────────────────────────
    /**
     * Guruhni topish / yaratish / sanalarini yangilash mantigi:
     *
     * 1. Xuddi shu nom va xuddi shu sanalar → mavjud guruhni qaytaradi (hech narsa o'zgarmaydi).
     * 2. Xuddi shu nom, BOSHQA sanalar → mavjud guruhning starts_at / ends_at ni yangilaydi.
     *    (Guruh yangi davr uchun "ko'chiriladi".)
     * 3. Nom topilmasa → yangi guruh yaratiladi.
     *
     * Sana keltirilmagan bo'lsa → mavjud guruh ishlatiladi yoki sana ko'rsatilmagan yangi guruh yaratiladi.
     */
    private function resolveGroup(string $name, ?Carbon $starts, ?Carbon $ends): Group
    {
        $group = Group::where('name', $name)->first();

        if ($group) {
            // Sanalar keltirilgan bo'lsa va farq qilsa — yangilaymiz
            if ($starts && $ends) {
                $needsUpdate =
                    $group->starts_at?->toDateString() !== $starts->toDateString() ||
                    $group->ends_at?->toDateString()   !== $ends->toDateString();

                if ($needsUpdate) {
                    $group->update([
                        'starts_at' => $starts,
                        'ends_at'   => $ends,
                        'is_active' => true,
                    ]);
                }
            }

            return $group;
        }

        // Yangi guruh yaratish
        return Group::create([
            'name'      => $name,
            'code'      => $this->uniqueGroupCode($name),
            'starts_at' => $starts ?? now(),
            'ends_at'   => $ends   ?? now()->addMonths(1),
            'is_active' => true,
        ]);
    }

    private function uniqueGroupCode(string $name): string
    {
        $base = 'GR-' . strtoupper(Str::slug($name, '-'));
        $base = substr($base, 0, 30);
        $code = $base;
        $i    = 1;

        while (Group::where('code', $code)->exists()) {
            $code = $base . '-' . $i++;
        }

        return $code;
    }

    // ─────────────────────────────────────────────────────────────────────
    /**
     * Sana formatlari qabul qilinadi:
     *   "01.05.2026"  d.m.Y   ← asosiy format
     *   "2026-05-01"  Y-m-d
     *   46152         Excel serial number
     */
    private function parseDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        $str = trim((string) $value);

        if (preg_match('/^\d{1,2}\.\d{1,2}\.\d{4}$/', $str)) {
            try {
                return Carbon::createFromFormat('d.m.Y', $str)->startOfDay();
            } catch (\Throwable) {}
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $str)) {
            try {
                return Carbon::createFromFormat('Y-m-d', $str)->startOfDay();
            } catch (\Throwable) {}
        }

        if (is_numeric($str) && (int) $str > 40000) {
            try {
                $dto = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $str);
                return Carbon::instance($dto)->startOfDay();
            } catch (\Throwable) {}
        }

        try {
            return Carbon::parse($str)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function nullable(mixed $value): ?string
    {
        $v = trim((string) ($value ?? ''));
        return $v === '' ? null : $v;
    }
}
