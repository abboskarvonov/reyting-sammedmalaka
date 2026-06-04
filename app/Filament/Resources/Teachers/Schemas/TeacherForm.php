<?php

namespace App\Filament\Resources\Teachers\Schemas;

use App\Models\Direction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TeacherForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make("Shaxsiy ma'lumotlar")
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Ism-familya')
                            ->required()
                            ->dehydrated(false)
                            ->afterStateHydrated(fn($component, $record) =>
                                $component->state($record?->user?->name)
                            ),
                    ]),

                Section::make("Ish ma'lumotlari")
                    ->columns(2)
                    ->schema([
                        TextInput::make('employee_id')
                            ->label('Xodim ID-kodi')
                            ->required(),
                        TextInput::make('position')
                            ->label('Lavozim')
                            ->default(null),
                        TextInput::make('department')
                            ->label("Bo'lim")
                            ->default(null),
                        TextInput::make('phone')
                            ->label('Telefon')
                            ->tel()
                            ->default(null),
                    ]),

                Section::make("Yo'nalishlar")
                    ->schema([
                        Select::make('directions')
                            ->label("Biriktiriladigan yo'nalishlar")
                            ->relationship('directions', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable(),
                    ]),

                Section::make('Holat')
                    ->schema([
                        Toggle::make('is_archived')
                            ->label('Arxivlangan')
                            ->default(false),
                    ]),
            ]);
    }
}
