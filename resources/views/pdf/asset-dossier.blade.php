<!doctype html>
<html lang="el">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 28px 30px 34px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #172033; line-height: 1.35; }
        h1 { margin: 0; font-size: 18px; color: #102a43; }
        h2 { margin: 15px 0 6px; padding: 5px 7px; font-size: 11px; color: #102a43; background: #eaf2ff; border-left: 4px solid #2563eb; }
        h3 { margin: 9px 0 4px; font-size: 9px; color: #334e68; }
        p { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 4px; text-align: left; vertical-align: top; }
        th { background: #f1f5f9; font-weight: bold; color: #243b53; }
        .title-table td { border: 0; padding: 0; vertical-align: middle; }
        .qr { width: 72px; height: 72px; }
        .muted { color: #667085; }
        .badge { display: inline-block; padding: 2px 5px; background: #eaf2ff; border: 1px solid #bfdbfe; color: #1e40af; }
        .money { text-align: right; white-space: nowrap; }
        .center { text-align: center; }
        .avoid-break { page-break-inside: avoid; }
        .page-break { page-break-before: always; }
        .empty { padding: 8px; text-align: center; color: #667085; border: 1px solid #d7dee8; }
        .summary td { width: 25%; }
        .summary strong { display: block; margin-top: 2px; font-size: 10px; color: #102a43; }
        .small { font-size: 7px; }
    </style>
</head>
<body>
@php
    $movementStatuses = ['draft' => 'Πρόχειρη', 'pending_approval' => 'Προς έγκριση', 'approved' => 'Εγκρίθηκε', 'completed' => 'Ολοκληρώθηκε'];
    $inventoryResults = ['found' => 'Βρέθηκε', 'not_found' => 'Δεν βρέθηκε', 'wrong_location' => 'Λάθος τοποθεσία', 'damaged' => 'Φθαρμένο'];
    $depreciationMethods = ['straight_line' => 'Σταθερή', 'declining_balance' => 'Φθίνουσα', 'none' => 'Μηδενική'];
    $depreciationTiming = ['full_month' => 'Πλήρης μήνας', 'next_month' => 'Από τον επόμενο μήνα'];
@endphp

@include('pdf.partials.organization-header')

<table class="title-table">
    <tr>
        <td>
            <h1>Ηλεκτρονικός Φάκελος Παγίου</h1>
            <p><strong>{{ $asset->asset_code }}</strong> — {{ $asset->name }}</p>
            <p class="muted">Ημερομηνία έκδοσης: {{ now()->format('d/m/Y H:i') }}</p>
        </td>
        <td style="width:78px;text-align:right">
            <img class="qr" src="data:image/svg+xml;base64,{{ $qrCode }}" alt="QR {{ $asset->asset_code }}">
        </td>
    </tr>
</table>

<table class="summary avoid-break" style="margin-top:10px">
    <tr>
        <td><span class="muted">Κατάσταση</span><strong>{{ \App\Models\AssetStatusTransaction::STATUSES[$asset->status] ?? $asset->status }}</strong></td>
        <td><span class="muted">Αξία κτήσης</span><strong>{{ number_format((float) $asset->purchase_value, 2, ',', '.') }} €</strong></td>
        <td><span class="muted">Σωρευμένες αποσβέσεις</span><strong>{{ number_format((float) $asset->accumulated_depreciation, 2, ',', '.') }} €</strong></td>
        <td><span class="muted">Αναπόσβεστη αξία</span><strong>{{ number_format((float) $asset->remaining_value, 2, ',', '.') }} €</strong></td>
    </tr>
</table>

<h2>Ταυτότητα και τρέχουσα ευθύνη</h2>
<table class="avoid-break">
    <tr><th style="width:20%">Περιγραφή</th><td>{{ $asset->name }}</td><th style="width:18%">Κατηγορία</th><td>{{ $asset->category?->name ?: '—' }}</td></tr>
    <tr><th>Αναλυτική περιγραφή</th><td>{{ $asset->description ?: '—' }}</td><th>Serial number</th><td>{{ $asset->serial_number ?: '—' }}</td></tr>
    <tr><th>Κατασκευαστής</th><td>{{ $asset->manufacturer ?: '—' }}</td><th>Μοντέλο</th><td>{{ $asset->model ?: '—' }}</td></tr>
    <tr><th>Τοποθεσία</th><td>{{ $asset->location?->name ?: '—' }}</td><th>Τμήμα</th><td>{{ $asset->department?->name ?: '—' }}</td></tr>
    <tr><th>Υπεύθυνος παγίου</th><td>{{ $asset->custodian?->name ?: '—' }}</td><th>Συνδεδεμένος χρήστης</th><td>{{ $asset->custodian?->user?->name ?: '—' }}</td></tr>
</table>

<h2>Κτήση και οικονομικά στοιχεία</h2>
<table class="avoid-break">
    <tr><th style="width:20%">Ημερομηνία απόκτησης</th><td>{{ $asset->purchase_date?->format('d/m/Y') ?: '—' }}</td><th style="width:20%">Έναρξη απόσβεσης</th><td>{{ $asset->depreciation_start_date?->format('d/m/Y') ?: '—' }}</td></tr>
    <tr><th>Προμηθευτής</th><td>{{ $asset->supplier?->name ?: '—' }}</td><th>Σύμβαση</th><td>{{ $asset->procurementContract?->contract_number ?: '—' }}</td></tr>
    <tr><th>Πηγή χρηματοδότησης</th><td>{{ $asset->fundingSource?->name ?: '—' }}</td><th>Κέντρο κόστους</th><td>{{ $asset->costCenter?->name ?: '—' }}</td></tr>
    <tr><th>Τιμολόγιο</th><td>{{ $asset->invoice_number ?: '—' }}</td><th>Ημερομηνία τιμολογίου</th><td>{{ $asset->invoice_date?->format('d/m/Y') ?: '—' }}</td></tr>
    <tr><th>Αξία κτήσης</th><td>{{ number_format((float) $asset->purchase_value, 2, ',', '.') }} €</td><th>ΦΠΑ</th><td>{{ number_format((float) $asset->vat_amount, 2, ',', '.') }} €</td></tr>
    <tr><th>Υπολειμματική αξία</th><td>{{ number_format((float) $asset->residual_value, 2, ',', '.') }} €</td><th>Κύριο βιβλίο</th><td>{{ $asset->depreciationBook?->name ?: '—' }}</td></tr>
</table>

<h2>Εγγύηση</h2>
<table class="avoid-break">
    <tr><th style="width:20%">Έναρξη</th><td>{{ $asset->warranty_starts_at?->format('d/m/Y') ?: '—' }}</td><th style="width:20%">Λήξη</th><td>{{ $asset->warranty_expires_at?->format('d/m/Y') ?: '—' }}</td></tr>
    <tr><th>Πάροχος</th><td>{{ $asset->warranty_provider ?: '—' }}</td><th>Όροι</th><td>{{ $asset->warranty_terms ?: '—' }}</td></tr>
</table>

<h2>Βιβλία και προφίλ αποσβέσεων</h2>
@if($asset->depreciationProfiles->isEmpty())
    <div class="empty">Δεν υπάρχουν προφίλ αποσβέσεων.</div>
@else
    <table>
        <thead><tr><th>Βιβλίο</th><th>Μέθοδος</th><th>Χρονισμός</th><th>Συντ.</th><th>Ωφέλιμη ζωή</th><th>Σωρευμένες</th><th>Αναπόσβεστη</th></tr></thead>
        <tbody>
        @foreach($asset->depreciationProfiles->sortBy('book.name') as $profile)
            <tr>
                <td>{{ $profile->book?->name ?: '—' }}</td>
                <td>{{ $depreciationMethods[$profile->depreciation_method] ?? $profile->depreciation_method }}</td>
                <td>{{ $depreciationTiming[$profile->depreciation_timing] ?? $profile->depreciation_timing }}</td>
                <td>{{ number_format((float) $profile->depreciation_rate, 2, ',', '.') }}%</td>
                <td>{{ $profile->useful_life_years }} έτη</td>
                <td class="money">{{ number_format((float) $profile->accumulated_depreciation, 2, ',', '.') }} €</td>
                <td class="money">{{ number_format((float) $profile->remaining_value, 2, ',', '.') }} €</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

<h3>Εγγραφές αποσβέσεων</h3>
@if($asset->depreciationEntries->isEmpty())
    <div class="empty">Δεν υπάρχουν εγγραφές αποσβέσεων.</div>
@else
    <table>
        <thead><tr><th>Βιβλίο</th><th>Περίοδος</th><th>Κατάσταση</th><th class="money">Απόσβεση</th><th class="money">Αναπόσβεστη αξία</th><th>Οριστικοποίηση</th></tr></thead>
        <tbody>
        @foreach($asset->depreciationEntries->sortByDesc(fn ($entry) => sprintf('%04d%02d', $entry->year, $entry->month)) as $entry)
            <tr>
                <td>{{ $entry->book?->name ?: '—' }}</td>
                <td>{{ str_pad((string) $entry->month, 2, '0', STR_PAD_LEFT) }}/{{ $entry->year }}</td>
                <td>{{ ['draft' => 'Πρόχειρη', 'posted' => 'Οριστική'][$entry->status] ?? $entry->status }}</td>
                <td class="money">{{ number_format((float) $entry->amount, 2, ',', '.') }} €</td>
                <td class="money">{{ number_format((float) $entry->book_value, 2, ',', '.') }} €</td>
                <td>{{ $entry->posted_at?->format('d/m/Y H:i') ?: '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

<div class="page-break"></div>
<h2>Ιστορικό μετακινήσεων</h2>
@if($asset->movements->isEmpty())
    <div class="empty">Δεν υπάρχουν μετακινήσεις.</div>
@else
    <table class="small">
        <thead><tr><th>Ημερομηνία</th><th>Από</th><th>Προς</th><th>Αιτία</th><th>Κατάσταση</th><th>Παράδοση / Παραλαβή</th></tr></thead>
        <tbody>
        @foreach($asset->movements->sortByDesc('movement_date') as $movement)
            <tr>
                <td>{{ $movement->movement_date?->format('d/m/Y') ?: '—' }}</td>
                <td>{{ $movement->fromLocation?->name ?: '—' }}<br>{{ $movement->fromDepartment?->name ?: '—' }}<br>{{ $movement->fromCustodian?->name ?: '—' }}</td>
                <td>{{ $movement->toLocation?->name ?: '—' }}<br>{{ $movement->toDepartment?->name ?: '—' }}<br>{{ $movement->toCustodian?->name ?: '—' }}</td>
                <td>{{ $movement->reason }}</td>
                <td>{{ $movementStatuses[$movement->status] ?? $movement->status }}</td>
                <td>{{ $movement->delivered_by_name ?: '—' }} / {{ $movement->received_by_name ?: '—' }}<br>{{ $movement->delivered_at?->format('d/m/Y') ?: '—' }} / {{ $movement->received_at?->format('d/m/Y') ?: '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

<h2>Κύκλος ζωής, διαγραφές και μεταβιβάσεις</h2>
@if($asset->statusTransactions->isEmpty())
    <div class="empty">Δεν υπάρχουν συναλλαγές κύκλου ζωής.</div>
@else
    <table class="small">
        <thead><tr><th>Ημερομηνία</th><th>Ενέργεια</th><th>Μεταβολή</th><th>Αιτία</th><th>Έγγραφο</th><th>Οικονομικό αποτέλεσμα</th></tr></thead>
        <tbody>
        @foreach($asset->statusTransactions->sortByDesc(fn ($item) => $item->document_date?->timestamp ?? $item->id) as $transaction)
            <tr>
                <td>{{ $transaction->document_date?->format('d/m/Y') ?: '—' }}</td>
                <td>{{ \App\Models\AssetStatusTransaction::TYPES[$transaction->transaction_type] ?? $transaction->transaction_type }}</td>
                <td>{{ (\App\Models\AssetStatusTransaction::STATUSES[$transaction->from_status] ?? $transaction->from_status) ?: '—' }} → {{ (\App\Models\AssetStatusTransaction::STATUSES[$transaction->to_status] ?? $transaction->to_status) ?: '—' }}</td>
                <td>{{ $transaction->reason }}</td>
                <td>{{ $transaction->document_number ?: '—' }}</td>
                <td>Έσοδο: {{ number_format((float) $transaction->disposal_proceeds, 2, ',', '.') }} €<br>Κέρδος/ζημία: {{ number_format((float) $transaction->gain_loss, 2, ',', '.') }} €</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

<h2>Φυσικές απογραφές</h2>
@if($asset->inventoryEntries->isEmpty())
    <div class="empty">Δεν υπάρχουν εγγραφές φυσικής απογραφής.</div>
@else
    <table>
        <thead><tr><th>Απογραφή</th><th>Επιβεβαίωση</th><th>Αποτέλεσμα</th><th>Πραγματική τοποθεσία</th><th>Επιβεβαίωσε</th><th>Παρατηρήσεις</th></tr></thead>
        <tbody>
        @foreach($asset->inventoryEntries->sortByDesc('verified_at') as $entry)
            <tr>
                <td>{{ $entry->cycle?->name ?: '—' }}</td>
                <td>{{ $entry->verified_at?->format('d/m/Y H:i') ?: '—' }}</td>
                <td>{{ $inventoryResults[$entry->result] ?? $entry->result }}</td>
                <td>{{ $entry->actualLocation?->name ?: '—' }}</td>
                <td>{{ $entry->verifier?->name ?: '—' }}</td>
                <td>{{ $entry->notes ?: '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

<h2>Συντηρήσεις</h2>
@if($asset->maintenances->isEmpty())
    <div class="empty">Δεν υπάρχουν εργασίες συντήρησης.</div>
@else
    <table>
        <thead><tr><th>Εργασία</th><th>Είδος</th><th>Κατάσταση</th><th>Προγραμματισμός</th><th>Εκτέλεση</th><th>Τεχνικός</th><th class="money">Κόστος</th></tr></thead>
        <tbody>
        @foreach($asset->maintenances->sortByDesc(fn ($item) => $item->scheduled_at?->timestamp ?? $item->id) as $maintenance)
            <tr>
                <td>{{ $maintenance->title }}<br><span class="muted">{{ $maintenance->document_number }}</span></td>
                <td>{{ \App\Models\AssetMaintenance::TYPES[$maintenance->maintenance_type] ?? $maintenance->maintenance_type }}</td>
                <td>{{ \App\Models\AssetMaintenance::STATUSES[$maintenance->status] ?? $maintenance->status }}</td>
                <td>{{ $maintenance->scheduled_at?->format('d/m/Y') ?: '—' }}</td>
                <td>{{ $maintenance->performed_at?->format('d/m/Y') ?: '—' }}<br>Επόμενο: {{ $maintenance->next_service_at?->format('d/m/Y') ?: '—' }}</td>
                <td>{{ $maintenance->technician ?: $maintenance->assignee?->name ?: '—' }}</td>
                <td class="money">{{ number_format((float) $maintenance->cost, 2, ',', '.') }} €</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

<h2>Ασφαλίσεις</h2>
@if($asset->insurances->isEmpty())
    <div class="empty">Δεν υπάρχουν ασφαλιστήρια.</div>
@else
    <table>
        <thead><tr><th>Εταιρεία</th><th>Αριθμός συμβολαίου</th><th>Έναρξη</th><th>Λήξη</th><th class="money">Ασφαλιζόμενη αξία</th><th>Όροι</th></tr></thead>
        <tbody>
        @foreach($asset->insurances->sortByDesc('expires_at') as $insurance)
            <tr>
                <td>{{ $insurance->company }}</td><td>{{ $insurance->policy_number }}</td>
                <td>{{ $insurance->starts_at?->format('d/m/Y') ?: '—' }}</td><td>{{ $insurance->expires_at?->format('d/m/Y') ?: '—' }}</td>
                <td class="money">{{ number_format((float) $insurance->insured_value, 2, ',', '.') }} €</td><td>{{ $insurance->terms ?: '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

<h2>Δικαιολογητικά</h2>
@if($asset->documents->isEmpty())
    <div class="empty">Δεν υπάρχουν δικαιολογητικά.</div>
@else
    <table>
        <thead><tr><th>Είδος</th><th>Τίτλος</th><th>Αριθμός</th><th>Ημερομηνία</th><th>Αρχείο</th><th>Καταχώρισε</th></tr></thead>
        <tbody>
        @foreach($asset->documents->sortByDesc(fn ($item) => $item->document_date?->timestamp ?? $item->id) as $document)
            <tr>
                <td>{{ \App\Models\AssetDocument::TYPES[$document->document_type] ?? $document->document_type }}</td>
                <td>{{ $document->title }}</td><td>{{ $document->document_number ?: '—' }}</td>
                <td>{{ $document->document_date?->format('d/m/Y') ?: '—' }}</td><td>{{ basename($document->file_path) }}</td><td>{{ $document->creator?->name ?: '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

<h2>Ιστορικό αλλαγών εγγραφής</h2>
@if($asset->activities->isEmpty())
    <div class="empty">Δεν υπάρχουν καταγεγραμμένες αλλαγές στην εγγραφή.</div>
@else
    <table>
        <thead><tr><th>Ημερομηνία</th><th>Ενέργεια</th><th>Περιγραφή</th><th>Χρήστης</th></tr></thead>
        <tbody>
        @foreach($asset->activities->sortByDesc('created_at') as $activity)
            <tr>
                <td>{{ $activity->created_at?->format('d/m/Y H:i') ?: '—' }}</td>
                <td>{{ (['created' => 'Δημιουργία', 'updated' => 'Τροποποίηση', 'deleted' => 'Διαγραφή', 'exported' => 'Εξαγωγή'][$activity->event] ?? $activity->event) ?: '—' }}</td>
                <td>{{ $activity->description }}</td>
                <td>{{ $activity->causer?->name ?: 'Σύστημα' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

<p class="muted" style="margin-top:12px">Δημιουργία εγγραφής: {{ $asset->created_at?->format('d/m/Y H:i') }} · Τελευταία ενημέρωση: {{ $asset->updated_at?->format('d/m/Y H:i') }}</p>

@include('pdf.partials.organization-footer')
</body>
</html>
