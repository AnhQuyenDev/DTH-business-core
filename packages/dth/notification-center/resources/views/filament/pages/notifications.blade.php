@php
    use Dth\NotificationCenter\Models\Notification;
    use Dth\NotificationCenter\Models\NotificationRecipient;
    use Dth\NotificationCenter\Support\NotificationPresenter;
    use Dth\NotificationCenter\Support\UiText;

    $items = $this->items();
    $counts = $this->counts();
    $mailboxCounts = $this->mailboxCounts();
    $summaryStats = $this->summaryStats();
    $selected = $this->selectedRecipient();
    $selectedNotification = $this->selectedNotification();
    $isInbox = $mailbox === 'inbox';
    $currentUser = auth()->user();
@endphp

<x-filament-panels::page>
    <div class="dth-notify-page dth-notify-page--workspace">
        <section class="dth-notify-summary-grid" aria-label="{{ app()->getLocale() === 'en' ? 'Notification summary' : 'Tổng quan thông báo' }}">
            <button type="button" wire:click="openInboxTab('all')" class="dth-notify-summary-card is-blue {{ $isInbox && $tab === 'all' ? 'is-active' : '' }}">
                <span class="dth-notify-summary-card__icon"><x-filament::icon icon="heroicon-o-inbox" /></span>
                <span class="dth-notify-summary-card__copy">
                    <small>{{ UiText::get('summary.inbox', 'Hộp thư đến') }}</small>
                    <strong>{{ $summaryStats['inbox'] ?? 0 }}</strong>
                    <em>{{ UiText::get('summary.inbox_help', 'Thông báo đã nhận') }}</em>
                </span>
            </button>

            <button type="button" wire:click="openInboxTab('unread')" class="dth-notify-summary-card is-violet {{ $isInbox && $tab === 'unread' ? 'is-active' : '' }}">
                <span class="dth-notify-summary-card__icon"><x-filament::icon icon="heroicon-o-envelope" /></span>
                <span class="dth-notify-summary-card__copy">
                    <small>{{ UiText::get('summary.unread', 'Chưa đọc') }}</small>
                    <strong>{{ $summaryStats['unread'] ?? 0 }}</strong>
                    <em>{{ UiText::get('summary.unread_help', 'Cần xem mới') }}</em>
                </span>
            </button>

            <button type="button" wire:click="openInboxTab('action')" class="dth-notify-summary-card is-amber {{ $isInbox && $tab === 'action' ? 'is-active' : '' }}">
                <span class="dth-notify-summary-card__icon"><x-filament::icon icon="heroicon-o-bolt" /></span>
                <span class="dth-notify-summary-card__copy">
                    <small>{{ UiText::get('summary.action', 'Cần xử lý') }}</small>
                    <strong>{{ $summaryStats['action'] ?? 0 }}</strong>
                    <em>{{ UiText::get('summary.action_help', 'Yêu cầu đang chờ') }}</em>
                </span>
            </button>

            <button type="button" wire:click="openSentToday" class="dth-notify-summary-card is-green {{ ! $isInbox && $period === '1' ? 'is-active' : '' }}">
                <span class="dth-notify-summary-card__icon"><x-filament::icon icon="heroicon-o-paper-airplane" /></span>
                <span class="dth-notify-summary-card__copy">
                    <small>{{ UiText::get('summary.sent_today', 'Đã gửi hôm nay') }}</small>
                    <strong>{{ $summaryStats['sent_today'] ?? 0 }}</strong>
                    <em>{{ UiText::get('summary.sent_today_help', 'Theo tài khoản của bạn') }}</em>
                </span>
            </button>

            <div class="dth-notify-summary-card is-red {{ ($summaryStats['email_failed'] ?? 0) > 0 ? 'has-alert' : '' }}">
                <span class="dth-notify-summary-card__icon"><x-filament::icon icon="heroicon-o-exclamation-triangle" /></span>
                <span class="dth-notify-summary-card__copy">
                    <small>{{ UiText::get('summary.email_failed', 'Email lỗi') }}</small>
                    <strong>{{ $summaryStats['email_failed'] ?? 0 }}</strong>
                    <em>{{ UiText::get('summary.email_failed_help', 'Cần kiểm tra nhật ký') }}</em>
                </span>
            </div>
        </section>

        <section class="dth-notify-workspace-controls">
            <div class="dth-notify-mailbox-switch" role="tablist" aria-label="{{ UiText::get('mailbox.label', 'Hộp thư thông báo') }}">
                <button type="button" wire:click="switchMailbox('inbox')" class="dth-notify-mailbox-tab {{ $isInbox ? 'is-active' : '' }}">
                    <x-filament::icon icon="heroicon-o-inbox" />
                    <span>
                        <strong>{{ UiText::get('mailbox.inbox', 'Hộp thư đến') }}</strong>
                        <small>{{ UiText::get('mailbox.inbox_help', 'Thông báo bạn đã nhận') }}</small>
                    </span>
                    <b>{{ $mailboxCounts['inbox'] ?? 0 }}</b>
                    @if(($mailboxCounts['unread'] ?? 0) > 0)
                        <i>{{ ($mailboxCounts['unread'] ?? 0) > 99 ? '99+' : ($mailboxCounts['unread'] ?? 0) }}</i>
                    @endif
                </button>

                <button type="button" wire:click="switchMailbox('sent')" class="dth-notify-mailbox-tab {{ ! $isInbox ? 'is-active' : '' }}">
                    <x-filament::icon icon="heroicon-o-paper-airplane" />
                    <span>
                        <strong>{{ UiText::get('mailbox.sent', 'Đã gửi') }}</strong>
                        <small>{{ UiText::get('mailbox.sent_help', 'Thông báo do bạn gửi') }}</small>
                    </span>
                    <b>{{ $mailboxCounts['sent'] ?? 0 }}</b>
                </button>
            </div>

            <div class="dth-notify-category-tabs" role="tablist">
                @foreach([
                    'all' => UiText::get('tabs.all', 'Tất cả'),
                    ...($isInbox ? ['unread' => UiText::get('tabs.unread', 'Chưa đọc')] : []),
                    'action' => UiText::get('tabs.action', 'Cần xử lý'),
                    'announcement' => UiText::get('tabs.announcement', 'Thông báo chung'),
                    'system' => UiText::get('tabs.system', 'Hệ thống'),
                ] as $key => $label)
                    <button type="button" wire:click="$set('tab', '{{ $key }}')" class="dth-notify-category-tab {{ $tab === $key ? 'is-active' : '' }}">
                        <span>{{ $label }}</span>
                        <b>{{ $counts[$key] ?? 0 }}</b>
                    </button>
                @endforeach
            </div>

            <div class="dth-notify-filter-toolbar">
                <label class="dth-notify-search">
                    <x-filament::icon icon="heroicon-o-magnifying-glass" />
                    <input
                        type="search"
                        wire:model.live.debounce.350ms="search"
                        placeholder="{{ $isInbox ? UiText::get('fields.search', 'Tìm theo tiêu đề, nội dung hoặc người gửi...') : UiText::get('fields.search_sent', 'Tìm theo tiêu đề, nội dung hoặc người nhận...') }}"
                    >
                </label>

                <select wire:model.live="type" class="dth-notify-select">
                    <option value="">{{ UiText::get('fields.type', 'Loại') }} · {{ UiText::get('tabs.all', 'Tất cả') }}</option>
                    @foreach((array) config('dth-notification-center.types', []) as $key => $label)
                        <option value="{{ $key }}">{{ NotificationPresenter::typeLabel($key) }}</option>
                    @endforeach
                </select>

                <select wire:model.live="module" class="dth-notify-select">
                    <option value="">{{ UiText::get('fields.module', 'Phân hệ') }} · {{ UiText::get('tabs.all', 'Tất cả') }}</option>
                    @foreach($this->moduleOptions() as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>

                <select wire:model.live="period" class="dth-notify-select">
                    <option value="">{{ UiText::get('fields.period', 'Thời gian') }} · {{ UiText::get('tabs.all', 'Tất cả') }}</option>
                    <option value="1">{{ app()->getLocale() === 'en' ? 'Last 24 hours' : '24 giờ qua' }}</option>
                    <option value="7">{{ app()->getLocale() === 'en' ? 'Last 7 days' : '7 ngày qua' }}</option>
                    <option value="30">{{ app()->getLocale() === 'en' ? 'Last 30 days' : '30 ngày qua' }}</option>
                </select>

                <div class="dth-notify-filter-actions">
                    @if($isInbox)
                        <button type="button" wire:click="markAllRead" class="dth-notify-toolbar-button" title="{{ UiText::get('actions.mark_all_read', 'Đánh dấu tất cả đã đọc') }}">
                            <x-filament::icon icon="heroicon-o-check-circle" />
                            <span>{{ UiText::get('actions.mark_all_read', 'Đã đọc tất cả') }}</span>
                        </button>
                    @endif
                    @if($this->hasActiveFilters())
                        <button type="button" wire:click="resetFilters" class="dth-notify-toolbar-button is-quiet" title="{{ UiText::get('actions.reset_filters', 'Đặt lại bộ lọc') }}">
                            <x-filament::icon icon="heroicon-o-arrow-path" />
                            <span>{{ UiText::get('actions.reset_filters', 'Đặt lại') }}</span>
                        </button>
                    @endif
                </div>
            </div>
        </section>

        <div class="dth-notify-master-detail">
            <section class="dth-notify-list-card">
                <div class="dth-notify-list-head">
                    <div class="dth-notify-list-head__title">
                        <span>{{ $isInbox ? UiText::get('mailbox.inbox_upper', 'HỘP THƯ ĐẾN') : UiText::get('mailbox.sent_upper', 'ĐÃ GỬI') }}</span>
                        <strong>{{ $items->count() }} {{ app()->getLocale() === 'en' ? 'items shown' : 'thông báo đang hiển thị' }}</strong>
                    </div>
                    @if($isInbox && $items->isNotEmpty())
                        <button type="button" wire:click="clearAll" wire:confirm="{{ app()->getLocale() === 'en' ? 'Clear all notifications from your inbox?' : 'Xóa toàn bộ thông báo khỏi hộp thư đến của bạn?' }}" class="dth-notify-clear-button">
                            <x-filament::icon icon="heroicon-o-trash" />
                            {{ UiText::get('actions.clear_all', 'Xóa toàn bộ') }}
                        </button>
                    @elseif(! $isInbox)
                        <span class="dth-notify-sent-note">
                            <x-filament::icon icon="heroicon-o-shield-check" />
                            {{ UiText::get('mailbox.sent_history_note', 'Lịch sử gửi chỉ đọc để bảo toàn truy vết.') }}
                        </span>
                    @endif
                </div>

                <div class="dth-notify-list">
                    @forelse($items as $item)
                        @if($isInbox)
                            @php
                                /** @var NotificationRecipient $recipient */
                                $recipient = $item;
                                $notification = $recipient->notification;
                            @endphp
                            @continue(!$notification)
                            <button
                                type="button"
                                wire:key="notification-recipient-{{ $recipient->id }}"
                                wire:click="selectRecipient({{ $recipient->id }})"
                                class="dth-notify-row {{ $recipient->read_at ? '' : 'is-unread' }} {{ $selectedRecipientId === $recipient->id ? 'is-selected' : '' }}"
                            >
                                <span class="dth-notify-icon dth-notify-icon--{{ NotificationPresenter::typeTone($notification->type) }}">
                                    <x-filament::icon :icon="NotificationPresenter::icon($notification->type)" />
                                </span>

                                <span class="dth-notify-row__main">
                                    <span class="dth-notify-row__top">
                                        <span class="dth-notify-row__headline">
                                            <strong>{{ $notification->title }}</strong>
                                            @unless($recipient->read_at)<i>{{ UiText::get('status.new', 'Mới') }}</i>@endunless
                                        </span>
                                        <time>{{ optional($notification->sent_at ?? $notification->created_at)->diffForHumans() }}</time>
                                    </span>

                                    <span class="dth-notify-row__body">{{ \Illuminate\Support\Str::limit($notification->body, 125) }}</span>

                                    <span class="dth-notify-row__route-line">
                                        <span><small>{{ UiText::get('fields.from', 'Từ') }}</small><b>{{ $notification->sender_name ?: UiText::get('mailbox.system_sender', 'Hệ thống') }}</b></span>
                                        <x-filament::icon icon="heroicon-o-arrow-long-right" />
                                        <span><small>{{ UiText::get('fields.to', 'Đến') }}</small><b>{{ UiText::get('mailbox.you', 'Bạn') }}</b></span>
                                    </span>

                                    <span class="dth-notify-row__meta">
                                        <span class="dth-notify-module-chip">{{ NotificationPresenter::moduleLabel($notification->source_module) }}</span>
                                        <span class="dth-notify-pill dth-notify-pill--{{ NotificationPresenter::typeTone($notification->type) }}">{{ NotificationPresenter::typeLabel($notification->type) }}</span>
                                        @foreach((array) $notification->channels as $channel)
                                            <span class="dth-notify-mini-channel">{{ NotificationPresenter::channelLabel($channel) }}</span>
                                        @endforeach
                                    </span>
                                </span>

                                <span class="dth-notify-row__end">
                                    @unless($recipient->read_at)<span class="dth-notify-unread-dot"></span>@endunless
                                    <x-filament::icon icon="heroicon-o-chevron-right" />
                                </span>
                            </button>
                        @else
                            @php
                                /** @var Notification $notification */
                                $notification = $item;
                                $delivery = $this->deliveryStats($notification);
                            @endphp
                            <button
                                type="button"
                                wire:key="sent-notification-{{ $notification->id }}"
                                wire:click="selectSentNotification({{ $notification->id }})"
                                class="dth-notify-row is-sent {{ $selectedNotificationId === $notification->id ? 'is-selected' : '' }}"
                            >
                                <span class="dth-notify-icon dth-notify-icon--{{ NotificationPresenter::typeTone($notification->type) }}">
                                    <x-filament::icon :icon="NotificationPresenter::icon($notification->type)" />
                                </span>

                                <span class="dth-notify-row__main">
                                    <span class="dth-notify-row__top">
                                        <span class="dth-notify-row__headline"><strong>{{ $notification->title }}</strong></span>
                                        <time>{{ optional($notification->sent_at ?? $notification->created_at)->diffForHumans() }}</time>
                                    </span>

                                    <span class="dth-notify-row__body">{{ \Illuminate\Support\Str::limit($notification->body, 125) }}</span>

                                    <span class="dth-notify-row__route-line">
                                        <span><small>{{ UiText::get('fields.from', 'Từ') }}</small><b>{{ UiText::get('mailbox.you', 'Bạn') }}</b></span>
                                        <x-filament::icon icon="heroicon-o-arrow-long-right" />
                                        <span><small>{{ UiText::get('fields.to', 'Đến') }}</small><b>{{ $this->recipientSummary($notification) }}</b></span>
                                    </span>

                                    <span class="dth-notify-row__meta">
                                        <span class="dth-notify-module-chip">{{ $this->audienceLabel($notification) }}</span>
                                        <span class="dth-notify-mini-stat"><b>{{ $delivery['total'] }}</b> {{ UiText::get('delivery.targeted', 'người nhận') }}</span>
                                        @foreach((array) $notification->channels as $channel)
                                            <span class="dth-notify-mini-channel">{{ NotificationPresenter::channelLabel($channel) }}</span>
                                        @endforeach
                                    </span>
                                </span>

                                <span class="dth-notify-row__end is-sent">
                                    <x-filament::icon icon="heroicon-o-paper-airplane" />
                                    <x-filament::icon icon="heroicon-o-chevron-right" />
                                </span>
                            </button>
                        @endif
                    @empty
                        <div class="dth-notify-empty-state">
                            <span class="dth-notify-empty-state__icon"><x-filament::icon :icon="$isInbox ? 'heroicon-o-bell-slash' : 'heroicon-o-paper-airplane'" /></span>
                            <strong>{{ $isInbox ? UiText::get('empty.title', 'Chưa có thông báo') : UiText::get('empty.sent_title', 'Bạn chưa gửi thông báo nào') }}</strong>
                            <span>{{ $isInbox ? UiText::get('empty.body', 'Thông báo nghiệp vụ và hệ thống sẽ xuất hiện tại đây.') : UiText::get('empty.sent_body', 'Thông báo bạn gửi thủ công hoặc phát sinh từ nghiệp vụ sẽ được lưu tại đây.') }}</span>
                        </div>
                    @endforelse
                </div>
            </section>

            <aside class="dth-notify-detail-card">
                @if($selectedNotification)
                    <div class="dth-notify-detail-head">
                        <span class="dth-notify-icon dth-notify-icon--{{ NotificationPresenter::typeTone($selectedNotification->type) }} dth-notify-icon--large">
                            <x-filament::icon :icon="NotificationPresenter::icon($selectedNotification->type)" />
                        </span>
                        <div class="dth-notify-detail-head__copy">
                            <span class="dth-notify-detail-head__badges">
                                <span class="dth-notify-pill dth-notify-pill--{{ NotificationPresenter::typeTone($selectedNotification->type) }}">{{ NotificationPresenter::typeLabel($selectedNotification->type) }}</span>
                                <span class="dth-notify-priority dth-notify-priority--{{ $selectedNotification->priority }}">{{ NotificationPresenter::priorityLabel($selectedNotification->priority) }}</span>
                                @if($isInbox && $selected && ! $selected->read_at)
                                    <span class="dth-notify-status-badge is-unread">{{ UiText::get('status.unread', 'Chưa đọc') }}</span>
                                @elseif($isInbox)
                                    <span class="dth-notify-status-badge is-read">{{ UiText::get('status.read', 'Đã đọc') }}</span>
                                @endif
                            </span>
                            <h2>{{ $selectedNotification->title }}</h2>
                            <p>{{ NotificationPresenter::moduleLabel($selectedNotification->source_module) }} · {{ optional($selectedNotification->sent_at ?? $selectedNotification->created_at)->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>

                    <div class="dth-notify-message-route">
                        <div class="dth-notify-message-party">
                            <span class="dth-notify-message-party__avatar"><x-filament::icon :icon="$isInbox ? 'heroicon-o-user' : 'heroicon-o-user-circle'" /></span>
                            <span>
                                <small>{{ UiText::get('fields.from', 'Từ') }}</small>
                                <strong>{{ $isInbox ? ($selectedNotification->sender_name ?: UiText::get('mailbox.system_sender', 'Hệ thống')) : (UiText::get('mailbox.you', 'Bạn').' · '.($currentUser?->name ?: $selectedNotification->sender_name)) }}</strong>
                                @if($isInbox && $selectedNotification->sender?->email)
                                    <em>{{ $selectedNotification->sender->email }}</em>
                                @elseif(! $isInbox && $currentUser?->email)
                                    <em>{{ $currentUser->email }}</em>
                                @endif
                            </span>
                        </div>

                        <span class="dth-notify-message-route__arrow"><x-filament::icon icon="heroicon-o-arrow-long-right" /></span>

                        <div class="dth-notify-message-party">
                            <span class="dth-notify-message-party__avatar is-target"><x-filament::icon icon="heroicon-o-users" /></span>
                            <span>
                                <small>{{ UiText::get('fields.to', 'Đến') }}</small>
                                @if($isInbox)
                                    <strong>{{ UiText::get('mailbox.you', 'Bạn') }} · {{ $currentUser?->name }}</strong>
                                    <em>{{ $currentUser?->email }}</em>
                                @else
                                    <strong>{{ $this->audienceLabel($selectedNotification) }}</strong>
                                    <em>{{ $this->recipientSummary($selectedNotification, 3) }}</em>
                                @endif
                            </span>
                        </div>
                    </div>

                    <div class="dth-notify-detail-facts">
                        <div>
                            <span><x-filament::icon icon="heroicon-o-clock" /></span>
                            <small>{{ UiText::get('fields.sent_at', 'Thời gian') }}</small>
                            <strong>{{ optional($selectedNotification->sent_at ?? $selectedNotification->created_at)->format('d/m/Y H:i') }}</strong>
                        </div>
                        <div>
                            <span><x-filament::icon icon="heroicon-o-squares-2x2" /></span>
                            <small>{{ UiText::get('fields.module', 'Phân hệ') }}</small>
                            <strong>{{ NotificationPresenter::moduleLabel($selectedNotification->source_module) }}</strong>
                        </div>
                        <div>
                            <span><x-filament::icon icon="heroicon-o-signal" /></span>
                            <small>{{ UiText::get('fields.delivery_channels', 'Kênh') }}</small>
                            <strong>{{ collect((array) $selectedNotification->channels)->map(fn ($channel) => NotificationPresenter::channelLabel($channel))->implode(' + ') }}</strong>
                        </div>
                    </div>

                    @unless($isInbox)
                        @php($delivery = $this->deliveryStats($selectedNotification))
                        <div class="dth-notify-delivery-strip">
                            <span><small>{{ UiText::get('delivery.targeted', 'Người nhận') }}</small><strong>{{ $delivery['total'] }}</strong></span>
                            <span><small>In-app</small><strong>{{ $delivery['in_app'] }}</strong></span>
                            <span><small>{{ UiText::get('delivery.email_sent', 'Email đã gửi') }}</small><strong>{{ $delivery['email_sent'] }}</strong></span>
                            @if($delivery['email_pending'] > 0)
                                <span class="is-pending"><small>{{ UiText::get('delivery.email_pending', 'Đang chờ') }}</small><strong>{{ $delivery['email_pending'] }}</strong></span>
                            @endif
                            @if($delivery['email_failed'] > 0)
                                <span class="is-failed"><small>{{ UiText::get('delivery.email_failed', 'Email lỗi') }}</small><strong>{{ $delivery['email_failed'] }}</strong></span>
                            @endif
                        </div>

                        <div class="dth-notify-recipient-preview">
                            <div class="dth-notify-section-label">
                                <span>{{ UiText::get('fields.actual_recipients', 'Người nhận thực tế') }}</span>
                                <small>{{ $delivery['total'] }} {{ app()->getLocale() === 'en' ? 'recipients' : 'người nhận' }}</small>
                            </div>
                            <div class="dth-notify-recipient-chips">
                                @foreach($this->recipientPreview($selectedNotification) as $previewRecipient)
                                    <span class="dth-notify-recipient-chip">
                                        <x-filament::icon icon="heroicon-o-user" />
                                        <span>
                                            <strong>{{ $previewRecipient->user?->name ?: '#'.$previewRecipient->user_id }}</strong>
                                            @if($previewRecipient->user?->email)<small>{{ $previewRecipient->user->email }}</small>@endif
                                        </span>
                                    </span>
                                @endforeach
                                @if($delivery['total'] > 8)
                                    <span class="dth-notify-recipient-chip is-more">+{{ $delivery['total'] - 8 }}</span>
                                @endif
                            </div>
                        </div>
                    @endunless

                    <div class="dth-notify-content-card">
                        <div class="dth-notify-section-label">
                            <span>{{ UiText::get('detail.content', 'Nội dung') }}</span>
                            <small>{{ $isInbox ? UiText::get('detail.received_message', 'Thông tin bạn nhận được') : UiText::get('detail.sent_message', 'Nội dung đã gửi') }}</small>
                        </div>
                        <p>{{ $selectedNotification->body }}</p>
                        @if($selectedNotification->detail_body)
                            <div>{!! nl2br(e($selectedNotification->detail_body)) !!}</div>
                        @endif
                    </div>

                    @if(! empty($selectedNotification->attachments))
                        <div class="dth-notify-attachments">
                            <div class="dth-notify-section-label">
                                <span>{{ UiText::get('fields.attachments', 'Tệp đính kèm') }}</span>
                                <small>{{ count((array) $selectedNotification->attachments) }} {{ app()->getLocale() === 'en' ? 'files' : 'tệp' }}</small>
                            </div>
                            <div class="dth-notify-attachments__list">
                                @foreach((array) $selectedNotification->attachments as $attachmentIndex => $attachment)
                                    <a href="{{ route('dth.notifications.attachments.download', ['notification' => $selectedNotification->uuid, 'attachment' => $attachmentIndex]) }}" class="dth-notify-attachment">
                                        <span class="dth-notify-attachment__icon"><x-filament::icon icon="heroicon-o-document" /></span>
                                        <span>{{ data_get($attachment, 'name', 'Tệp đính kèm') }}</span>
                                        <x-filament::icon icon="heroicon-o-arrow-down-tray" />
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="dth-notify-detail-actions">
                        @if($selectedNotification->action_url)
                            <a href="{{ $selectedNotification->action_url }}" class="dth-notify-primary-link">
                                {{ $selectedNotification->action_label ?: UiText::get('actions.open', 'Mở chi tiết') }}
                                <x-filament::icon icon="heroicon-o-arrow-up-right" />
                            </a>
                        @endif

                        @if($isInbox && $selected)
                            <button type="button" wire:click="toggleRead({{ $selected->id }})" class="dth-notify-secondary-button">
                                <x-filament::icon :icon="$selected->read_at ? 'heroicon-o-envelope' : 'heroicon-o-check'" />
                                {{ $selected->read_at ? UiText::get('actions.mark_unread', 'Đánh dấu chưa đọc') : UiText::get('actions.mark_read', 'Đánh dấu đã đọc') }}
                            </button>
                            <button type="button" wire:click="deleteRecipient({{ $selected->id }})" wire:confirm="{{ app()->getLocale() === 'en' ? 'Delete this notification from your inbox?' : 'Xóa thông báo này khỏi hộp thư đến của bạn?' }}" class="dth-notify-danger-button">
                                <x-filament::icon icon="heroicon-o-trash" />
                                {{ UiText::get('actions.delete', 'Xóa') }}
                            </button>
                        @else
                            <span class="dth-notify-history-lock">
                                <x-filament::icon icon="heroicon-o-lock-closed" />
                                {{ UiText::get('mailbox.sent_readonly', 'Bản gửi được giữ lại để truy vết lịch sử.') }}
                            </span>
                        @endif
                    </div>
                @else
                    <div class="dth-notify-empty-state dth-notify-empty-state--detail">
                        <span class="dth-notify-empty-state__icon"><x-filament::icon :icon="$isInbox ? 'heroicon-o-inbox' : 'heroicon-o-paper-airplane'" /></span>
                        <strong>{{ $isInbox ? UiText::get('empty.detail', 'Chọn một thông báo để xem nội dung chi tiết.') : UiText::get('empty.sent_detail', 'Chọn một thông báo đã gửi để xem người nhận và trạng thái phân phối.') }}</strong>
                    </div>
                @endif
            </aside>
        </div>
    </div>
</x-filament-panels::page>
