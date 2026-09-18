<?php
namespace Dth\AccountManagement\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $url, private readonly string $expiresAt) {}

    public function via(object $notifiable): array { return ['mail']; }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Lời mời truy cập DTH Business Core')
            ->greeting('Bạn được mời tham gia DTH Business Core')
            ->line('Một quản trị viên đã tạo tài khoản truy cập cho bạn.')
            ->action('Kích hoạt tài khoản', $this->url)
            ->line('Liên kết hết hạn lúc '.$this->expiresAt.'.')
            ->line('Nếu bạn không mong đợi lời mời này, hãy bỏ qua email.');
    }
}
