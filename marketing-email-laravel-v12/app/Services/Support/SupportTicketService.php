<?php

namespace App\Services\Support;

use App\Enums\Crm\DepartmentFunction;
use App\Enums\Support\TicketPriority;
use App\Enums\Support\TicketStatus;
use App\Mail\Support\SupportTicketReplyMail;
use App\Models\Crm\Customer;
use App\Models\Crm\Staff;
use App\Models\Marketing\SendingAccount;
use App\Models\Support\SupportTicket;
use App\Models\User;
use App\Services\Marketing\AuditLogService;
use App\Services\Marketing\SendingAccountMailerService;
use App\Services\Security\BusinessNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SupportTicketService
{
    public function __construct(
        private readonly SendingAccountMailerService $mailer,
        private readonly AuditLogService $auditLog,
        private readonly BusinessNotificationService $notifications,
    ) {}

    public function create(Customer $customer, User $actor, array $data): SupportTicket
    {
        $this->assertCustomerCareActor($actor);
        $customer->loadMissing('currentOwner');

        $assignedStaffId = $this->resolveAssignedStaffId(
            $customer,
            isset($data['assigned_staff_id']) ? (int) $data['assigned_staff_id'] : null,
        );

        $ticket = DB::transaction(function () use ($customer, $actor, $data, $assignedStaffId): SupportTicket {
            $ticket = SupportTicket::query()->create([
                'ticket_code' => $this->nextCode(),
                'customer_id' => $customer->id,
                'assigned_staff_id' => $assignedStaffId,
                'requester_name' => trim((string) ($data['requester_name'] ?? $customer->display_name)),
                'requester_email' => trim((string) ($data['requester_email'] ?? $customer->email ?? $customer->business_email)),
                'subject' => trim((string) $data['subject']),
                'description' => trim((string) ($data['description'] ?? '')) ?: null,
                'channel' => $data['channel'] ?? 'email',
                'status' => TicketStatus::Open,
                'priority' => $data['priority'] ?? config('v1_workflow.support.default_priority', TicketPriority::Normal->value),
                'opened_at' => now(),
                'last_activity_at' => now(),
                'created_by_user_id' => $actor->id,
            ]);

            if (filled($ticket->description)) {
                $ticket->messages()->create([
                    'direction' => 'inbound',
                    'sender_email' => $ticket->requester_email,
                    'body' => $ticket->description,
                    'created_by_user_id' => $actor->id,
                    'sent_at' => now(),
                ]);
            }

            return $ticket;
        });

        $this->auditLog->log('support.ticket_created', $ticket, [], [
            'customer_id' => $customer->id,
            'assigned_staff_id' => $assignedStaffId,
        ]);
        $ticket->loadMissing('assignedStaff.user');
        $this->notifications->send($ticket->assignedStaff?->user, __('v1.notification.support_ticket_title'), $ticket->ticket_code.' · '.$ticket->subject);

        return $ticket->fresh(['customer', 'assignedStaff', 'messages']);
    }

    public function createFromInboundEmail(Customer $customer, array $data): SupportTicket
    {
        $customer->loadMissing('currentOwner');

        $ticket = DB::transaction(function () use ($customer, $data): SupportTicket {
            $ticket = SupportTicket::query()->create([
                'ticket_code' => $this->nextCode(),
                'customer_id' => $customer->id,
                'assigned_staff_id' => $customer->currentOwner?->id,
                'requester_name' => trim((string) ($data['requester_name'] ?? $customer->display_name)),
                'requester_email' => trim((string) ($data['requester_email'] ?? $customer->email ?? $customer->business_email)),
                'subject' => trim((string) $data['subject']),
                'description' => trim((string) ($data['description'] ?? '')) ?: null,
                'channel' => 'email',
                'status' => TicketStatus::Open,
                'priority' => config('v1_workflow.support.default_priority', TicketPriority::Normal->value),
                'opened_at' => now(),
                'last_activity_at' => now(),
                'created_by_user_id' => null,
                'metadata' => [
                    'source' => 'inbound_email_webhook',
                ],
            ]);

            $ticket->messages()->create([
                'direction' => 'inbound',
                'sender_email' => $ticket->requester_email,
                'body' => (string) ($ticket->description ?? ''),
                'external_message_id' => $data['external_message_id'] ?? null,
                'created_by_user_id' => null,
                'sent_at' => now(),
            ]);

            return $ticket;
        });

        $this->auditLog->log('support.ticket_created_from_email', $ticket, [], [
            'customer_id' => $customer->id,
            'requester_email' => $ticket->requester_email,
        ]);
        $ticket->loadMissing('assignedStaff.user');
        $this->notifications->send($ticket->assignedStaff?->user, __('v1.notification.support_ticket_title'), $ticket->ticket_code.' · '.$ticket->subject);
        $this->notifications->notifyPermission('customer-care.manage-assignments', __('v1.notification.support_ticket_title'), $ticket->ticket_code.' · '.$ticket->subject);

        return $ticket->fresh(['customer', 'assignedStaff', 'messages']);
    }

    public function replyByEmail(SupportTicket $ticket, User $actor, string $body): SupportTicket
    {
        $this->assertCustomerCareActor($actor);
        $this->assertActorCanAccessTicket($ticket, $actor);

        $body = trim($body);
        if ($body === '') {
            throw ValidationException::withMessages(['body' => 'Nội dung phản hồi không được để trống.']);
        }

        if (! filter_var($ticket->requester_email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages(['requester_email' => 'Ticket chưa có email người nhận hợp lệ.']);
        }

        $account = $this->resolveSendingAccount();
        $this->mailer->send(
            account: $account,
            recipientEmail: $ticket->requester_email,
            mailable: new SupportTicketReplyMail($ticket->loadMissing('customer'), $body),
        );

        $ticket->messages()->create([
            'direction' => 'outbound',
            'sender_email' => $account->from_email,
            'recipient_email' => $ticket->requester_email,
            'body' => $body,
            'created_by_user_id' => $actor->id,
            'sent_at' => now(),
        ]);

        $ticket->update([
            'status' => TicketStatus::PendingCustomer,
            'last_activity_at' => now(),
        ]);

        $this->auditLog->log('support.ticket_replied', $ticket, [], [
            'sending_account_id' => $account->id,
            'recipient' => $ticket->requester_email,
        ]);

        return $ticket->fresh('messages');
    }

    public function recordInboundEmail(SupportTicket $ticket, string $from, string $body, ?string $externalMessageId = null): SupportTicket
    {
        if ($externalMessageId && $ticket->messages()->where('external_message_id', $externalMessageId)->exists()) {
            return $ticket;
        }

        $ticket->messages()->create([
            'direction' => 'inbound',
            'sender_email' => $from,
            'recipient_email' => null,
            'body' => trim($body),
            'external_message_id' => $externalMessageId,
            'sent_at' => now(),
        ]);

        $ticket->update([
            'status' => TicketStatus::Open,
            'last_activity_at' => now(),
        ]);
        $ticket->loadMissing('assignedStaff.user');
        $this->notifications->send($ticket->assignedStaff?->user, __('v1.notification.support_reply_title'), $ticket->ticket_code.' · '.$ticket->subject);

        return $ticket->fresh('messages');
    }

    private function assertCustomerCareActor(User $actor): void
    {
        if (! $actor->can('customer-care.manage-tickets')) {
            throw ValidationException::withMessages([
                'support_ticket' => 'Chỉ nhân sự có chức năng Chăm sóc khách hàng mới được thao tác ticket.',
            ]);
        }
    }

    private function assertActorCanAccessTicket(SupportTicket $ticket, User $actor): void
    {
        if ($actor->can('customer-care.manage-assignments')) {
            return;
        }

        $staffId = $actor->staff?->id;
        if ($staffId === null) {
            throw ValidationException::withMessages([
                'support_ticket' => 'Tài khoản chưa liên kết hồ sơ nhân viên.',
            ]);
        }

        $ticket->loadMissing('customer.assignments');
        $canAccess = $ticket->assigned_staff_id === $staffId
            || $ticket->customer?->assignments
                ?->contains(fn ($assignment): bool =>
                    (int) $assignment->staff_id === $staffId
                    && (string) ($assignment->status?->value ?? $assignment->status) === 'active'
                );

        if (! $canAccess) {
            throw ValidationException::withMessages([
                'support_ticket' => 'Ticket này không thuộc phạm vi chăm sóc của bạn.',
            ]);
        }
    }

    private function resolveAssignedStaffId(Customer $customer, ?int $requestedStaffId): ?int
    {
        if ($requestedStaffId === null) {
            return $customer->currentOwner?->id;
        }

        $staff = Staff::query()
            ->withBusinessFunction(DepartmentFunction::CustomerService)
            ->whereKey($requestedStaffId)
            ->whereHas('user', fn ($query) => $query->where('is_active', true))
            ->where('employment_status', 'active')
            ->first();

        if ($staff === null) {
            throw ValidationException::withMessages([
                'assigned_staff_id' => 'Chỉ có thể giao ticket cho nhân sự CSKH đang hoạt động.',
            ]);
        }

        return $staff->id;
    }

    private function resolveSendingAccount(): SendingAccount
    {
        $preferred = config('v1_workflow.support.reply_sending_account_id');

        if ($preferred) {
            $account = SendingAccount::query()->whereKey((int) $preferred)->where('status', 'active')->first();
            if ($account) {
                return $account;
            }
        }

        $account = SendingAccount::query()
            ->where('status', 'active')
            ->where(function ($query): void {
                $query->whereNull('department_id')
                    ->orWhereHas('department', fn ($department) =>
                        $department->where('function_key', DepartmentFunction::CustomerService->value)
                    );
            })
            ->orderByRaw('department_id is null')
            ->first();

        if (! $account) {
            throw ValidationException::withMessages([
                'sending_account' => 'Chưa có tài khoản gửi email hoạt động dành cho CSKH.',
            ]);
        }

        return $account;
    }

    private function nextCode(): string
    {
        $next = (SupportTicket::query()->max('id') ?? 0) + 1;
        return 'TKT-'.now()->format('Ymd').'-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}
