<table style="width:100%;border-collapse:collapse;margin:0 0 12px;border:0">
    <tr>
        @if($logoDataUri)
            <td style="width:68px;border:0;padding:0 10px 0 0;vertical-align:middle">
                <img src="{{ $logoDataUri }}" alt="Λογότυπο" style="max-width:58px;max-height:58px">
            </td>
        @endif
        <td style="border:0;padding:0;vertical-align:middle">
            <div style="font-size:15px;font-weight:bold;color:#172033">{{ $settings->organization_name }}</div>
            @if($settings->tax_id)<div style="font-size:9px;color:#596579">ΑΦΜ: {{ $settings->tax_id }}</div>@endif
            @if($settings->contactLine())<div style="font-size:9px;color:#596579">{{ $settings->contactLine() }}</div>@endif
        </td>
    </tr>
</table>
