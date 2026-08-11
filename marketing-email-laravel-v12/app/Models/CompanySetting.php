<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $fillable = [
        'company_name',
        'tax_code',
        'address',
        'phone',
        'email',
        'website',
        'logo_path',
        'vietqr_client_id',
        'vietqr_api_key',
        'quotation_approval_mode',
        'quotation_approval_amount_threshold',
        'quotation_approval_discount_threshold_percent',
        'quotation_confirmation_mode',
        'payment_evidence_required',
        'support_tickets_enabled',
    ];

    protected function casts(): array
    {
        return [
            'vietqr_api_key' => 'encrypted',
            'quotation_approval_amount_threshold' => 'decimal:2',
            'quotation_approval_discount_threshold_percent' => 'decimal:4',
            'payment_evidence_required' => 'boolean',
            'support_tickets_enabled' => 'boolean',
        ];
    }

    public static function firstOrCreateDefault(): self
    {
        return static::query()->firstOrCreate(['id' => 1], [
            'company_name' => env('APP_NAME', 'Laravel'),
            'quotation_approval_mode' => config('v1_workflow.quotation_approval.mode', 'amount_or_discount'),
            'quotation_approval_amount_threshold' => config('v1_workflow.quotation_approval.amount_threshold', 20000000),
            'quotation_approval_discount_threshold_percent' => config('v1_workflow.quotation_approval.discount_threshold_percent', 10),
            'quotation_confirmation_mode' => config('v1_workflow.customer_confirmation.mode', 'click'),
            'payment_evidence_required' => (bool) config('v1_workflow.payment_notice.evidence_required', false),
            'support_tickets_enabled' => true,
        ]);
    }
}
