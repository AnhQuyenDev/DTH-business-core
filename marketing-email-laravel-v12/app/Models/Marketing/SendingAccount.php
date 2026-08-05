<?php

namespace App\Models\Marketing;

use App\Models\Crm\Department;
use App\Services\Marketing\AuditLogService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SendingAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'provider',
        'from_name',
        'from_email',
        'reply_to',
        'config_encrypted',
        'daily_limit',
        'hourly_limit',
        'status',
        'department_id',
    ];

    protected function casts(): array
    {
        return [
            'config_encrypted' => 'encrypted:array',
            'daily_limit' => 'integer',
            'hourly_limit' => 'integer',
        ];
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    protected static function booted(): void
    {
        static::created(function (self $account): void {
            app(AuditLogService::class)->log('sending_account.created', $account, [], $account->attributesToArray());
        });

        static::updated(function (self $account): void {
            app(AuditLogService::class)->log('sending_account.updated', $account, $account->getOriginal(), $account->getChanges());
        });

        static::deleted(function (self $account): void {
            app(AuditLogService::class)->log('sending_account.deleted', $account, $account->getOriginal(), []);
        });
    }
}
