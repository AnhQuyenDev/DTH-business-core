<?php

namespace Dth\Commercial\Models;

use Dth\Commercial\Enums\ServiceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Service extends Model
{
    use SoftDeletes;

    protected $table = 'commercial_services';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => ServiceStatus::class,
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $service): void {
            $service->service_code = strtoupper(trim((string) $service->service_code));
            $service->name = trim((string) $service->name);
            if (blank($service->slug) && filled($service->name)) {
                $base = Str::slug($service->name) ?: strtolower($service->service_code);
                $slug = $base;
                $suffix = 2;
                while (static::withTrashed()->where('slug', $slug)->when($service->exists, fn ($q) => $q->where('id', '!=', $service->getKey()))->exists()) {
                    $slug = $base.'-'.$suffix++;
                }
                $service->slug = $slug;
            }
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'service_id')->orderBy('sort_order')->orderBy('name');
    }

    public function bundles(): HasMany
    {
        return $this->hasMany(Bundle::class, 'primary_service_id')->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Legacy relation kept so existing external code does not fatal while the
     * old commercial_service_packages table remains available for rollback.
     */
    public function packages(): HasMany
    {
        return $this->hasMany(ServicePackage::class, 'service_id')->orderBy('sort_order')->orderBy('name');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'service_id');
    }

    public function reference(): string
    {
        return filled($this->slug) ? (string) $this->slug : (string) $this->service_code;
    }
}
