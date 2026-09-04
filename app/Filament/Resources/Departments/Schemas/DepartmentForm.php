<?php

namespace App\Filament\Resources\Departments\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DepartmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                TextInput::make('name')
                    ->label('Όνομα Τμήματος')
                    ->required(),

                Textarea::make('description')
                    ->label('Περιγραφή')
                    ->rows(3),

            ]);
    }
}