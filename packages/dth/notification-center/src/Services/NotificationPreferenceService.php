<?php

namespace Dth\NotificationCenter\Services;

use Dth\NotificationCenter\Models\Notification;
use Dth\NotificationCenter\Models\NotificationPreference;
use Illuminate\Support\Facades\Schema;

final class NotificationPreferenceService
{
    public function forUser(int $userId): NotificationPreference
    {
        return NotificationPreference::query()->firstOrCreate(
            ['user_id' => $userId],
            [
                'in_app_enabled' => true,
                'email_enabled' => true,
                'email_digest' => 'immediate',
                'content_preview' => true,
                'quiet_hours_enabled' => false,
                'muted_modules' => [],
                'type_preferences' => [],
            ],
        );
    }

    /** @return array<string, mixed> */
    public function valuesForUser(int $userId): array
    {
        if (! Schema::hasTable('dth_notification_preferences')) {
            return [
                'in_app_enabled' => true,
                'email_enabled' => true,
                'content_preview' => true,
                'muted_modules' => [],
            ];
        }

        $preference = $this->forUser($userId);

        return [
            'in_app_enabled' => (bool) $preference->in_app_enabled,
            'email_enabled' => (bool) $preference->email_enabled,
            'content_preview' => (bool) $preference->content_preview,
            'muted_modules' => (array) ($preference->muted_modules ?? []),
        ];
    }

    /** @param array<string, mixed> $data */
    public function updateUser(int $userId, array $data): NotificationPreference
    {
        $preference = $this->forUser($userId);
        $preference->fill([
            'in_app_enabled' => (bool) ($data['in_app_enabled'] ?? false),
            'email_enabled' => (bool) ($data['email_enabled'] ?? false),
            'content_preview' => (bool) ($data['content_preview'] ?? false),
            'muted_modules' => array_values(array_unique(array_filter((array) ($data['muted_modules'] ?? [])))),
        ])->save();

        return $preference;
    }

    public function allowsChannel(int $userId, string $channel, Notification $notification): bool
    {
        if ($notification->is_mandatory) {
            return true;
        }

        if (! Schema::hasTable('dth_notification_preferences')) {
            return true;
        }

        $preference = $this->forUser($userId);
        $mutedModules = (array) ($preference->muted_modules ?? []);
        if ($notification->source_module && in_array($notification->source_module, $mutedModules, true)) {
            return false;
        }

        $typePreference = (array) data_get($preference->type_preferences ?? [], $notification->type, []);
        if (array_key_exists($channel, $typePreference)) {
            return (bool) $typePreference[$channel];
        }

        return match ($channel) {
            'email' => (bool) $preference->email_enabled,
            default => (bool) $preference->in_app_enabled,
        };
    }

    public function contentPreviewEnabled(int $userId): bool
    {
        if (! Schema::hasTable('dth_notification_preferences')) {
            return true;
        }

        return (bool) $this->forUser($userId)->content_preview;
    }
}
