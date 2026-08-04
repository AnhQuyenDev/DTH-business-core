<?php

namespace App\Models\Sales;

use App\Models\Crm\Staff;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceBookAccessRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'price_book_id',
        'access_type',
        'role',
        'department',
        'staff_id',
        'branch_id',
        'can_view',
        'can_create_quotation',
        'discount_limit_type',
        'discount_limit_value',
    ];

    protected function casts(): array
    {
        return [
            'can_view' => 'boolean',
            'can_create_quotation' => 'boolean',
            'discount_limit_value' => 'decimal:2',
        ];
    }

    public function priceBook(): BelongsTo
    {
        return $this->belongsTo(PriceBook::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
