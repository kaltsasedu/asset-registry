<!doctype html>
<html lang="el">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Οικονομικές Αναφορές Παγίων</title>
    <style>
        body { margin: 0; padding: 28px; background: #f6f8fb; color: #172033; font-family: Arial, sans-serif; }
        h1, h2, h3 { margin: 0 0 14px; } h1 { font-size: 26px; } h2 { margin-top: 28px; font-size: 20px; }
        .page-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 18px; }
        .panel, .summary-item { border: 1px solid #d8e0ea; border-radius: 10px; background: #fff; }
        .panel { padding: 18px; margin-bottom: 18px; } .filters { display: flex; flex-wrap: wrap; align-items: end; gap: 12px; }
        label { display: grid; gap: 5px; color: #344054; font-size: 13px; font-weight: 700; }
        select, input, button, .button { min-height: 38px; border: 1px solid #b8c2d0; border-radius: 6px; padding: 0 10px; background: #fff; color: #172033; }
        button, .button { display: inline-flex; align-items: center; justify-content: center; border: 0; background: #1769aa; color: #fff; font-weight: 700; cursor: pointer; text-decoration: none; }
        .button.secondary { background: #475467; } .summary { display: grid; grid-template-columns: repeat(4, minmax(180px, 1fr)); gap: 14px; }
        .summary-item { padding: 14px; } .summary-item strong { display: block; color: #667085; font-size: 13px; } .summary-item span { display: block; margin-top: 7px; font-size: 20px; font-weight: 800; }
        .muted { color: #667085; } .table-wrap { overflow-x: auto; } table { width: 100%; border-collapse: collapse; background: #fff; font-size: 13px; }
        th, td { border: 1px solid #d8e0ea; padding: 9px; text-align: left; vertical-align: top; } th { background: #edf3fa; color: #344054; white-space: nowrap; }
        td.number { text-align: right; white-space: nowrap; } .columns { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
        .small-table { font-size: 12px; } .notice { border-left: 4px solid #d97706; padding: 10px 12px; background: #fff7ed; color: #7c2d12; }
        @media (max-width: 900px) { body { padding: 16px; } .page-header { align-items: stretch; flex-direction: column; } .summary, .columns { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="page-header">
        <div>
            <h1>Οικονομικές Αναφορές Παγίων</h1>
            <p class="muted">Βιβλίο αποσβέσεων: <strong>{{ $book?->name ?? 'Δεν επιλέχθηκε βιβλίο' }}</strong></p>
        </div>
        <a class="button secondary" href="{{ url('/admin') }}">← Πίνακας Ελέγχου</a>
    </div>

    <form class="panel filters" method="get">
        <label>
            Βιβλίο αποσβέσεων
            <select name="book_id">
                @foreach ($books as $availableBook)
                    <option value="{{ $availableBook->id }}" @selected($bookId === $availableBook->id)>{{ $availableBook->name }}</option>
                @endforeach
            </select>
        </label>
        <label>
            Ημερομηνία μητρώου
            <input type="date" name="as_of" value="{{ $asOf->toDateString() }}">
        </label>
        <label>
            Μήνας αποσβέσεων
            <input type="number" name="month" min="1" max="12" value="{{ $month }}">
        </label>
        <label>
            Έτος αποσβέσεων
            <input type="number" name="year" min="2000" max="2100" value="{{ $year }}">
        </label>
        <label>
            Πηγή χρηματοδότησης
            <select name="funding_source_id"><option value="0">Όλες</option>@foreach ($fundingSources as $source)<option value="{{ $source->id }}" @selected($fundingSourceId === $source->id)>{{ $source->name }}</option>@endforeach</select>
        </label>
        <label>
            Κέντρο κόστους
            <select name="cost_center_id"><option value="0">Όλα</option>@foreach ($costCenters as $center)<option value="{{ $center->id }}" @selected($costCenterId === $center->id)>{{ $center->name }}</option>@endforeach</select>
        </label>
        <label>
            Σύμβαση
            <select name="contract_id"><option value="0">Όλες</option>@foreach ($contracts as $contract)<option value="{{ $contract->id }}" @selected($contractId === $contract->id)>{{ $contract->contract_number }}</option>@endforeach</select>
        </label>
        <button type="submit">Ενημέρωση</button>
        <a class="button secondary" href="{{ route('depreciation.reports', array_merge(request()->query(), ['format' => 'excel'])) }}">Εξαγωγή Excel</a>
        <a class="button secondary" href="{{ route('depreciation.reports', array_merge(request()->query(), ['format' => 'pdf'])) }}">Λήψη PDF</a>
    </form>

    <div class="summary">
        <div class="summary-item"><strong>Αξία κτήσης</strong><span>€ {{ number_format($registerRows->sum('purchase_value'), 2, ',', '.') }}</span></div>
        <div class="summary-item"><strong>Σωρευμένες αποσβέσεις</strong><span>€ {{ number_format($registerRows->sum('accumulated_depreciation'), 2, ',', '.') }}</span></div>
        <div class="summary-item"><strong>Αναπόσβεστη αξία</strong><span>€ {{ number_format($registerRows->sum('remaining_value'), 2, ',', '.') }}</span></div>
        <div class="summary-item"><strong>Αποσβέσεις περιόδου {{ sprintf('%02d/%d', $month, $year) }}</strong><span>€ {{ number_format($periodEntries->sum('amount'), 2, ',', '.') }}</span></div>
    </div>

    <h2>Μητρώο παγίων ανά ημερομηνία <span class="muted">({{ $asOf->format('d/m/Y') }})</span></h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Κωδικός</th><th>Πάγιο</th><th>Κατηγορία</th><th>Τμήμα</th><th>Τοποθεσία</th><th>Αξία κτήσης</th><th>Σωρευμένες αποσβέσεις</th><th>Αναπόσβεστη αξία</th></tr></thead>
            <tbody>
                @forelse ($registerRows as $row)
                    <tr><td>{{ $row['code'] }}</td><td>{{ $row['name'] }}</td><td>{{ $row['category'] }}</td><td>{{ $row['department'] }}</td><td>{{ $row['location'] }}</td><td class="number">€ {{ number_format($row['purchase_value'], 2, ',', '.') }}</td><td class="number">€ {{ number_format($row['accumulated_depreciation'], 2, ',', '.') }}</td><td class="number">€ {{ number_format($row['remaining_value'], 2, ',', '.') }}</td></tr>
                @empty
                    <tr><td colspan="8">Δεν βρέθηκαν πάγια για το επιλεγμένο βιβλίο και την ημερομηνία αναφοράς.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h2>Ανάλυση οικονομικών μεγεθών</h2>
    <div class="columns">
        @foreach (['Κατηγορία' => $byCategory, 'Τμήμα' => $byDepartment, 'Τοποθεσία' => $byLocation] as $title => $items)
            <div class="panel">
                <h3>Ανά {{ $title }}</h3>
                <div class="table-wrap"><table class="small-table"><thead><tr><th>{{ $title }}</th><th>Πάγια</th><th>Αξία κτήσης</th><th>Αναπόσβ.</th></tr></thead><tbody>
                    @foreach ($items as $item)
                        <tr><td>{{ $item['label'] }}</td><td>{{ $item['assets'] }}</td><td class="number">€ {{ number_format($item['purchase_value'], 2, ',', '.') }}</td><td class="number">€ {{ number_format($item['remaining_value'], 2, ',', '.') }}</td></tr>
                    @endforeach
                </tbody></table></div>
            </div>
        @endforeach
    </div>

    <h2>Ανάλυση χρηματοδότησης και συμβάσεων</h2>
    <div class="columns">
        @foreach (['Πηγή χρηματοδότησης' => $byFundingSource, 'Κέντρο κόστους' => $byCostCenter, 'Σύμβαση' => $byContract] as $title => $items)
            <div class="panel">
                <h3>Ανά {{ $title }}</h3>
                <div class="table-wrap"><table class="small-table"><thead><tr><th>{{ $title }}</th><th>Πάγια</th><th>Αξία κτήσης</th><th>Αποσβέσεις</th><th>Αναπόσβ.</th></tr></thead><tbody>
                    @foreach ($items as $item)
                        <tr><td>{{ $item['label'] }}</td><td>{{ $item['assets'] }}</td><td class="number">€ {{ number_format($item['purchase_value'], 2, ',', '.') }}</td><td class="number">€ {{ number_format($item['accumulated_depreciation'], 2, ',', '.') }}</td><td class="number">€ {{ number_format($item['remaining_value'], 2, ',', '.') }}</td></tr>
                    @endforeach
                </tbody></table></div>
            </div>
        @endforeach
    </div>

    <h2>Αποσβέσεις περιόδου <span class="muted">({{ sprintf('%02d/%d', $month, $year) }})</span></h2>
    <div class="table-wrap"><table><thead><tr><th>Κωδικός</th><th>Πάγιο</th><th>Ποσό απόσβεσης</th><th>Αναπόσβεστη αξία μετά την εγγραφή</th></tr></thead><tbody>
        @forelse ($periodEntries as $entry)
            <tr><td>{{ $entry->asset?->asset_code }}</td><td>{{ $entry->asset?->name }}</td><td class="number">€ {{ number_format($entry->amount, 2, ',', '.') }}</td><td class="number">€ {{ number_format($entry->book_value, 2, ',', '.') }}</td></tr>
        @empty
            <tr><td colspan="4">Δεν υπάρχουν οριστικοποιημένες εγγραφές για την επιλεγμένη περίοδο.</td></tr>
        @endforelse
    </tbody></table></div>

    <h2>Πάγια προς πλήρη απόσβεση</h2>
    <div class="notice">{{ $nearFull->count() }} πάγια αναμένεται να ολοκληρώσουν την απόσβεσή τους στην επόμενη περίοδο. Πλήρως αποσβεσμένα: {{ $fullyDepreciated->count() }}.</div>

    <h2>Συμφωνία διαγραφών και μεταβιβάσεων</h2>
    <div class="table-wrap"><table><thead><tr><th>Ημερομηνία</th><th>Κωδικός</th><th>Πάγιο</th><th>Τύπος</th><th>Αναπόσβεστη αξία βιβλίου</th><th>Έσοδο</th><th>Κέρδος / Ζημία</th></tr></thead><tbody>
        @forelse ($disposals as $disposal)
            <tr><td>{{ $disposal['date']?->format('d/m/Y') }}</td><td>{{ $disposal['code'] }}</td><td>{{ $disposal['name'] }}</td><td>{{ $disposal['type'] }}</td><td class="number">€ {{ number_format($disposal['book_value'], 2, ',', '.') }}</td><td class="number">€ {{ number_format($disposal['proceeds'], 2, ',', '.') }}</td><td class="number">€ {{ number_format($disposal['gain_loss'], 2, ',', '.') }}</td></tr>
        @empty
            <tr><td colspan="7">Δεν υπάρχουν διαγραφές ή μεταβιβάσεις έως την ημερομηνία αναφοράς.</td></tr>
        @endforelse
    </tbody></table></div>
@include('filament.partials.contextual-help')
</body>
</html>
