<?php

namespace Tests\Feature;

use App\Filament\Pages\CustomerCarePage;
use App\Models\Crm\Customer;
use App\Models\Crm\CustomerAssignment;
use App\Models\Crm\Staff;
use App\Models\Marketing\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerCarePageRenderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $staffUser;

    private Staff $staff;

    private Customer $customer;

    private CustomerAssignment $assignment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->staffUser = User::factory()->create([
            'role' => 'customer_service_staff',
        ]);

        $this->staff = Staff::factory()->create([
            'user_id' => $this->staffUser->id,
            'employment_status' => 'active',
        ]);

        $contact = Contact::query()->create(['contact_type' => 'personal']);

        $this->customer = Customer::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'active',
        ]);

        $this->assignment =
            CustomerAssignment::factory()->create([
                'customer_id' => $this->customer->id,
                'staff_id' => $this->staff->id,
                'assignment_type' => 'owner',
                'status' => 'active',
                'starts_at' => now(),
            ]);
    }

    public function test_customer_care_page_renders(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CustomerCarePage::class, [
                'customerId' => $this->customer->id,
            ])
            ->assertSuccessful();
    }

    public function test_staff_can_release_assigned_customer(): void
    {
        $component = Livewire::actingAs($this->staffUser)
            ->test(CustomerCarePage::class, [
                'customerId' => $this->customer->id,
            ])
            ->set('selectedCustomerId', $this->customer->id);

        $this->assertTrue($component->instance()->canRelease());

        $component
            ->set('releaseReason', 'no_response')
            ->call('releaseCustomer')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('customer_assignments', [
            'id' => $this->assignment->id,
            'status' => 'ended',
        ]);
    }

    public function test_tabs_render_content(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CustomerCarePage::class, [
                'customerId' => $this->customer->id,
            ])
            ->assertSuccessful()
            ->assertSee($this->customer->display_name);
    }
}
