<!doctype html>
<html lang="el">
<head>
    <meta charset="utf-8">
    <title>Δελτίο Εργασίας Συντήρησης</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 12px; }
        h1 { margin: 0 0 4px; font-size: 20px; }
        .muted { color: #667085; } table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        th, td { border: 1px solid #cbd5e1; padding: 8px; text-align: left; vertical-align: top; }
        th { width: 30%; background: #f1f5f9; } .signatures { margin-top: 52px; width: 100%; }
        .signature { display: inline-block; width: 45%; margin-right: 4%; border-top: 1px solid #475569; padding-top: 8px; text-align: center; }
    </style>
</head>
<body>
    @include('pdf.partials.organization-header')
    <h1>Δελτίο Εργασίας Συντήρησης</h1>
    <div class="muted">Αριθμός εργασίας: {{ $maintenance->id }}</div>
    <table>
        <tr><th>Πάγιο</th><td>{{ $maintenance->asset?->asset_code }} — {{ $maintenance->asset?->name }}</td></tr>
        <tr><th>Κατηγορία / Τοποθεσία</th><td>{{ $maintenance->asset?->category?->name ?? '—' }} / {{ $maintenance->asset?->location?->name ?? '—' }}</td></tr>
        <tr><th>Εργασία</th><td>{{ $maintenance->title }}</td></tr>
        <tr><th>Είδος / Κατάσταση</th><td>{{ \App\Models\AssetMaintenance::TYPES[$maintenance->maintenance_type] }} / {{ \App\Models\AssetMaintenance::STATUSES[$maintenance->status] }}</td></tr>
        <tr><th>Προγραμματισμός</th><td>{{ $maintenance->scheduled_at?->format('d/m/Y') ?? '—' }}</td></tr>
        <tr><th>Τεχνικός / Συνεργείο</th><td>{{ $maintenance->technician ?? '—' }}</td></tr>
        <tr><th>Ανατέθηκε σε</th><td>{{ $maintenance->assignee?->name ?? '—' }}</td></tr>
        <tr><th>Περιγραφή</th><td>{{ $maintenance->description ?? '—' }}</td></tr>
        <tr><th>Παρατηρήσεις</th><td>{{ $maintenance->notes ?? '—' }}</td></tr>
    </table>
    <div class="signatures">
        <div class="signature">Υπεύθυνος παγίου</div>
        <div class="signature">Τεχνικός / Συνεργείο</div>
    </div>
    @include('pdf.partials.organization-footer')
</body>
</html>
