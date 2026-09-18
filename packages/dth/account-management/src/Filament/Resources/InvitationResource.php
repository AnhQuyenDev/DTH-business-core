<?php
namespace Dth\AccountManagement\Filament\Resources;
use Dth\AccountManagement\Enums\InvitationStatus;
use Dth\AccountManagement\Filament\Navigation\AccountManagementNavigationGroup;
use Dth\AccountManagement\Filament\Resources\InvitationResource\Pages;
use Dth\AccountManagement\Models\AccountInvitation;
use Dth\AccountManagement\Services\InvitationService;
use Dth\AccountManagement\Support\AccountAuthorization;
use Dth\AccountManagement\Support\StatusColor;
use Dth\AccountManagement\Support\UiText;
use Filament\Actions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
class InvitationResource extends Resource
{
 protected static ?string $model=AccountInvitation::class; protected static string|\BackedEnum|null $navigationIcon='heroicon-o-paper-airplane'; protected static string|\UnitEnum|null $navigationGroup=AccountManagementNavigationGroup::Accounts; protected static ?int $navigationSort=40; protected static ?string $slug='account-invitations';
 public static function getNavigationLabel():string{return UiText::get('navigation.invitations','Lời mời');} public static function getModelLabel():string{return UiText::get('models.invitation','Lời mời');} public static function getPluralModelLabel():string{return UiText::get('models.invitations','Lời mời');}
 public static function form(Schema $schema):Schema{return $schema->components([Section::make(UiText::get('sections.invitation','Thông tin lời mời'))->icon('heroicon-o-envelope')->schema([
  TextInput::make('name')->label(UiText::get('common.fields.name','Họ và tên'))->maxLength(255),TextInput::make('email')->label(UiText::get('common.fields.email','Email'))->email()->required()->maxLength(255),DateTimePicker::make('expires_at')->label(UiText::get('fields.expires_at','Hết hạn lúc'))->default(fn()=>now()->addHours((int)config('dth-account-management.invitations.expires_hours',72)))->required()->seconds(false),
  Select::make('roles')->label(UiText::get('fields.roles','Vai trò'))->relationship('roles','name')->multiple()->searchable()->preload()->required(),Select::make('groups')->label(UiText::get('fields.groups','Nhóm'))->relationship('groups','name')->multiple()->searchable()->preload(),
 ])->columns(2)->columnSpanFull()]);}
 public static function table(Table $table):Table{return $table->columns([TextColumn::make('email')->label(UiText::get('common.fields.email','Email'))->searchable(),TextColumn::make('roles.name')->label(UiText::get('fields.roles','Vai trò'))->badge()->separator(','),TextColumn::make('status')->label(UiText::get('common.fields.status','Trạng thái'))->badge()->formatStateUsing(fn($state):string=>InvitationStatus::options()[$state instanceof \BackedEnum?$state->value:(string)$state]??(string)$state)->color(fn($state):string=>StatusColor::account($state)),TextColumn::make('expires_at')->label(UiText::get('fields.expires','Hết hạn'))->dateTime('d/m/Y H:i')->sortable(),TextColumn::make('invitedBy.name')->label(UiText::get('fields.invited_by','Người mời'))->placeholder('—')])->filters([SelectFilter::make('status')->options(InvitationStatus::options())])->recordActions([Actions\ActionGroup::make([
  Actions\Action::make('resend')->label(UiText::get('common.actions.resend','Gửi lại'))->icon('heroicon-o-paper-airplane')->visible(fn(AccountInvitation $record):bool=>$record->status===InvitationStatus::Pending)->action(fn(AccountInvitation $record)=>app(InvitationService::class)->issue($record,true)),
  Actions\Action::make('revoke')->label(UiText::get('common.actions.revoke','Thu hồi'))->icon('heroicon-o-no-symbol')->color('danger')->visible(fn(AccountInvitation $record):bool=>$record->status===InvitationStatus::Pending)->requiresConfirmation()->action(fn(AccountInvitation $record)=>$record->forceFill(['status'=>'revoked'])->save()),
  Actions\DeleteAction::make()->label(UiText::get('common.actions.delete','Xóa'))->visible(fn(AccountInvitation $record):bool=>$record->status!==InvitationStatus::Accepted),
  ])->icon('heroicon-o-ellipsis-vertical')->iconButton()]);}
 public static function getPages():array{return['index'=>Pages\ListInvitations::route('/'),'create'=>Pages\CreateInvitation::route('/create')];}
 public static function canViewAny():bool{return app(AccountAuthorization::class)->allows('accounts.view');} public static function canCreate():bool{return app(AccountAuthorization::class)->allows('accounts.invitations.manage');} public static function canDelete(Model $record):bool{return app(AccountAuthorization::class)->allows('accounts.invitations.manage');}
}
