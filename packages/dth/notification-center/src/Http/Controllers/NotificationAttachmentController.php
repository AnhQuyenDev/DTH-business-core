<?php

namespace Dth\NotificationCenter\Http\Controllers;

use Dth\NotificationCenter\Models\Notification;
use Dth\NotificationCenter\Services\NotificationAuthorization;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class NotificationAttachmentController
{
    public function __invoke(string $notification, int $attachment): StreamedResponse|Response
    {
        $record = Notification::query()->where('uuid', $notification)->firstOrFail();
        $userId = (int) auth()->id();

        $isRecipient = $record->recipients()
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->exists();

        $isSender = (int) $record->sender_user_id === $userId;

        abort_unless($isRecipient || $isSender || app(NotificationAuthorization::class)->canViewAudit(), 403);

        $item = data_get((array) $record->attachments, $attachment);
        abort_unless(is_array($item), 404);

        $disk = (string) data_get($item, 'disk', config('dth-notification-center.attachments.disk', 'local'));
        $path = (string) data_get($item, 'path', '');
        abort_unless($path !== '' && Storage::disk($disk)->exists($path), 404);

        return Storage::disk($disk)->download(
            $path,
            (string) data_get($item, 'name', basename($path)),
        );
    }
}
