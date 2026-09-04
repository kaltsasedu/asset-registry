<?php

namespace App\Filament\Resources\AssetCategories\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class AssetCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                TextInput::make('name')
                    ->label('Όνομα Κατηγορίας')
                    ->required()
                    ->maxLength(255),

                TextInput::make('default_rate')
                    ->label('Συντελεστής Απόσβεσης (%)')
                    ->numeric()
                    ->step('0.01'),

                Select::make('default_depreciation_method')->label('Προεπιλεγμένη μέθοδος απόσβεσης')->options([
                    'straight_line' => 'Σταθερή', 'declining_balance' => 'Φθίνουσα', 'none' => 'Μηδενική',
                ])->default('straight_line')->required(),

                Select::make('default_depreciation_timing')->label('Χρονισμός απόσβεσης')->options([
                    'full_month' => 'Πλήρης μήνας', 'next_month' => 'Από τον επόμενο μήνα',
                ])->default('full_month')->required(),

                Textarea::make('description')
                    ->label('Περιγραφή')
                    ->rows(4)
                    ->columnSpanFull(),

            ]);
    }
}
