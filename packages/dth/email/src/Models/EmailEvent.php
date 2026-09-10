<?php

namespace Dth\Email\Models;

use Dth\Email\Enums\EmailEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailEvent extends Model
{
    protected $table = 'email_events';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'event_type' => EmailEventType::class,
            'payload' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(EmailMessage::class, 'message_id');
    }
}
