<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\Crm\Customer;
use App\Models\Support\SupportTicket;
use App\Models\Support\SupportTicketMessage;
use App\Services\Business\WorkflowPolicyService;
use App\Services\Support\SupportTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class SupportInboundEmailController extends Controller
{
    public function __invoke(
        Request $request,
        SupportTicketService $tickets,
        WorkflowPolicyService $workflowPolicy,
    ): JsonResponse {
        abort_unless($workflowPolicy->supportTicketsEnabled(), 404);

        $secret = (string) config('v1_workflow.support.inbound_webhook_secret', '');
        abort_if($secret === '', 503, 'Support inbound email webhook is not configured.');

        $provided = (string) $request->header('X-Support-Webhook-Secret', '');
        abort_unless(hash_equals($secret, $provided), 401);

        $data = $request->validate([
            'from_email' => ['required', 'email', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
            'message_id' => ['nullable', 'string', 'max:500'],
            'ticket_code' => ['nullable', 'string', 'max:50'],
        ]);

        $externalMessageId = trim((string) ($data['message_id'] ?? ''));
        if ($externalMessageId !== '') {
            $existingMessage = SupportTicketMessage::query()
                ->with('ticket:id,ticket_code')
                ->where('external_message_id', $externalMessageId)
                ->first();

            if ($existingMessage?->ticket) {
                return response()->json([
                    'status' => 'duplicate_ignored',
                    'ticket_code' => $existingMessage->ticket->ticket_code,
                ]);
            }
        }

        $ticketCode = trim((string) ($data['ticket_code'] ?? ''));
        if ($ticketCode === '' && preg_match('/\[(TKT-[A-Z0-9-]+)\]/i', (string) $data['subject'], $matches)) {
            $ticketCode = strtoupper($matches[1]);
        }

        if ($ticketCode !== '') {
            $ticket = SupportTicket::query()->where('ticket_code', $ticketCode)->first();
            if ($ticket !== null) {
                $ticket = $tickets->recordInboundEmail(
                    $ticket,
                    (string) $data['from_email'],
                    (string) $data['body'],
                    $data['message_id'] ?? null,
                );

                return response()->json([
                    'status' => 'updated',
                    'ticket_code' => $ticket->ticket_code,
                ]);
            }
        }

        $email = Str::lower(trim((string) $data['from_email']));
        $customer = Customer::query()
            ->where(function ($query) use ($email): void {
                $query->whereRaw('LOWER(email) = ?', [$email])
                    ->orWhereRaw('LOWER(business_email) = ?', [$email])
                    ->orWhere('normalized_email', $email);
            })
            ->first();

        if ($customer === null) {
            return response()->json([
                'message' => 'Không tìm thấy khách hàng đã mua hàng tương ứng với email người gửi.',
            ], 422);
        }

        $ticket = $tickets->createFromInboundEmail($customer, [
            'requester_name' => $data['from_name'] ?? $customer->display_name,
            'requester_email' => $data['from_email'],
            'subject' => $data['subject'],
            'description' => $data['body'],
            'external_message_id' => $data['message_id'] ?? null,
        ]);

        return response()->json([
            'status' => 'created',
            'ticket_code' => $ticket->ticket_code,
        ], 201);
    }
}
