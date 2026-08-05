<?php

namespace Tests\Feature\Marketing;

use App\Filament\Resources\LandingPageSubmissionResource\Pages\ListLandingPageSubmissions;
use App\Models\Marketing\Contact;
use App\Models\Marketing\LandingPage;
use App\Models\Marketing\LandingPageSubmission;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class LandingPageSubmissionResourceTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::factory()->create(['role' => 'marketing_staff']);
    }

    private function makeLandingPage(): LandingPage
    {
        return LandingPage::query()->create([
            'name' => 'VPS Landing',
            'slug' => 'vps-landing-'.Str::random(6),
        ]);
    }

    private function makeSubmission(string $type, string $status = 'processed'): LandingPageSubmission
    {
        $contact = Contact::query()->create(['contact_type' => $type]);

        return LandingPageSubmission::query()->create([
            'landing_page_id' => $this->makeLandingPage()->id,
            'contact_id' => $contact->id,
            'data' => ['name' => 'Test'],
            'normalized_email' => 'test@example.com',
            'status' => $status,
            'submission_type' => $type,
            'submitted_at' => now(),
        ]);
    }

    public function test_personal_submission_displays_ca_nhan(): void
    {
        $this->makeSubmission('personal');

        $this->actingAs($this->makeUser());

        Livewire::test(ListLandingPageSubmissions::class)
            ->assertSee('Cá nhân');
    }

    public function test_business_submission_displays_doanh_nghiep(): void
    {
        $this->makeSubmission('business');

        $this->actingAs($this->makeUser());

        Livewire::test(ListLandingPageSubmissions::class)
            ->assertSee('Doanh nghiệp');
    }

    public function test_processed_status_displays_da_ghi_nhan(): void
    {
        $this->makeSubmission('personal', 'processed');

        $this->actingAs($this->makeUser());

        Livewire::test(ListLandingPageSubmissions::class)
            ->assertSee('Đã ghi nhận');
    }

    public function test_submission_resource_has_no_direct_qualification_edit(): void
    {
        $this->actingAs($this->makeUser());

        $page = Livewire::test(ListLandingPageSubmissions::class)->instance();

        $table = $page->getTable();

        $actions = collect($table->getActions())
            ->flatMap(fn ($action) => $action instanceof ActionGroup
                ? $action->getActions()
                : [$action])
            ->values();

        $actionNames = $actions->map(fn ($action) => $action->getName())->all();

        $this->assertNotContains('qualification_status', $actionNames);
        $this->assertNotContains('assigned_staff_id', $actionNames);

        foreach ($actions as $action) {
            if (! method_exists($action, 'getForm')) {
                continue;
            }

            $form = $action->getForm(Form::make($page));

            if ($form === null) {
                continue;
            }

            foreach ($form->getComponents() as $component) {
                $this->assertNotSame('qualification_status', $component->getName());
                $this->assertNotSame('assigned_staff_id', $component->getName());
            }
        }
    }
}
