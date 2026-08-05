<?php

namespace App\Models\Crm;

use App\Enums\Crm\CompanyContactDecisionRole;
use App\Models\Marketing\Contact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyContact extends Model
{
    protected $fillable = [
        'company_id',
        'contact_id',
        'job_title',
        'department',
        'decision_role',
        'is_primary',
        'is_active',
        'joined_at',
        'left_at',
    ];

    protected function casts(): array
    {
        return [
            'decision_role' => CompanyContactDecisionRole::class,
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
            'joined_at' => 'date',
            'left_at' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
