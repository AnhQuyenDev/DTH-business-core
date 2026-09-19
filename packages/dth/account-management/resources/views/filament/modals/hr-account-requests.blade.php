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
                        <div style="margin-top:8px;padding:9px 11px;border:1px solid #dbeafe;border-radius:10px;background:#f8fbff;color:#334155;">
                            <div style="font-size:11px;font-weight:800;color:#2563eb;margin-bottom:2px;">{{ \Dth\AccountManagement\Support\UiText::get('hr_requests.note', 'Ghi chú từ Nhân sự') }}</div>
                            <div>{{ $item['note'] }}</div>
                        </div>
                    @endif
                    <div style="margin-top:7px;font-size:12px;color:#94a3b8;">
                        {{ \Dth\AccountManagement\Support\UiText::get('hr_requests.requested_by', 'Gửi bởi') }} {{ $item['requested_by_name'] ?: 'HR' }}{{ ! empty($item['requested_at']) ? ' · '.$item['requested_at'] : '' }}
                    </div>
                </div>
            </div>

            <div style="display:flex;flex-direction:column;gap:8px;align-items:stretch;min-width:170px;">
                @if (($item['type'] ?? '') === 'sync_account_identity')
                    @php($syncModalId = 'sync-hr-identity-'.(int) $item['employee_id'])
                    <x-filament::button
                        type="button"
                        color="gray"
                        icon="heroicon-o-arrows-right-left"
                        class="dth-acc-entry-action"
                        x-on:click="$dispatch('open-modal', { id: '{{ $syncModalId }}' })"
                    >
                        {{ \Dth\AccountManagement\Support\UiText::get('hr_requests.sync_complete', 'Đồng bộ & hoàn tất') }}
                    </x-filament::button>

                    <x-filament::modal :id="$syncModalId" width="lg" icon="heroicon-o-arrows-right-left">
                        <x-slot name="heading">
                            {{ \Dth\AccountManagement\Support\UiText::get('hr_requests.sync_complete', 'Đồng bộ & hoàn tất') }}
                        </x-slot>
                        <x-slot name="description">
                            {{ \Dth\AccountManagement\Support\UiText::get('hr_requests.sync_confirm', 'Đồng bộ tên, email và số điện thoại của tài khoản theo hồ sơ Nhân sự rồi hoàn tất yêu cầu?') }}
                        </x-slot>

                        <div style="display:grid;gap:8px;padding:4px 0;color:#64748b;font-size:13px;line-height:1.55;">
                            <div style="padding:11px 12px;border:1px solid #e2e8f0;border-radius:12px;background:#f8fafc;">
                                <strong style="display:block;color:#172033;margin-bottom:3px;">{{ $item['employee_code'] }} · {{ $item['full_name'] }}</strong>
                                @if (! empty($item['differences']))
                                    <span>{{ \Dth\AccountManagement\Support\UiText::get('hr_requests.differences', 'Thông tin đang khác') }}: {{ implode(', ', $item['differences']) }}</span>
                                @endif
                            </div>
                            <span>{{ \Dth\AccountManagement\Support\UiText::get('hr_requests.sync_modal_body', 'Hệ thống sẽ cập nhật danh tính tài khoản theo hồ sơ Nhân sự, đóng yêu cầu và thông báo kết quả cho người gửi.') }}</span>
                        </div>

                        <x-slot name="footerActions">
                            <x-filament::button
                                type="button"
                                color="gray"
                                class="dth-acc-form-action dth-acc-form-action--secondary"
                                x-on:click="$dispatch('close-modal', { id: '{{ $syncModalId }}' })"
                            >
                                {{ \Dth\AccountManagement\Support\UiText::get('common.actions.cancel', 'Hủy') }}
                            </x-filament::button>
                            <x-filament::button
                                type="button"
                                class="dth-acc-form-action dth-acc-form-action--primary"
                                wire:click="synchronizeHrIdentityRequest({{ (int) $item['employee_id'] }})"
                                wire:loading.attr="disabled"
                                wire:target="synchronizeHrIdentityRequest"
                            >
                                {{ \Dth\AccountManagement\Support\UiText::get('hr_requests.sync_complete', 'Đồng bộ & hoàn tất') }}
                            </x-filament::button>
                        </x-slot>
                    </x-filament::modal>
                @endif

                @if (! empty($item['action_url']))
                    <a href="{{ $item['action_url'] }}" class="fi-btn dth-acc-entry-action" style="white-space:nowrap;text-decoration:none;justify-content:center;">
                        {{ ($item['type'] ?? '') === 'sync_account_identity' ? \Dth\AccountManagement\Support\UiText::get('hr_requests.review_account', 'Mở tài khoản') : \Dth\AccountManagement\Support\UiText::get('hr_requests.create_account', 'Tạo tài khoản') }}
                    </a>
                @endif
            </div>
        </div>
    @empty
        <div style="padding:20px;text-align:center;color:#64748b;">
            {{ \Dth\AccountManagement\Support\UiText::get('hr_requests.empty', 'Không có yêu cầu tài khoản nào đang chờ xử lý.') }}
        </div>
    @endforelse
</div>
