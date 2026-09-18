<?php
namespace Dth\AccountManagement\Filament;

use Dth\AccountManagement\Filament\Pages\AccountOverview;
use Dth\AccountManagement\Filament\Resources\AccessSettingResource;
use Dth\AccountManagement\Filament\Resources\AccountUserResource;
use Dth\AccountManagement\Filament\Resources\AuditLogResource;
use Dth\AccountManagement\Filament\Resources\GroupResource;
use Dth\AccountManagement\Filament\Resources\InvitationResource;
use Dth\AccountManagement\Filament\Resources\RoleResource;
use Dth\AccountManagement\Filament\Resources\SessionResource;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;

final class AccountManagementPlugin implements Plugin
{
    public static function make(): static { return app(static::class); }
    public function getId(): string { return 'dth-account-management'; }

    public function register(Panel $panel): void
    {
        if (! config('dth-account-management.enabled', true)) return;
        $resources = [];
        $features = (array) config('dth-account-management.features', []);
        if ($features['users'] ?? true) $resources[] = AccountUserResource::class;
        if ($features['roles'] ?? true) $resources[] = RoleResource::class;
        if ($features['groups'] ?? true) $resources[] = GroupResource::class;
        if ($features['invitations'] ?? true) $resources[] = InvitationResource::class;
        if ($features['sessions'] ?? true) $resources[] = SessionResource::class;
        if ($features['audit'] ?? true) $resources[] = AuditLogResource::class;
        if ($features['settings'] ?? true) $resources[] = AccessSettingResource::class;
        $pages = ($features['overview'] ?? true) ? [AccountOverview::class] : [];
        $panel->pages($pages)->resources($resources);
    }

    public function boot(Panel $panel): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): string => view('dth-account-management::filament.partials.ui-assets')->render(),
        );
    }
}
