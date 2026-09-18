<?php

namespace Dth\AccountManagement\Filament\Resources;

use Dth\AccountManagement\Filament\Navigation\AccountManagementNavigationGroup;
use Dth\AccountManagement\Filament\Resources\AuditLogResource\Pages;
use Dth\AccountManagement\Models\AccountAuditLog;
use Dth\AccountManagement\Support\AccountAuthorization;
use Dth\AccountManagement\Support\StatusColor;
use Dth\AccountManagement\Support\UiText;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AuditLogResource extends Resource
{
    protected static ?string $model = AccountAuditLog::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static string|\UnitEnum|null $navigationGroup = AccountManagementNavigationGroup::Accounts;
    protected static ?int $navigationSort = 60;
    protected static ?string $slug = 'account-audit-logs';

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.audit', 'Nhật ký hoạt động', context: 'navigation');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label(UiText::get('fields.time', 'Thời gian'))
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                TextColumn::make('actor.name')
                    ->label(UiText::get('fields.actor', 'Người thực hiện'))
                    ->description(fn (AccountAuditLog $record): ?string => $record->actor?->email)
                    ->placeholder(UiText::get('common.system', 'Hệ thống'))
                    ->searchable(),
                TextColumn::make('module')
                    ->label(UiText::get('fields.module', 'Phân hệ'))
                    ->formatStateUsing(fn ($state): string => UiText::get('modules.'.(string) $state, (string) $state))
                    ->badge()
                    ->color(fn ($state): string => StatusColor::module((string) $state))
                    ->sortable(),
                TextColumn::make('event')
                    ->label(UiText::get('fields.event', 'Sự kiện'))
                    ->formatStateUsing(fn ($state): string => UiText::get('events.'.(string) $state, (string) $state))
                    ->badge(),
                TextColumn::make('description')
                    ->label(UiText::get('common.fields.description', 'Mô tả'))
                    ->wrap()
                    ->limit(80),
                TextColumn::make('ip_address')
                    ->label(UiText::get('fields.ip_address', 'Địa chỉ IP'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('module')
                    ->label(UiText::get('fields.module', 'Phân hệ'))
                    ->options([
                        'accounts' => UiText::get('modules.accounts', 'Tài khoản & Phân quyền'),
                        'commercial' => UiText::get('modules.commercial', 'Dịch vụ & Kinh doanh'),
                        'crm' => UiText::get('modules.crm', 'CRM'),
                        'marketing' => UiText::get('modules.marketing', 'Marketing'),
                        'human-resource' => UiText::get('modules.human-resource', 'Nhân sự'),
                        'email' => UiText::get('modules.email', 'Email'),
                        'core' => UiText::get('modules.core', 'Hệ thống lõi'),
                    ]),
                SelectFilter::make('event')
                    ->label(UiText::get('fields.event', 'Sự kiện'))
                    ->options([
                        'created' => UiText::get('events.created', 'Tạo mới'),
                        'updated' => UiText::get('events.updated', 'Cập nhật'),
                        'deleted' => UiText::get('events.deleted', 'Xóa'),
                        'login' => UiText::get('events.login', 'Đăng nhập'),
                        'logout' => UiText::get('events.logout', 'Đăng xuất'),
                        'login_failed' => UiText::get('events.login_failed', 'Đăng nhập thất bại'),
                    ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListAuditLogs::route('/')];
    }

    public static function canViewAny(): bool
    {
        return app(AccountAuthorization::class)->allows('accounts.audit.view');
    }

    public static function canCreate(): bool { return false; }
    public static function canEdit(Model $record): bool { return false; }
    public static function canDelete(Model $record): bool { return false; }
}
