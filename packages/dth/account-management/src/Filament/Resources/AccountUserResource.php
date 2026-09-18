<?php
namespace Dth\AccountManagement\Filament\Resources;

use Dth\AccountManagement\Enums\AccountStatus;
use Dth\AccountManagement\Filament\Navigation\AccountManagementNavigationGroup;
use Dth\AccountManagement\Filament\Resources\AccountUserResource\Pages;
use Dth\AccountManagement\Models\AccountUser;
use Dth\AccountManagement\Services\EmployeeLinkService;
use Dth\AccountManagement\Support\AccountAuthorization;
use Dth\AccountManagement\Support\StatusColor;
use Dth\AccountManagement\Support\UiText;
use Filament\Actions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AccountUserResource extends Resource
{
    protected static ?string $model = AccountUser::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';
    protected static string|\UnitEnum|null $navigationGroup = AccountManagementNavigationGroup::Accounts;
    protected static ?int $navigationSort = 10;
    protected static ?string $slug = 'account-users';
    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string { return UiText::get('navigation.users', 'Người dùng', context: 'navigation'); }
    public static function getModelLabel(): string { return UiText::get('models.user', 'Người dùng', context: 'model'); }
    public static function getPluralModelLabel(): string { return UiText::get('models.users', 'Người dùng', context: 'model'); }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(UiText::get('sections.identity', 'Thông tin tài khoản'))
                ->icon('heroicon-o-identification')
                ->schema([
                    TextInput::make('name')->label(UiText::get('common.fields.name', 'Tên'))->required()->maxLength(255),
                    TextInput::make('email')->label(UiText::get('common.fields.email', 'Email'))->email()->required()->unique(ignoreRecord: true)->maxLength(255),
                    TextInput::make('phone')->label(UiText::get('fields.phone', 'Số điện thoại'))->tel()->maxLength(30),
                    Select::make('account_status')->label(UiText::get('common.fields.status', 'Trạng thái'))->options(AccountStatus::options())->default('active')->required()->native(false),
                    Select::make('preferred_locale')->label(UiText::get('fields.locale', 'Ngôn ngữ'))->options(['vi' => UiText::get('languages.vi', 'Tiếng Việt'), 'en' => UiText::get('languages.en', 'Tiếng Anh')])->default('vi')->native(false),
                    Select::make('timezone')->label(UiText::get('fields.timezone', 'Múi giờ'))->options([
                        'Asia/Ho_Chi_Minh' => 'Asia/Ho_Chi_Minh (UTC+7)', 'Asia/Bangkok' => 'Asia/Bangkok (UTC+7)', 'UTC' => 'UTC',
                    ])->default('Asia/Ho_Chi_Minh')->searchable(),
                ])->columns(2)->columnSpanFull(),

            Section::make(UiText::get('sections.access', 'Vai trò & quyền truy cập'))
                ->icon('heroicon-o-shield-check')
                ->schema([
                    Select::make('roles')->label(UiText::get('fields.roles', 'Vai trò'))->relationship('roles', 'name')->multiple()->preload()->searchable(),
                    Select::make('groups')->label(UiText::get('fields.groups', 'Nhóm'))->relationship('groups', 'name')->multiple()->preload()->searchable(),
                    Select::make('directPermissions')->label(UiText::get('fields.direct_permissions', 'Quyền bổ sung (ngoại lệ)'))->relationship('directPermissions', 'name')->multiple()->preload()->searchable()->helperText(UiText::get('fields.direct_permissions_help', 'Chỉ dùng cho ngoại lệ cá nhân; quyền từ vai trò vẫn là nguồn chính.')),
                    Select::make('employee_id')
                        ->label(UiText::get('fields.employee', 'Nhân viên liên kết'))
                        ->options(fn (?AccountUser $record): array => app(EmployeeLinkService::class)->options($record?->id))
                        ->searchable()->preload()->placeholder(UiText::get('fields.employee_none', 'Không liên kết nhân viên'))
                        ->visible(fn (): bool => app(EmployeeLinkService::class)->available()),
                ])->columns(2)->columnSpanFull(),

            Section::make(UiText::get('sections.security', 'Bảo mật'))
                ->icon('heroicon-o-lock-closed')
                ->schema([
                    TextInput::make('password')->label(UiText::get('fields.password', 'Mật khẩu'))->password()->revealable()
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->dehydrated(fn ($state): bool => filled($state))->minLength(10),
                    TextInput::make('password_confirmation')->label(UiText::get('fields.password_confirmation', 'Xác nhận mật khẩu'))->password()->revealable()
                        ->same('password')->dehydrated(false)->required(fn (string $operation): bool => $operation === 'create'),
                    Toggle::make('must_change_password')->label(UiText::get('fields.must_change_password', 'Buộc đổi mật khẩu khi đăng nhập'))->default(false),
                    DateTimePicker::make('locked_until')->label(UiText::get('fields.locked_until', 'Khóa đến'))->seconds(false),
                ])->columns(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label(UiText::get('common.fields.name', 'Tên'))->description(fn (AccountUser $record): string => $record->email)->searchable(['name','email'])->sortable(),
            TextColumn::make('employee')->label(UiText::get('fields.employee', 'Nhân viên'))->state(fn (AccountUser $record): string => app(EmployeeLinkService::class)->labelForUser($record->id) ?? '—')->wrap(),
            TextColumn::make('roles.name')->label(UiText::get('fields.roles', 'Vai trò'))->badge()->separator(',')->limitList(3),
            TextColumn::make('account_status')->label(UiText::get('common.fields.status', 'Trạng thái'))->badge()->formatStateUsing(function ($state): string { $value = $state instanceof \BackedEnum ? $state->value : (string) $state; return AccountStatus::options()[$value] ?? $value; })->color(fn ($state): string => StatusColor::account($state)),
            TextColumn::make('last_login_at')->label(UiText::get('fields.last_login', 'Đăng nhập gần nhất'))->dateTime('d/m/Y H:i')->placeholder(UiText::get('common.never_logged_in', 'Chưa đăng nhập'))->sortable(),
        ])->filters([
            SelectFilter::make('account_status')->label(UiText::get('common.fields.status', 'Trạng thái'))->options(AccountStatus::options()),
            SelectFilter::make('roles')->relationship('roles', 'name')->label(UiText::get('fields.roles', 'Vai trò')),
        ])->recordActions([
            Actions\ActionGroup::make([
                Actions\EditAction::make()->label(UiText::get('common.actions.edit', 'Chỉnh sửa')),
                Actions\Action::make('unlock')->label(UiText::get('common.actions.unlock', 'Mở khóa'))->icon('heroicon-o-lock-open')->color('success')
                    ->visible(fn (AccountUser $record): bool => $record->isLocked())
                    ->action(fn (AccountUser $record) => $record->forceFill(['locked_until' => null, 'failed_login_attempts' => 0])->save()),
                Actions\Action::make('forcePassword')->label(UiText::get('actions.force_password_change', 'Buộc đổi mật khẩu'))->icon('heroicon-o-key')
                    ->action(fn (AccountUser $record) => $record->forceFill(['must_change_password' => true])->save()),
                Actions\Action::make('revokeSessions')->label(UiText::get('actions.revoke_all_sessions', 'Đăng xuất tất cả thiết bị'))->icon('heroicon-o-arrow-right-start-on-rectangle')->color('danger')->requiresConfirmation()
                    ->action(fn (AccountUser $record) => $record->sessions()->delete()),
            ])->icon('heroicon-o-ellipsis-vertical')->iconButton(),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListAccountUsers::route('/'), 'create' => Pages\CreateAccountUser::route('/create'), 'edit' => Pages\EditAccountUser::route('/{record}/edit')];
    }

    public static function canViewAny(): bool { return app(AccountAuthorization::class)->allows('accounts.view'); }
    public static function canCreate(): bool { return app(AccountAuthorization::class)->allows('accounts.users.manage'); }
    public static function canEdit(Model $record): bool { return app(AccountAuthorization::class)->allows('accounts.users.manage'); }
    public static function canDelete(Model $record): bool { return false; }
}
