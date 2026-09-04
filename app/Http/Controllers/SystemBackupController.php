<?php

namespace App\Http\Controllers;

use App\Models\SystemBackup;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SystemBackupController extends Controller
{
    public function download(SystemBackup $backup): StreamedResponse
    {
        abort_unless(
            auth()->user()?->hasRole('Διαχειριστής') || auth()->user()?->can('system-backups.download'),
            403,
        );
        abort_unless($backup->status === 'completed' && Storage::disk('local')->exists($backup->file_path), 404);

        return Storage::disk('local')->download($backup->file_path, $backup->file_name, [
            'Content-Type' => 'application/zip',
        ]);
    }
}
