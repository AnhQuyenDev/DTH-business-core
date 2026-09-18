@php
    $inspection = app(\Dth\AccountManagement\Services\EffectivePermissionService::class)->inspect($record);
@endphp

<div class="dth-acc-effective-permissions">
    <div class="dth-acc-effective-permissions__summary">
        <div>
            <span>{{ \Dth\AccountManagement\Support\UiText::get('permission_inspector.roles', 'Vai trò') }}</span>
            <strong>{{ $inspection['roles'] ? implode(', ', $inspection['roles']) : \Dth\AccountManagement\Support\UiText::get('permission_inspector.no_roles', 'Không có vai trò') }}</strong>
        </div>
        <div>
            <span>{{ \Dth\AccountManagement\Support\UiText::get('permission_inspector.data_scope', 'Phạm vi dữ liệu') }}</span>
            <strong>{{ $inspection['data_scope'] }}</strong>
        </div>
        <div>
            <span>{{ \Dth\AccountManagement\Support\UiText::get('permission_inspector.total', 'Tổng quyền hiệu lực') }}</span>
            <strong>{{ number_format($inspection['total']) }}</strong>
        </div>
    </div>

    @forelse ($inspection['groups'] as $group)
        <section class="dth-acc-effective-permissions__module">
            <header>
                <div>
                    <span class="dth-acc-effective-permissions__eyebrow">{{ strtoupper($group['module']) }}</span>
                    <h4>{{ $group['label'] }}</h4>
                </div>
                <span class="dth-acc-effective-permissions__count">{{ count($group['permissions']) }}</span>
            </header>

            <div class="dth-acc-effective-permissions__grid">
                @foreach ($group['permissions'] as $permission)
                    <article class="dth-acc-effective-permissions__item">
                        <div class="dth-acc-effective-permissions__item-head">
                            <strong>{{ $permission['name'] }}</strong>
                            <code>{{ $permission['key'] }}</code>
                        </div>
                        <p>{{ $permission['description'] ?: \Dth\AccountManagement\Support\UiText::get('permission_inspector.no_description', 'Quyền truy cập được cấp cho chức năng này.') }}</p>
                        <div class="dth-acc-effective-permissions__sources">
                            @foreach ($permission['sources'] as $source)
                                <span>{{ $source }}</span>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @empty
        <div class="dth-acc-effective-permissions__empty">
            {{ \Dth\AccountManagement\Support\UiText::get('permission_inspector.empty', 'Người dùng chưa có quyền truy cập hiệu lực nào.') }}
        </div>
    @endforelse
</div>
