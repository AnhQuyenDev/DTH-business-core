<?php
namespace Dth\AccountManagement\Filament\Resources;

use Dth\AccountManagement\Enums\DataScope;
use Dth\AccountManagement\Filament\Navigation\AccountManagementNavigationGroup;
use Dth\AccountManagement\Filament\Resources\RoleResource\Pages;
use Dth\AccountManagement\Models\AccountRole;
use Dth\AccountManagement\Support\AccountAuthorization;
use Dth\AccountManagement\Support\UiText;
use Filament\Actions;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

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
                TextInput::make('name')->label(UiText::get('common.fields.name', 'Tên'))->required()->maxLength(255),
                TextInput::make('key')->label(UiText::get('fields.key', 'Mã định danh'))->required()->alphaDash()->maxLength(100)->unique(ignoreRecord: true),
                Select::make('data_scope')->label(UiText::get('fields.data_scope', 'Phạm vi dữ liệu'))->options(DataScope::options())->default('own')->required()->native(false),
                Select::make('color')->label(UiText::get('fields.color', 'Màu'))->options(['gray'=>UiText::get('colors.gray','Xám'),'primary'=>UiText::get('colors.primary','Màu chính'),'info'=>UiText::get('colors.info','Thông tin'),'success'=>UiText::get('colors.success','Thành công'),'warning'=>UiText::get('colors.warning','Cảnh báo'),'danger'=>UiText::get('colors.danger','Nguy hiểm')])->default('gray')->native(false),
                Toggle::make('is_active')->label(UiText::get('fields.is_active', 'Hoạt động'))->default(true),
                Toggle::make('is_system')->label(UiText::get('fields.is_system', 'Vai trò hệ thống'))->disabled(fn (?AccountRole $record): bool => $record?->is_system ?? false)->dehydrated(fn (?AccountRole $record): bool => ! ($record?->is_system ?? false)),
                Textarea::make('description')->label(UiText::get('common.fields.description', 'Mô tả'))->rows(3)->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),
            Section::make(UiText::get('sections.permissions', 'Quyền của vai trò'))->icon('heroicon-o-key')->description(UiText::get('sections.permissions_help', 'Chọn các khả năng mà người dùng mang vai trò này được phép thực hiện.'))->schema([
                CheckboxList::make('permissions')->label(UiText::get('fields.permissions', 'Quyền'))->relationship('permissions', 'name')->searchable()->bulkToggleable()->columns(2)->columnSpanFull(),
            ])->columnSpanFull(),
        ]);
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
        ])->filters([TernaryFilter::make('is_active')->label(UiText::get('fields.is_active', 'Hoạt động'))])
        ->recordActions([Actions\ActionGroup::make([Actions\EditAction::make()->label(UiText::get('common.actions.edit', 'Chỉnh sửa')), Actions\DeleteAction::make()->label(UiText::get('common.actions.delete', 'Xóa'))->visible(fn (AccountRole $record): bool => ! $record->is_system && ! $record->users()->exists())])->icon('heroicon-o-ellipsis-vertical')->iconButton()]);
    }

    public static function getPages(): array { return ['index'=>Pages\ListRoles::route('/'),'create'=>Pages\CreateRole::route('/create'),'edit'=>Pages\EditRole::route('/{record}/edit')]; }
    public static function canViewAny(): bool { return app(AccountAuthorization::class)->allows('accounts.view'); }
    public static function canCreate(): bool { return app(AccountAuthorization::class)->allows('accounts.roles.manage'); }
    public static function canEdit(Model $record): bool { return app(AccountAuthorization::class)->allows('accounts.roles.manage'); }
    public static function canDelete(Model $record): bool { return app(AccountAuthorization::class)->allows('accounts.roles.manage') && ! ($record instanceof AccountRole && $record->is_system); }
}
