<?php

namespace Dth\NotificationCenter\Filament\Resources;

use Dth\NotificationCenter\Filament\Navigation\NotificationNavigationGroup;

use Dth\NotificationCenter\Filament\Resources\NotificationTemplateResource\Pages;
use Dth\NotificationCenter\Models\NotificationTemplate;
use Dth\NotificationCenter\Services\NotificationAuthorization;
use Dth\NotificationCenter\Support\NotificationPresenter;
use Dth\NotificationCenter\Support\UiText;
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
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplateResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $model = NotificationTemplate::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
    protected static string|\UnitEnum|null $navigationGroup = NotificationNavigationGroup::Notifications;
    protected static ?int $navigationSort = 20;
    protected static ?string $slug = 'notification-templates';

    public static function getNavigationLabel(): string { return app()->getLocale() === 'en' ? 'Notification templates' : 'Mẫu thông báo'; }
    public static function getModelLabel(): string { return app()->getLocale() === 'en' ? 'Notification template' : 'Mẫu thông báo'; }
    public static function getPluralModelLabel(): string { return static::getNavigationLabel(); }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(app()->getLocale() === 'en' ? 'Template definition' : 'Định nghĩa mẫu')
                ->description(app()->getLocale() === 'en' ? 'Define when this template is used, its default channels and how it is categorized.' : 'Xác định mẫu được dùng cho phân hệ nào, kênh mặc định và cách phân loại thông báo.')
                ->icon('heroicon-o-document-text')
                ->schema([
                    TextInput::make('name')->label(app()->getLocale() === 'en' ? 'Name' : 'Tên')->required()->maxLength(255)->helperText(UiText::get('helpers.template.name', 'Tên dễ hiểu để quản trị viên nhận biết mục đích của mẫu.')),
                    TextInput::make('code')->label(app()->getLocale() === 'en' ? 'Template code' : 'Mã mẫu')->required()->maxLength(160)->unique(ignoreRecord: true)->helperText(UiText::get('helpers.template.code', 'Mã ổn định dùng bởi code và event, ví dụ hr.account_request_submitted.')),
                    TextInput::make('source_module')->label(UiText::get('fields.module', 'Phân hệ'))->maxLength(80)->helperText(UiText::get('helpers.template.module', 'Phân hệ phát sinh thông báo, ví dụ human-resource, accounts, crm.')),
                    Select::make('type')->label(UiText::get('fields.type', 'Loại thông báo'))->options(NotificationPresenter::typeOptions())->required()->native(false)->helperText(UiText::get('helpers.template.type', 'Loại giúp phân nhóm và lọc thông báo trong Notification Center.')),
                    Select::make('priority')->label(UiText::get('fields.priority', 'Mức độ ưu tiên'))->options(NotificationPresenter::priorityOptions())->required()->native(false)->helperText(UiText::get('helpers.template.priority', 'Mức ưu tiên mặc định khi template được sử dụng.')),
                    CheckboxList::make('channels')->label(UiText::get('fields.channels', 'Kênh gửi'))->options(['in_app' => 'In-app', 'email' => 'Email'])->columns(2)->required()->helperText(UiText::get('helpers.template.channels', 'Các kênh mặc định. Workflow có thể ghi đè khi gửi nếu cần.')),
                    Toggle::make('is_mandatory')->label(UiText::get('fields.mandatory', 'Thông báo bắt buộc'))->helperText(UiText::get('helpers.template.mandatory', 'Bật khi thông báo phải được gửi bất kể lựa chọn cá nhân của người nhận.')),
                    Toggle::make('is_active')->label(app()->getLocale() === 'en' ? 'Active' : 'Hoạt động')->default(true)->helperText(UiText::get('helpers.template.active', 'Tắt để ngừng sử dụng mẫu mà không xóa lịch sử.')),
                ])->columns(2)->columnSpanFull(),
            Section::make(app()->getLocale() === 'en' ? 'Content' : 'Nội dung')
                ->description(app()->getLocale() === 'en' ? 'Compose reusable In-app and Email content. Template variables are resolved when the workflow sends a notification.' : 'Soạn nội dung In-app và Email có thể tái sử dụng. Các biến template sẽ được thay bằng dữ liệu khi workflow gửi thông báo.')
                ->schema([
                    TextInput::make('title_template')->label(UiText::get('fields.title', 'Tiêu đề'))->required()->maxLength(255)->helperText(UiText::get('helpers.template.title', 'Tiêu đề ngắn. Có thể dùng biến như {{employee_name}}.'))->columnSpanFull(),
                    Textarea::make('body_template')->label(UiText::get('fields.body', 'Nội dung tóm tắt'))->required()->rows(3)->helperText(UiText::get('helpers.template.body', 'Nội dung tóm tắt hiển thị trên chuông và danh sách thông báo.'))->columnSpanFull(),
                    TextInput::make('email_subject_template')->label(app()->getLocale() === 'en' ? 'Email subject' : 'Tiêu đề Email')->maxLength(255)->helperText(UiText::get('helpers.template.email_subject', 'Tiêu đề email; để trống thì hệ thống dùng tiêu đề thông báo.'))->columnSpanFull(),
                    Textarea::make('email_body_template')->label(app()->getLocale() === 'en' ? 'Email body / detailed content' : 'Nội dung Email / nội dung chi tiết')->rows(5)->helperText(UiText::get('helpers.template.email_body', 'Nội dung chi tiết cho Email. Có thể dùng các biến dữ liệu của workflow.'))->columnSpanFull(),
                    TextInput::make('action_label_template')->label(UiText::get('fields.action_label', 'Tên nút hành động'))->maxLength(120)->helperText(UiText::get('helpers.template.action_label', 'Tên CTA, ví dụ Xử lý yêu cầu hoặc Xem chi tiết.')),
                ])->columns(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(app()->getLocale() === 'en' ? 'Name' : 'Tên')->searchable()->sortable(),
                TextColumn::make('code')->label(app()->getLocale() === 'en' ? 'Code' : 'Mã mẫu')->copyable()->searchable(),
                TextColumn::make('source_module')->label(UiText::get('fields.module', 'Phân hệ'))->formatStateUsing(fn ($state): string => NotificationPresenter::moduleLabel((string) $state)),
                TextColumn::make('type')->label(UiText::get('fields.type', 'Loại'))->badge()->formatStateUsing(fn ($state): string => NotificationPresenter::typeLabel((string) $state)),
                TextColumn::make('priority')->label(UiText::get('fields.priority', 'Ưu tiên'))->badge()->formatStateUsing(fn ($state): string => NotificationPresenter::priorityLabel((string) $state)),
                IconColumn::make('is_active')->label(app()->getLocale() === 'en' ? 'Active' : 'Hoạt động')->boolean(),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\EditAction::make()->label(app()->getLocale() === 'en' ? 'Edit' : 'Chỉnh sửa'),
                    Actions\DeleteAction::make()->label(UiText::get('actions.delete', 'Xóa')),
                ])->icon('heroicon-o-ellipsis-vertical')->iconButton(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotificationTemplates::route('/'),
            'create' => Pages\CreateNotificationTemplate::route('/create'),
            'edit' => Pages\EditNotificationTemplate::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool { return app(NotificationAuthorization::class)->canManageTemplates(); }
    public static function canCreate(): bool { return app(NotificationAuthorization::class)->canManageTemplates(); }
    public static function canEdit(Model $record): bool { return app(NotificationAuthorization::class)->canManageTemplates(); }
    public static function canDelete(Model $record): bool { return app(NotificationAuthorization::class)->canManageTemplates(); }
}
