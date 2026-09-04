<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                TextInput::make('name')
                    ->label('Επωνυμία')
                    ->required()
                    ->maxLength(255),

                TextInput::make('afm')
                    ->label('ΑΦΜ')
                    ->maxLength(20),

                TextInput::make('email')
                    ->label('Ηλεκτρονική Διεύθυνση')
                    ->email(),

                TextInput::make('phone')
                    ->label('Τηλέφωνο'),

                Textarea::make('address')
                    ->label('Διεύθυνση')
                    ->rows(3)
                    ->columnSpanFull(),

            ]);
    }
}
