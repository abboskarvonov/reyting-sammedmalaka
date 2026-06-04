<?php

namespace App\Filament\Resources\Students\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StudentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // ── Asosiy ma'lumotlar ────────────────────────────────
                Section::make("Asosiy ma'lumotlar")
                    ->columns(2)
                    ->schema([
                        TextInput::make('student_id')
                            ->label('ID-kod')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('TLV-2026-001'),
                        TextInput::make('full_name')
                            ->label('Ism-familiya')
                            ->required(),
                        Select::make('group_id')
                            ->label('Guruh')
                            ->relationship('group', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('Guruh sanalarini guruh ro\'yxatidan tahrirlash mumkin'),
                        TextInput::make('phone')
                            ->label('Telefon')
                            ->tel()
                            ->default(null),
                    ]),

                // ── Guruh davri (o'qish sanasi) ───────────────────────
                Section::make("O'qish davri")
                    ->columns(2)
                    ->schema([
                        Placeholder::make('group_starts_at')
                            ->label('Boshlanish sanasi')
                            ->content(fn ($record) => $record?->group?->starts_at?->format('d.m.Y') ?? '—'),
                        Placeholder::make('group_ends_at')
                            ->label('Tugash sanasi')
                            ->content(fn ($record) => $record?->group?->ends_at?->format('d.m.Y') ?? '—'),
                    ])
                    ->visibleOn('edit'),

                // ── Ta'lim ma'lumotlari ───────────────────────────────
                Section::make("Ta'lim hujjatlari")
                    ->columns(2)
                    ->schema([
                        TextInput::make('muassasa_nomi')
                            ->label('Muassasa nomi')
                            ->placeholder('Samarqand DTI')
                            ->default(null),
                        TextInput::make('diplom_raqam')
                            ->label('Diplom raqami')
                            ->placeholder('DIP-2024-001')
                            ->default(null),
                    ]),

                // ── Shaxsiy hujjatlar ─────────────────────────────────
                Section::make('Shaxsiy hujjatlar')
                    ->columns(2)
                    ->schema([
                        TextInput::make('passport_seriya_raqam')
                            ->label('Pasport seriya/raqam')
                            ->placeholder('AA1234567')
                            ->default(null),
                        TextInput::make('pinfl')
                            ->label('PINFL')
                            ->placeholder('12345678901234')
                            ->maxLength(14)
                            ->default(null),
                    ]),

                // ── Holat ─────────────────────────────────────────────
                Section::make('Holat')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Faol')
                            ->default(true),
                    ]),
            ]);
    }
}
