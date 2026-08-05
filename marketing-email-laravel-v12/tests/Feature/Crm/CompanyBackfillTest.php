<?php

namespace Tests\Feature\Crm;

use App\Models\Crm\BusinessContactProfile;
use App\Models\Crm\Company;
use App\Models\Marketing\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CompanyBackfillTest extends TestCase
{
    use RefreshDatabase;

    private function makeLegacyProfilesForSameCompany(): void
    {
        foreach ([
            [
                'legal_representative' => 'Nguyen Van A',
                'business_email' => 'a@abc.com.vn',
                'business_phone' => '0901111111',
            ],
            [
                'legal_representative' => 'Nguyen Van B',
                'business_email' => 'b@abc.com.vn',
                'business_phone' => '0902222222',
            ],
        ] as $person) {
            $contact = Contact::query()->create(['contact_type' => 'business']);

            BusinessContactProfile::query()->create(array_merge($person, [
                'contact_id' => $contact->id,
                'company_name' => 'Công ty TNHH ABC',
                'tax_code' => '0312345678',
            ]));
        }
    }

    public function test_backfill_is_idempotent(): void
    {
        $this->makeLegacyProfilesForSameCompany();

        $this->artisan('crm:backfill-companies')
            ->assertSuccessful();

        $firstCompanies = Company::query()->count();
        $firstLinks = DB::table('company_contacts')->count();

        $this->artisan('crm:backfill-companies')
            ->assertSuccessful();

        $this->assertSame($firstCompanies, Company::query()->count());
        $this->assertSame(
            $firstLinks,
            DB::table('company_contacts')->count()
        );
    }

    public function test_dry_run_does_not_write_database(): void
    {
        $this->makeLegacyProfilesForSameCompany();

        $this->artisan('crm:backfill-companies --dry-run')
            ->assertSuccessful();

        $this->assertDatabaseCount('companies', 0);
        $this->assertDatabaseCount('company_contacts', 0);
    }
}
