<?php

namespace App\Filament\Resources\Locations\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LocationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                TextInput::make('name')
                    ->label('Όνομα Τοποθεσίας')
                    ->required(),

                TextInput::make('building')
                    ->label('Κτίριο'),

                TextInput::make('floor')
                    ->label('Όροφος'),

                TextInput::make('room')
                    ->label('Γραφείο'),

            ]);
    }
}