<?php

namespace Dth\NotificationCenter\Services;

use Dth\NotificationCenter\Jobs\SendNotificationEmail;
use Dth\NotificationCenter\Models\Notification;
use Dth\NotificationCenter\Models\NotificationRecipient;
use Dth\NotificationCenter\Models\NotificationTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class NotificationManager
{
    public function __construct(
        private readonly RecipientDirectory $directory,
        private readonly NotificationPreferenceService $preferences,
    ) {}

    /**
     * @param array<string, mixed> $message
     * @param array<int> $recipientUserIds
     * @param array<int, string> $channels
     */
    public function send(array $message, array $recipientUserIds, array $channels = ['in_app']): Notification
    {
        $enabledChannels = [];
        if (config('dth-notification-center.features.in_app', true)) {
            $enabledChannels[] = 'in_app';
        }
        if (config('dth-notification-center.features.email', true)) {
            $enabledChannels[] = 'email';
        }

        $channels = array_values(array_unique(array_intersect($enabledChannels, $channels)));
        if ($channels === [] && $enabledChannels !== []) {
            $channels = [$enabledChannels[0]];
        }

        $recipientUserIds = $this->directory->resolve(['user_ids' => $recipientUserIds], false);
        $notification = DB::transaction(function () use ($message, $recipientUserIds, $channels): Notification {
            $notification = Notification::query()->create([
                'uuid' => (string) Str::uuid(),
                'type' => (string) ($message['type'] ?? 'system'),
                'priority' => (string) ($message['priority'] ?? 'normal'),
                'source_module' => $message['source_module'] ?? 'core',
                'source_event' => $message['source_event'] ?? null,
                'source_type' => $message['source_type'] ?? null,
                'source_id' => isset($message['source_id']) ? (string) $message['source_id'] : null,
                'sender_user_id' => $message['sender_user_id'] ?? auth()->id(),
                'sender_name' => $message['sender_name'] ?? auth()->user()?->name,
                'title' => trim((string) ($message['title'] ?? 'Thông báo')),
                'body' => trim((string) ($message['body'] ?? '')),
                'detail_body' => isset($message['detail_body']) ? trim((string) $message['detail_body']) : null,
                'action_label' => $message['action_label'] ?? null,
                'action_url' => $message['action_url'] ?? null,
                'channels' => $channels,
                'attachments' => array_values((array) ($message['attachments'] ?? [])),
                'is_mandatory' => (bool) ($message['mandatory'] ?? false),
                'is_manual' => (bool) ($message['manual'] ?? false),
                'metadata' => (array) ($message['metadata'] ?? []),
                'sent_at' => now(),
                'expires_at' => $message['expires_at'] ?? null,
            ]);

            foreach ($recipientUserIds as $userId) {
                $inApp = in_array('in_app', $channels, true) && $this->preferences->allowsChannel($userId, 'in_app', $notification);
                $email = in_array('email', $channels, true) && $this->preferences->allowsChannel($userId, 'email', $notification);

                NotificationRecipient::query()->create([
                    'notification_id' => $notification->id,
                    'user_id' => $userId,
                    'in_app_delivered_at' => $inApp ? now() : null,
                    'email_status' => $email ? 'queued' : 'not_requested',
                    'email_queued_at' => $email ? now() : null,
                ]);
            }

            return $notification;
        });

        if (in_array('email', $channels, true)) {
            $this->dispatchEmailJobs($notification);
        }

        return $notification->fresh(['recipients']);
    }

    /**
     * @param array<string, mixed> $message
     * @param array<string, mixed> $targets
     * @param array<int, string> $channels
     */
    public function sendToTargets(array $message, array $targets, array $channels, bool $allowBroadcast = false): Notification
    {
        return $this->send($message, $this->directory->resolve($targets, $allowBroadcast), $channels);
    }

    /**
     * @param array<string, mixed> $message
     * @param array<int, string> $channels
     */
    public function sendToPermission(string $permission, array $message, array $channels = ['in_app']): Notification
    {
        return $this->send($message, $this->directory->usersWithPermission($permission), $channels);
    }

    /**
     * @param array<string, mixed> $variables
     * @param array<int> $recipientUserIds
     * @param array<string, mixed> $overrides
     */
    public function sendFromTemplate(string $code, array $variables, array $recipientUserIds, array $overrides = []): ?Notification
    {
        if (! Schema::hasTable('dth_notification_templates')) {
            return null;
        }

        $template = NotificationTemplate::query()->where('code', $code)->where('is_active', true)->first();
        if (! $template) {
            return null;
        }

        $replace = [];
        foreach ($variables as $key => $value) {
            $replace['{{'.$key.'}}'] = (string) $value;
        }

        $templateMetadata = [];
        if ($template->email_subject_template) {
            $templateMetadata['email_subject'] = strtr($template->email_subject_template, $replace);
        }
        if ($template->email_body_template) {
            $templateMetadata['email_body'] = strtr($template->email_body_template, $replace);
        }

        $message = array_merge([
            'type' => $template->type,
            'priority' => $template->priority,
            'source_module' => $template->source_module,
            'title' => strtr($template->title_template, $replace),
            'body' => strtr($template->body_template, $replace),
            'detail_body' => null,
            'action_label' => $template->action_label_template ? strtr($template->action_label_template, $replace) : null,
            'mandatory' => (bool) $template->is_mandatory,
            'metadata' => $templateMetadata,
        ], $overrides);

        $message['metadata'] = array_merge($templateMetadata, (array) ($overrides['metadata'] ?? []));

        return $this->send($message, $recipientUserIds, (array) ($overrides['channels'] ?? $template->channels ?? ['in_app']));
    }

    public function unreadCount(int $userId): int
    {
        if (! Schema::hasTable('dth_notification_recipients')) {
            return 0;
        }

        return NotificationRecipient::query()
            ->where('user_id', $userId)
            ->whereNotNull('in_app_delivered_at')
            ->whereNull('read_at')
            ->whereNull('deleted_at')
            ->whereHas('notification', fn ($query) => $query
                ->where(fn ($inner) => $inner->whereNull('expires_at')->orWhere('expires_at', '>', now())))
            ->count();
    }

    public function markRead(int $recipientId, int $userId): void
    {
        NotificationRecipient::query()
            ->whereKey($recipientId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->update(['read_at' => now(), 'updated_at' => now()]);
    }

    public function markUnread(int $recipientId, int $userId): void
    {
        NotificationRecipient::query()
            ->whereKey($recipientId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->update(['read_at' => null, 'updated_at' => now()]);
    }

    public function markAllRead(int $userId): void
    {
        NotificationRecipient::query()
            ->where('user_id', $userId)
            ->whereNotNull('in_app_delivered_at')
            ->whereNull('deleted_at')
            ->whereNull('read_at')
            ->update(['read_at' => now(), 'updated_at' => now()]);
    }

    public function deleteForUser(int $recipientId, int $userId): void
    {
        NotificationRecipient::query()
            ->whereKey($recipientId)
            ->where('user_id', $userId)
            ->update(['deleted_at' => now(), 'updated_at' => now()]);
    }

    public function clearForUser(int $userId): void
    {
        NotificationRecipient::query()
            ->where('user_id', $userId)
            ->whereNotNull('in_app_delivered_at')
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now(), 'updated_at' => now()]);
    }

    private function dispatchEmailJobs(Notification $notification): void
    {
        NotificationRecipient::query()
            ->where('notification_id', $notification->id)
            ->where('email_status', 'queued')
            ->pluck('id')
            ->each(function ($recipientId): void {
                $job = new SendNotificationEmail((int) $recipientId);
                if (config('dth-notification-center.email.queue', true)) {
                    dispatch($job);
                } else {
                    app()->call([$job, 'handle']);
                }
            });
    }
}
