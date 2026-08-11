<?php

namespace Tests\Feature\V1;

use App\Models\CompanySetting;
use App\Models\Crm\Customer;
use App\Models\Support\SupportTicket;
use App\Models\Support\SupportTicketMessage;
use App\Models\User;
use App\Services\Support\SupportTicketService;
use Database\Seeders\V1AcceptanceTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SupportTicketFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(V1AcceptanceTestSeeder::class);

        CompanySetting::firstOrCreateDefault()->update([
            'support_tickets_enabled' => true,
        ]);
    }

    public function test_customer_service_can_create_post_sale_ticket_but_sales_cannot(): void
    {
        $customer = Customer::factory()->create([
            'customer_code' => 'CUS-V1-SUPPORT-001',
            'email' => 'buyer@example.com',
        ]);

        $customerService = User::query()->where('email', 'support.v1@dth.local')->firstOrFail();
        $sales = User::query()->where('email', 'commercial.v1@dth.local')->firstOrFail();

        $ticket = app(SupportTicketService::class)->create($customer, $customerService, [
            'requester_name' => 'Buyer',
            'requester_email' => 'buyer@example.com',
            'subject' => 'Cần hỗ trợ sau mua',
            'description' => 'Không đăng nhập được dịch vụ.',
            'priority' => 'normal',
        ]);

        $this->assertSame($customer->id, $ticket->customer_id);
        $this->assertDatabaseHas('support_tickets', [
            'id' => $ticket->id,
            'customer_id' => $customer->id,
            'requester_email' => 'buyer@example.com',
        ]);

        $this->expectException(ValidationException::class);
        app(SupportTicketService::class)->create($customer, $sales, [
            'requester_email' => 'buyer@example.com',
            'subject' => 'Sales must not create support ticket',
        ]);
    }

    public function test_inbound_email_webhook_creates_ticket_and_deduplicates_message_id(): void
    {
        config()->set('v1_workflow.support.inbound_webhook_secret', 'v1-test-secret');

        $customer = Customer::factory()->create([
            'customer_code' => 'CUS-V1-SUPPORT-002',
            'email' => 'support-buyer@example.com',
            'normalized_email' => 'support-buyer@example.com',
        ]);

        $payload = [
            'from_email' => 'support-buyer@example.com',
            'from_name' => 'Support Buyer',
            'subject' => 'Hosting không truy cập được',
            'body' => 'Nhờ bộ phận CSKH kiểm tra giúp.',
            'message_id' => 'mail-v1-0001@example.test',
        ];

        $this->withHeader('X-Support-Webhook-Secret', 'v1-test-secret')
            ->postJson('/webhooks/support/inbound-email', $payload)
            ->assertCreated()
            ->assertJsonPath('status', 'created');

        $this->assertSame(1, SupportTicket::query()->count());
        $this->assertSame(1, SupportTicketMessage::query()->count());
        $this->assertDatabaseHas('support_tickets', [
            'customer_id' => $customer->id,
            'requester_email' => 'support-buyer@example.com',
        ]);

        $this->withHeader('X-Support-Webhook-Secret', 'v1-test-secret')
            ->postJson('/webhooks/support/inbound-email', $payload)
            ->assertOk()
            ->assertJsonPath('status', 'duplicate_ignored');

        $this->assertSame(1, SupportTicket::query()->count());
        $this->assertSame(1, SupportTicketMessage::query()->count());
    }

    public function test_inbound_email_webhook_rejects_unknown_customer(): void
    {
        config()->set('v1_workflow.support.inbound_webhook_secret', 'v1-test-secret');

        $this->withHeader('X-Support-Webhook-Secret', 'v1-test-secret')
            ->postJson('/webhooks/support/inbound-email', [
                'from_email' => 'unknown@example.com',
                'subject' => 'Unknown sender',
                'body' => 'No purchased customer exists for this email.',
                'message_id' => 'mail-v1-unknown@example.test',
            ])
            ->assertStatus(422);

        $this->assertDatabaseCount('support_tickets', 0);
    }
}
