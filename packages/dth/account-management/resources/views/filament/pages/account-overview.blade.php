@php
    $t = static fn (string $key, string $fallback): string => \Dth\AccountManagement\Support\UiText::get($key, $fallback);
@endphp

<div class="dth-acc-overview">
    <div class="dth-acc-kpis">
        <div class="dth-acc-kpi"><span class="dth-acc-kpi__icon"><x-filament::icon icon="heroicon-o-users" /></span><div><span>{{ $t('overview.total_users', 'Tổng tài khoản') }}</span><strong>{{ number_format($snapshot['total_users']) }}</strong><small>{{ $t('overview.total_users_help', 'Người dùng đã đăng ký') }}</small></div></div>
        <div class="dth-acc-kpi"><span class="dth-acc-kpi__icon"><x-filament::icon icon="heroicon-o-check-circle" /></span><div><span>{{ $t('overview.active_users', 'Đang hoạt động') }}</span><strong>{{ number_format($snapshot['active_users']) }}</strong><small>{{ $t('overview.active_users_help', 'Có thể truy cập hệ thống') }}</small></div></div>
        <div class="dth-acc-kpi"><span class="dth-acc-kpi__icon"><x-filament::icon icon="heroicon-o-lock-closed" /></span><div><span>{{ $t('overview.locked_users', 'Đang bị khóa') }}</span><strong>{{ number_format($snapshot['locked_users']) }}</strong><small>{{ $t('overview.locked_users_help', 'Cần kiểm tra bảo mật') }}</small></div></div>
        <div class="dth-acc-kpi"><span class="dth-acc-kpi__icon"><x-filament::icon icon="heroicon-o-shield-check" /></span><div><span>{{ $t('overview.roles', 'Vai trò') }}</span><strong>{{ number_format($snapshot['roles']) }}</strong><small>{{ $t('overview.roles_help', 'Vai trò đang hoạt động') }}</small></div></div>
        <div class="dth-acc-kpi"><span class="dth-acc-kpi__icon"><x-filament::icon icon="heroicon-o-computer-desktop" /></span><div><span>{{ $t('overview.active_sessions', 'Phiên đang hoạt động') }}</span><strong>{{ number_format($snapshot['active_sessions']) }}</strong><small>{{ $t('overview.active_sessions_help', 'Hoạt động trong thời gian gần đây') }}</small></div></div>
        <div class="dth-acc-kpi"><span class="dth-acc-kpi__icon"><x-filament::icon icon="heroicon-o-paper-airplane" /></span><div><span>{{ $t('overview.pending_invitations', 'Lời mời chờ xử lý') }}</span><strong>{{ number_format($snapshot['pending_invitations']) }}</strong><small>{{ $t('overview.pending_invitations_help', 'Chưa kích hoạt tài khoản') }}</small></div></div>
    </div>

    <div class="dth-acc-grid">
        <section class="dth-acc-panel">
            <div class="dth-acc-panel__head"><div><span class="eyebrow">{{ $t('overview.role_distribution_eyebrow', 'PHÂN QUYỀN') }}</span><h3>{{ $t('overview.role_distribution', 'Phân bố vai trò') }}</h3></div><a href="{{ url('/admin/account-roles') }}">{{ $t('overview.manage_roles', 'Quản lý vai trò') }} →</a></div>
            <div class="dth-acc-role-list">
                @php $max = max(1, (int) ($snapshot['role_distribution']->max('users_count') ?? 1)); @endphp
                @forelse($snapshot['role_distribution'] as $role)
                    <div class="dth-acc-role-row"><div class="dth-acc-role-row__meta"><strong>{{ $role->name }}</strong><span>{{ $role->users_count }} {{ $t('overview.users_unit', 'người dùng') }}</span></div><div class="dth-acc-role-row__track"><span style="width: {{ max(6, round(($role->users_count / $max) * 100)) }}%"></span></div></div>
                @empty
                    <div class="dth-acc-empty">{{ $t('overview.no_role_data', 'Chưa có dữ liệu vai trò.') }}</div>
                @endforelse
            </div>
        </section>

        <section class="dth-acc-panel dth-acc-security-panel">
            <div class="dth-acc-panel__head"><div><span class="eyebrow">{{ $t('overview.security_eyebrow', 'BẢO MẬT') }}</span><h3>{{ $t('overview.security_health', 'Sức khỏe bảo mật') }}</h3></div></div>
            <div class="dth-acc-security-state {{ $snapshot['permissions_enforced'] ? 'is-on' : 'is-off' }}">
                <span class="dth-acc-security-state__dot"></span>
                <div>
                    <strong>{{ $snapshot['permissions_enforced'] ? $t('overview.permissions_enforced', 'Đang áp dụng phân quyền') : $t('overview.permissions_not_enforced', 'Chưa bật áp dụng phân quyền') }}</strong>
                    <p>{{ $snapshot['permissions_enforced'] ? $t('overview.permissions_enforced_help', 'Các phân hệ đang kiểm tra vai trò và quyền truy cập trước khi cho phép thao tác.') : $t('overview.permissions_not_enforced_help', 'Hãy gán vai trò cho người dùng trước, sau đó bật kiểm tra quyền trong Cấu hình truy cập.') }}</p>
                </div>
            </div>
            <div class="dth-acc-security-actions">
                <a href="{{ url('/admin/account-users') }}">{{ $t('overview.manage_users', 'Quản lý người dùng') }}</a>
                <a href="{{ url('/admin/account-settings') }}">{{ $t('overview.access_settings', 'Cấu hình truy cập') }}</a>
                <a href="{{ url('/admin/account-sessions') }}">{{ $t('overview.check_sessions', 'Kiểm tra phiên đăng nhập') }}</a>
            </div>
        </section>
    </div>

    <section class="dth-acc-panel dth-acc-activity">
        <div class="dth-acc-panel__head"><div><span class="eyebrow">{{ $t('overview.activity_eyebrow', 'NHẬT KÝ') }}</span><h3>{{ $t('overview.recent_activity', 'Hoạt động gần đây') }}</h3></div><a href="{{ url('/admin/account-audit-logs') }}">{{ $t('overview.view_all', 'Xem tất cả') }} →</a></div>
        <div class="dth-acc-activity-list">
            @forelse($snapshot['recent_activity'] as $item)
                @php
                    $moduleLabel = $t('modules.'.(string) $item->module, (string) $item->module);
                    $eventLabel = $t('events.'.(string) $item->event, (string) $item->event);
                @endphp
                <div class="dth-acc-activity-row">
                    <span class="dth-acc-activity-icon">{{ strtoupper(substr((string) $item->module, 0, 1)) }}</span>
                    <div class="dth-acc-activity-copy"><strong>{{ $item->description ?: $eventLabel }}</strong><span>{{ $item->actor?->name ?? $t('common.system', 'Hệ thống') }} · {{ $moduleLabel }}</span></div>
                    <time>{{ $item->created_at?->format('d/m/Y H:i') }}</time>
                </div>
            @empty
                <div class="dth-acc-empty">{{ $t('overview.no_activity', 'Nhật ký sẽ xuất hiện khi người dùng bắt đầu thao tác.') }}</div>
            @endforelse
        </div>
    </section>
</div>
