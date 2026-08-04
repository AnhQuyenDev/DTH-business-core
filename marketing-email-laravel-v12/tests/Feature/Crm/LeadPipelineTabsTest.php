<?php

namespace Tests\Feature\Crm;

use App\Enums\Crm\ContactQualificationStatus;
use App\Filament\Resources\ContactQualificationResource\Pages\ListContactQualifications;
use App\Models\Crm\ContactQualification;
use App\Models\Marketing\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadPipelineTabsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tabs_are_grouped_into_five_phases(): void
    {
        $tabs = $this->app->make(ListContactQualifications::class)->getTabs();

        $this->assertSame(
            ['all', 'not_contacted', 'in_progress', 'qualified', 'converted'],
            array_keys($tabs),
        );
    }

    public function test_phase_tabs_filter_records_by_grouped_statuses(): void
    {
        $pipeline = [
            ContactQualificationStatus::New,
            ContactQualificationStatus::Assigned,
            ContactQualificationStatus::Contacting,
            ContactQualificationStatus::FollowUp,
            ContactQualificationStatus::Qualified,
            ContactQualificationStatus::Unqualified,
            ContactQualificationStatus::Converted,
        ];

        foreach ($pipeline as $status) {
            ContactQualification::query()->create([
                'contact_id' => Contact::query()->create(['contact_type' => 'personal'])->id,
                'status' => $status,
            ]);
        }

        $page = $this->app->make(ListContactQualifications::class);

        $expected = [
            'all' => 7,
            'not_contacted' => 2,
            'in_progress' => 2,
            'qualified' => 1,
            'converted' => 1,
        ];

        foreach ($expected as $key => $count) {
            $this->assertSame(
                $count,
                $page->getTabs()[$key]->modifyQuery(ContactQualification::query())->count(),
                "Tab [$key] phải lọc đúng số bản ghi.",
            );
        }
    }
}
