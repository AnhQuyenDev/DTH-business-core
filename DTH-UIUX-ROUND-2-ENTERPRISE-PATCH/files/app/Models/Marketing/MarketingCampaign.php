<?php

namespace App\Models\Marketing;

use App\Support\Slugs\UniqueSlug;

use App\Models\Sales\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingCampaign extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
        'start_date',
        'end_date',
        'budget',
        'notes',
        'created_by',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $campaign): void {
            if (blank($campaign->slug) && filled($campaign->name)) {
                $campaign->slug = UniqueSlug::make($campaign, $campaign->name);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'budget' => 'decimal:2',
        ];
    }

    public function landingPages(): HasMany
    {
        return $this->hasMany(LandingPage::class, 'marketing_campaign_id');
    }

    /**
     * Phạm vi dịch vụ mà chiến dịch quảng cáo được phép quảng bá.
     *
     * Một Campaign có thể quảng bá nhiều dịch vụ (Hosting + VPS), nhưng mỗi
     * Landing Page thuộc Campaign chỉ chọn một dịch vụ cụ thể trong phạm vi này.
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(
            Service::class,
            'marketing_campaign_service'
        )->withTimestamps();
    }

    public function advertisesService(?int $serviceId): bool
    {
        if ($serviceId === null) {
            return false;
        }

        return $this->services()
            ->whereKey($serviceId)
            ->exists();
    }
}
