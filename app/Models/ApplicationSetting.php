<?php

namespace App\Models;

use App\Models\Concerns\LogsBusinessActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ApplicationSetting extends Model
{
    use LogsBusinessActivity;

    protected $fillable = [
        'organization_name',
        'tax_id',
        'address',
        'phone',
        'email',
        'logo_path',
        'show_logo_on_reports',
        'report_footer',
        'asset_code_prefix',
        'asset_code_digits',
        'warranty_warning_days',
        'insurance_warning_days',
        'maintenance_warning_days',
        'receipt_overdue_days',
        'backup_enabled',
        'backup_frequency',
        'backup_time',
        'backup_retention_days',
        'backup_include_files',
    ];

    protected $casts = [
        'show_logo_on_reports' => 'boolean',
        'asset_code_digits' => 'integer',
        'warranty_warning_days' => 'integer',
        'insurance_warning_days' => 'integer',
        'maintenance_warning_days' => 'integer',
        'receipt_overdue_days' => 'integer',
        'backup_enabled' => 'boolean',
        'backup_retention_days' => 'integer',
        'backup_include_files' => 'boolean',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], static::defaults());
    }

    public static function defaults(): array
    {
        return [
            'organization_name' => config('app.name', 'Μητρώο Παγίων'),
            'show_logo_on_reports' => true,
            'asset_code_prefix' => 'PAG',
            'asset_code_digits' => 6,
            'warranty_warning_days' => 90,
            'insurance_warning_days' => 30,
            'maintenance_warning_days' => 30,
            'receipt_overdue_days' => 30,
            'backup_enabled' => true,
            'backup_frequency' => 'daily',
            'backup_time' => '02:00',
            'backup_retention_days' => 30,
            'backup_include_files' => true,
        ];
    }

    public function assetCode(int $number): string
    {
        return rtrim((string) $this->asset_code_prefix, '-') . '-' . str_pad(
            (string) $number,
            max(3, (int) $this->asset_code_digits),
            '0',
            STR_PAD_LEFT,
        );
    }

    public function assetCodeExample(): string
    {
        return $this->assetCode(1);
    }

    public function logoDataUri(): ?string
    {
        if (! $this->show_logo_on_reports || blank($this->logo_path) || ! Storage::disk('local')->exists($this->logo_path)) {
            return null;
        }

        $mime = Storage::disk('local')->mimeType($this->logo_path) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode(Storage::disk('local')->get($this->logo_path));
    }

    public function contactLine(): string
    {
        return collect([$this->address, $this->phone, $this->email])
            ->filter()
            ->join(' · ');
    }
}
