<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\InventoryCycle;
use App\Models\InventoryEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InventoryScanController extends Controller
{
    public function show(InventoryCycle $cycle): View
    {
        abort_unless($this->canPerformInventory(), 403);
        abort_unless($cycle->status !== 'completed', 403, 'Η απογραφή έχει ολοκληρωθεί.');

        $locations = \App\Models\Location::query()->orderBy('name')->get(['id', 'name']);

        return view('inventory.scan', compact('cycle', 'locations'));
    }

    public function store(Request $request, InventoryCycle $cycle): RedirectResponse
    {
        abort_unless($this->canPerformInventory(), 403);
        abort_unless($cycle->status !== 'completed', 403, 'Η απογραφή έχει ολοκληρωθεί.');

        $data = $request->validate([
            'asset_code' => ['required', 'string', 'max:100'],
            'result' => ['required', 'in:found,wrong_location,damaged'],
            'actual_location_id' => ['nullable', 'exists:locations,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $asset = Asset::query()->where('asset_code', trim($data['asset_code']))->first();

        if (! $asset) {
            return back()->withInput()->with('scan_error', 'Δεν βρέθηκε πάγιο με αυτόν τον κωδικό QR.');
        }

        if ($cycle->status === 'draft') {
            $cycle->update(['status' => 'active']);
        }

        InventoryEntry::updateOrCreate(
            ['inventory_cycle_id' => $cycle->id, 'asset_id' => $asset->id],
            [
                'verified_by' => $request->user()->id,
                'result' => $data['result'],
                'actual_location_id' => ($data['actual_location_id'] ?? null) ?: $asset->location_id,
                'notes' => $data['notes'] ?? null,
                'verified_at' => now(),
            ],
        );

        return back()->with('scan_success', "Καταχωρίστηκε το πάγιο {$asset->asset_code} — {$asset->name}.");
    }

    public function sync(Request $request, InventoryCycle $cycle): JsonResponse
    {
        abort_unless($this->canPerformInventory(), 403);
        abort_unless($cycle->status !== 'completed', 403, 'Η απογραφή έχει ολοκληρωθεί.');

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:250'],
            'items.*.client_id' => ['required', 'string', 'max:100'],
            'items.*.asset_code' => ['required', 'string', 'max:100'],
            'items.*.result' => ['required', 'in:found,wrong_location,damaged'],
            'items.*.actual_location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'items.*.notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $accepted = [];
        $rejected = [];

        DB::transaction(function () use ($data, $cycle, $request, &$accepted, &$rejected): void {
            if ($cycle->status === 'draft') {
                $cycle->update(['status' => 'active']);
            }

            foreach ($data['items'] as $item) {
                $asset = Asset::query()->where('asset_code', trim($item['asset_code']))->first();

                if (! $asset) {
                    $rejected[] = [
                        'client_id' => $item['client_id'],
                        'message' => 'Δεν βρέθηκε επιτρεπόμενο πάγιο με αυτόν τον κωδικό.',
                    ];
                    continue;
                }

                InventoryEntry::updateOrCreate(
                    ['inventory_cycle_id' => $cycle->id, 'asset_id' => $asset->id],
                    [
                        'verified_by' => $request->user()->id,
                        'result' => $item['result'],
                        'actual_location_id' => ($item['actual_location_id'] ?? null) ?: $asset->location_id,
                        'notes' => $item['notes'] ?? null,
                        'verified_at' => now(),
                    ],
                );

                $accepted[] = [
                    'client_id' => $item['client_id'],
                    'asset_code' => $asset->asset_code,
                    'asset_name' => $asset->name,
                ];
            }
        });

        return response()->json([
            'accepted' => $accepted,
            'rejected' => $rejected,
            'message' => count($accepted) . ' σαρώσεις συγχρονίστηκαν.',
        ]);
    }

    private function canPerformInventory(): bool
    {
        return auth()->user()?->hasRole('Διαχειριστής') || auth()->user()?->can('inventory.perform');
    }
}
