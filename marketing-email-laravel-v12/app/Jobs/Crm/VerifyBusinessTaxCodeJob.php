<?php

namespace App\Jobs\Crm;

use App\Models\Crm\BusinessContactProfile;
use App\Services\Crm\TaxCodeVerificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VerifyBusinessTaxCodeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public BusinessContactProfile $profile,
    ) {}

    public function handle(TaxCodeVerificationService $service): void
    {
        $result = $service->verify($this->profile);

        $this->profile->update([
            'tax_verification_status' => $result->status->value,
            'tax_verified_at' => now(),
            'tax_verification_provider' => class_basename(app(\App\Contracts\Tax\TaxCodeVerificationProvider::class)),
            'tax_verification_data' => $result->rawData,
            'tax_verification_message' => $result->message,
        ]);

        if ($result->status->isVerified() && $result->companyName) {
            if (blank($this->profile->company_name)) {
                $this->profile->updateQuietly(['company_name' => $result->companyName]);
            }
        }
    }

    public function failed(\Throwable $e): void
    {
        $this->profile->update([
            'tax_verification_status' => \App\Enums\Crm\TaxVerificationStatus::Error->value,
            'tax_verification_message' => 'Verification job failed: ' . $e->getMessage(),
        ]);
    }
}
