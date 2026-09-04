<?php

namespace App\Filament\Widgets;

use App\Models\Asset;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\InventoryCycle;

class AssetStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [

            Stat::make(
                'Συνολικά Πάγια',
                Asset::count()
            ),

            Stat::make('Χωρίς υπεύθυνο', Asset::whereNull('asset_custodian_id')->count())->color('warning'),
            Stat::make('Χωρίς serial number', Asset::whereNull('serial_number')->orWhere('serial_number', '')->count())->color('warning'),
            Stat::make('Χωρίς τιμολόγιο', Asset::whereNull('invoice_number')->orWhere('invoice_number', '')->count())->color('danger'),
            Stat::make('Χωρίς τοποθεσία', Asset::whereNull('location_id')->count())->color('danger'),
            Stat::make('Μη επιβεβαιωμένα στην τελευταία απογραφή', $this->unverifiedInLatestInventory())->color('danger'),

            Stat::make(
                'Συνολική Αξία Κτήσης',
                number_format(
                    Asset::sum('purchase_value'),
                    2,
                    ',',
                    '.'
                ) . ' €'
            ),

            Stat::make(
                'Ενεργά Πάγια',
                Asset::where(
                    'status',
                    'in_use'
                )->count()
            ),

            Stat::make(
                'Πωλημένα Πάγια',
                Asset::where(
                    'status',
                    'sold'
                )->count()
            ),

            Stat::make(
                'Αναπόσβεστη Αξία',
                number_format(
                    Asset::sum('remaining_value'),
                    2,
                    ',',
                    '.'
                ) . ' €'
            ),

            Stat::make(
                'Ετήσιες Αποσβέσεις',
                number_format(
                    Asset::sum('annual_depreciation'),
                    2,
                    ',',
                    '.'
                ) . ' €'
            ),

        ];
    }

    private function unverifiedInLatestInventory(): int
    {
        $cycle = InventoryCycle::where('status', 'completed')->latest('ends_at')->first();
        return $cycle ? Asset::whereDoesntHave('inventoryEntries', fn ($query) => $query->where('inventory_cycle_id', $cycle->id)->where('result', 'found'))->count() : 0;
    }
}
