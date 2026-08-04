<?php

namespace App\Models\Sales;

use App\Enums\Sales\ConfirmationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationConfirmation extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_id',
        'confirmation_type',
        'signer_name',
        'signer_position',
        'signer_email',
        'signer_phone',
        'confirmation_code',
        'otp_verified_at',
        'confirmed_at',
        'ip_address',
        'user_agent',
        'confirmation_data',
    ];

    protected function casts(): array
    {
        return [
            'confirmation_type' => ConfirmationType::class,
            'confirmed_at' => 'datetime',
            'otp_verified_at' => 'datetime',
            'confirmation_data' => 'json',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }
}
