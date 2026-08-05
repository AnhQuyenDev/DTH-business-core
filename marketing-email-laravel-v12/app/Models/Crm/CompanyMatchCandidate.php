<?php

namespace App\Models\Crm;

use App\Models\Marketing\Contact;
use App\Models\Marketing\LandingPageSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyMatchCandidate extends Model
{
    protected $fillable = [
        'submission_id',
        'contact_id',
        'suggested_company_id',
        'confidence_score',
        'matched_by',
        'status',
        'evidence',
        'reviewed_by_user_id',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'confidence_score' => 'integer',
            'evidence' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(LandingPageSubmission::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function suggestedCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'suggested_company_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
