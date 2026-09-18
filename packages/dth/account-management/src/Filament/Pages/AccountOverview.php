<?php
namespace Dth\AccountManagement\Filament\Pages;

use Dth\AccountManagement\Filament\Navigation\AccountManagementNavigationGroup;
use Dth\AccountManagement\Services\AccountAnalyticsService;
use Dth\AccountManagement\Support\AccountAuthorization;
use Dth\AccountManagement\Support\UiText;
use Filament\Pages\Dashboard;
use Filament\Schemas\Components\View as SchemaView;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class AccountOverview extends Dashboard
{
    protected static string $routePath = 'account-overview';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';
    protected static string|\UnitEnum|null $navigationGroup = AccountManagementNavigationGroup::Accounts;
    protected static ?int $navigationSort = 0;

    public static function getNavigationLabel(): string { return UiText::get('navigation.overview', 'Tổng quan tài khoản', context: 'navigation'); }
    public function getTitle(): string|Htmlable { return UiText::get('overview.title', 'Tài khoản & Phân quyền'); }
    public function getSubheading(): string|Htmlable|null { return UiText::get('overview.subheading', 'Quản lý người dùng nội bộ, quyền truy cập, phiên đăng nhập và nhật ký bảo mật trên toàn hệ thống.'); }
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            SchemaView::make('dth-account-management::filament.pages.account-overview')
                ->viewData(['snapshot' => app(AccountAnalyticsService::class)->snapshot()]),
        ]);
    }
    public static function canAccess(): bool { return app(AccountAuthorization::class)->allows('accounts.view'); }
}
