<?php

namespace App\Models\Marketing;

use App\Models\Crm\Customer;
use App\Models\User;
use App\Services\Marketing\AuditLogService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuppressionEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'reason',
        'source',
        'campaign_id',
        'contact_id',
        'customer_id',
        'note',
        'created_by',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    protected static function booted(): void
    {
        static::created(function (self $entry): void {
            app(AuditLogService::class)->log('suppression.created', $entry, [], $entry->attributesToArray());
        });
    }
}
