<?php

namespace Tests\Feature\Crm;

use App\Filament\Resources\DepartmentResource;
use App\Filament\Resources\DepartmentResource\Pages\ListDepartments;
use App\Livewire\DepartmentPositionsTable;
use App\Livewire\DepartmentStaffTable;
use App\Mail\MarketingCampaignMail;
use App\Models\Crm\Customer;
use App\Models\Crm\Department;
use App\Models\Crm\Position;
use App\Models\Crm\Staff;
use App\Models\Marketing\Contact;
use App\Models\Marketing\EmailEvent;
use App\Models\Marketing\SendingAccount;
use App\Models\User;
use App\Services\Crm\CustomerCareEmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class RoleAccessAndCareFlowTest extends TestCase
{
    use RefreshDatabase;

    private const NON_ADMIN_ROLES = [
        'marketing_manager',
        'marketing_staff',
        'customer_service_manager',
        'customer_service_staff',
        'viewer',
    ];

    private function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function makeCustomer(): Customer
    {
        $contact = Contact::query()->create(['contact_type' => 'personal']);

        return Customer::factory()->create(['contact_id' => $contact->id]);
    }

    // ---------- Admin role: department management ----------

    public function test_admin_can_open_department_pages(): void
    {
        $this->actingAs($this->makeUser('admin'));

        $this->get('/admin/departments')->assertOk();
        $this->assertTrue(DepartmentResource::canCreate());

        $department = Department::query()->create(['code' => 'support', 'name' => 'Support']);
        $this->assertTrue(DepartmentResource::canEdit($department));
        $this->assertTrue(DepartmentResource::canDelete($department));
    }

    public function test_department_pages_are_admin_only(): void
    {
        foreach (self::NON_ADMIN_ROLES as $role) {
            $user = $this->actingAs($this->makeUser($role));

            $this->get('/admin/departments')->assertForbidden();
            $this->assertFalse(DepartmentResource::canViewAny());
            $this->assertFalse(DepartmentResource::canDelete(Department::query()->firstOrNew(['code' => 'x'])));
        }
    }

    public function test_department_delete_detaches_children(): void
    {
        $this->actingAs($this->makeUser('admin'));

        $department = Department::query()->create(['name' => 'To Delete', 'code' => 'to_delete']);
        $staff = Staff::factory()->create(['department_id' => $department->id]);
        $position = Position::query()->create(['title' => 'Manager', 'department_id' => $department->id]);
        $account = SendingAccount::factory()->create(['department_id' => $department->id]);

        $this->assertTrue(DepartmentResource::canDelete($department));

        $department->delete();

        $this->assertNull($staff->refresh()->department_id);
        $this->assertNull($position->refresh()->department_id);
        $this->assertNull($account->refresh()->department_id);
    }

    public function test_clicking_department_selects_it_and_shows_detail(): void
    {
        $this->actingAs($this->makeUser('admin'));

        $department = Department::query()->create(['name' => 'customer_service', 'code' => 'click_me']);

        $component = Livewire::test(ListDepartments::class)
            ->callTableColumnAction('name', $department)
            ->assertSet('selectedDepartmentId', $department->id)
            ->assertSee('click_me');

        $component
            ->callTableColumnAction('code', $department)
            ->assertSet('selectedDepartmentId', $department->id);

        $component
            ->callTableColumnAction('staff_count', $department)
            ->assertSet('selectedDepartmentId', $department->id);
    }

    // ---------- Other roles: positions / sending accounts ----------

    public function test_position_resource_access_by_role(): void
    {
        $this->actingAs($this->makeUser('admin'));
        $this->get('/admin/positions')->assertOk();

        foreach (['marketing_manager', 'marketing_staff', 'customer_service_manager', 'customer_service_staff', 'viewer'] as $role) {
            $this->actingAs($this->makeUser($role));
            $this->get('/admin/positions')->assertForbidden();
        }
    }

    public function test_position_create_is_admin_only(): void
    {
        $this->actingAs($this->makeUser('customer_service_manager'));
        $this->get('/admin/positions/create')->assertForbidden();

        $this->actingAs($this->makeUser('admin'));
        $this->get('/admin/positions/create')->assertOk();
    }

    public function test_staff_pages_are_admin_only(): void
    {
        foreach (['marketing_manager', 'marketing_staff', 'customer_service_manager', 'customer_service_staff', 'viewer'] as $role) {
            $this->actingAs($this->makeUser($role));
            $this->get('/admin/staff')->assertForbidden();
        }

        $this->actingAs($this->makeUser('admin'));
        $this->get('/admin/staff')->assertOk();
    }

    public function test_sending_account_pages_require_admin(): void
    {
        $this->actingAs($this->makeUser('marketing_manager'));
        $this->get('/admin/sending-accounts')->assertForbidden();
        $this->get('/admin/sending-accounts/create')->assertForbidden();

        $this->actingAs($this->makeUser('admin'));
        $this->get('/admin/sending-accounts')->assertOk();
        $this->get('/admin/sending-accounts/create')->assertOk();
    }

    // ---------- Care email flow: staff sends through department account ----------

    private function makeCsStaff(string $role = 'customer_service_staff'): array
    {
        $department = Department::query()->create(['name' => 'customer_service', 'code' => 'care_test']);
        $user = $this->makeUser($role);
        $staff = Staff::query()->create([
            'user_id' => $user->id,
            'employee_code' => 'EMP' . $user->id,
            'full_name' => $user->name,
            'department_id' => $department->id,
            'employment_status' => 'active',
        ]);

        return [$department, $user, $staff];
    }

    public function test_cs_staff_sends_care_email_through_department_account(): void
    {
        [$department, , $staff] = $this->makeCsStaff();

        $account = SendingAccount::factory()->create([
            'provider' => 'smtp',
            'department_id' => $department->id,
            'config_encrypted' => [
                'host' => 'smtp.example.test',
                'port' => 587,
                'username' => 'user@example.test',
                'password' => 'secret',
                'encryption' => 'tls',
            ],
        ]);

        $customer = $this->makeCustomer();

        $service = app(CustomerCareEmailService::class);

        Mail::fake(['care_' . $account->id]);

        $service->send(
            $customer,
            'Chăm sóc: {{ $customer->display_name }}',
            '<p>Chào {{ $customer->display_name }}</p>',
            account: $account,
            staffId: $staff->id,
        );

        Mail::mailer('care_' . $account->id)->assertSent(MarketingCampaignMail::class, 1);

        $this->assertDatabaseHas('email_events', ['customer_id' => $customer->id, 'event_type' => 'sent']);
        $this->assertDatabaseHas('customer_interactions', [
            'customer_id' => $customer->id,
            'staff_id' => $staff->id,
            'interaction_type' => 'email',
            'status' => 'completed',
        ]);
    }

    public function test_staff_without_department_account_gets_clear_error(): void
    {
        [$department, , $staff] = $this->makeCsStaff();

        $service = app(CustomerCareEmailService::class);
        $info = $service->getSenderInfo($staff->id);

        $this->assertNull($info['account']);
        $this->assertFalse($info['ready']);
        $this->assertNotNull($info['error']);
        $this->assertStringContainsString('customer_service', $info['error']);

        $customer = $this->makeCustomer();

        $this->expectException(\RuntimeException::class);
        $service->send($customer, 'Subject', '<p>Body</p>', staffId: $staff->id);
    }

    public function test_account_not_smtp_ready_rejects_care_email(): void
    {
        [$department, , $staff] = $this->makeCsStaff();

        $account = SendingAccount::factory()->create([
            'provider' => 'laravel_mail',
            'department_id' => $department->id,
        ]);

        $service = app(CustomerCareEmailService::class);
        $info = $service->getSenderInfo($staff->id);

        $this->assertSame($account->id, $info['account']?->id);
        $this->assertFalse($info['ready']);

        $customer = $this->makeCustomer();

        $this->expectException(\RuntimeException::class);
        $service->send($customer, 'Subject', '<p>Body</p>', staffId: $staff->id);
    }

    public function test_no_staff_falls_back_to_shared_account(): void
    {
        $shared = SendingAccount::factory()->create(['department_id' => null]);

        $service = app(CustomerCareEmailService::class);

        $this->assertSame($shared->id, $service->resolveAccount(null)?->id);
        $this->assertSame($shared->id, $service->defaultSendingAccount()?->id);

        $info = $service->getSenderInfo(null);
        $this->assertSame($shared->id, $info['account']?->id);
        $this->assertFalse($info['ready']);
    }

    // ---------- Department page: positions & staff merged inside ----------

    public function test_position_created_inside_department_gets_its_department(): void
    {
        $this->actingAs($this->makeUser('admin'));

        $department = Department::query()->create(['name' => 'customer_service', 'code' => 'merged_test']);

        Livewire::test(DepartmentPositionsTable::class, [
            'departmentId' => $department->id,
        ])
            ->callTableAction('create', data: [
                'title' => 'NV Chăm sóc KH',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('positions', [
            'title' => 'NV Chăm sóc KH',
            'department_id' => $department->id,
        ]);
    }

    public function test_staff_created_inside_department_gets_its_department(): void
    {
        $this->actingAs($this->makeUser('admin'));

        $department = Department::query()->create(['name' => 'customer_service', 'code' => 'merged_test']);
        $position = Position::query()->create(['title' => 'NV Chăm sóc KH', 'department_id' => $department->id]);
        $user = $this->makeUser('customer_service_staff');

        Livewire::test(DepartmentStaffTable::class, [
            'departmentId' => $department->id,
        ])
            ->callTableAction('create', data: [
                'user_id' => $user->id,
                'position_id' => $position->id,
                'employee_code' => 'EMP-9001',
                'full_name' => 'Nhân viên mới',
                'employment_status' => 'active',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('staff', [
            'employee_code' => 'EMP-9001',
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);
    }

    public function test_staff_create_rejects_position_from_other_department(): void
    {
        $this->actingAs($this->makeUser('admin'));

        $csDepartment = Department::query()->create(['name' => 'customer_service', 'code' => 'merged_cs']);
        $salesDepartment = Department::query()->create(['name' => 'sales', 'code' => 'merged_sales']);
        $salesPosition = Position::query()->create(['title' => 'Sale', 'department_id' => $salesDepartment->id]);
        $user = $this->makeUser('customer_service_staff');

        Livewire::test(DepartmentStaffTable::class, [
            'departmentId' => $csDepartment->id,
        ])
            ->callTableAction('create', data: [
                'user_id' => $user->id,
                'position_id' => $salesPosition->id,
                'employee_code' => 'EMP-9002',
                'full_name' => 'Sai chức danh',
                'employment_status' => 'active',
            ])
            ->assertHasTableActionErrors(['position_id']);
    }
}