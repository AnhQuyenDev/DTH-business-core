<?php

namespace App\Models\Crm;

use App\Enums\Crm\TaxVerificationStatus;
use App\Models\Marketing\Contact;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessContactProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_id',
        'company_id',
        'company_name',
        'tax_code',
        'company_address',
        'ward',
        'province',
        'legal_representative',
        'contact_position',
        'business_email',
        'business_phone',
        'industry',
        'tax_verification_status',
        'tax_verified_at',
        'tax_verification_provider',
        'tax_verification_data',
        'tax_verification_message',
    ];

    protected function casts(): array
    {
        return [
            'tax_verification_status' => TaxVerificationStatus::class,
            'tax_verified_at' => 'datetime',
            'tax_verification_data' => 'json',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isTaxVerified(): bool
    {
        return $this->tax_verification_status === TaxVerificationStatus::Verified;
    }
}
