<?php
namespace Dth\AccountManagement\Filament\Resources;

use Dth\AccountManagement\Enums\DataScope;
use Dth\AccountManagement\Filament\Navigation\AccountManagementNavigationGroup;
use Dth\AccountManagement\Filament\Resources\RoleResource\Pages;
use Dth\AccountManagement\Models\AccountRole;
use Dth\AccountManagement\Services\PermissionRegistryService;
use Dth\AccountManagement\Support\AccountAuthorization;
use Dth\AccountManagement\Support\UiText;
use Filament\Actions;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class RoleResource extends Resource
{
    protected static ?string $model = AccountRole::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';
    protected static string|\UnitEnum|null $navigationGroup = AccountManagementNavigationGroup::Accounts;
    protected static ?int $navigationSort = 20;
    protected static ?string $slug = 'account-roles';

    public static function getNavigationLabel(): string { return UiText::get('navigation.roles', 'Vai trò & Phân quyền', context: 'navigation'); }
    public static function getModelLabel(): string { return UiText::get('models.role', 'Vai trò'); }
    public static function getPluralModelLabel(): string { return UiText::get('models.roles', 'Vai trò'); }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(UiText::get('sections.role', 'Định nghĩa vai trò'))->icon('heroicon-o-shield-check')->schema([
                TextInput::make('name')->label(UiText::get('common.fields.name', 'Tên'))->required()->maxLength(255)->helperText(UiText::get('fields.role_name_help', 'Tên hiển thị của vai trò trong danh sách và bộ lọc.')),
                TextInput::make('key')->label(UiText::get('fields.key', 'Mã định danh'))->required()->alphaDash()->maxLength(100)->unique(ignoreRecord: true)->helperText(UiText::get('fields.key_help', 'Mã định danh duy nhất, chỉ gồm chữ, số và dấu gạch, dùng trong cấu hình hệ thống.')),
                Select::make('data_scope')->label(UiText::get('fields.data_scope', 'Phạm vi dữ liệu'))->options(DataScope::options())->default('own')->required()->native(false)->helperText(UiText::get('fields.data_scope_help', 'Phạm vi dữ liệu mà người dùng mang vai trò này được phép truy cập.')),
                Select::make('color')->label(UiText::get('fields.color', 'Màu'))->options(['gray'=>UiText::get('colors.gray','Xám'),'primary'=>UiText::get('colors.primary','Màu chính'),'info'=>UiText::get('colors.info','Thông tin'),'success'=>UiText::get('colors.success','Thành công'),'warning'=>UiText::get('colors.warning','Cảnh báo'),'danger'=>UiText::get('colors.danger','Nguy hiểm')])->default('gray')->native(false)->helperText(UiText::get('fields.color_help', 'Màu hiển thị badge của vai trò trên giao diện.')),
                Toggle::make('is_active')->label(UiText::get('fields.is_active', 'Hoạt động'))->default(true)->helperText(UiText::get('fields.is_active_help', 'Tắt để tạm ngừng sử dụng mà không cần xóa dữ liệu.')),
                Toggle::make('is_system')->label(UiText::get('fields.is_system', 'Vai trò hệ thống'))->disabled(fn (?AccountRole $record): bool => $record?->is_system ?? false)->dehydrated(fn (?AccountRole $record): bool => ! ($record?->is_system ?? false))->helperText(UiText::get('fields.is_system_help', 'Vai trò hệ thống không thể xóa và một số trường sẽ bị khóa chỉnh sửa.')),
                Textarea::make('description')->label(UiText::get('common.fields.description', 'Mô tả'))->rows(3)->columnSpanFull()->helperText(UiText::get('fields.description_help', 'Ghi chú nội bộ, chỉ hiển thị cho quản trị viên.')),
            ])->columns(2)->columnSpanFull(),
            Section::make(UiText::get('sections.permissions', 'Quyền của vai trò'))->icon('heroicon-o-key')->description(UiText::get('sections.permissions_help', 'Chọn các khả năng mà người dùng mang vai trò này được phép thực hiện.'))->schema([
                Tabs::make('permission_modules')->contained(false)->tabs(static::permissionTabs())->columnSpanFull(),
            ])->columnSpanFull(),
        ]);
    }

    /** @return array<Tab> */
    public static function permissionTabs(): array
    {
        return collect(app(PermissionRegistryService::class)->groupedOptions())
            ->map(fn (array $options, string $module): Tab => Tab::make(UiText::get('modules.'.$module, $module))
                ->badge((string) count($options))
                ->schema([
                    CheckboxList::make("permission_selection.{$module}")->hiddenLabel()->options($options)->columns(2)->bulkToggleable()->searchable(),
                ]))
            ->values()
            ->all();
    }

    /** Groups the role's currently assigned permission ids by module, to fill the tabbed selector. */
    public static function permissionSelectionFromRecord(?AccountRole $record): array
    {
        if (! $record) return [];
        $ids = app(PermissionRegistryService::class)->expandPermissionIds(
            $record->permissions()->pluck('account_permissions.id')->map(fn ($id) => (int) $id)->all(),
        );

        return \Dth\AccountManagement\Models\AccountPermission::query()->whereIn('id', $ids)->get(['id', 'module'])
            ->groupBy('module')
            ->map(fn ($items) => $items->pluck('id')->all())
            ->all();
    }

    /** Splits the per-module tabbed selection out of the form data, returning [$cleanData, $permissionIds]. */
    public static function splitPermissionData(array $data): array
    {
        // Filament normally hydrates dot-path fields as a nested array, but
        // accept flattened state as well so a single checked permission is
        // never lost when Tabs/CheckboxList serialization changes.
        $selection = (array) ($data['permission_selection'] ?? []);
        foreach (array_keys($data) as $key) {
            if (! str_starts_with((string) $key, 'permission_selection.')) continue;
            $module = substr((string) $key, strlen('permission_selection.'));
            $selection[$module] = $data[$key];
            unset($data[$key]);
        }

        $ids = collect($selection)->flatten()->filter(fn ($id) => is_numeric($id))->map(fn ($id) => (int) $id)->unique()->values()->all();
        $ids = app(PermissionRegistryService::class)->expandPermissionIds($ids);
        unset($data['permission_selection']);
        return [$data, $ids];
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label(UiText::get('common.fields.name', 'Tên'))->searchable()->sortable(),
            TextColumn::make('key')->label(UiText::get('fields.key', 'Mã định danh'))->copyable()->searchable(),
            TextColumn::make('users_count')->counts('users')->label(UiText::get('models.users', 'Người dùng'))->sortable(),
            TextColumn::make('permissions_count')->counts('permissions')->label(UiText::get('fields.permission_count', 'Số quyền'))->sortable(),
            TextColumn::make('data_scope')->label(UiText::get('fields.data_scope', 'Phạm vi dữ liệu'))->badge()->formatStateUsing(fn ($state): string => DataScope::options()[$state instanceof \BackedEnum ? $state->value : (string) $state] ?? (string) $state),
            IconColumn::make('is_active')->label(UiText::get('fields.is_active', 'Hoạt động'))->boolean(),
        ])->filters([
            TernaryFilter::make('is_active')->label(UiText::get('fields.is_active', 'Hoạt động')),
            TernaryFilter::make('is_system')->label(UiText::get('fields.is_system', 'Vai trò hệ thống')),
            SelectFilter::make('data_scope')->label(UiText::get('fields.data_scope', 'Phạm vi dữ liệu'))->options(DataScope::options()),
            SelectFilter::make('color')->label(UiText::get('fields.color', 'Màu'))->options(['gray'=>UiText::get('colors.gray','Xám'),'primary'=>UiText::get('colors.primary','Màu chính'),'info'=>UiText::get('colors.info','Thông tin'),'success'=>UiText::get('colors.success','Thành công'),'warning'=>UiText::get('colors.warning','Cảnh báo'),'danger'=>UiText::get('colors.danger','Nguy hiểm')]),
            SelectFilter::make('module')->label(UiText::get('fields.module', 'Phân hệ'))
                ->options(fn (): array => app(PermissionRegistryService::class)->moduleOptions())
                ->query(fn (Builder $query, array $data) => $query->when($data['value'] ?? null, fn (Builder $q, string $module) => $q->whereHas('permissions', fn (Builder $p) => $p->where('module', $module)))),
            TernaryFilter::make('has_users')->label(UiText::get('fields.has_users', 'Đang gán cho người dùng'))
                ->queries(
                    true: fn (Builder $query) => $query->whereHas('users'),
                    false: fn (Builder $query) => $query->whereDoesntHave('users'),
                    blank: fn (Builder $query) => $query,
                ),
        ])
        ->recordActions([Actions\ActionGroup::make([Actions\EditAction::make()->label(UiText::get('common.actions.edit', 'Chỉnh sửa')), Actions\DeleteAction::make()->label(UiText::get('common.actions.delete', 'Xóa'))->visible(fn (AccountRole $record): bool => ! $record->is_system && ! $record->users()->exists())])->icon('heroicon-o-ellipsis-vertical')->iconButton()->visible(fn (): bool => app(AccountAuthorization::class)->allows('accounts.roles.manage'))])
        ->toolbarActions([
            Actions\BulkActionGroup::make([
                Actions\BulkAction::make('activate')
                    ->label(UiText::get('actions.activate_roles', 'Kích hoạt'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn (Collection $records) => $records->each->update(['is_active' => true]))
                    ->deselectRecordsAfterCompletion(),
                Actions\BulkAction::make('deactivate')
                    ->label(UiText::get('actions.deactivate_roles', 'Vô hiệu hóa'))
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(fn (Collection $records) => $records->each->update(['is_active' => false]))
                    ->deselectRecordsAfterCompletion(),
                Actions\DeleteBulkAction::make()
                    ->label(UiText::get('common.actions.delete', 'Xóa'))
                    ->using(function (Collection $records): void {
                        $deletable = $records->reject(fn (AccountRole $role): bool => $role->is_system || $role->users()->exists());
                        $skipped = $records->count() - $deletable->count();
                        $deletable->each->delete();
                        if ($skipped > 0) {
                            Notification::make()
                                ->warning()
                                ->title(UiText::get('notifications.roles_not_deletable', 'Một số vai trò không thể xóa'))
                                ->body(UiText::get('notifications.roles_not_deletable_body', 'Vai trò hệ thống hoặc đang được gán cho người dùng sẽ bị bỏ qua.'))
                                ->send();
                        }
                    }),
            ])->visible(fn (): bool => app(AccountAuthorization::class)->allows('accounts.roles.manage')),
        ]);
    }

    public static function getPages(): array { return ['index'=>Pages\ListRoles::route('/'),'create'=>Pages\CreateRole::route('/create'),'edit'=>Pages\EditRole::route('/{record}/edit')]; }
    public static function canViewAny(): bool { return app(AccountAuthorization::class)->allows('accounts.view'); }
    public static function canCreate(): bool { return app(AccountAuthorization::class)->allows('accounts.roles.manage'); }
    public static function canEdit(Model $record): bool { return app(AccountAuthorization::class)->allows('accounts.roles.manage'); }
    public static function canDelete(Model $record): bool { return app(AccountAuthorization::class)->allows('accounts.roles.manage') && ! ($record instanceof AccountRole && $record->is_system); }
}
