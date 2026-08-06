<?php

namespace App\Services\Sales;

use App\Enums\Sales\ConfirmationType;
use App\Enums\Sales\QuotationStatus;
use App\Models\Sales\Quotation;
use App\Services\Marketing\AuditLogService;
use Illuminate\Support\Facades\DB;

class QuotationConfirmationService
{
    public function __construct(
        private readonly QuotationStateMachine $stateMachine,
        private readonly AuditLogService $auditLog,
        private readonly QuotationInteractionService $interactions,
        private readonly QuotationOpportunitySyncService $opportunitySync,
    ) {}

    public function accept(Quotation $quotation, array $data, ?string $otpVerifiedEmail = null): Quotation
    {
        $this->validateConfirmation($quotation);

        return DB::transaction(function () use ($quotation, $data, $otpVerifiedEmail) {
            $this->stateMachine->validateTransition($quotation->status, QuotationStatus::Accepted);

            $quotation->confirmations()->create([
                'confirmation_type' => ConfirmationType::Accepted,
                'signer_name' => $data['signer_name'],
                'signer_position' => $data['signer_position'] ?? null,
                'signer_email' => $data['signer_email'],
                'signer_phone' => $data['signer_phone'] ?? null,
                'confirmation_code' => strtoupper(bin2hex(random_bytes(8))),
                'otp_verified_at' => $otpVerifiedEmail ? now() : null,
                'confirmed_at' => now(),
                'ip_address' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 1000),
                'confirmation_data' => json_encode($data),
            ]);

            $quotation->update([
                'status' => QuotationStatus::Accepted,
                'accepted_at' => now(),
                'payment_status' => 'unpaid',
            ]);

            $this->interactions->logAccepted(
                $quotation,
                $data['signer_name'],
            );

            $this->opportunitySync->onAccepted($quotation);
            $this->auditLog->log('quotation.accepted', $quotation, [], $data);

            return $quotation->fresh();
        });
    }

    public function reject(Quotation $quotation, array $data): Quotation
    {
        $this->validateConfirmation($quotation);

        return DB::transaction(function () use ($quotation, $data) {
            $this->stateMachine->validateTransition($quotation->status, QuotationStatus::Rejected);

            $quotation->confirmations()->create([
                'confirmation_type' => ConfirmationType::Rejected,
                'signer_name' => $data['signer_name'],
                'signer_email' => $data['signer_email'],
                'confirmation_code' => strtoupper(bin2hex(random_bytes(8))),
                'confirmed_at' => now(),
                'ip_address' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 1000),
                'confirmation_data' => json_encode($data),
            ]);

            $quotation->update([
                'status' => QuotationStatus::Rejected,
                'rejected_at' => now(),
            ]);

            $this->interactions->logRejected(
                $quotation,
                $data['reason'] ?? '',
            );

            $this->opportunitySync->onRejected($quotation);
            $this->auditLog->log('quotation.rejected', $quotation, [], $data);

            return $quotation->fresh();
        });
    }

    public function requestRevision(Quotation $quotation, array $data): Quotation
    {
        $this->validateConfirmation($quotation);

        return DB::transaction(function () use ($quotation, $data) {
            $this->stateMachine->validateTransition($quotation->status, QuotationStatus::RevisionRequested);

            $quotation->confirmations()->create([
                'confirmation_type' => ConfirmationType::RevisionRequested,
                'signer_name' => $data['signer_name'],
                'signer_email' => $data['signer_email'],
                'confirmation_code' => strtoupper(bin2hex(random_bytes(8))),
                'confirmed_at' => now(),
                'ip_address' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 1000),
                'confirmation_data' => json_encode($data),
            ]);

            $quotation->update([
                'status' => QuotationStatus::RevisionRequested,
                'revision_requested_at' => now(),
            ]);

            $this->interactions->logRevisionRequested(
                $quotation,
                $data['reason'] ?? '',
            );

            $this->opportunitySync->onRevisionRequested($quotation);
            $this->auditLog->log('quotation.revision_requested', $quotation, [], $data);

            return $quotation->fresh();
        });
    }

    private function validateConfirmation(Quotation $quotation): void
    {
        if (! $quotation->status->canConfirm()) {
            throw new \InvalidArgumentException('This quotation cannot be confirmed.');
        }

        if ($quotation->valid_until && $quotation->valid_until->isPast()) {
            throw new \InvalidArgumentException('This quotation has expired.');
        }

        if ($quotation->status === QuotationStatus::Superseded) {
            throw new \InvalidArgumentException('This quotation version has been superseded.');
        }
    }
}
