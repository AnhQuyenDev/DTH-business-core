<?php

namespace Dth\Email\Models;

use Dth\Email\Enums\SuppressionReason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailSuppression extends Model
{
    protected $table = 'email_suppressions';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'reason' => SuppressionReason::class,
            'released_at' => 'datetime',
        ];
    }

    public function setEmailAttribute(string $value): void
    {
        $this->attributes['email'] = mb_strtolower(trim($value));
    }

    public function getSuppressionStatusAttribute(): string
    {
        return $this->released_at === null ? 'active' : 'released';
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(EmailMessage::class, 'message_id');
    }
}
