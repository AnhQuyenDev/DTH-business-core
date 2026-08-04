<?php

namespace App\Models\Sales;

use App\Enums\Sales\AudienceType;
use App\Enums\Sales\PriceBookStatus;
use App\Enums\Sales\TaxMode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PriceBook extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'price_book_code',
        'name',
        'description',
        'audience_type',
        'currency',
        'tax_mode',
        'valid_from',
        'valid_until',
        'status',
        'is_default',
        'created_by',
        'updated_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'audience_type' => AudienceType::class,
            'tax_mode' => TaxMode::class,
            'status' => PriceBookStatus::class,
            'valid_from' => 'date',
            'valid_until' => 'date',
            'approved_at' => 'datetime',
            'is_default' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PriceBookItem::class);
    }

    public function accessRules(): HasMany
    {
        return $this->hasMany(PriceBookAccessRule::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
