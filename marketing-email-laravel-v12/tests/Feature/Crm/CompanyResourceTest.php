<?php

namespace Tests\Feature\Crm;

use App\Enums\Crm\CompanyLifecycleStage;
use App\Filament\Resources\CompanyResource;
use App\Filament\Resources\CompanyResource\Pages\ViewCompany;
use App\Filament\Resources\CompanyResource\RelationManagers\ContactsRelationManager;
use App\Models\Crm\BusinessContactProfile;
use App\Models\Crm\Company;
use App\Models\Marketing\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CompanyResourceTest extends TestCase
{
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();

        config()->set(
            'business_flow.v2_enabled',
            true
        );
    }
    private function makeCompany(): Company
    {
        return Company::query()->create([
            'company_code' => 'COM-2026-000001',
            'legal_name' => 'Công ty TNHH ABC',
            'normalized_name' => 'abc',
            'tax_code' => '0312345678',
            'email_domain' => 'abc.com.vn',
            'lifecycle_stage' => CompanyLifecycleStage::Prospect,
        ]);
    }

    private function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    public function test_customer_service_user_can_view_companies(): void
    {
        $this->makeCompany();

        $this->actingAs($this->makeUser('customer_service_staff'));

        Livewire::test(CompanyResource\Pages\ListCompanies::class)
            ->assertSuccessful()
            ->assertSee('Công ty TNHH ABC');
    }

    public function test_marketing_staff_cannot_view_companies(): void
    {
        $this->makeCompany();

        $this->actingAs($this->makeUser('marketing_staff'));

        $this->assertFalse(CompanyResource::canViewAny());
    }

    public function test_company_contacts_relation_manager_lists_contacts(): void
    {
        $company = $this->makeCompany();
        $contact = Contact::query()->create(['contact_type' => 'business']);

        BusinessContactProfile::query()->create([
            'contact_id' => $contact->id,
            'company_id' => $company->id,
            'company_name' => 'Công ty TNHH ABC',
            'legal_representative' => 'Nguyen Van A',
            'business_email' => 'sales@abc.com.vn',
        ]);

        $company->contacts()->syncWithoutDetaching([$contact->id => [
            'job_title' => 'Director',
            'is_primary' => true,
            'is_active' => true,
        ]]);

        $this->actingAs($this->makeUser('customer_service_staff'));

        Livewire::test(ContactsRelationManager::class, [
            'ownerRecord' => $company,
            'pageClass' => ViewCompany::class,
        ])->assertSuccessful();
    }

    public function test_company_lifecycle_stage_badge_renders(): void
    {
        $this->makeCompany();

        $this->actingAs($this->makeUser('customer_service_staff'));

        Livewire::test(CompanyResource\Pages\ListCompanies::class)
            ->assertSuccessful()
            ->assertSee('Tiềm năng');
    }
}
