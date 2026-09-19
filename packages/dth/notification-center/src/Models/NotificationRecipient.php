<?php

namespace Dth\NotificationCenter\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationRecipient extends Model
{
    protected $table = 'dth_notification_recipients';

    protected $fillable = [
        'notification_id', 'user_id', 'in_app_delivered_at', 'read_at', 'deleted_at',
        'email_status', 'email_attempts', 'email_queued_at', 'email_sent_at', 'email_failed_at', 'email_last_error',
    ];

    protected function casts(): array
    {
        return [
            'in_app_delivered_at' => 'datetime',
            'read_at' => 'datetime',
            'deleted_at' => 'datetime',
            'email_queued_at' => 'datetime',
            'email_sent_at' => 'datetime',
            'email_failed_at' => 'datetime',
        ];
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class, 'notification_id');
    }

    public function user(): BelongsTo
    {
        $model = (string) config('auth.providers.users.model', \App\Models\User::class);
        return $this->belongsTo($model, 'user_id');
    }
}
