<?php

namespace Tests\Feature\Crm;

use App\Enums\Crm\ContactQualificationStatus;
use App\Filament\Resources\ContactQualificationResource\Pages\ListContactQualifications;
use App\Models\Crm\ContactQualification;
use App\Models\Marketing\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactQualificationTabsTest extends TestCase
{
    use RefreshDatabase;

    private function makeQualification(ContactQualificationStatus $status): ContactQualification
    {
        return ContactQualification::query()->create([
            'contact_id' => Contact::query()->create(['contact_type' => 'personal'])->id,
            'status' => $status,
        ]);
    }

    public function test_new_qualification_belongs_to_not_contacted_tab(): void
    {
        $this->makeQualification(ContactQualificationStatus::New);

        $page = $this->app->make(ListContactQualifications::class);

        $this->assertSame(
            1,
            $page->getTabs()['not_contacted']->modifyQuery(ContactQualification::query())->count(),
        );
    }

    public function test_assigned_qualification_belongs_to_in_progress_tab(): void
    {
        $this->makeQualification(ContactQualificationStatus::Assigned);

        $page = $this->app->make(ListContactQualifications::class);

        $this->assertSame(
            1,
            $page->getTabs()['in_progress']->modifyQuery(ContactQualification::query())->count(),
        );
    }

    public function test_contacting_qualification_belongs_to_in_progress_tab(): void
    {
        $this->makeQualification(ContactQualificationStatus::Contacting);

        $page = $this->app->make(ListContactQualifications::class);

        $this->assertSame(
            1,
            $page->getTabs()['in_progress']->modifyQuery(ContactQualification::query())->count(),
        );
    }

    public function test_new_qualification_is_not_in_in_progress_tab(): void
    {
        $this->makeQualification(ContactQualificationStatus::New);

        $page = $this->app->make(ListContactQualifications::class);

        $this->assertSame(
            0,
            $page->getTabs()['in_progress']->modifyQuery(ContactQualification::query())->count(),
        );
    }
}
