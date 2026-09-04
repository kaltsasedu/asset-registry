<!doctype html>
<html lang="el">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 6.5px; color: #172033; }
        h1 { margin: 0 0 3px; font-size: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #b8c2cf; padding: 3px; text-align: left; vertical-align: top; }
        th { background: #eaf2ff; }
        .meta { margin: 0 0 10px; color: #596579; }
    </style>
</head>
<body>
    @include('pdf.partials.organization-header')
    <h1>Μητρώο Παγίων</h1>
    <p class="meta">Ημερομηνία έκδοσης: {{ now()->format('d/m/Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>Κωδ.</th><th>Πάγιο / Περιγραφή</th><th>Κατηγορία</th><th>Προμηθευτής</th>
                <th>Σύμβαση / Χρηματοδότηση</th><th>Θέση</th><th>Serial / Μοντέλο</th><th>Απόκτηση</th>
                <th>Αξία</th><th>Αποσβέσεις</th><th>Τιμολόγιο</th><th>Κατάσταση</th>
            </tr>
        </thead>
        <tbody>
        @foreach($assets as $asset)
            <tr>
                <td>{{ $asset->asset_code }}</td>
                <td>{{ $asset->name }}<br>{{ $asset->description }}</td>
                <td>{{ $asset->category?->name }}</td>
                <td>{{ $asset->supplier?->name }}</td>
                <td>{{ $asset->procurementContract?->contract_number }}<br>{{ $asset->fundingSource?->name }}<br>{{ $asset->costCenter?->name }}</td>
                <td>{{ $asset->location?->name }}<br>{{ $asset->department?->name }}</td>
                <td>{{ $asset->serial_number }}<br>{{ $asset->manufacturer }} {{ $asset->model }}</td>
                <td>{{ $asset->purchase_date?->format('d/m/Y') }}<br>Απόσβ.: {{ $asset->depreciation_start_date?->format('d/m/Y') }}</td>
                <td>Κτήση: {{ number_format($asset->purchase_value, 2, ',', '.') }} €<br>ΦΠΑ: {{ number_format($asset->vat_amount, 2, ',', '.') }} €<br>Υπολ.: {{ number_format($asset->residual_value, 2, ',', '.') }} €</td>
                <td>Συντ.: {{ $asset->depreciation_rate }}%<br>Συσσ.: {{ number_format($asset->accumulated_depreciation, 2, ',', '.') }} €<br>Αναπ.: {{ number_format($asset->remaining_value, 2, ',', '.') }} €</td>
                <td>{{ $asset->invoice_number }}<br>{{ $asset->invoice_date?->format('d/m/Y') }}</td>
                <td>{{ \App\Models\AssetStatusTransaction::STATUSES[$asset->status] ?? $asset->status }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    @include('pdf.partials.organization-footer')
</body>
</html>
