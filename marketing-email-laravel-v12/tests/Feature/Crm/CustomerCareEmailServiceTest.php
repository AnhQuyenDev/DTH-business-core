<?php

namespace Tests\Feature\Crm;

use App\Mail\MarketingCampaignMail;
use App\Models\Crm\Customer;
use App\Models\Marketing\Contact;
use App\Models\Marketing\SendingAccount;
use App\Services\Crm\CustomerCareEmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\Support\MakesV1Actors;
use Tests\TestCase;

class CustomerCareEmailServiceTest extends TestCase
{
    use MakesV1Actors;
    use RefreshDatabase;

    private function customer(): Customer
    {
        $contact = Contact::query()->create(['contact_type' => 'personal']);

        return Customer::factory()->create(['contact_id' => $contact->id]);
    }

    private function readySmtpAccount(?int $departmentId): SendingAccount
    {
        return SendingAccount::factory()->create([
            'provider' => 'smtp',
            'department_id' => $departmentId,
            'status' => 'active',
            'config_encrypted' => [
                'host' => 'smtp.example.test',
                'port' => 587,
                'username' => 'care@example.test',
                'password' => 'secret',
                'encryption' => 'tls',
            ],
        ]);
    }

    public function test_customer_care_staff_sends_through_their_physical_department_account(): void
    {
        [, $staff] = $this->makeV1CustomerCareStaff();
        $account = $this->readySmtpAccount($staff->department_id);
        $customer = $this->customer();
        Mail::fake();

        app(CustomerCareEmailService::class)->send(
            $customer,
            'Chăm sóc: {{ $customer->display_name }}',
            '<p>Chào {{ $customer->display_name }}</p>',
            account: $account,
            staffId: $staff->id,
        );

        Mail::assertSent(MarketingCampaignMail::class, 1);
        $this->assertDatabaseHas('email_events', [
            'customer_id' => $customer->id,
            'event_type' => 'sent',
        ]);
        $this->assertDatabaseHas('customer_interactions', [
            'customer_id' => $customer->id,
            'staff_id' => $staff->id,
            'interaction_type' => 'email',
            'status' => 'completed',
        ]);
    }

    public function test_staff_without_department_sending_account_gets_a_clear_error(): void
    {
        [, $staff] = $this->makeV1CustomerCareStaff();
        $service = app(CustomerCareEmailService::class);

        $info = $service->getSenderInfo($staff->id);
        $this->assertNull($info['account']);
        $this->assertFalse($info['ready']);
        $this->assertNotNull($info['error']);

        $this->expectException(RuntimeException::class);
        $service->send($this->customer(), 'Subject', '<p>Body</p>', staffId: $staff->id);
    }

    public function test_non_smtp_department_account_is_not_accepted_for_care_email(): void
    {
        [, $staff] = $this->makeV1CustomerCareStaff();
        $account = SendingAccount::factory()->create([
            'provider' => 'laravel_mail',
            'department_id' => $staff->department_id,
            'status' => 'active',
        ]);
        $service = app(CustomerCareEmailService::class);

        $info = $service->getSenderInfo($staff->id);
        $this->assertSame($account->id, $info['account']?->id);
        $this->assertFalse($info['ready']);

        $this->expectException(RuntimeException::class);
        $service->send($this->customer(), 'Subject', '<p>Body</p>', staffId: $staff->id);
    }

    public function test_system_email_without_staff_can_use_shared_account(): void
    {
        $shared = $this->readySmtpAccount(null);
        $service = app(CustomerCareEmailService::class);

        $this->assertSame($shared->id, $service->resolveAccount(null)?->id);
        $this->assertSame($shared->id, $service->defaultSendingAccount()?->id);
        $this->assertTrue($service->getSenderInfo(null)['ready']);
    }
}
