<?php

namespace Dth\NotificationCenter\Livewire;

use Dth\NotificationCenter\Filament\Pages\NotificationsPage;
use Dth\NotificationCenter\Models\NotificationRecipient;
use Dth\NotificationCenter\Services\NotificationManager;
use Dth\NotificationCenter\Services\NotificationPreferenceService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class NotificationBell extends Component
{
    public function markAllRead(): void
    {
        if (! auth()->check()) {
            return;
        }

        app(NotificationManager::class)->markAllRead((int) auth()->id());
    }

    public function clearAll(): void
    {
        if (! auth()->check()) {
            return;
        }

        app(NotificationManager::class)->clearForUser((int) auth()->id());
    }


    public function deleteRecipient(int $recipientId): void
    {
        if (! auth()->check()) {
            return;
        }

        app(NotificationManager::class)->deleteForUser($recipientId, (int) auth()->id());
    }

    public function markRead(int $recipientId): void
    {
        if (! auth()->check()) {
            return;
        }

        app(NotificationManager::class)->markRead($recipientId, (int) auth()->id());
    }

    public function render(): View
    {
        $userId = (int) auth()->id();
        $items = collect();
        $unreadCount = 0;
        $preview = true;

        if ($userId > 0 && Schema::hasTable('dth_notification_recipients')) {
            $unreadCount = app(NotificationManager::class)->unreadCount($userId);
            $preview = app(NotificationPreferenceService::class)->contentPreviewEnabled($userId);
            $items = NotificationRecipient::query()
                ->with('notification')
                ->where('user_id', $userId)
                ->whereNotNull('in_app_delivered_at')
                ->whereNull('deleted_at')
                ->whereHas('notification', fn ($query) => $query
                    ->where(fn ($inner) => $inner->whereNull('expires_at')->orWhere('expires_at', '>', now())))
                ->latest('id')
                ->limit((int) config('dth-notification-center.ui.recent_limit', 8))
                ->get();
        }

        return view('dth-notification-center::livewire.notification-bell', [
            'items' => $items,
            'unreadCount' => $unreadCount,
            'previewContent' => $preview,
            'centerUrl' => NotificationsPage::getUrl(),
            'pollSeconds' => max(10, (int) config('dth-notification-center.ui.poll_seconds', 30)),
        ]);
    }
}
