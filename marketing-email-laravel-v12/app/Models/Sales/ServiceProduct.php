<?php

namespace App\Models\Sales;

use App\Enums\Sales\PackageStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'service_id','product_code','name','description','unit','default_quantity','status','sort_order','created_by','updated_by',
    ];

    protected function casts(): array
    {
        return ['status' => PackageStatus::class, 'default_quantity' => 'integer'];
    }

    public function service(): BelongsTo { return $this->belongsTo(Service::class); }
    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(ServicePackage::class, 'service_package_products')
            ->withPivot('quantity')->withTimestamps();
    }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
}
