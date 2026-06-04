<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentImportTemplate implements
    FromArray,
    WithHeadings,
    WithStyles,
    ShouldAutoSize,
    WithTitle
{
    public function title(): string
    {
        return 'Tinglovchilar';
    }

    /**
     * Ustun sarlavhalari — import qilishda aynan shu nomlar ishlatiladi.
     */
    public function headings(): array
    {
        return [
            'ism_familiya',
            'id_code',
            'muassasa_nomi',
            'telefon',
            'diplom_raqam',
            'passport_seriya_raqam',
            'PINFL',
            'group',
            'start_date',
            'end_date',
        ];
    }

    /**
     * Namuna qator (1 ta misol).
     * Sana formati: kun.oy.yil — masalan: 01.05.2026
     */
    public function array(): array
    {
        return [
            [
                'Abdullayev Jasur Bahodir o\'g\'li',
                'TLV-2026-001',
                'Samarqand DTI',
                '+998901234567',
                'DIP-2024-00123',
                'AA1234567',
                '12345678901234',
                'Kardiologiya I guruh',
                '01.05.2026',
                '30.05.2026',
            ],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $headerCount = count($this->headings());
        $lastCol     = chr(ord('A') + $headerCount - 1); // 'J' for 10 columns

        // Sarlavha qatori uslubi
        return [
            1 => [
                'font' => [
                    'bold'  => true,
                    'color' => ['argb' => 'FFFFFFFF'],
                    'size'  => 11,
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1A3A5C'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                    'wrapText'   => true,
                ],
            ],
            // Namuna qator
            2 => [
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFF0F7F8'],
                ],
            ],
        ];
    }
}
