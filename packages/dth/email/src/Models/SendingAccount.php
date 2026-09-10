<?php

namespace Dth\Email\Models;

use Dth\Email\Enums\SendingAccountStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SendingAccount extends Model
{
    use SoftDeletes;

    protected $table = 'email_sending_accounts';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => SendingAccountStatus::class,
            'encrypted_config' => 'encrypted:array',
            'last_tested_at' => 'datetime',
        ];
    }


    public function setFromEmailAttribute(string $value): void
    {
        $this->attributes['from_email'] = mb_strtolower(trim($value));
    }

    public function setReplyToAttribute(?string $value): void
    {
        $value = $value === null ? null : mb_strtolower(trim($value));
        $this->attributes['reply_to'] = $value === '' ? null : $value;
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(SendingDomain::class, 'sending_domain_id');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(EmailCampaign::class, 'sending_account_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(EmailMessage::class, 'sending_account_id');
    }
}
