<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">Έλεγχοι Ποιότητας</x-slot>
            <p class="text-sm text-gray-600 dark:text-gray-300">Εντοπισμός αποκλίσεων που επηρεάζουν την αξιοπιστία του μητρώου, την οικονομική παρακολούθηση και τη λογοδοσία.</p>
        </x-filament::section>

        <div class="grid gap-6 lg:grid-cols-2">
            <x-filament::section>
                <x-slot name="heading">Διπλοί σειριακοί αριθμοί ({{ $duplicateSerials->count() }})</x-slot>
                @forelse ($duplicateSerials as $row)
                    <p class="border-b py-2 text-sm"><strong>{{ $row->serial_number }}</strong> · {{ $row->total }} πάγια</p>
                @empty
                    <p class="text-sm text-success-600">Δεν εντοπίστηκαν διπλοί σειριακοί αριθμοί.</p>
                @endforelse
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Διπλά τιμολόγια ({{ $duplicateInvoices->count() }})</x-slot>
                @forelse ($duplicateInvoices as $row)
                    <p class="border-b py-2 text-sm"><strong>{{ $row->invoice_number }}</strong> · {{ $row->supplier?->name ?? 'Χωρίς προμηθευτή' }} · {{ $row->total }} παραλαβές</p>
                @empty
                    <p class="text-sm text-success-600">Δεν εντοπίστηκαν διπλοί αριθμοί τιμολογίων.</p>
                @endforelse
            </x-filament::section>
        </div>

        <x-filament::section>
            <x-slot name="heading">Ελλιπή βασικά στοιχεία ({{ $missing->count() }})</x-slot>
            <div class="overflow-x-auto">
                <table class="w-full text-sm"><thead><tr><th class="text-left">Κωδικός</th><th class="text-left">Πάγιο</th><th class="text-left">Ελλείψεις</th></tr></thead>
                    <tbody>
                    @forelse ($missing as $asset)
                        <tr class="border-t"><td class="py-2"><a class="text-primary-600" href="{{ \App\Filament\Resources\Assets\AssetResource::getUrl('edit', ['record' => $asset]) }}">{{ $asset->asset_code }}</a></td><td>{{ $asset->name }}</td><td>{{ collect(['Κατηγορία' => ! $asset->asset_category_id, 'Τοποθεσία' => ! $asset->location_id, 'Υπεύθυνος' => ! $asset->asset_custodian_id])->filter()->keys()->join(', ') }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="py-2 text-success-600">Δεν υπάρχουν ελλιπείς εγγραφές.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <div class="grid gap-6 lg:grid-cols-2">
            <x-filament::section>
                <x-slot name="heading">Ασυμφωνία συντελεστή και ωφέλιμης ζωής ({{ $rateMismatches->count() }})</x-slot>
                @forelse ($rateMismatches as $asset)
                    <p class="border-b py-2 text-sm"><a class="text-primary-600" href="{{ \App\Filament\Resources\Assets\AssetResource::getUrl('edit', ['record' => $asset]) }}">{{ $asset->asset_code }}</a> · {{ $asset->depreciation_rate }}% / {{ $asset->useful_life_years }} έτη</p>
                @empty
                    <p class="text-sm text-success-600">Δεν εντοπίστηκαν ασυμφωνίες.</p>
                @endforelse
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Παράδοξη αναπόσβεστη αξία ({{ $valueAnomalies->count() }})</x-slot>
                @forelse ($valueAnomalies as $asset)
                    <p class="border-b py-2 text-sm"><a class="text-primary-600" href="{{ \App\Filament\Resources\Assets\AssetResource::getUrl('edit', ['record' => $asset]) }}">{{ $asset->asset_code }}</a> · Κτήση {{ number_format($asset->purchase_value, 2, ',', '.') }} €, αναπόσβεστη {{ number_format($asset->remaining_value, 2, ',', '.') }} €</p>
                @empty
                    <p class="text-sm text-success-600">Δεν εντοπίστηκαν παράδοξες αξίες.</p>
                @endforelse
            </x-filament::section>
        </div>

        <x-filament::section>
            <x-slot name="heading">Μετακινήσεις χωρίς παραλήπτη ή χωρίς ολοκλήρωση ({{ $movementIssues->count() }})</x-slot>
            <div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr><th class="text-left">Πάγιο</th><th class="text-left">Ημερομηνία</th><th class="text-left">Παραλήπτης</th><th class="text-left">Κατάσταση</th></tr></thead><tbody>
                @forelse ($movementIssues as $movement)
                    <tr class="border-t"><td class="py-2">{{ $movement->asset?->asset_code }} · {{ $movement->asset?->name }}</td><td>{{ $movement->movement_date?->format('d/m/Y') }}</td><td>{{ $movement->toCustodian?->name ?? 'Δεν έχει οριστεί' }}</td><td>{{ ['draft' => 'Πρόχειρη', 'pending_approval' => 'Προς έγκριση', 'approved' => 'Εγκρίθηκε', 'completed' => 'Ολοκληρώθηκε'][$movement->status] ?? $movement->status }}</td></tr>
                @empty
                    <tr><td colspan="4" class="py-2 text-success-600">Δεν υπάρχουν εκκρεμείς ή ελλιπείς μετακινήσεις.</td></tr>
                @endforelse
            </tbody></table></div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Έλεγχοι συμβάσεων και χρηματοδότησης ({{ $contractIssues->count() }})</x-slot>
            <div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr><th class="text-left">Σύμβαση</th><th class="text-left">Προμηθευτής</th><th class="text-left">Ελλείψεις / απόκλιση</th><th class="text-right">Υπόλοιπο</th></tr></thead><tbody>
                @forelse ($contractIssues as $contract)
                    <tr class="border-t"><td class="py-2"><a class="text-primary-600" href="{{ \App\Filament\Resources\ProcurementContracts\ProcurementContractResource::getUrl('edit', ['record' => $contract]) }}">{{ $contract->contract_number }}</a></td><td>{{ $contract->supplier?->name }}</td><td>{{ collect(['Χωρίς πηγή χρηματοδότησης' => ! $contract->funding_source_id, 'Χωρίς κέντρο κόστους' => ! $contract->cost_center_id, 'Υπέρβαση συμβατικού ποσού' => $contract->remaining_amount < -0.005])->filter()->keys()->join(', ') }}</td><td class="text-right">{{ number_format($contract->remaining_amount, 2, ',', '.') }} €</td></tr>
                @empty
                    <tr><td colspan="4" class="py-2 text-success-600">Δεν εντοπίστηκαν ελλιπείς ή υπερβατικές συμβάσεις.</td></tr>
                @endforelse
            </tbody></table></div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Αλλαγές μετά την τελευταία κλειδωμένη περίοδο</x-slot>
            @if ($lastLocked)
                <p class="mb-3 text-sm text-gray-500">Τελευταία κλειδωμένη περίοδος: {{ sprintf('%02d/%d', $lastLocked->month, $lastLocked->year) }}.</p>
                <div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr><th class="text-left">Ημερομηνία</th><th class="text-left">Πάγιο</th><th class="text-left">Χρήστης</th><th class="text-left">Ενέργεια</th></tr></thead><tbody>
                    @forelse ($lockedChanges as $activity)
                        <tr class="border-t"><td class="py-2">{{ $activity->created_at?->format('d/m/Y H:i') }}</td><td>{{ $activity->subject?->asset_code }} · {{ $activity->subject?->name }}</td><td>{{ $activity->causer?->name ?? 'Σύστημα' }}</td><td>{{ $activity->description }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="py-2 text-success-600">Δεν εντοπίστηκαν αλλαγές.</td></tr>
                    @endforelse
                </tbody></table></div>
            @else
                <p class="text-sm text-gray-500">Δεν υπάρχει ακόμη κλειδωμένη περίοδος απόσβεσης.</p>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
