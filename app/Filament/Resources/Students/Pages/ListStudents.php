<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Imports\StudentImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ── Shablon yuklab olish ──────────────────────────────────
            Action::make('downloadTemplate')
                ->label('Shablon')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(route('students.import-template'))
                ->openUrlInNewTab(),

            // ── Excel import ──────────────────────────────────────────
            Action::make('importExcel')
                ->label('Excel import')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->modalHeading('Tinglovchilarni Excel orqali import qilish')
                ->modalDescription(
                    'Faqat .xlsx / .xls fayllari qabul qilinadi. '
                    . 'Sarlavha qatori (1-qator): '
                    . 'ism_familiya | id_code | muassasa_nomi | telefon | diplom_raqam | '
                    . 'passport_seriya_raqam | PINFL | group | start_date | end_date. '
                    . 'Sana formati: 01.05.2026'
                )
                ->modalSubmitActionLabel('Import qilish')
                ->form([
                    FileUpload::make('file')
                        ->label('Excel fayl (.xlsx)')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'application/octet-stream',
                        ])
                        ->maxSize(10240)
                        ->disk('local')
                        ->directory('imports/students')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $filePath = Storage::disk('local')->path($data['file']);

                    $import = new StudentImport();
                    Excel::import($import, $filePath);

                    // Vaqtinchalik faylni o'chirish
                    Storage::disk('local')->delete($data['file']);

                    $body = "Muvaffaqiyatli import: {$import->imported} ta tinglovchi.";
                    if ($import->skipped > 0) {
                        $body .= " O'tkazib yuborildi: {$import->skipped} ta qator.";
                    }

                    if ($import->imported > 0) {
                        Notification::make()
                            ->success()
                            ->title('Import yakunlandi')
                            ->body($body)
                            ->send();
                    } else {
                        Notification::make()
                            ->warning()
                            ->title('Hech narsa import qilinmadi')
                            ->body(
                                ! empty($import->errors)
                                    ? implode(' | ', array_slice($import->errors, 0, 3))
                                    : "Fayl bo'sh yoki format noto'g'ri."
                            )
                            ->send();
                    }
                }),

            CreateAction::make(),
        ];
    }
}
