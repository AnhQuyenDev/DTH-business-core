<div style="overflow-x:auto">
    <table style="width:100%;border-collapse:collapse;font-size:14px">
        <thead>
            <tr>
                @foreach ([
                    \Dth\Marketing\Support\UiText::get('submission.name', 'Name'),
                    \Dth\Marketing\Support\UiText::get('submission.email', 'Email'),
                    \Dth\Marketing\Support\UiText::get('submission.phone', 'Phone'),
                    \Dth\Marketing\Support\UiText::get('submission.type', 'Type'),
                    \Dth\Marketing\Support\UiText::get('submission.source', 'Source'),
                    \Dth\Marketing\Support\UiText::get('submission.lead_status', 'Lead status'),
                    \Dth\Marketing\Support\UiText::get('submission.submitted_at', 'Submitted'),
                ] as $h)
                    <th style="text-align:left;padding:9px;border-bottom:1px solid #d1d5db">{{ $h }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
        @forelse ($sample as $row)
            <tr>
                <td style="padding:9px;border-bottom:1px solid #e5e7eb">{{ $row['name'] ?: '—' }}</td>
                <td style="padding:9px;border-bottom:1px solid #e5e7eb">{{ $row['email'] ?: '—' }}</td>
                <td style="padding:9px;border-bottom:1px solid #e5e7eb">{{ $row['phone'] ?: '—' }}</td>
                <td style="padding:9px;border-bottom:1px solid #e5e7eb">{{ $row['type'] ?: '—' }}</td>
                <td style="padding:9px;border-bottom:1px solid #e5e7eb">{{ $row['source'] ?: '—' }}</td>
                <td style="padding:9px;border-bottom:1px solid #e5e7eb">{{ $row['lead_status'] ?: 'N/A' }}</td>
                <td style="padding:9px;border-bottom:1px solid #e5e7eb">{{ $row['submitted_at'] ?: '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="7" style="padding:18px;text-align:center">{{ \Dth\Marketing\Support\UiText::get('segment.no_matches', 'No matching contacts.') }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
