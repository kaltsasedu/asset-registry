<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\InventoryCycle;
use App\Models\InventoryEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InventoryDeviationController extends Controller
{
    public function show(InventoryCycle $cycle): View
    {
        abort_unless($this->canViewInventory(), 403);
        $expectedAssets = $this->expectedAssetsQuery($cycle)->with(['location', 'department', 'custodian'])->get();
        $entries = $cycle->entries()->with(['asset.location', 'actualLocation', 'verifier'])->get();
        $scannedAssetIds = $entries->pluck('asset_id');

        $notFoundAssets = $entries->where('result', 'not_found')->pluck('asset')->filter();
        $unscannedAssets = $expectedAssets->whereNotIn('id', $scannedAssetIds);

        return view('inventory.deviations', [
            'cycle' => $cycle,
            'expectedCount' => $expectedAssets->count(),
            'foundCount' => $entries->where('result', 'found')->count(),
            'missingAssets' => $notFoundAssets->merge($unscannedAssets)->unique('id')->values(),
            'wrongLocationEntries' => $entries->where('result', 'wrong_location')->values(),
            'damagedEntries' => $entries->where('result', 'damaged')->values(),
            'canCompleteEntireCycle' => $this->canCompleteEntireCycle($cycle, auth()->user()),
        ]);
    }

    public function complete(Request $request, InventoryCycle $cycle): RedirectResponse
    {
        abort_unless($this->canPerformInventory(), 403);
        abort_if($cycle->status === 'completed', 422, 'Η απογραφή έχει ήδη οριστικοποιηθεί.');
        abort_unless($this->canCompleteEntireCycle($cycle, $request->user()), 403, 'Η οριστικοποίηση απαιτεί πρόσβαση σε όλα τα πάγια αυτής της απογραφής.');

        $request->validate(['confirmation' => ['accepted']]);

        DB::transaction(function () use ($cycle, $request): void {
            $expectedAssets = $this->expectedAssetsQuery($cycle)->pluck('id');
            $scannedAssetIds = $cycle->entries()->pluck('asset_id');
            $missingAssetIds = $expectedAssets->diff($scannedAssetIds);

            foreach ($missingAssetIds as $assetId) {
                InventoryEntry::create([
                    'inventory_cycle_id' => $cycle->id,
                    'asset_id' => $assetId,
                    'verified_by' => $request->user()->id,
                    'result' => 'not_found',
                    'verified_at' => now(),
                    'notes' => 'Δεν καταγράφηκε κατά τη φυσική απογραφή.',
                ]);
            }

            $cycle->update([
                'status' => 'completed',
                'ends_at' => $cycle->ends_at ?? now()->toDateString(),
            ]);
        });

        return redirect()->route('inventory.deviations', $cycle)
            ->with('completion_success', 'Η απογραφή οριστικοποιήθηκε. Τα μη καταγεγραμμένα πάγια σημειώθηκαν ως «Δεν βρέθηκαν».');
    }

    private function expectedAssetsQuery(InventoryCycle $cycle): Builder
    {
        return Asset::query()
            ->where('status', '!=', 'sold')
            ->when($cycle->location_id, fn (Builder $query) => $query->where('location_id', $cycle->location_id))
            ->when($cycle->department_id, fn (Builder $query) => $query->where('department_id', $cycle->department_id));
    }

    private function canViewInventory(): bool
    {
        return auth()->user()?->hasRole('Διαχειριστής') || auth()->user()?->can('inventory.view');
    }

    private function canPerformInventory(): bool
    {
        return auth()->user()?->hasRole('Διαχειριστής') || auth()->user()?->can('inventory.perform');
    }

    private function canCompleteEntireCycle(InventoryCycle $cycle, $user): bool
    {
        if ($user?->hasFullDataAccess()) {
            return true;
        }

        $visibleCount = $this->expectedAssetsQuery($cycle)->count();
        $allCount = Asset::query()
            ->withoutGlobalScope('visible_to_user')
            ->where('status', '!=', 'sold')
            ->when($cycle->location_id, fn (Builder $query) => $query->where('location_id', $cycle->location_id))
            ->when($cycle->department_id, fn (Builder $query) => $query->where('department_id', $cycle->department_id))
            ->count();

        return $visibleCount === $allCount;
    }
}
