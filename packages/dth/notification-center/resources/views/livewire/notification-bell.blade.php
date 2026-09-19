@php
    use Dth\NotificationCenter\Support\NotificationPresenter;
@endphp
<div
    class="dth-notify-bell"
    x-data="{ open: false }"
    wire:poll.{{ $pollSeconds }}s
    @keydown.escape.window="open = false"
>
    <button
        type="button"
        class="dth-notify-bell__trigger"
        aria-label="{{ \Dth\NotificationCenter\Support\UiText::get('navigation.notifications', 'Thông báo') }}"
        @click="open = !open"
    >
        <x-filament::icon icon="heroicon-o-bell" />
        @if($unreadCount > 0)
            <span class="dth-notify-bell__badge">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
        @endif
    </button>

    <div
        class="dth-notify-bell__panel"
        x-cloak
        x-show="open"
        x-transition.opacity.scale.origin.top.right
        @click.outside="open = false"
    >
        <div class="dth-notify-bell__header">
            <div>
                <strong>{{ \Dth\NotificationCenter\Support\UiText::get('navigation.notifications', 'Thông báo') }}</strong>
                <span>{{ $unreadCount }} {{ app()->getLocale() === 'en' ? 'unread' : 'chưa đọc' }}</span>
            </div>
            <button type="button" wire:click="markAllRead" @click.stop class="dth-notify-link-button">
                {{ \Dth\NotificationCenter\Support\UiText::get('actions.mark_all_read', 'Đánh dấu tất cả đã đọc') }}
            </button>
        </div>

        <div class="dth-notify-bell__list">
            @forelse($items as $recipient)
                @php($notification = $recipient->notification)
                @continue(!$notification)
                <div class="dth-notify-bell__item {{ $recipient->read_at ? '' : 'is-unread' }}">
                    <a
                        href="{{ $centerUrl }}?notification={{ $notification->id }}"
                        class="dth-notify-bell__item-link"
                        wire:click="markRead({{ $recipient->id }})"
                        @click="open = false"
                    >
                        <span class="dth-notify-icon dth-notify-icon--{{ NotificationPresenter::typeTone($notification->type) }}">
                            <x-filament::icon :icon="NotificationPresenter::icon($notification->type)" />
                        </span>
                        <span class="dth-notify-bell__copy">
                            <span class="dth-notify-bell__title">{{ $notification->title }}</span>
                            <span class="dth-notify-bell__body">
                                {{ $previewContent ? \Illuminate\Support\Str::limit($notification->body, 105) : (app()->getLocale() === 'en' ? 'You have a new notification.' : 'Bạn có một thông báo mới.') }}
                            </span>
                            <span class="dth-notify-bell__meta">
                                {{ $notification->sender_name ?: (app()->getLocale() === 'en' ? 'System' : 'Hệ thống') }}
                                · {{ NotificationPresenter::moduleLabel($notification->source_module) }}
                                · {{ optional($notification->sent_at ?? $notification->created_at)->diffForHumans() }}
                            </span>
                            <span class="dth-notify-bell__channels">
                                @foreach((array) $notification->channels as $channel)
                                    <i>{{ NotificationPresenter::channelLabel($channel) }}</i>
                                @endforeach
                            </span>
                        </span>
                        @unless($recipient->read_at)
                            <span class="dth-notify-unread-dot"></span>
                        @endunless
                    </a>
                    <button
                        type="button"
                        class="dth-notify-bell__delete"
                        wire:click="deleteRecipient({{ $recipient->id }})"
                        wire:confirm="{{ app()->getLocale() === 'en' ? 'Delete this notification?' : 'Xóa thông báo này?' }}"
                        @click.stop
                        aria-label="{{ \Dth\NotificationCenter\Support\UiText::get('actions.delete', 'Xóa') }}"
                    >
                        <x-filament::icon icon="heroicon-o-trash" />
                    </button>
                </div>
            @empty
                <div class="dth-notify-bell__empty">
                    <x-filament::icon icon="heroicon-o-bell-slash" />
                    <strong>{{ \Dth\NotificationCenter\Support\UiText::get('empty.title', 'Chưa có thông báo') }}</strong>
                    <span>{{ \Dth\NotificationCenter\Support\UiText::get('empty.body', 'Thông báo nghiệp vụ và hệ thống sẽ xuất hiện tại đây.') }}</span>
                </div>
            @endforelse
        </div>

        <div class="dth-notify-bell__footer">
            <a href="{{ $centerUrl }}" @click="open = false">
                {{ \Dth\NotificationCenter\Support\UiText::get('actions.view_all', 'Xem tất cả') }}
                <x-filament::icon icon="heroicon-o-arrow-right" />
            </a>
            @if($items->isNotEmpty())
                <button
                    type="button"
                    class="dth-notify-clear-button"
                    wire:click="clearAll"
                    wire:confirm="{{ app()->getLocale() === 'en' ? 'Clear all notifications from your inbox?' : 'Xóa toàn bộ thông báo khỏi hộp thư của bạn?' }}"
                    @click.stop
                >
                    {{ \Dth\NotificationCenter\Support\UiText::get('actions.clear_all', 'Xóa toàn bộ') }}
                </button>
            @endif
        </div>
    </div>
</div>
