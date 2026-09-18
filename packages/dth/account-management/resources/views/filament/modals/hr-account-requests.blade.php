@php
    $requests = $requests ?? [];
@endphp

<div class="space-y-3">
    @forelse ($requests as $item)
        <div style="border:1px solid #e2e8f0;border-radius:14px;padding:14px 16px;background:#fff;display:flex;gap:16px;align-items:flex-start;justify-content:space-between;">
            <div style="min-width:0;flex:1;">
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:6px;">
                    <strong style="font-size:14px;color:#0f172a;">{{ $item['employee_code'] }} · {{ $item['full_name'] }}</strong>
                    @if (($item['type'] ?? '') === 'sync_account_identity')
                        <span style="font-size:11px;font-weight:700;padding:3px 8px;border-radius:999px;background:#fff7ed;color:#c2410c;">{{ \Dth\AccountManagement\Support\UiText::get('hr_requests.sync_identity', 'Cần đồng bộ danh tính') }}</span>
                    @else
                        <span style="font-size:11px;font-weight:700;padding:3px 8px;border-radius:999px;background:#eff6ff;color:#1d4ed8;">{{ \Dth\AccountManagement\Support\UiText::get('hr_requests.provision', 'Yêu cầu cấp tài khoản') }}</span>
                    @endif
                </div>

                <div style="font-size:13px;color:#64748b;line-height:1.55;">
                    @if (! empty($item['requested_email']))
                        <div>{{ \Dth\AccountManagement\Support\UiText::get('hr_requests.requested_email', 'Email đề nghị') }}: <strong style="color:#334155;">{{ $item['requested_email'] }}</strong></div>
                    @endif
                    @if (! empty($item['requested_phone']))
                        <div>{{ \Dth\AccountManagement\Support\UiText::get('hr_requests.phone', 'Số điện thoại') }}: {{ $item['requested_phone'] }}</div>
                    @endif
                    @if (! empty($item['differences']))
                        <div>{{ \Dth\AccountManagement\Support\UiText::get('hr_requests.differences', 'Thông tin đang khác') }}: {{ implode(', ', $item['differences']) }}</div>
                    @endif
                    @if (! empty($item['note']))
                        <div>{{ \Dth\AccountManagement\Support\UiText::get('hr_requests.note', 'Ghi chú') }}: {{ $item['note'] }}</div>
                    @endif
                    <div style="margin-top:4px;font-size:12px;color:#94a3b8;">
                        {{ \Dth\AccountManagement\Support\UiText::get('hr_requests.requested_by', 'Gửi bởi') }} {{ $item['requested_by_name'] ?: 'HR' }}{{ ! empty($item['requested_at']) ? ' · '.$item['requested_at'] : '' }}
                    </div>
                </div>
            </div>

            @if (! empty($item['action_url']))
                <a href="{{ $item['action_url'] }}" class="fi-btn dth-acc-entry-action" style="white-space:nowrap;text-decoration:none;">
                    {{ ($item['type'] ?? '') === 'sync_account_identity' ? \Dth\AccountManagement\Support\UiText::get('hr_requests.review_account', 'Kiểm tra tài khoản') : \Dth\AccountManagement\Support\UiText::get('hr_requests.create_account', 'Tạo tài khoản') }}
                </a>
            @endif
        </div>
    @empty
        <div style="padding:20px;text-align:center;color:#64748b;">
            {{ \Dth\AccountManagement\Support\UiText::get('hr_requests.empty', 'Không có yêu cầu tài khoản nào đang chờ xử lý.') }}
        </div>
    @endforelse
</div>
