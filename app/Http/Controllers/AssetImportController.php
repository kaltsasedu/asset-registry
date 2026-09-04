<?php

namespace App\Http\Controllers;

use App\Exports\AssetImportTemplateExport;
use App\Exports\AssetImportResultExport;
use App\Services\AssetSpreadsheetImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetImportController extends Controller
{
    public function index(): View
    {
        $this->authorizeImport();
        return view('assets.import', ['preview' => null, 'mode' => 'create_only', 'token' => null, 'result' => session('asset_import_result')]);
    }

    public function template()
    {
        $this->authorizeImport();
        return Excel::download(new AssetImportTemplateExport(), 'protypo-eisagogis-pagion.xlsx');
    }

    public function preview(Request $request, AssetSpreadsheetImportService $service): View|RedirectResponse
    {
        $this->authorizeImport();
        $validated = $request->validate([
            'mode' => ['required', 'in:create_only,update_existing'],
            'import_file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:20480'],
        ], [
            'import_file.required' => 'Επιλέξτε αρχείο Excel ή CSV.',
            'import_file.mimes' => 'Επιτρέπονται αρχεία XLSX, XLS ή CSV.',
            'import_file.max' => 'Το αρχείο δεν μπορεί να υπερβαίνει τα 20 MB.',
        ]);

        $previous = session('asset_import_pending');
        if (is_array($previous) && filled($previous['path'] ?? null)) Storage::disk('local')->delete($previous['path']);
        session()->forget('asset_import_pending');

        $path = $request->file('import_file')->store('asset-imports/tmp', 'local');
        $preview = $service->preview(Storage::disk('local')->path($path), $validated['mode']);
        if ($preview['global_errors']) {
            Storage::disk('local')->delete($path);
            return back()->withErrors(['import_file' => implode(' ', $preview['global_errors'])]);
        }

        $token = (string) Str::uuid();
        session(['asset_import_pending' => ['token' => $token, 'path' => $path, 'mode' => $validated['mode']]]);

        return view('assets.import', ['preview' => $preview, 'mode' => $validated['mode'], 'token' => $token, 'result' => null]);
    }

    public function store(Request $request, AssetSpreadsheetImportService $service): RedirectResponse
    {
        $this->authorizeImport();
        $pending = session('asset_import_pending');
        abort_unless(is_array($pending) && hash_equals((string) ($pending['token'] ?? ''), (string) $request->input('token')), 419);
        abort_unless(Storage::disk('local')->exists($pending['path']), 410, 'Το προσωρινό αρχείο δεν είναι πλέον διαθέσιμο.');

        $result = $service->import(Storage::disk('local')->path($pending['path']), $pending['mode']);
        Storage::disk('local')->delete($pending['path']);
        session()->forget('asset_import_pending');

        $previousReport = session('asset_import_report');
        if (is_string($previousReport)) Storage::disk('local')->delete($previousReport);
        $reportPath = 'asset-imports/reports/apotelesma-' . Str::uuid() . '.xlsx';
        Excel::store(new AssetImportResultExport($result), $reportPath, 'local');
        session(['asset_import_report' => $reportPath]);
        $result['report_available'] = true;

        return redirect()->route('assets.import')->with('asset_import_result', $result);
    }

    public function report(): StreamedResponse
    {
        $this->authorizeImport();
        $path = session('asset_import_report');
        abort_unless(is_string($path) && Storage::disk('local')->exists($path), 404);
        return Storage::disk('local')->download($path, 'apotelesma-eisagogis-pagion.xlsx');
    }

    private function authorizeImport(): void
    {
        $user = auth()->user();
        abort_unless($user && $user->hasFullDataAccess() && ($user->hasRole('Διαχειριστής') || $user->can('assets.import')), 403);
    }
}
