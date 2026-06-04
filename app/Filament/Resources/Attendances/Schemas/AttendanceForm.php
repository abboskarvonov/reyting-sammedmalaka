<?php

namespace App\Filament\Resources\Attendances\Schemas;

use App\Models\Teacher;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class AttendanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('teacher_id')
                    ->label("O'qituvchi")
                    ->required()
                    ->searchable()
                    ->live()
                    ->getSearchResultsUsing(fn (string $search): array => Teacher::with('user')
                        ->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                        ->get()
                        ->pluck('user.name', 'id')
                        ->toArray()
                    )
                    ->getOptionLabelUsing(fn ($value): string => Teacher::with('user')->find($value)?->user?->name ?? ''),
                DatePicker::make('date')
                    ->label('Sana')
                    ->required()
                    ->live()
                    ->default(today()->toDateString())
                    ->rules(fn ($get, $record) => [
                        Rule::unique('attendances', 'date')
                            ->where('teacher_id', $get('teacher_id'))
                            ->ignore($record?->id),
                    ])
                    ->validationMessages([
                        'unique' => 'Bu o\'qituvchi uchun ushbu sanada davomat allaqachon kiritilgan.',
                    ]),
                Select::make('status')
                    ->label('Holat')
                    ->options([
                        'on_time' => "O'z vaqtida",
                        'late' => 'Kechikdi',
                        'excused' => 'Sababli kelmadi',
                        'absent' => 'Sababsiz kelmadi',
                    ])
                    ->default('absent')
                    ->required()
                    ->live(),
                TimePicker::make('check_in_time')
                    ->label('Kirish vaqti')
                    ->visible(fn ($get) => in_array($get('status'), ['on_time', 'late'])),
                TextInput::make('late_minutes')
                    ->label('Kechikish (daqiqa)')
                    ->numeric()
                    ->default(0)
                    ->visible(fn ($get) => $get('status') === 'late'),
                Textarea::make('reason')
                    ->label('Sabab')
                    ->default(null)
                    ->columnSpanFull()
                    ->visible(fn ($get) => $get('status') === 'excused'),
            ]);
    }
}
