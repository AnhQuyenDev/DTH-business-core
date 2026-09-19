@php use Dth\NotificationCenter\Support\UiText; @endphp
<x-filament-panels::page>
    <form wire:submit="save" class="dth-notify-settings-grid">
        @if(config('dth-notification-center.features.in_app', true))
        <section class="dth-notify-settings-card">
            <div class="dth-notify-settings-card__head">
                <span class="dth-notify-icon dth-notify-icon--blue"><x-filament::icon icon="heroicon-o-bell" /></span>
                <div>
                    <h2>In-app</h2>
                    <p>{{ UiText::get('settings.in_app_help', 'Hiển thị thông báo trên biểu tượng chuông và Notification Center.') }}</p>
                </div>
            </div>
            <label class="dth-notify-setting-row">
                <span>
                    <strong>{{ UiText::get('settings.in_app', 'Nhận thông báo In-app') }}</strong>
                    <small>{{ UiText::get('settings.in_app_help', 'Hiển thị thông báo trên biểu tượng chuông và Notification Center.') }}</small>
                </span>
                <input type="checkbox" wire:model="inAppEnabled" class="dth-notify-switch-input">
            </label>
            <label class="dth-notify-setting-row">
                <span>
                    <strong>{{ UiText::get('settings.content_preview', 'Hiển thị nội dung xem trước trên chuông') }}</strong>
                    <small>{{ UiText::get('settings.content_preview_help', 'Tắt nếu bạn không muốn nội dung chi tiết xuất hiện trong panel thông báo nhanh.') }}</small>
                </span>
                <input type="checkbox" wire:model="contentPreview" class="dth-notify-switch-input">
            </label>
        </section>
        @endif

        @if(config('dth-notification-center.features.email', true))
        <section class="dth-notify-settings-card">
            <div class="dth-notify-settings-card__head">
                <span class="dth-notify-icon dth-notify-icon--green"><x-filament::icon icon="heroicon-o-envelope" /></span>
                <div>
                    <h2>Email</h2>
                    <p>{{ UiText::get('settings.email_help', 'Cho phép gửi email giao dịch từ hệ thống tới địa chỉ tài khoản của bạn.') }}</p>
                </div>
            </div>
            <label class="dth-notify-setting-row">
                <span>
                    <strong>{{ UiText::get('settings.email', 'Nhận thông báo Email') }}</strong>
                    <small>{{ UiText::get('settings.email_help', 'Cho phép gửi email giao dịch từ hệ thống tới địa chỉ tài khoản của bạn.') }}</small>
                </span>
                <input type="checkbox" wire:model="emailEnabled" class="dth-notify-switch-input">
            </label>
        </section>
        @endif

        <section class="dth-notify-settings-card dth-notify-settings-card--wide">
            <div class="dth-notify-settings-card__head">
                <span class="dth-notify-icon dth-notify-icon--slate"><x-filament::icon icon="heroicon-o-adjustments-horizontal" /></span>
                <div>
                    <h2>{{ UiText::get('settings.muted_modules', 'Tạm tắt thông báo theo phân hệ') }}</h2>
                    <p>{{ UiText::get('settings.muted_modules_help', 'Thông báo bắt buộc vẫn được gửi dù phân hệ đang bị tạm tắt.') }}</p>
                </div>
            </div>
            <div class="dth-notify-module-options">
                @foreach($this->moduleOptions() as $key => $label)
                    <label>
                        <input type="checkbox" value="{{ $key }}" wire:model="mutedModules">
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </section>

        <div class="dth-notify-settings-actions">
            <button type="submit" class="dth-notify-primary-link">
                <x-filament::icon icon="heroicon-o-check" />
                {{ UiText::get('actions.save', 'Lưu cài đặt') }}
            </button>
        </div>
    </form>
</x-filament-panels::page>
