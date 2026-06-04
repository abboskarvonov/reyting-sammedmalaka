<?php

namespace App\Filament\Resources\Ratings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class RatingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('teacher_id')
                    ->label("O'qituvchi")
                    ->relationship('teacher', 'id')
                    ->required(),
                Select::make('direction_id')
                    ->label("Yo'nalish")
                    ->relationship('direction', 'name')
                    ->required(),
                Select::make('student_id')
                    ->label('Talaba')
                    ->relationship('student', 'id')
                    ->required(),
                TextInput::make('academic_year')
                    ->label("O'quv yili")
                    ->required(),
                Select::make('semester')
                    ->label('Semestr')
                    ->options(['1' => '1-semestr', '2' => '2-semestr'])
                    ->default('1')
                    ->required(),
                TextInput::make('total_score')
                    ->label('Umumiy ball')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                Textarea::make('comment')
                    ->label('Izoh')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
