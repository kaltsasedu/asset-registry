<!doctype html>
<html lang="el">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #172033; }
        h1 { margin: 0 0 4px; font-size: 16px; } h2 { margin: 18px 0 7px; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; } th, td { border: 1px solid #bfc9d8; padding: 4px; text-align: left; vertical-align: top; } th { background: #eaf2fb; } .number { text-align: right; } .summary td { width: 25%; } .muted { color: #667085; }
    </style>
</head>
<body>
    @include('pdf.partials.organization-header')
    <h1>Οικονομικό Μητρώο Παγίων</h1>
    <p>Βιβλίο αποσβέσεων: <strong>{{ $book?->name }}</strong> | Ημερομηνία αναφοράς: {{ $asOf->format('d/m/Y') }} | Έκδοση: {{ now()->format('d/m/Y H:i') }}</p>
    <table class="summary"><tr><td>Αξία κτήσης<br><strong>€ {{ number_format($registerRows->sum('purchase_value'), 2, ',', '.') }}</strong></td><td>Σωρευμένες αποσβέσεις<br><strong>€ {{ number_format($registerRows->sum('accumulated_depreciation'), 2, ',', '.') }}</strong></td><td>Αναπόσβεστη αξία<br><strong>€ {{ number_format($registerRows->sum('remaining_value'), 2, ',', '.') }}</strong></td><td>Αποσβέσεις περιόδου<br><strong>€ {{ number_format($periodEntries->sum('amount'), 2, ',', '.') }}</strong></td></tr></table>
    <h2>Μητρώο παγίων</h2>
    <table><thead><tr><th>Κωδ.</th><th>Πάγιο</th><th>Κατηγορία</th><th>Τμήμα</th><th>Τοποθεσία</th><th>Αξία κτήσης</th><th>Συσσ. αποσβ.</th><th>Αναπόσβ. αξία</th></tr></thead><tbody>
        @foreach ($registerRows as $row)
            <tr><td>{{ $row['code'] }}</td><td>{{ $row['name'] }}</td><td>{{ $row['category'] }}</td><td>{{ $row['department'] }}</td><td>{{ $row['location'] }}</td><td class="number">{{ number_format($row['purchase_value'], 2, ',', '.') }}</td><td class="number">{{ number_format($row['accumulated_depreciation'], 2, ',', '.') }}</td><td class="number">{{ number_format($row['remaining_value'], 2, ',', '.') }}</td></tr>
        @endforeach
    </tbody></table>
    <h2>Ανάλυση ανά κατηγορία</h2>
    <table><thead><tr><th>Κατηγορία</th><th>Πάγια</th><th>Αξία κτήσης</th><th>Σωρευμένες αποσβέσεις</th><th>Αναπόσβεστη αξία</th></tr></thead><tbody>
        @foreach ($byCategory as $item)
            <tr><td>{{ $item['label'] }}</td><td>{{ $item['assets'] }}</td><td class="number">{{ number_format($item['purchase_value'], 2, ',', '.') }}</td><td class="number">{{ number_format($item['accumulated_depreciation'], 2, ',', '.') }}</td><td class="number">{{ number_format($item['remaining_value'], 2, ',', '.') }}</td></tr>
        @endforeach
    </tbody></table>
    <h2>Ανάλυση ανά τμήμα και τοποθεσία</h2>
    <table><thead><tr><th>Τμήμα</th><th>Πάγια</th><th>Αξία κτήσης</th><th>Αναπόσβεστη αξία</th><th>Τοποθεσία</th><th>Πάγια</th><th>Αξία κτήσης</th><th>Αναπόσβεστη αξία</th></tr></thead><tbody>
        @php
            $dimensionRows = max($byDepartment->count(), $byLocation->count());
        @endphp
        @for ($index = 0; $index < $dimensionRows; $index++)
            @php
                $department = $byDepartment->get($index);
                $location = $byLocation->get($index);
            @endphp
            <tr><td>{{ $department['label'] ?? '' }}</td><td>{{ $department['assets'] ?? '' }}</td><td class="number">{{ isset($department) ? number_format($department['purchase_value'], 2, ',', '.') : '' }}</td><td class="number">{{ isset($department) ? number_format($department['remaining_value'], 2, ',', '.') : '' }}</td><td>{{ $location['label'] ?? '' }}</td><td>{{ $location['assets'] ?? '' }}</td><td class="number">{{ isset($location) ? number_format($location['purchase_value'], 2, ',', '.') : '' }}</td><td class="number">{{ isset($location) ? number_format($location['remaining_value'], 2, ',', '.') : '' }}</td></tr>
        @endfor
    </tbody></table>
    <h2>Ανάλυση χρηματοδότησης, κέντρων κόστους και συμβάσεων</h2>
    <table><thead><tr><th>Διάσταση</th><th>Ονομασία</th><th>Πάγια</th><th>Αξία κτήσης</th><th>Σωρευμένες αποσβέσεις</th><th>Αναπόσβεστη αξία</th></tr></thead><tbody>
        @foreach (['Χρηματοδότηση' => $byFundingSource, 'Κέντρο κόστους' => $byCostCenter, 'Σύμβαση' => $byContract] as $dimension => $items)
            @foreach ($items as $item)
                <tr><td>{{ $dimension }}</td><td>{{ $item['label'] }}</td><td>{{ $item['assets'] }}</td><td class="number">{{ number_format($item['purchase_value'], 2, ',', '.') }}</td><td class="number">{{ number_format($item['accumulated_depreciation'], 2, ',', '.') }}</td><td class="number">{{ number_format($item['remaining_value'], 2, ',', '.') }}</td></tr>
            @endforeach
        @endforeach
    </tbody></table>
    <h2>Συμφωνία διαγραφών και μεταβιβάσεων</h2>
    <table><thead><tr><th>Ημ.</th><th>Κωδικός</th><th>Πάγιο</th><th>Τύπος</th><th>Αναπόσβ. αξία</th><th>Έσοδο</th><th>Κέρδος / Ζημία</th></tr></thead><tbody>
        @foreach ($disposals as $disposal)
            <tr><td>{{ $disposal['date']?->format('d/m/Y') }}</td><td>{{ $disposal['code'] }}</td><td>{{ $disposal['name'] }}</td><td>{{ $disposal['type'] }}</td><td class="number">{{ number_format($disposal['book_value'], 2, ',', '.') }}</td><td class="number">{{ number_format($disposal['proceeds'], 2, ',', '.') }}</td><td class="number">{{ number_format($disposal['gain_loss'], 2, ',', '.') }}</td></tr>
        @endforeach
    </tbody></table>
    @include('pdf.partials.organization-footer')
</body>
</html>
