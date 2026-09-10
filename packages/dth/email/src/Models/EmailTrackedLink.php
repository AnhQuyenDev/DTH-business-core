<?php

namespace Dth\Email\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTrackedLink extends Model
{
    protected $table = 'email_tracked_links';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['last_clicked_at' => 'datetime'];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(EmailMessage::class, 'message_id');
    }
}
