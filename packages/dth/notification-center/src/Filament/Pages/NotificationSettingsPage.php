<?php

namespace Dth\NotificationCenter\Filament\Pages;

use Dth\NotificationCenter\Filament\Support\NotificationPageUi;
use Dth\NotificationCenter\Services\NotificationPreferenceService;
use Dth\NotificationCenter\Support\UiText;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Schema as SchemaFacade;

class NotificationSettingsPage extends Page
{
    protected string $view = 'dth-notification-center::filament.pages.settings';
    protected static string $routePath = 'notification-settings';
    protected static bool $shouldRegisterNavigation = false;

    public bool $inAppEnabled = true;
    public bool $emailEnabled = true;
    public bool $contentPreview = true;
    /** @var array<int, string> */
    public array $mutedModules = [];

    public function mount(): void
    {
        abort_unless(auth()->check(), 403);
        $values = app(NotificationPreferenceService::class)->valuesForUser((int) auth()->id());
        $this->inAppEnabled = (bool) ($values['in_app_enabled'] ?? true);
        $this->emailEnabled = (bool) ($values['email_enabled'] ?? true);
        $this->contentPreview = (bool) ($values['content_preview'] ?? true);
        $this->mutedModules = array_values((array) ($values['muted_modules'] ?? []));
    }

    public function getTitle(): string|Htmlable
    {
        return NotificationPageUi::title(UiText::get('page.settings_title', 'Cài đặt thông báo'), 'settings');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('page.settings_subheading', 'Chọn cách bạn muốn nhận thông báo In-app và Email.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(UiText::get('actions.back', 'Quay lại thông báo'))
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-notify-entry-action'])
                ->url(fn (): string => NotificationsPage::getUrl()),
        ];
    }

    public function save(): void
    {
        app(NotificationPreferenceService::class)->updateUser((int) auth()->id(), [
            'in_app_enabled' => $this->inAppEnabled,
            'email_enabled' => $this->emailEnabled,
            'content_preview' => $this->contentPreview,
            'muted_modules' => $this->mutedModules,
        ]);

        FilamentNotification::make()
            ->success()
            ->title(UiText::get('notifications.settings_saved', 'Đã lưu cài đặt thông báo'))
            ->send();
    }

    /** @return array<string, string> */
    public function moduleOptions(): array
    {
        $modules = [
            'accounts', 'human-resource', 'crm', 'commercial', 'marketing', 'email', 'notifications', 'core',
        ];

        if (SchemaFacade::hasTable('dth_notifications')) {
            $modules = array_values(array_unique([
                ...$modules,
                ...\Dth\NotificationCenter\Models\Notification::query()->whereNotNull('source_module')->distinct()->pluck('source_module')->all(),
            ]));
        }

        return collect($modules)
            ->filter()
            ->mapWithKeys(fn (string $module): array => [$module => UiText::get('modules.'.$module, str($module)->headline()->toString())])
            ->all();
    }

    public static function canAccess(): bool
    {
        return auth()->check() && config('dth-notification-center.features.preferences', true);
    }
}
