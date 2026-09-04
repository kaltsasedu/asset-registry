<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/** Καταγράφει μεταβολές επιχειρησιακών και οικονομικών εγγραφών. */
trait LogsBusinessActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName($this->businessActivityLogName())
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected function businessActivityLogName(): string
    {
        return match (class_basename($this)) {
            'AssetMovement' => 'Μετακινήσεις Παγίων',
            'DepreciationBook' => 'Βιβλία Αποσβέσεων',
            'DepreciationPeriod' => 'Περίοδοι Αποσβέσεων',
            'DepreciationEntry' => 'Εγγραφές Αποσβέσεων',
            'AssetDepreciationProfile' => 'Προφίλ Αποσβέσεων',
            'AssetStatusTransaction' => 'Κύκλος Ζωής Παγίων',
            'AssetDocument' => 'Δικαιολογητικά Παγίων',
            'InventoryCycle' => 'Φυσικές Απογραφές',
            'InventoryEntry' => 'Ευρήματα Απογραφής',
            'AssetMaintenance' => 'Συντηρήσεις Παγίων',
            'AssetInsurance' => 'Ασφαλίσεις Παγίων',
            'AssetCustodian' => 'Υπεύθυνοι Παγίων',
            'AssetReceipt' => 'Παραλαβές Παγίων',
            'AssetReceiptLine' => 'Γραμμές Παραλαβής Παγίων',
            'FundingSource' => 'Πηγές Χρηματοδότησης',
            'CostCenter' => 'Κέντρα Κόστους',
            'ProcurementContract' => 'Συμβάσεις',
            'ApplicationSetting' => 'Ρυθμίσεις Φορέα',
            'SystemBackup' => 'Αντίγραφα Ασφαλείας',
            default => class_basename($this),
        };
    }
}
