<?php

namespace App\Filament\Resources\Groups\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class GroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Guruh nomi')
                    ->required(),
                TextInput::make('code')
                    ->label('Kod')
                    ->required()
                    ->unique(ignoreRecord: true),
                DatePicker::make('starts_at')
                    ->label('Boshlanish sanasi')
                    ->required()
                    ->native(false)
                    ->displayFormat('d.m.Y'),
                DatePicker::make('ends_at')
                    ->label('Tugash sanasi')
                    ->required()
                    ->native(false)
                    ->displayFormat('d.m.Y')
                    ->after('starts_at'),
                Toggle::make('is_active')
                    ->label('Faol')
                    ->default(true),
            ]);
    }
}
