<?php

namespace App\Filament\Resources\Tasks\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('title')
                    ->label('Topshiriq nomi')
                    ->required()
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->label('Tavsif')
                    ->rows(3)
                    ->columnSpanFull()
                    ->default(null),

                DatePicker::make('due_date')
                    ->label('Muddat (oxirgi sana)')
                    ->displayFormat('d.m.Y')
                    ->native(false),

                Select::make('priority')
                    ->label('Muhimlik darajasi')
                    ->options([
                        'low'    => 'Past',
                        'medium' => "O'rta",
                        'high'   => 'Yuqori',
                    ])
                    ->default('medium')
                    ->required(),
            ]);
    }
}
