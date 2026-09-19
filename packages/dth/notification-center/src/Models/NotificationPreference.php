<?php

namespace Dth\NotificationCenter\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $table = 'dth_notification_preferences';

    protected $fillable = [
        'user_id', 'in_app_enabled', 'email_enabled', 'email_digest', 'content_preview',
        'quiet_hours_enabled', 'quiet_from', 'quiet_to', 'muted_modules', 'type_preferences',
    ];

    protected function casts(): array
    {
        return [
            'in_app_enabled' => 'boolean',
            'email_enabled' => 'boolean',
            'content_preview' => 'boolean',
            'quiet_hours_enabled' => 'boolean',
            'muted_modules' => 'array',
            'type_preferences' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        $model = (string) config('auth.providers.users.model', \App\Models\User::class);
        return $this->belongsTo($model, 'user_id');
    }
}
