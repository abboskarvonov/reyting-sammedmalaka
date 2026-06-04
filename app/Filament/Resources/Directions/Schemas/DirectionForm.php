<?php

namespace App\Filament\Resources\Directions\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DirectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Yo\'nalish nomi')
                    ->required()
                    ->maxLength(255),
                TextInput::make('unique_key')
                    ->label('Unikal kalit')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(50)
                    ->helperText('Masalan: KRD, JRR, PDT — katta harflar bilan'),
            ]);
    }
}
