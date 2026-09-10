<?php

namespace Dth\Email\Models;

use Dth\Email\Enums\SendingDomainStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SendingDomain extends Model
{
    use SoftDeletes;

    protected $table = 'email_sending_domains';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => SendingDomainStatus::class,
            'verified_at' => 'datetime',
            'last_checked_at' => 'datetime',
        ];
    }


    public function setDomainAttribute(string $value): void
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('#^https?://#', '', $value) ?? $value;
        $value = rtrim($value, '/');

        $this->attributes['domain'] = $value;
    }

    public function sendingAccounts(): HasMany
    {
        return $this->hasMany(SendingAccount::class, 'sending_domain_id');
    }
}
