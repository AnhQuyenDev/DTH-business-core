<?php

namespace App\Services\Sales;

use App\Enums\Sales\ConfirmationType;
use App\Enums\Sales\CustomerResponseChannel;
use App\Enums\Sales\QuotationStatus;
use App\Models\Sales\Quotation;
use App\Models\User;
use App\Services\Marketing\AuditLogService;
use App\Services\Security\BusinessNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuotationConfirmationService
{
    public function __construct(
        private readonly QuotationStateMachine $stateMachine,
        private readonly AuditLogService $auditLog,
        private readonly QuotationInteractionService $interactions,
        private readonly QuotationOpportunitySyncService $opportunitySync,
        private readonly BusinessNotificationService $notifications,
    ) {}

    public function accept(
        Quotation $quotation,
        array $data,
        ?string $otpVerifiedEmail = null,
    ): Quotation {
        $this->validateConfirmation($quotation);

        return DB::transaction(function () use (
            $quotation,
            $data,
            $otpVerifiedEmail,
        ): Quotation {
            $this->stateMachine->validateTransition(
                $quotation->status,
                QuotationStatus::Accepted,
            );

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
                'confirmation_data' => $this->confirmationData(
                    $data,
                    $otpVerifiedEmail,
                ),
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
            DB::afterCommit(fn () => $this->notifyResponse($quotation, 'accepted', $data));

            return $quotation->fresh();
        });
    }

    public function reject(
        Quotation $quotation,
        array $data,
        ?string $otpVerifiedEmail = null,
    ): Quotation {
        $this->validateConfirmation($quotation);

        return DB::transaction(function () use (
            $quotation,
            $data,
            $otpVerifiedEmail,
        ): Quotation {
            $this->stateMachine->validateTransition(
                $quotation->status,
                QuotationStatus::Rejected,
            );

            $quotation->confirmations()->create([
                'confirmation_type' => ConfirmationType::Rejected,
                'signer_name' => $data['signer_name'],
                'signer_position' => $data['signer_position'] ?? null,
                'signer_email' => $data['signer_email'],
                'signer_phone' => $data['signer_phone'] ?? null,
                'confirmation_code' => strtoupper(bin2hex(random_bytes(8))),
                'otp_verified_at' => $otpVerifiedEmail ? now() : null,
                'confirmed_at' => now(),
                'ip_address' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 1000),
                'confirmation_data' => $this->confirmationData(
                    $data,
                    $otpVerifiedEmail,
                ),
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
            DB::afterCommit(fn () => $this->notifyResponse($quotation, 'rejected', $data));

            return $quotation->fresh();
        });
    }

    public function requestRevision(
        Quotation $quotation,
        array $data,
        ?string $otpVerifiedEmail = null,
    ): Quotation {
        $this->validateConfirmation($quotation);

        return DB::transaction(function () use (
            $quotation,
            $data,
            $otpVerifiedEmail,
        ): Quotation {
            $this->stateMachine->validateTransition(
                $quotation->status,
                QuotationStatus::RevisionRequested,
            );

            $quotation->confirmations()->create([
                'confirmation_type' => ConfirmationType::RevisionRequested,
                'signer_name' => $data['signer_name'],
                'signer_position' => $data['signer_position'] ?? null,
                'signer_email' => $data['signer_email'],
                'signer_phone' => $data['signer_phone'] ?? null,
                'confirmation_code' => strtoupper(bin2hex(random_bytes(8))),
                'otp_verified_at' => $otpVerifiedEmail ? now() : null,
                'confirmed_at' => now(),
                'ip_address' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 1000),
                'confirmation_data' => $this->confirmationData(
                    $data,
                    $otpVerifiedEmail,
                ),
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
            DB::afterCommit(fn () => $this->notifyResponse($quotation, 'revision_requested', $data));

            return $quotation->fresh();
        });
    }

    /**
     * Record a customer's response that was received directly by Sales over
     * the phone (or another assisted conversation channel).
     *
     * This is deliberately not marked as OTP verification: Sales is recording
     * what the customer told them, not impersonating the customer.
     */
    public function recordAssistedResponse(
        Quotation $quotation,
        User $actor,
        array $data,
    ): Quotation {
        if (! $actor->can('recordCustomerResponse', $quotation)) {
            throw ValidationException::withMessages([
                'confirmation' => 'Bạn không có quyền ghi nhận phản hồi cho báo giá này.',
            ]);
        }

        if (! filter_var(
            $data['confirmed_direct_contact'] ?? false,
            FILTER_VALIDATE_BOOLEAN,
        )) {
            throw ValidationException::withMessages([
                'confirmed_direct_contact' => 'Bạn phải xác nhận đã trao đổi trực tiếp với khách hàng.',
            ]);
        }

        $responseType = (string) ($data['response_type'] ?? '');
        $responseChannel = (string) ($data['response_channel'] ?? CustomerResponseChannel::Phone->value);
        if (! array_key_exists($responseChannel, CustomerResponseChannel::assistedOptions())) {
            throw ValidationException::withMessages(['response_channel' => 'Kênh phản hồi khách hàng không hợp lệ.']);
        }

        if (! in_array($responseType, [
            ConfirmationType::Accepted->value,
            ConfirmationType::Rejected->value,
            ConfirmationType::RevisionRequested->value,
        ], true)) {
            throw ValidationException::withMessages([
                'response_type' => 'Phản hồi khách hàng không hợp lệ.',
            ]);
        }

        $signerName = trim((string) ($data['signer_name'] ?? ''));
        $signerEmail = mb_strtolower(trim((string) ($data['signer_email'] ?? '')));
        $conversationNote = trim((string) ($data['conversation_note'] ?? ''));
        $reason = trim((string) ($data['reason'] ?? ''));

        if ($signerName === '' || ! filter_var($signerEmail, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'signer_email' => 'Phải chọn một người liên hệ hợp lệ đã có trong CRM.',
            ]);
        }

        $quotation->loadMissing([
            'opportunity.primaryContact',
            'opportunity.contacts',
        ]);

        if ($quotation->opportunity !== null) {
            $knownEmails = collect([
                $quotation->opportunity->primaryContact,
            ])->merge($quotation->opportunity->contacts ?? collect())
                ->filter()
                ->map(fn ($contact): string => mb_strtolower(
                    trim((string) $contact->email)
                ))
                ->filter()
                ->unique();

            if ($knownEmails->isNotEmpty() && ! $knownEmails->contains($signerEmail)) {
                throw ValidationException::withMessages([
                    'signer_email' => 'Người phản hồi phải là một liên hệ đã có trong Cơ hội kinh doanh.',
                ]);
            }
        }

        if ($conversationNote === '') {
            throw ValidationException::withMessages([
                'conversation_note' => 'Cần ghi lại nội dung trao đổi với khách hàng.',
            ]);
        }

        if (
            in_array($responseType, [
                ConfirmationType::Rejected->value,
                ConfirmationType::RevisionRequested->value,
            ], true)
            && $reason === ''
        ) {
            throw ValidationException::withMessages([
                'reason' => 'Vui lòng nhập lý do hoặc nội dung khách hàng yêu cầu.',
            ]);
        }

        $confirmationData = array_merge($data, [
            'signer_name' => $signerName,
            'signer_email' => $signerEmail,
            'reason' => $reason !== '' ? $reason : null,
            'verification_method' => 'assisted_recorded_by_staff',
            'response_channel' => $responseChannel,
            'recorded_by_user_id' => $actor->id,
            'recorded_by_name' => $actor->name,
            'recorded_at' => now()->toIso8601String(),
        ]);

        return match ($responseType) {
            ConfirmationType::Accepted->value => $this->accept(
                $quotation,
                $confirmationData,
            ),
            ConfirmationType::Rejected->value => $this->reject(
                $quotation,
                $confirmationData,
            ),
            ConfirmationType::RevisionRequested->value => $this->requestRevision(
                $quotation,
                $confirmationData,
            ),
        };
    }

    /** @deprecated Use recordAssistedResponse(). */
    public function recordPhoneResponse(Quotation $quotation, User $actor, array $data): Quotation
    {
        $data['response_channel'] = $data['response_channel'] ?? CustomerResponseChannel::Phone->value;
        return $this->recordAssistedResponse($quotation, $actor, $data);
    }

    private function notifyResponse(Quotation $quotation, string $type, array $data): void
    {
        $quotation->loadMissing('assignedStaff.user');
        $reason = trim((string) ($data['reason'] ?? $data['conversation_note'] ?? ''));
        $channel = (string) ($data['response_channel'] ?? CustomerResponseChannel::PublicLink->value);
        $title = match ($type) {
            'accepted' => __('v1.notification.quotation_accepted_title'),
            'rejected' => __('v1.notification.quotation_rejected_title'),
            default => __('v1.notification.quotation_revision_title'),
        };
        $body = __('v1.notification.quotation_response_body', [
            'code' => $quotation->quotation_code,
            'channel' => CustomerResponseChannel::tryFrom($channel)?->label() ?? $channel,
            'detail' => $reason !== '' ? $reason : '—',
        ]);
        $this->notifications->send($quotation->assignedStaff?->user, $title, $body);
        if ($type === 'revision_requested') {
            $this->notifications->notifyPermission('sales.approve-quotations', $title, $body);
        }
    }

    private function validateConfirmation(Quotation $quotation): void
    {
        if (! $quotation->status->canConfirm()) {
            throw ValidationException::withMessages([
                'confirmation' => 'Báo giá hiện không còn ở trạng thái chờ khách xác nhận.',
            ]);
        }

        if ($quotation->valid_until && $quotation->valid_until->isPast()) {
            throw ValidationException::withMessages([
                'confirmation' => 'Báo giá đã hết hiệu lực.',
            ]);
        }

        if ($quotation->status === QuotationStatus::Superseded) {
            throw ValidationException::withMessages([
                'confirmation' => 'Phiên bản báo giá này đã được thay thế.',
            ]);
        }
    }

    private function confirmationData(
        array $data,
        ?string $otpVerifiedEmail,
    ): array {
        $verificationMethod = $data['verification_method'] ?? null;

        if ($otpVerifiedEmail !== null) {
            $verificationMethod = 'email_otp';
        }

        return array_merge($data, [
            'otp_verified_email' => $otpVerifiedEmail,
            'verification_method' => $verificationMethod,
        ]);
    }
}
