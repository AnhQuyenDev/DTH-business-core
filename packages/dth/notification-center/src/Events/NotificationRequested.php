<?php

namespace Dth\NotificationCenter\Events;

final class NotificationRequested
{
    /**
     * @param array<string, mixed> $message
     * @param array<int, string> $channels
     * @param array<int> $recipientUserIds
     * @param array<string, mixed> $targets
     */
    public function __construct(
        public readonly array $message,
        public readonly array $channels = ['in_app'],
        public readonly array $recipientUserIds = [],
        public readonly array $targets = [],
        public readonly ?string $permission = null,
        public readonly bool $allowBroadcast = false,
    ) {}
}
