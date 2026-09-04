<!doctype html>
<html lang="el">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 6mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; color: #111827; }
        .label-sheet { width: 100%; border-collapse: separate; border-spacing: 2mm; table-layout: fixed; }
        .label-cell {
            position: relative;
            height: {{ $layout['cell_height'] }}mm;
            border: 0.35mm solid #64748b;
            border-radius: 1.5mm;
            padding: 2mm;
            overflow: hidden;
            vertical-align: top;
            font-size: {{ $layout['font_size'] }}pt;
            line-height: 1.22;
        }
        .empty-cell { border-color: transparent; }
        .qr { position: absolute; top: 2mm; right: 2mm; width: {{ $layout['qr_size'] }}mm; height: {{ $layout['qr_size'] }}mm; }
        .qr img { display: block; width: 100%; height: 100%; }
        .details { padding-right: {{ $layout['qr_size'] + 3 }}mm; }
        .brand-logo { float: left; width: 5mm; height: 5mm; margin: 0 1mm 0.5mm 0; object-fit: contain; }
        .registry { margin-bottom: 0.8mm; color: #475569; font-size: 0.88em; font-weight: bold; }
        .code { margin-bottom: 0.8mm; color: #0f3b70; font-size: 1.25em; font-weight: bold; }
        .name { margin-bottom: 1mm; font-weight: bold; }
        .meta { margin-top: 0.55mm; color: #334155; }
        .meta strong { color: #111827; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
@foreach($labels->chunk($perPage) as $pageIndex => $page)
    <table class="label-sheet">
        <tbody>
        @foreach($page->chunk($layout['columns']) as $row)
            <tr>
                @foreach($row as $label)
                    @if($label)
                        @php($asset = $label['asset'])
                        <td class="label-cell">
                            <div class="details">
                                @if($logoDataUri)<img class="brand-logo" src="{{ $logoDataUri }}" alt="Λογότυπο">@endif
                                <div class="registry">{{ $settings->organization_name }}</div>
                                <div class="code">{{ $asset->asset_code }}</div>
                                <div class="name">{{ \Illuminate\Support\Str::limit($asset->name, 62) }}</div>
                                @if($options['show_serial'] && filled($asset->serial_number))
                                    <div class="meta"><strong>Serial:</strong> {{ \Illuminate\Support\Str::limit($asset->serial_number, 34) }}</div>
                                @endif
                                @if($options['show_location'] && $asset->location)
                                    <div class="meta"><strong>Θέση:</strong> {{ \Illuminate\Support\Str::limit($asset->location->name, 34) }}</div>
                                @endif
                                @if($options['show_department'] && $asset->department)
                                    <div class="meta"><strong>Τμήμα:</strong> {{ \Illuminate\Support\Str::limit($asset->department->name, 34) }}</div>
                                @endif
                                @if($options['show_custodian'] && $asset->custodian)
                                    <div class="meta"><strong>Υπεύθυνος:</strong> {{ \Illuminate\Support\Str::limit($asset->custodian->name, 30) }}</div>
                                @endif
                            </div>
                            <div class="qr"><img src="data:image/svg+xml;base64,{{ $label['qr'] }}" alt="QR {{ $asset->asset_code }}"></div>
                        </td>
                    @else
                        <td class="label-cell empty-cell"></td>
                    @endif
                @endforeach
                @for($missing = $row->count(); $missing < $layout['columns']; $missing++)
                    <td class="label-cell empty-cell"></td>
                @endfor
            </tr>
        @endforeach
        </tbody>
    </table>
    @if(!$loop->last)<div class="page-break"></div>@endif
@endforeach
</body>
</html>
