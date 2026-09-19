<?php

namespace Dth\HumanResource\Filament\Resources;

use Dth\HumanResource\Enums\EmploymentStatus;
use Dth\HumanResource\Filament\Navigation\HumanResourceNavigationGroup;
use Dth\HumanResource\Filament\Resources\EmployeeResource\Pages;
use Dth\HumanResource\Filament\Resources\EmployeeResource\RelationManagers\AvailabilitiesRelationManager;
use Dth\HumanResource\Filament\Resources\EmployeeResource\RelationManagers\BusinessFunctionsRelationManager;
use Dth\HumanResource\Models\Department;
use Dth\HumanResource\Models\Employee;
use Dth\HumanResource\Models\Position;
use Dth\HumanResource\Services\EmployeeAccountService;
use Dth\HumanResource\Support\HumanResourceAuthorization;
use Dth\HumanResource\Support\StatusColor;
use Dth\HumanResource\Support\UiText;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';
    protected static string|\UnitEnum|null $navigationGroup = HumanResourceNavigationGroup::HumanResource;
    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.employees', 'Employees', context: 'navigation');
    }

    public static function getModelLabel(): string
    {
        return UiText::get('models.employee', 'Employee', context: 'model');
    }

    public static function getPluralModelLabel(): string
    {
        return UiText::get('models.employee_plural', 'Employees', context: 'model');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(UiText::get('sections.identity', 'Employee identity'))
                ->icon('heroicon-o-user-circle')
                ->schema([
                    TextInput::make('employee_code')
                        ->label(UiText::get('fields.employee_code', 'Employee code'))
                        ->disabled()
                        ->dehydrated(false)
                        ->visible(fn (?Employee $record): bool => $record !== null),
                    TextInput::make('full_name')
                        ->label(UiText::get('fields.full_name', 'Full name'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('email')
                        ->label(UiText::get('common.fields.email', 'Email'))
                        ->email()
                        ->maxLength(255),
                    TextInput::make('phone')
                        ->label(UiText::get('fields.phone', 'Phone'))
                        ->tel()
                        ->maxLength(30),
                    TextInput::make('_account_link_status')
                        ->label(UiText::get('fields.system_account', 'Tài khoản hệ thống'))
                        ->default(fn (?Employee $record): string => app(EmployeeAccountService::class)->accountSummary($record))
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText(UiText::get(
                            'fields.system_account_help',
                            'Việc cấp hoặc liên kết tài khoản được thực hiện từ menu thao tác của nhân viên để bảo đảm đúng quyền và lịch sử truy cập.',
                        )),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make(UiText::get('sections.organization', 'Organization'))
                ->icon('heroicon-o-building-office-2')
                ->schema([
                    Select::make('department_id')
                        ->label(UiText::get('fields.department', 'Department'))
                        ->options(fn (): array => Department::options())
                        ->searchable()
                        ->preload(),
                    Select::make('position_id')
                        ->label(UiText::get('fields.position', 'Job title'))
                        ->options(fn (): array => Position::groupedOptions())
                        ->searchable()
                        ->preload(),
                    Select::make('employment_status')
                        ->label(UiText::get('fields.employment_status', 'Employment status'))
                        ->options(EmploymentStatus::options())
                        ->default(EmploymentStatus::Active->value)
                        ->required()
                        ->native(false),
                    DatePicker::make('started_at')
                        ->label(UiText::get('fields.started_at', 'Start date')),
                    DatePicker::make('ended_at')
                        ->label(UiText::get('fields.ended_at', 'End date')),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee_code')
                    ->label(UiText::get('fields.employee_code', 'Employee code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('full_name')
                    ->label(UiText::get('fields.full_name', 'Full name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('department.name')
                    ->label(UiText::get('fields.department', 'Department'))
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('position.title')
                    ->label(UiText::get('fields.position', 'Job title'))
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('employment_status')
                    ->label(UiText::get('fields.employment_status', 'Employment status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => ($state instanceof EmploymentStatus ? $state : EmploymentStatus::tryFrom((string) $state))?->label() ?? (string) $state)
                    ->color(fn ($state): string => StatusColor::employment($state))
                    ->sortable(),
                TextColumn::make('_account_status')
                    ->label(UiText::get('fields.system_account', 'Tài khoản hệ thống'))
                    ->state(fn (Employee $record): string => app(EmployeeAccountService::class)->accountStatusLabel($record))
                    ->description(fn (Employee $record): ?string => app(EmployeeAccountService::class)->linkedAccountEmail($record))
                    ->badge()
                    ->color(fn (Employee $record): string => app(EmployeeAccountService::class)->accountStatusColor($record)),
                TextColumn::make('email')
                    ->label(UiText::get('common.fields.email', 'Email'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('phone')
                    ->label(UiText::get('fields.phone', 'Phone'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('employment_status')
                    ->label(UiText::get('fields.employment_status', 'Employment status'))
                    ->options(EmploymentStatus::options()),
                SelectFilter::make('department_id')
                    ->label(UiText::get('fields.department', 'Department'))
                    ->options(fn (): array => Department::options()),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\ViewAction::make()->label(UiText::get('common.actions.view', 'View')),
                    Actions\EditAction::make()->label(UiText::get('common.actions.edit', 'Edit')),

                    Actions\Action::make('provisionAccount')
                        ->label(UiText::get('actions.provision_account', 'Cấp tài khoản mới'))
                        ->icon('heroicon-o-user-plus')
                        ->color('primary')
                        ->url(fn (Employee $record): ?string => app(EmployeeAccountService::class)->provisioningUrl($record))
                        ->visible(fn (Employee $record): bool => app(HumanResourceAuthorization::class)->allows('hr.manage')
                            && app(EmployeeAccountService::class)->available()
                            && ! $record->user_id
                            && app(EmployeeAccountService::class)->canManageAccounts()
                            && filled(app(EmployeeAccountService::class)->provisioningUrl($record))),

                    Actions\Action::make('requestAccount')
                        ->label(UiText::get('actions.request_account', 'Yêu cầu cấp tài khoản'))
                        ->icon('heroicon-o-paper-airplane')
                        ->color('gray')
                        ->modalIcon('heroicon-o-paper-airplane')
                        ->modalWidth('2xl')
                        ->modalHeading(UiText::get('account.request_title', 'Yêu cầu quản trị cấp tài khoản'))
                        ->modalDescription(UiText::get(
                            'account.request_description',
                            'Yêu cầu sẽ xuất hiện trong module Tài khoản & Phân quyền để quản trị viên xử lý. HR không cần và không được sửa thông tin đăng nhập khi chưa có quyền quản trị tài khoản.',
                        ))
                        ->modalSubmitAction(fn (Actions\Action $action): Actions\Action => $action
                            ->label(UiText::get('actions.send_request', 'Gửi yêu cầu'))
                            ->icon('heroicon-o-paper-airplane')
                            ->extraAttributes(['class' => 'dth-hr-modal-action dth-hr-modal-action--primary']))
                        ->modalCancelAction(fn (Actions\Action $action): Actions\Action => $action
                            ->label(UiText::get('common.actions.cancel', 'Hủy thao tác'))
                            ->extraAttributes(['class' => 'dth-hr-modal-action dth-hr-modal-action--secondary']))
                        ->schema([
                            TextInput::make('requested_email')
                                ->label(UiText::get('fields.requested_login_email', 'Requested login email'))
                                ->default(fn (Employee $record): ?string => $record->email)
                                ->email()
                                ->required()
                                ->maxLength(255)
                                ->helperText(UiText::get('fields.requested_login_email_help', 'Proposed sign-in email. You can enter it here even when the employee profile does not have an email yet.')),
                            Textarea::make('note')
                                ->label(UiText::get('fields.request_note', 'Note for administrator'))
                                ->helperText(UiText::get('fields.request_note_help', 'Describe why the account is needed, its expected duration, or any special access notes.'))
                                ->rows(3)
                                ->maxLength(1000),
                        ])
                        ->action(function (Employee $record, array $data): void {
                            $adminRecipients = app(EmployeeAccountService::class)->requestProvisioning(
                                $record,
                                $data['note'] ?? null,
                                $data['requested_email'] ?? null,
                            );

                            $feedback = Notification::make()
                                ->title(UiText::get('notifications.account_request_sent', 'Account request sent'))
                                ->body($adminRecipients > 0
                                    ? UiText::get('notifications.account_request_sent_body', 'The request was recorded and sent to an account administrator for processing.')
                                    : UiText::get('notifications.account_request_saved_no_admin', 'The request was saved, but no account administrator is currently eligible to receive the notification.'))
                                ->persistent();

                            if ($adminRecipients > 0) {
                                $feedback->success();
                            } else {
                                $feedback->warning();
                            }

                            $feedback->send();
                        })
                        ->visible(fn (Employee $record): bool => app(HumanResourceAuthorization::class)->allows('hr.manage')
                            && app(EmployeeAccountService::class)->available()
                            && ! $record->user_id
                            && ! app(EmployeeAccountService::class)->canManageAccounts()
                            && ! app(EmployeeAccountService::class)->hasPendingAdminRequest($record)),

                    Actions\Action::make('linkExistingAccount')
                        ->label(UiText::get('actions.link_existing_account', 'Liên kết tài khoản hiện có'))
                        ->icon('heroicon-o-link')
                        ->color('gray')
                        ->modalIcon('heroicon-o-link')
                        ->modalWidth('2xl')
                        ->modalHeading(UiText::get('account.link_title', 'Liên kết tài khoản hiện có'))
                        ->modalDescription(UiText::get(
                            'account.link_description',
                            'Chỉ các tài khoản chưa thuộc nhân viên nào và không phải tài khoản quản trị được bảo vệ mới xuất hiện. Tài khoản đã từng được sử dụng cho người khác không được tái sử dụng.',
                        ))
                        ->modalSubmitAction(fn (Actions\Action $action): Actions\Action => $action
                            ->label(UiText::get('actions.link_existing_account', 'Liên kết tài khoản'))
                            ->icon('heroicon-o-link')
                            ->extraAttributes(['class' => 'dth-hr-modal-action dth-hr-modal-action--primary']))
                        ->modalCancelAction(fn (Actions\Action $action): Actions\Action => $action
                            ->label(UiText::get('common.actions.cancel', 'Hủy thao tác'))
                            ->extraAttributes(['class' => 'dth-hr-modal-action dth-hr-modal-action--secondary']))
                        ->schema([
                            Select::make('account_user_id')
                                ->label(UiText::get('fields.login_account', 'Tài khoản đăng nhập'))
                                ->options(fn (Employee $record): array => app(EmployeeAccountService::class)->linkableAccountOptions($record))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->helperText(new HtmlString('<div class="dth-hr-helper-legend"><span class="dth-hr-helper-pill dth-hr-helper-pill--match">Khớp hồ sơ</span><span class="dth-hr-helper-pill dth-hr-helper-pill--review">Cần xác minh</span></div><div class="dth-hr-helper-note">Tài khoản mang nhãn <strong>Khớp hồ sơ</strong> có tên/email trùng với hồ sơ nhân viên. Tài khoản mang nhãn <strong>Cần xác minh</strong> chưa từng sử dụng nhưng thông tin hiện tại khác với hồ sơ nhân viên.</div>')),
                            Toggle::make('confirm_identity_mismatch')
                                ->label(UiText::get('fields.confirm_identity_mismatch', 'Tôi đã xác minh đúng chủ tài khoản'))
                                ->helperText(UiText::get('fields.confirm_identity_mismatch_help', 'Chỉ bật khi tài khoản được đánh dấu cần xác minh. Nếu HR không có quyền quản trị tài khoản, hệ thống sẽ tự gửi yêu cầu để Admin đồng bộ lại tên/email.')),
                            Textarea::make('note')
                                ->label(UiText::get('fields.request_note', 'Note for administrator'))
                                ->helperText(UiText::get('fields.link_note_help', 'Record how the identity was verified or anything the Account administrator should know before synchronization.'))
                                ->rows(3)
                                ->maxLength(1000),
                        ])
                        ->action(function (Employee $record, array $data): void {
                            $result = app(EmployeeAccountService::class)->linkExisting(
                                $record,
                                (int) $data['account_user_id'],
                                (bool) ($data['confirm_identity_mismatch'] ?? false),
                                $data['note'] ?? null,
                            );

                            $feedback = Notification::make()
                                ->title(UiText::get('notifications.account_linked', 'Account linked'))
                                ->body($result['admin_request_created']
                                    ? ((int) ($result['admin_recipient_count'] ?? 0) > 0
                                        ? UiText::get('notifications.account_identity_request_created', 'The account identity differs from the employee record. A synchronization request was sent to an account administrator.')
                                        : UiText::get('notifications.account_identity_request_saved_no_admin', 'The account was linked, but no account administrator is currently eligible to receive the synchronization request.'))
                                    : UiText::get('notifications.account_linked_body', 'The account is now linked to this employee.'));

                            if ($result['admin_request_created'] && (int) ($result['admin_recipient_count'] ?? 0) === 0) {
                                $feedback->warning()->persistent();
                            } else {
                                $feedback->success();
                            }

                            $feedback->send();
                        })
                        ->visible(fn (Employee $record): bool => app(HumanResourceAuthorization::class)->allows('hr.manage')
                            && app(EmployeeAccountService::class)->available()
                            && ! $record->user_id
                            && app(EmployeeAccountService::class)->linkableAccountOptions($record) !== []),

                    Actions\Action::make('unlinkAccount')
                        ->label(UiText::get('actions.unlink_account', 'Gỡ liên kết tài khoản'))
                        ->icon('heroicon-o-link-slash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalIcon('heroicon-o-link-slash')
                        ->modalWidth('xl')
                        ->modalDescription(UiText::get('account.unlink_description', 'Thao tác này chỉ gỡ liên kết với hồ sơ nhân viên, không xóa hoặc vô hiệu hóa tài khoản. Chỉ quản trị viên tài khoản mới được thực hiện.'))
                        ->modalSubmitAction(fn (Actions\Action $action): Actions\Action => $action
                            ->label(UiText::get('actions.unlink_account', 'Gỡ liên kết tài khoản'))
                            ->extraAttributes(['class' => 'dth-hr-modal-action dth-hr-modal-action--danger']))
                        ->modalCancelAction(fn (Actions\Action $action): Actions\Action => $action
                            ->label(UiText::get('common.actions.cancel', 'Hủy thao tác'))
                            ->extraAttributes(['class' => 'dth-hr-modal-action dth-hr-modal-action--secondary']))
                        ->action(function (Employee $record): void {
                            app(EmployeeAccountService::class)->unlink($record);
                            Notification::make()
                                ->title(UiText::get('notifications.account_unlinked', 'Đã gỡ liên kết tài khoản'))
                                ->success()
                                ->send();
                        })
                        ->visible(fn (Employee $record): bool => app(HumanResourceAuthorization::class)->allows('hr.manage')
                            && (bool) $record->user_id
                            && app(EmployeeAccountService::class)->canManageAccounts()),

                    Actions\DeleteAction::make()->label(UiText::get('common.actions.delete', 'Delete')),
                ])->icon('heroicon-o-ellipsis-vertical')->iconButton(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()
                        ->label(UiText::get('common.actions.delete', 'Delete'))
                        ->authorizeIndividualRecords(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        $relations = [];

        if (config('dth-human-resource.features.availability', true)) {
            $relations[] = AvailabilitiesRelationManager::class;
        }
        if (config('dth-human-resource.features.business_functions', true)) {
            $relations[] = BusinessFunctionsRelationManager::class;
        }

        return $relations;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'view' => Pages\ViewEmployee::route('/{record}'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }

}
