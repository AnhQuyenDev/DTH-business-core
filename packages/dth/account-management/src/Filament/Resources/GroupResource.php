<?php
namespace Dth\AccountManagement\Filament\Resources;
use Dth\AccountManagement\Enums\GroupType;
use Dth\AccountManagement\Filament\Navigation\AccountManagementNavigationGroup;
use Dth\AccountManagement\Filament\Resources\GroupResource\Pages;
use Dth\AccountManagement\Models\AccountGroup;
use Dth\AccountManagement\Support\AccountAuthorization;
use Dth\AccountManagement\Support\UiText;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
class GroupResource extends Resource
{
    protected static ?string $model=AccountGroup::class; protected static string|\BackedEnum|null $navigationIcon='heroicon-o-user-group'; protected static string|\UnitEnum|null $navigationGroup=AccountManagementNavigationGroup::Accounts; protected static ?int $navigationSort=30; protected static ?string $slug='account-groups';
    public static function getNavigationLabel(): string{return UiText::get('navigation.groups','Nhóm & Đơn vị');} public static function getModelLabel():string{return UiText::get('models.group','Nhóm');} public static function getPluralModelLabel():string{return UiText::get('models.groups','Nhóm');}
    public static function form(Schema $schema):Schema{return $schema->components([
        Section::make(UiText::get('sections.group','Thông tin nhóm'))->icon('heroicon-o-users')->schema([
            TextInput::make('name')->label(UiText::get('common.fields.name','Tên'))->required()->maxLength(255)->helperText(UiText::get('fields.group_name_help','Tên hiển thị của nhóm hoặc đơn vị.')), TextInput::make('code')->label(UiText::get('fields.group_code','Mã nhóm'))->required()->alphaDash()->unique(ignoreRecord:true)->maxLength(80)->helperText(UiText::get('fields.group_code_help','Mã định danh duy nhất, dùng để tham chiếu nhóm trong hệ thống.')),
            Select::make('type')->label(UiText::get('fields.group_type','Loại nhóm'))->options(GroupType::options())->default('team')->required()->native(false)->helperText(UiText::get('fields.group_type_help','Phân loại nhóm để dễ lọc và báo cáo.')),
            Select::make('parent_id')->label(UiText::get('fields.parent_group','Nhóm cha'))->relationship('parent','name')->searchable()->preload()->helperText(UiText::get('fields.parent_group_help','Chọn nhóm cha nếu đây là nhóm con trong cây tổ chức.')), Toggle::make('is_active')->label(UiText::get('fields.is_active','Hoạt động'))->default(true)->helperText(UiText::get('fields.is_active_help','Tắt để tạm ngừng sử dụng mà không cần xóa dữ liệu.')),
            Textarea::make('description')->label(UiText::get('common.fields.description','Mô tả'))->rows(3)->columnSpanFull()->helperText(UiText::get('fields.description_help','Ghi chú nội bộ, chỉ hiển thị cho quản trị viên.')),
        ])->columns(2)->columnSpanFull(),
        Section::make(UiText::get('sections.members','Thành viên'))->icon('heroicon-o-user-plus')->schema([Select::make('users')->label(UiText::get('models.users','Người dùng'))->relationship('users','name')->multiple()->searchable()->preload()->columnSpanFull()->helperText(UiText::get('fields.group_users_help','Chọn những người dùng thuộc nhóm này.'))])->columnSpanFull(),
    ]);}
    public static function table(Table $table):Table{return $table->columns([TextColumn::make('code')->label(UiText::get('fields.group_code','Mã nhóm'))->searchable()->sortable(),TextColumn::make('name')->label(UiText::get('common.fields.name','Tên'))->searchable()->sortable(),TextColumn::make('type')->label(UiText::get('fields.group_type','Loại nhóm'))->badge()->formatStateUsing(fn($state):string=>GroupType::options()[$state instanceof \BackedEnum?$state->value:(string)$state]??(string)$state),TextColumn::make('users_count')->counts('users')->label(UiText::get('fields.members','Thành viên'))->sortable(),TextColumn::make('parent.name')->label(UiText::get('fields.parent_group','Nhóm cha'))->placeholder('—'),IconColumn::make('is_active')->label(UiText::get('fields.is_active','Hoạt động'))->boolean()])->filters([SelectFilter::make('type')->options(GroupType::options())])->recordActions([Actions\ActionGroup::make([Actions\EditAction::make()->label(UiText::get('common.actions.edit','Chỉnh sửa')),Actions\DeleteAction::make()->label(UiText::get('common.actions.delete','Xóa'))])->icon('heroicon-o-ellipsis-vertical')->iconButton()->visible(fn (): bool => app(AccountAuthorization::class)->allows('accounts.groups.manage'))]);}
    public static function getPages():array{return['index'=>Pages\ListGroups::route('/'),'create'=>Pages\CreateGroup::route('/create'),'edit'=>Pages\EditGroup::route('/{record}/edit')];}
    public static function canViewAny():bool{return app(AccountAuthorization::class)->allows('accounts.view');} public static function canCreate():bool{return app(AccountAuthorization::class)->allows('accounts.groups.manage');} public static function canEdit(Model $record):bool{return app(AccountAuthorization::class)->allows('accounts.groups.manage');} public static function canDelete(Model $record):bool{return app(AccountAuthorization::class)->allows('accounts.groups.manage');}
}
