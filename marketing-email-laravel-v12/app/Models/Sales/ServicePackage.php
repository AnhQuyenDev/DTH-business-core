<?php

namespace App\Models\Sales;

use App\Enums\Sales\AudienceType;
use App\Enums\Sales\BillingPeriodUnit;
use App\Enums\Sales\PackageStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServicePackage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'service_id',
        'package_code',
        'name',
        'description',
        'audience_type',
        'billing_period',
        'billing_period_unit',
        'unit',
        'default_quantity',
        'status',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'audience_type' => AudienceType::class,
            'billing_period_unit' => BillingPeriodUnit::class,
            'status' => PackageStatus::class,
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
