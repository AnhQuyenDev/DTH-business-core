<?php

namespace Dth\NotificationCenter\Filament\Pages;

use Dth\NotificationCenter\Filament\Support\NotificationPageUi;
use Dth\NotificationCenter\Filament\Resources\NotificationLogResource;
use Dth\NotificationCenter\Filament\Resources\NotificationTemplateResource;
use Dth\NotificationCenter\Models\Notification;
use Dth\NotificationCenter\Models\NotificationRecipient;
use Dth\NotificationCenter\Services\NotificationAuthorization;
use Dth\NotificationCenter\Services\NotificationManager;
use Dth\NotificationCenter\Services\RecipientDirectory;
use Dth\NotificationCenter\Support\NotificationPresenter;
use Dth\NotificationCenter\Support\UiText;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class NotificationsPage extends Page
{
    protected string $view = 'dth-notification-center::filament.pages.notifications';
    protected static string $routePath = 'notifications';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bell';
    protected static string|\UnitEnum|null $navigationGroup = null;
    protected static ?int $navigationSort = 5;

    public string $mailbox = 'inbox';
    public string $tab = 'all';
    public string $search = '';
    public string $type = '';
    public string $module = '';
    public string $period = '';
    public ?int $selectedRecipientId = null;
    public ?int $selectedNotificationId = null;

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.notifications', 'Thông báo');
    }

    public function getTitle(): string|Htmlable
    {
        return NotificationPageUi::title(UiText::get('page.title', 'Thông báo'), 'bell');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('page.subheading', 'Theo dõi yêu cầu cần xử lý, cập nhật nghiệp vụ và cảnh báo quan trọng trên toàn hệ thống.');
    }

    public function mount(): void
    {
        abort_unless(auth()->check(), 403);
        if (! Schema::hasTable('dth_notification_recipients')) {
            return;
        }

        $requestedMailbox = (string) request()->query('mailbox', 'inbox');
        $this->mailbox = in_array($requestedMailbox, ['inbox', 'sent'], true) ? $requestedMailbox : 'inbox';
        $notificationId = (int) request()->query('notification', 0);

        if ($this->mailbox === 'sent') {
            $query = $this->baseSentQuery();
            if ($notificationId > 0) {
                $query->whereKey($notificationId);
            }
            $this->selectedNotificationId = $query->latest('id')->value('id');
            return;
        }

        $query = $this->baseInboxQuery();
        if ($notificationId > 0) {
            $query->where('notification_id', $notificationId);
        }

        $this->selectedRecipientId = $query->latest('id')->value('id');
        if ($this->selectedRecipientId) {
            app(NotificationManager::class)->markRead($this->selectedRecipientId, (int) auth()->id());
        }
    }

    protected function getHeaderActions(): array
    {
        $directory = app(RecipientDirectory::class);
        $authorization = app(NotificationAuthorization::class);

        return [
            Action::make('sendNotification')
                ->label(UiText::get('actions.send', 'Gửi thông báo'))
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->extraAttributes(['class' => 'dth-notify-entry-action dth-notify-entry-action--primary dth-notify-entry-action--send'])
                ->modalIcon('heroicon-o-paper-airplane')
                ->modalHeading(UiText::get('actions.send', 'Gửi thông báo'))
                ->modalDescription(UiText::get('helpers.send.description', 'Chọn kênh, người nhận và nội dung. Hệ thống tự loại tài khoản đang gửi khỏi tập người nhận.'))
                ->modalWidth('5xl')
                ->modalSubmitAction(fn (Action $action): Action => $action
                    ->label(UiText::get('actions.send', 'Gửi thông báo'))
                    ->icon('heroicon-o-paper-airplane')
                    ->extraAttributes(['class' => 'dth-notify-modal-action dth-notify-modal-action--primary']))
                ->modalCancelAction(fn (Action $action): Action => $action
                    ->label(UiText::get('actions.cancel', 'Hủy'))
                    ->extraAttributes(['class' => 'dth-notify-modal-action dth-notify-modal-action--secondary']))
                ->schema([
                    Section::make(UiText::get('composer.delivery_title', 'Phân phối'))
                        ->description(UiText::get('composer.delivery_help', 'Chọn kênh gửi và xác định chính xác tập người nhận.'))
                        ->icon('heroicon-o-paper-airplane')
                        ->schema([
                            CheckboxList::make('channels')
                                ->label(UiText::get('fields.channels', 'Kênh gửi'))
                                ->options(fn (): array => array_filter([
                                    'in_app' => config('dth-notification-center.features.in_app', true) ? UiText::get('channels.in_app', 'In-app') : null,
                                    'email' => config('dth-notification-center.features.email', true) ? UiText::get('channels.email', 'Email') : null,
                                ]))
                                ->default(fn (): array => config('dth-notification-center.features.in_app', true) ? ['in_app'] : ['email'])
                                ->columns(2)
                                ->required()
                                ->helperText(UiText::get('helpers.send.channels', 'In-app hiển thị trên chuông; Email gửi tới địa chỉ email của tài khoản.'))
                                ->columnSpanFull(),

                            Select::make('recipient_type')
                                ->label(UiText::get('fields.recipient_type', 'Người nhận'))
                                ->options(function () use ($authorization, $directory): array {
                                    $options = ['user' => UiText::get('recipient_types.user', 'Người dùng cụ thể')];
                                    if ($authorization->canTargetRoles() && $directory->roleOptions() !== []) {
                                        $options['role'] = UiText::get('recipient_types.role', 'Vai trò');
                                    }
                                    if ($authorization->canTargetDepartments() && $directory->departmentOptions() !== []) {
                                        $options['department'] = UiText::get('recipient_types.department', 'Phòng ban');
                                    }
                                    return $options;
                                })
                                ->default('user')
                                ->required()
                                ->native(false)
                                ->live()
                                ->afterStateUpdated(function (Set $set): void {
                                    $set('user_ids', []);
                                    $set('role_ids', []);
                                    $set('department_ids', []);
                                })
                                ->helperText(UiText::get('helpers.send.recipient_type', 'Chọn cách gom người nhận. Trường tương ứng sẽ xuất hiện bên cạnh.')),

                            Select::make('user_ids')
                                ->label(UiText::get('fields.users', 'Người dùng cụ thể'))
                                ->options(fn (): array => $directory->userOptions((int) auth()->id()))
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->required(fn (Get $get): bool => $get('recipient_type') === 'user')
                                ->visible(fn (Get $get): bool => $get('recipient_type') === 'user')
                                ->hintIcon('heroicon-o-information-circle', tooltip: UiText::get('helpers.send.users', 'Tài khoản đang đăng nhập được tự động loại khỏi danh sách.')),

                            Select::make('role_ids')
                                ->label(UiText::get('fields.roles', 'Vai trò'))
                                ->options(fn (): array => $directory->roleOptions())
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->required(fn (Get $get): bool => $get('recipient_type') === 'role')
                                ->visible(fn (Get $get): bool => $get('recipient_type') === 'role' && $authorization->canTargetRoles())
                                ->hintIcon('heroicon-o-information-circle', tooltip: UiText::get('helpers.send.roles', 'Gửi tới các tài khoản đang hoạt động thuộc vai trò đã chọn.')),

                            Select::make('department_ids')
                                ->label(UiText::get('fields.departments', 'Phòng ban'))
                                ->options(fn (): array => $directory->departmentOptions())
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->required(fn (Get $get): bool => $get('recipient_type') === 'department')
                                ->visible(fn (Get $get): bool => $get('recipient_type') === 'department' && $authorization->canTargetDepartments())
                                ->hintIcon('heroicon-o-information-circle', tooltip: UiText::get('helpers.send.departments', 'Gửi tới tài khoản đang liên kết với nhân viên trong phòng ban HR đã chọn.')),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),

                    Section::make(UiText::get('composer.content_title', 'Nội dung thông báo'))
                        ->description(UiText::get('composer.content_help', 'Soạn nội dung ngắn gọn; thông tin chi tiết chỉ xuất hiện khi người nhận mở thông báo.'))
                        ->icon('heroicon-o-pencil-square')
                        ->schema([
                            Select::make('type')
                                ->label(UiText::get('fields.type', 'Loại thông báo'))
                                ->options(NotificationPresenter::typeOptions())
                                ->default('announcement')
                                ->required()
                                ->native(false)
                                ->hintIcon('heroicon-o-information-circle', tooltip: UiText::get('helpers.send.type', 'Loại thông báo giúp người nhận lọc và hiểu mục đích.')),
                            Select::make('priority')
                                ->label(UiText::get('fields.priority', 'Mức độ ưu tiên'))
                                ->options(NotificationPresenter::priorityOptions())
                                ->default('normal')
                                ->required()
                                ->native(false)
                                ->hintIcon('heroicon-o-information-circle', tooltip: UiText::get('helpers.send.priority', 'Chỉ dùng Cao hoặc Khẩn cấp cho nội dung thật sự cần chú ý sớm.')),

                            TextInput::make('title')
                                ->label(UiText::get('fields.title', 'Tiêu đề'))
                                ->required()
                                ->maxLength(255)
                                ->placeholder(UiText::get('composer.title_placeholder', 'Ví dụ: Phê duyệt phiếu lương tháng 09/2026'))
                                ->columnSpanFull(),

                            Textarea::make('body')
                                ->label(UiText::get('fields.body', 'Nội dung tóm tắt'))
                                ->required()
                                ->rows(3)
                                ->maxLength(3000)
                                ->placeholder(UiText::get('composer.summary_placeholder', 'Nội dung ngắn xuất hiện trên danh sách và chuông thông báo.')),
                            Textarea::make('detail_body')
                                ->label(UiText::get('fields.detail_body', 'Nội dung chi tiết'))
                                ->rows(3)
                                ->placeholder(UiText::get('composer.detail_placeholder', 'Bổ sung bối cảnh, hướng dẫn hoặc thông tin chi tiết nếu cần.')),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),

                    Section::make(UiText::get('composer.action_title', 'Hành động & tệp đính kèm'))
                        ->description(UiText::get('composer.action_help', 'Không bắt buộc. Dùng khi người nhận cần mở một màn hình nghiệp vụ hoặc tải tài liệu liên quan.'))
                        ->icon('heroicon-o-link')
                        ->schema([
                            TextInput::make('action_label')
                                ->label(UiText::get('fields.action_label', 'Tên nút hành động'))
                                ->maxLength(120)
                                ->placeholder(UiText::get('composer.action_label_placeholder', 'Ví dụ: Xem nhân viên')),
                            TextInput::make('action_url')
                                ->label(UiText::get('fields.action_url', 'Đường dẫn hành động'))
                                ->maxLength(2000)
                                ->placeholder('/admin/...'),

                            FileUpload::make('attachments')
                                ->label(UiText::get('fields.attachments', 'Tệp đính kèm'))
                                ->disk((string) config('dth-notification-center.attachments.disk', 'local'))
                                ->directory(fn (): string => trim((string) config('dth-notification-center.attachments.directory', 'dth-notifications/attachments'), '/').'/'.now()->format('Y/m'))
                                ->multiple()
                                ->maxFiles((int) config('dth-notification-center.attachments.max_files', 5))
                                ->maxSize((int) config('dth-notification-center.attachments.max_size_kb', 10240))
                                ->storeFileNamesIn('attachment_names')
                                ->previewable(false)
                                ->acceptedFileTypes((array) config('dth-notification-center.attachments.accepted_types', []))
                                ->helperText(UiText::get('helpers.send.attachments', 'Tối đa 5 tệp, mỗi tệp 10 MB.'))
                                ->columnSpanFull(),

                            Toggle::make('mandatory')
                                ->label(UiText::get('fields.mandatory', 'Thông báo bắt buộc'))
                                ->helperText(UiText::get('helpers.send.mandatory', 'Bỏ qua tùy chọn tắt kênh của người nhận. Chỉ dùng cho cảnh báo hoặc chính sách quan trọng.'))
                                ->visible(fn (): bool => $authorization->canManageSystemSettings())
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ])
                ->action(function (array $data) use ($directory, $authorization): void {
                    $recipientType = (string) ($data['recipient_type'] ?? 'user');
                    $targets = [
                        'user_ids' => $recipientType === 'user' ? (array) ($data['user_ids'] ?? []) : [],
                        'role_ids' => $recipientType === 'role' && $authorization->canTargetRoles() ? (array) ($data['role_ids'] ?? []) : [],
                        'group_ids' => [],
                        'department_ids' => $recipientType === 'department' && $authorization->canTargetDepartments() ? (array) ($data['department_ids'] ?? []) : [],
                        'broadcast' => false,
                    ];

                    $recipientIds = $directory->resolve($targets, false);
                    $currentUserId = (int) auth()->id();
                    $recipientIds = array_values(array_filter($recipientIds, fn (int $id): bool => $id !== $currentUserId));

                    if ($recipientIds === []) {
                        $attachmentDisk = (string) config('dth-notification-center.attachments.disk', 'local');
                        foreach ((array) ($data['attachments'] ?? []) as $uploadedPath) {
                            if (is_string($uploadedPath) && $uploadedPath !== '') {
                                Storage::disk($attachmentDisk)->delete($uploadedPath);
                            }
                        }

                        FilamentNotification::make()
                            ->warning()
                            ->title(UiText::get('notifications.no_recipient', 'Chưa có người nhận phù hợp'))
                            ->body(UiText::get('notifications.no_recipient_body', 'Hãy chọn ít nhất một người dùng, vai trò hoặc phòng ban có tài khoản đang hoạt động.'))
                            ->send();
                        return;
                    }

                    $rawPaths = (array) ($data['attachments'] ?? []);
                    $rawNames = (array) ($data['attachment_names'] ?? []);
                    $paths = array_values(array_filter($rawPaths));
                    $orderedNames = array_values($rawNames);
                    $attachments = [];
                    foreach ($paths as $index => $path) {
                        $attachments[] = [
                            'disk' => (string) config('dth-notification-center.attachments.disk', 'local'),
                            'path' => (string) $path,
                            'name' => (string) ($rawNames[$path] ?? $orderedNames[$index] ?? basename((string) $path)),
                        ];
                    }

                    $notification = app(NotificationManager::class)->send([
                        'type' => (string) ($data['type'] ?? 'announcement'),
                        'priority' => (string) ($data['priority'] ?? 'normal'),
                        'source_module' => 'notifications',
                        'source_event' => 'notifications.manual.sent',
                        'sender_user_id' => auth()->id(),
                        'sender_name' => auth()->user()?->name,
                        'title' => (string) $data['title'],
                        'body' => (string) $data['body'],
                        'detail_body' => $data['detail_body'] ?? null,
                        'action_label' => $data['action_label'] ?? null,
                        'action_url' => $data['action_url'] ?? null,
                        'attachments' => $attachments,
                        'mandatory' => $authorization->canManageSystemSettings() && (bool) ($data['mandatory'] ?? false),
                        'manual' => true,
                        'metadata' => ['targets' => $targets, 'recipient_type' => $recipientType],
                    ], $recipientIds, (array) ($data['channels'] ?? ['in_app']));

                    FilamentNotification::make()
                        ->success()
                        ->title(UiText::get('notifications.sent', 'Đã gửi thông báo'))
                        ->body(strtr(UiText::get('notifications.sent_body', 'Thông báo đã được tạo cho :count người nhận.'), [':count' => (string) $notification->recipients->count()]))
                        ->send();
                })
                ->visible(fn (): bool => config('dth-notification-center.features.manual_send', true) && $authorization->canSend()),

            Action::make('notificationTemplates')
                ->label(UiText::get('actions.templates', 'Mẫu thông báo'))
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-notify-entry-action dth-notify-entry-action--templates'])
                ->url(fn (): string => NotificationTemplateResource::getUrl('index'))
                ->visible(fn (): bool => $authorization->canManageTemplates()),

            Action::make('notificationLog')
                ->label(UiText::get('actions.delivery_log', 'Nhật ký gửi'))
                ->icon('heroicon-o-queue-list')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-notify-entry-action dth-notify-entry-action--log'])
                ->url(fn (): string => NotificationLogResource::getUrl('index'))
                ->visible(fn (): bool => $authorization->canViewAudit()),

            Action::make('notificationSettings')
                ->label(UiText::get('actions.settings', 'Cài đặt thông báo'))
                ->icon('heroicon-o-cog-6-tooth')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-notify-entry-action dth-notify-entry-action--settings'])
                ->url(fn (): string => NotificationSettingsPage::getUrl())
                ->visible(fn (): bool => config('dth-notification-center.features.preferences', true)),
        ];
    }

    public function openInboxTab(string $tab = 'all'): void
    {
        $this->switchMailbox('inbox');
        $this->tab = in_array($tab, ['all', 'unread', 'action', 'announcement', 'system'], true) ? $tab : 'all';
        $this->selectedRecipientId = $this->filteredInboxQuery()->latest('id')->value('id');
        if ($this->selectedRecipientId) {
            app(NotificationManager::class)->markRead($this->selectedRecipientId, (int) auth()->id());
        }
    }

    public function openSentToday(): void
    {
        $this->switchMailbox('sent');
        $this->period = '1';
        $this->selectedNotificationId = $this->filteredSentQuery()->latest('id')->value('id');
    }

    public function switchMailbox(string $mailbox): void
    {
        if (! in_array($mailbox, ['inbox', 'sent'], true)) {
            return;
        }

        $this->mailbox = $mailbox;
        $this->tab = 'all';
        $this->selectedRecipientId = null;
        $this->selectedNotificationId = null;

        if ($mailbox === 'sent') {
            $this->selectedNotificationId = $this->filteredSentQuery()->latest('id')->value('id');
            return;
        }

        $this->selectedRecipientId = $this->filteredInboxQuery()->latest('id')->value('id');
        if ($this->selectedRecipientId) {
            app(NotificationManager::class)->markRead($this->selectedRecipientId, (int) auth()->id());
        }
    }

    public function selectSentNotification(int $notificationId): void
    {
        $notification = $this->baseSentQuery()->whereKey($notificationId)->first();
        if (! $notification) {
            return;
        }

        $this->selectedNotificationId = $notificationId;
    }

    public function selectRecipient(int $recipientId): void
    {
        $recipient = $this->baseInboxQuery()->whereKey($recipientId)->first();
        if (! $recipient) {
            return;
        }

        $this->selectedRecipientId = $recipientId;
        app(NotificationManager::class)->markRead($recipientId, (int) auth()->id());
    }

    public function toggleRead(int $recipientId): void
    {
        $recipient = $this->baseInboxQuery()->whereKey($recipientId)->first();
        if (! $recipient) {
            return;
        }

        if ($recipient->read_at) {
            app(NotificationManager::class)->markUnread($recipientId, (int) auth()->id());
        } else {
            app(NotificationManager::class)->markRead($recipientId, (int) auth()->id());
        }
    }

    public function markAllRead(): void
    {
        if ($this->mailbox !== 'inbox') {
            return;
        }
        app(NotificationManager::class)->markAllRead((int) auth()->id());
    }

    public function deleteRecipient(int $recipientId): void
    {
        app(NotificationManager::class)->deleteForUser($recipientId, (int) auth()->id());
        if ($this->selectedRecipientId === $recipientId) {
            $this->selectedRecipientId = $this->filteredInboxQuery()->latest('id')->value('id');
        }

        FilamentNotification::make()
            ->success()
            ->title(UiText::get('notifications.deleted', 'Đã xóa thông báo khỏi hộp thư của bạn'))
            ->send();
    }

    public function clearAll(): void
    {
        if ($this->mailbox !== 'inbox') {
            return;
        }
        app(NotificationManager::class)->clearForUser((int) auth()->id());
        $this->selectedRecipientId = null;

        FilamentNotification::make()
            ->success()
            ->title(UiText::get('notifications.cleared', 'Đã xóa toàn bộ thông báo khỏi hộp thư của bạn'))
            ->send();
    }

    /** @return Collection<int, NotificationRecipient|Notification> */
    public function items(): Collection
    {
        if (! Schema::hasTable('dth_notification_recipients')) {
            return collect();
        }

        $query = $this->mailbox === 'sent'
            ? $this->filteredSentQuery()
            : $this->filteredInboxQuery();

        return $query
            ->latest('id')
            ->limit((int) config('dth-notification-center.ui.page_limit', 100))
            ->get();
    }

    public function selectedRecipient(): ?NotificationRecipient
    {
        if ($this->mailbox !== 'inbox' || ! $this->selectedRecipientId || ! Schema::hasTable('dth_notification_recipients')) {
            return null;
        }

        return $this->baseInboxQuery()->whereKey($this->selectedRecipientId)->first();
    }

    public function selectedNotification(): ?Notification
    {
        if (! Schema::hasTable('dth_notifications')) {
            return null;
        }

        if ($this->mailbox === 'sent') {
            if (! $this->selectedNotificationId) {
                return null;
            }
            return $this->baseSentQuery()->whereKey($this->selectedNotificationId)->first();
        }

        return $this->selectedRecipient()?->notification;
    }

    /** @return array<string, int> */
    public function mailboxCounts(): array
    {
        if (! Schema::hasTable('dth_notification_recipients')) {
            return ['inbox' => 0, 'sent' => 0, 'unread' => 0];
        }

        $inbox = $this->baseInboxQuery();
        $sent = Schema::hasTable('dth_notifications') ? $this->baseSentQuery() : null;

        return [
            'inbox' => (clone $inbox)->count(),
            'sent' => $sent ? (clone $sent)->count() : 0,
            'unread' => (clone $inbox)->whereNull('read_at')->count(),
        ];
    }

    /** @return array<string, int> */
    public function summaryStats(): array
    {
        if (! Schema::hasTable('dth_notification_recipients') || ! Schema::hasTable('dth_notifications')) {
            return [
                'inbox' => 0,
                'unread' => 0,
                'action' => 0,
                'sent_today' => 0,
                'email_failed' => 0,
            ];
        }

        $inbox = $this->baseInboxQuery();
        $sent = $this->baseSentQuery();

        return [
            'inbox' => (clone $inbox)->count(),
            'unread' => (clone $inbox)->whereNull('read_at')->count(),
            'action' => (clone $inbox)->whereHas('notification', fn (Builder $query) => $query->whereIn('type', ['action_required', 'approval']))->count(),
            'sent_today' => (clone $sent)->where('sent_at', '>=', now()->startOfDay())->count(),
            'email_failed' => NotificationRecipient::query()
                ->where('email_status', 'failed')
                ->whereHas('notification', fn (Builder $query) => $query->where('sender_user_id', (int) auth()->id()))
                ->count(),
        ];
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->type = '';
        $this->module = '';
        $this->period = '';
        $this->tab = 'all';

        if ($this->mailbox === 'sent') {
            $this->selectedNotificationId = $this->filteredSentQuery()->latest('id')->value('id');
            return;
        }

        $this->selectedRecipientId = $this->filteredInboxQuery()->latest('id')->value('id');
    }

    public function hasActiveFilters(): bool
    {
        return $this->search !== '' || $this->type !== '' || $this->module !== '' || $this->period !== '' || $this->tab !== 'all';
    }

    /** @return array<string, int> */
    public function counts(): array
    {
        if (! Schema::hasTable('dth_notification_recipients')) {
            return ['all' => 0, 'unread' => 0, 'action' => 0, 'announcement' => 0, 'system' => 0];
        }

        if ($this->mailbox === 'sent') {
            $base = $this->baseSentQuery();
            return [
                'all' => (clone $base)->count(),
                'unread' => 0,
                'action' => (clone $base)->whereIn('type', ['action_required', 'approval'])->count(),
                'announcement' => (clone $base)->where('type', 'announcement')->count(),
                'system' => (clone $base)->where('type', 'system')->count(),
            ];
        }

        $base = $this->baseInboxQuery();

        return [
            'all' => (clone $base)->count(),
            'unread' => (clone $base)->whereNull('read_at')->count(),
            'action' => (clone $base)->whereHas('notification', fn (Builder $query) => $query->whereIn('type', ['action_required', 'approval']))->count(),
            'announcement' => (clone $base)->whereHas('notification', fn (Builder $query) => $query->where('type', 'announcement'))->count(),
            'system' => (clone $base)->whereHas('notification', fn (Builder $query) => $query->where('type', 'system'))->count(),
        ];
    }

    public function audienceLabel(Notification $notification): string
    {
        $metadata = (array) ($notification->metadata ?? []);
        $recipientType = (string) ($metadata['recipient_type'] ?? '');
        $targets = (array) ($metadata['targets'] ?? []);
        $directory = app(RecipientDirectory::class);

        if ($recipientType === 'role') {
            $labels = $this->labelsForIds($directory->roleOptions(), (array) ($targets['role_ids'] ?? []));
            if ($labels !== []) {
                return UiText::get('recipient_types.role', 'Vai trò').': '.implode(', ', $labels);
            }
        }

        if ($recipientType === 'department') {
            $labels = $this->labelsForIds($directory->departmentOptions(), (array) ($targets['department_ids'] ?? []));
            if ($labels !== []) {
                return UiText::get('recipient_types.department', 'Phòng ban').': '.implode(', ', $labels);
            }
        }

        if ($recipientType === 'user') {
            return UiText::get('recipient_types.user', 'Người dùng cụ thể');
        }

        return UiText::get('fields.recipients', 'Người nhận');
    }

    public function recipientSummary(Notification $notification, int $limit = 2): string
    {
        $notification->loadMissing('recipients.user');
        $names = $notification->recipients
            ->map(fn (NotificationRecipient $recipient): string => trim((string) ($recipient->user?->name ?: $recipient->user?->email ?: ('#'.$recipient->user_id))))
            ->filter()
            ->unique()
            ->values();

        if ($names->isEmpty()) {
            return UiText::get('empty.recipients', 'Không có người nhận');
        }

        $shown = $names->take($limit)->implode(', ');
        $remaining = max(0, $names->count() - $limit);

        return $remaining > 0 ? $shown.' +'.$remaining : $shown;
    }

    /** @return array{total:int,in_app:int,email_sent:int,email_pending:int,email_failed:int} */
    public function deliveryStats(Notification $notification): array
    {
        $notification->loadMissing('recipients');
        $recipients = $notification->recipients;

        return [
            'total' => $recipients->count(),
            'in_app' => $recipients->whereNotNull('in_app_delivered_at')->count(),
            'email_sent' => $recipients->where('email_status', 'sent')->count(),
            'email_pending' => $recipients->whereIn('email_status', ['queued', 'sending'])->count(),
            'email_failed' => $recipients->where('email_status', 'failed')->count(),
        ];
    }

    /** @return Collection<int, NotificationRecipient> */
    public function recipientPreview(Notification $notification, int $limit = 8): Collection
    {
        $notification->loadMissing('recipients.user');
        return $notification->recipients->take($limit);
    }

    private function labelsForIds(array $options, array $ids): array
    {
        return collect($ids)
            ->filter(fn ($id): bool => is_numeric($id))
            ->map(fn ($id): ?string => $options[(int) $id] ?? null)
            ->filter()
            ->values()
            ->all();
    }

    /** @return array<string, string> */
    public function moduleOptions(): array
    {
        if (! Schema::hasTable('dth_notifications')) {
            return [];
        }

        return \Dth\NotificationCenter\Models\Notification::query()
            ->whereNotNull('source_module')
            ->distinct()
            ->orderBy('source_module')
            ->pluck('source_module')
            ->mapWithKeys(fn ($module): array => [(string) $module => NotificationPresenter::moduleLabel((string) $module)])
            ->all();
    }

    private function baseInboxQuery(): Builder
    {
        return NotificationRecipient::query()
            ->with(['user', 'notification.sender'])
            ->where('user_id', (int) auth()->id())
            ->whereNotNull('in_app_delivered_at')
            ->whereNull('deleted_at')
            ->whereHas('notification', fn (Builder $query) => $query
                ->where(fn (Builder $inner) => $inner->whereNull('expires_at')->orWhere('expires_at', '>', now())));
    }

    private function filteredInboxQuery(): Builder
    {
        $query = $this->baseInboxQuery();

        match ($this->tab) {
            'unread' => $query->whereNull('read_at'),
            'action' => $query->whereHas('notification', fn (Builder $builder) => $builder->whereIn('type', ['action_required', 'approval'])),
            'announcement' => $query->whereHas('notification', fn (Builder $builder) => $builder->where('type', 'announcement')),
            'system' => $query->whereHas('notification', fn (Builder $builder) => $builder->where('type', 'system')),
            default => null,
        };

        if ($this->search !== '') {
            $term = '%'.trim($this->search).'%';
            $query->whereHas('notification', fn (Builder $builder) => $builder->where(function (Builder $nested) use ($term): void {
                $nested->where('title', 'like', $term)
                    ->orWhere('body', 'like', $term)
                    ->orWhere('detail_body', 'like', $term)
                    ->orWhere('sender_name', 'like', $term);
            }));
        }

        if ($this->type !== '') {
            $query->whereHas('notification', fn (Builder $builder) => $builder->where('type', $this->type));
        }

        if ($this->module !== '') {
            $query->whereHas('notification', fn (Builder $builder) => $builder->where('source_module', $this->module));
        }

        $days = match ($this->period) {
            '1' => 1,
            '7' => 7,
            '30' => 30,
            default => null,
        };
        if ($days) {
            $query->whereHas('notification', fn (Builder $builder) => $builder->where('sent_at', '>=', now()->subDays($days)));
        }

        return $query;
    }

    private function baseSentQuery(): Builder
    {
        return Notification::query()
            ->with(['sender', 'recipients.user'])
            ->where('sender_user_id', (int) auth()->id());
    }

    private function filteredSentQuery(): Builder
    {
        $query = $this->baseSentQuery();

        match ($this->tab) {
            'action' => $query->whereIn('type', ['action_required', 'approval']),
            'announcement' => $query->where('type', 'announcement'),
            'system' => $query->where('type', 'system'),
            default => null,
        };

        if ($this->search !== '') {
            $term = '%'.trim($this->search).'%';
            $query->where(function (Builder $builder) use ($term): void {
                $builder->where('title', 'like', $term)
                    ->orWhere('body', 'like', $term)
                    ->orWhere('detail_body', 'like', $term)
                    ->orWhereHas('recipients.user', fn (Builder $userQuery) => $userQuery
                        ->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term));
            });
        }

        if ($this->type !== '') {
            $query->where('type', $this->type);
        }

        if ($this->module !== '') {
            $query->where('source_module', $this->module);
        }

        $days = match ($this->period) {
            '1' => 1,
            '7' => 7,
            '30' => 30,
            default => null,
        };
        if ($days) {
            $query->where('sent_at', '>=', now()->subDays($days));
        }

        return $query;
    }

    public static function canAccess(): bool
    {
        return auth()->check();
    }
}
