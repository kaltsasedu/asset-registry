<!doctype html>
<html lang="el">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Μαζική Εισαγωγή Παγίων</title>
    <style>
        body{margin:0;padding:28px;background:#f6f8fb;color:#172033;font-family:Arial,sans-serif}.wrap{max-width:1280px;margin:auto}.header{display:flex;justify-content:space-between;align-items:flex-start;gap:16px}.actions{display:flex;gap:8px;flex-wrap:wrap}.panel{margin-top:18px;padding:18px;border:1px solid #d8e0ea;border-radius:10px;background:#fff}.grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}label{display:grid;gap:6px;font-size:13px;font-weight:700}select,input[type=file]{min-height:42px;padding:8px;border:1px solid #b8c2d0;border-radius:6px;background:#fff}.button,button{display:inline-flex;min-height:40px;align-items:center;justify-content:center;padding:0 14px;border:0;border-radius:6px;background:#1769aa;color:#fff;text-decoration:none;font-weight:700;cursor:pointer}.secondary{background:#475467}.success{border-left:4px solid #16a34a;background:#f0fdf4}.error{border-left:4px solid #dc2626;background:#fef2f2}.summary{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}.summary div{padding:14px;border:1px solid #d8e0ea;border-radius:8px}.summary strong{display:block;font-size:24px;margin-top:5px}.table-wrap{overflow:auto;max-height:600px}table{width:100%;border-collapse:collapse;font-size:13px}th,td{padding:8px;border:1px solid #d8e0ea;text-align:left;vertical-align:top}th{position:sticky;top:0;background:#edf3fa}.valid{color:#15803d;font-weight:700}.invalid{color:#b91c1c;font-weight:700}.muted{color:#667085;font-size:13px}ul{margin:5px 0;padding-left:18px}@media(max-width:800px){body{padding:14px}.header{flex-direction:column}.grid,.summary{grid-template-columns:1fr}}
    </style>
</head>
<body><div class="wrap">
    <div class="header"><div><h1>Μαζική Εισαγωγή Παγίων</h1><p class="muted">Έλεγχος και προεπισκόπηση πριν από οποιαδήποτε μεταβολή στο μητρώο.</p></div><div class="actions"><a class="button secondary" href="{{ url('/admin/assets') }}">← Πάγια</a><a class="button secondary" href="{{ url('/admin') }}">Πίνακας Ελέγχου</a></div></div>

    @if ($errors->any())<div class="panel error"><strong>Η προεπισκόπηση δεν ολοκληρώθηκε.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    @if ($result)
        <div class="panel success"><h2>Η εισαγωγή ολοκληρώθηκε</h2><div class="summary"><div>Νέα πάγια<strong>{{ $result['created'] }}</strong></div><div>Ενημερωμένα<strong>{{ $result['updated'] }}</strong></div><div>Αποτυχημένες γραμμές<strong>{{ count($result['failed']) }}</strong></div></div>@if($result['report_available'] ?? false)<p><a class="button" href="{{ route('assets.import.report') }}">Λήψη αναλυτικής αναφοράς Excel</a></p>@endif</div>
        @if ($result['failed'])<div class="panel"><h2>Γραμμές που δεν εισήχθησαν</h2><div class="table-wrap"><table><thead><tr><th>Γραμμή</th><th>Σφάλματα</th></tr></thead><tbody>@foreach(array_slice($result['failed'],0,100) as $failed)<tr><td>{{ $failed['row_number'] }}</td><td>{{ implode(' · ', $failed['errors']) }}</td></tr>@endforeach</tbody></table></div></div>@endif
    @endif

    <div class="panel"><h2>1. Προετοιμασία αρχείου</h2><p>Χρησιμοποιήστε το πρότυπο για σωστές ελληνικές κεφαλίδες. Οι κατηγορίες και τα προαιρετικά μητρώα πρέπει να έχουν ήδη καταχωριστεί.</p><a class="button secondary" href="{{ route('assets.import.template') }}">Λήψη προτύπου Excel</a></div>

    <form class="panel" method="post" action="{{ route('assets.import.preview') }}" enctype="multipart/form-data">@csrf<h2>2. Επιλογή αρχείου και λειτουργίας</h2><div class="grid"><label>Λειτουργία<select name="mode" required><option value="create_only" @selected($mode==='create_only')>Μόνο δημιουργία νέων παγίων</option><option value="update_existing" @selected($mode==='update_existing')>Ενημέρωση υπαρχόντων με κωδικό παγίου</option></select></label><label>Αρχείο Excel ή CSV<input type="file" name="import_file" accept=".xlsx,.xls,.csv" required></label></div><p><button type="submit">Έλεγχος και προεπισκόπηση</button></p></form>

    @if ($preview)
        <div class="panel"><h2>3. Αποτέλεσμα ελέγχου</h2><div class="summary"><div>Σύνολο γραμμών<strong>{{ $preview['total_count'] }}</strong></div><div>Έγκυρες<strong>{{ $preview['valid_count'] }}</strong></div><div>Με σφάλματα<strong>{{ $preview['invalid_count'] }}</strong></div></div>
            <p class="muted">Εμφανίζονται έως 100 γραμμές. Οι γραμμές με σφάλματα θα παραλειφθούν.</p><div class="table-wrap"><table><thead><tr><th>Γραμμή</th><th>Κατάσταση</th><th>Κωδικός</th><th>Περιγραφή</th><th>Κατηγορία</th><th>Σειριακός αριθμός</th><th>Αξία</th><th>Σφάλματα</th></tr></thead><tbody>@foreach(array_slice($preview['rows'],0,100) as $row)<tr><td>{{ $row['row_number'] }}</td><td class="{{ $row['valid']?'valid':'invalid' }}">{{ $row['valid']?'Έγκυρη':'Σφάλμα' }}</td><td>{{ $row['display']['asset_code'] }}</td><td>{{ $row['display']['name'] }}</td><td>{{ $row['display']['category'] }}</td><td>{{ $row['display']['serial_number'] }}</td><td>{{ $row['display']['purchase_value'] !== null ? number_format($row['display']['purchase_value'],2,',','.') . ' €' : '' }}</td><td>{{ implode(' · ', $row['errors']) }}</td></tr>@endforeach</tbody></table></div>
            @if($preview['valid_count']>0)<form method="post" action="{{ route('assets.import.store') }}" onsubmit="return confirm('Να εκτελεστεί η εισαγωγή των έγκυρων γραμμών;')">@csrf<input type="hidden" name="token" value="{{ $token }}"><p><button type="submit">Εκτέλεση εισαγωγής {{ $preview['valid_count'] }} γραμμών</button></p></form>@endif
        </div>
    @endif
</div>@include('filament.partials.contextual-help')</body></html>
