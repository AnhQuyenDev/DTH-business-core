<?php

namespace Dth\Commercial\Models;

use Dth\Commercial\Enums\AudienceType;
use Dth\Commercial\Enums\BillingPeriodUnit;
use Dth\Commercial\Enums\ServiceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServicePackage extends Model
{
    use SoftDeletes;

    protected $table = 'commercial_service_packages';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'audience_type' => AudienceType::class,
            'billing_period_unit' => BillingPeriodUnit::class,
            'status' => ServiceStatus::class,
            'billing_period' => 'integer',
            'default_quantity' => 'integer',
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function reference(): string
    {
        return (string) $this->package_code;
    }
}
