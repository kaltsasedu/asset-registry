<?php

namespace App\Models;

use App\Models\Concerns\LogsBusinessActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemBackup extends Model
{
    use LogsBusinessActivity;

    public const STATUSES = [
        'running' => 'Σε εξέλιξη',
        'completed' => 'Ολοκληρώθηκε',
        'failed' => 'Απέτυχε',
    ];

    public const INTEGRITY_STATUSES = [
        'unknown' => 'Δεν ελέγχθηκε',
        'valid' => 'Έγκυρο',
        'invalid' => 'Μη έγκυρο',
    ];

    protected $fillable = [
        'file_name', 'file_path', 'includes_database', 'includes_files', 'size_bytes',
        'checksum_sha256', 'status', 'integrity_status', 'error_message', 'created_by',
        'started_at', 'completed_at', 'verified_at', 'restored_at', 'restored_by',
    ];

    protected $casts = [
        'includes_database' => 'boolean',
        'includes_files' => 'boolean',
        'size_bytes' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'verified_at' => 'datetime',
        'restored_at' => 'datetime',
    ];

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function restorer(): BelongsTo { return $this->belongsTo(User::class, 'restored_by'); }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = max(0, (int) $this->size_bytes);

        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2, ',', '.') . ' GB';
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 2, ',', '.') . ' KB';

        return $bytes . ' B';
    }
}
