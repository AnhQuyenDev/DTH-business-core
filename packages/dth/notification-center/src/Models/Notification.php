<?php

namespace Dth\NotificationCenter\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Notification extends Model
{
    protected $table = 'dth_notifications';

    protected $fillable = [
        'uuid', 'type', 'priority', 'source_module', 'source_event', 'source_type', 'source_id',
        'sender_user_id', 'sender_name', 'title', 'body', 'detail_body', 'action_label', 'action_url',
        'channels', 'attachments', 'is_mandatory', 'is_manual', 'metadata', 'sent_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'attachments' => 'array',
            'metadata' => 'array',
            'is_mandatory' => 'boolean',
            'is_manual' => 'boolean',
            'sent_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(NotificationRecipient::class, 'notification_id');
    }

    public function sender(): BelongsTo
    {
        $model = (string) config('auth.providers.users.model', \App\Models\User::class);
        return $this->belongsTo($model, 'sender_user_id');
    }
}
