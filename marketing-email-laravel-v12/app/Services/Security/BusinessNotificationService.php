<?php

namespace App\Services\Security;

use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class BusinessNotificationService
{
    public function notifyPermission(string $permission, string $title, ?string $body = null, ?string $url = null, string $icon = 'heroicon-o-bell'): void
    {
        if (! Schema::hasTable('notifications')) return;

        $users = User::query()->where('is_active', true)->get()
            ->filter(fn (User $user): bool => $user->can($permission));

        $this->send($users, $title, $body, $url, $icon);
    }

    /** @param iterable<User>|User|null $recipients */
    public function send(iterable|User|null $recipients, string $title, ?string $body = null, ?string $url = null, string $icon = 'heroicon-o-bell'): void
    {
        if (! Schema::hasTable('notifications') || $recipients === null) return;
        $users = $recipients instanceof User ? collect([$recipients]) : collect($recipients);
        $users = $users->filter(fn ($user): bool => $user instanceof User && $user->is_active)->unique('id')->values();
        if ($users->isEmpty()) return;

        $notification = Notification::make()->title($title)->icon($icon)->body($body ?: null);
        if ($url) {
            $notification->actions([Action::make('open')->label(__('action.open'))->url($url)->markAsRead()]);
        }
        $notification->sendToDatabase($users);
    }
}
