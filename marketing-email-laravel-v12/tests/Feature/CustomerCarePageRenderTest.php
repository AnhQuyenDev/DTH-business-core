<?php

namespace Tests\Feature;

use App\Filament\Pages\CustomerCarePage;
use App\Models\Crm\Customer;
use App\Models\Crm\CustomerAssignment;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerCarePageRenderTest extends TestCase
{
    private function prepareDb(): void
    {
        config(['database.default' => 'mysql']);
        config(['database.connections.mysql.database' => 'marketing_email']);
        DB::purge('mysql');
    }

    public function test_customer_care_page_renders(): void
    {
        $this->prepareDb();
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $user = User::query()->firstOrFail();
        $customer = Customer::query()->firstOrFail();

        $component = Livewire::actingAs($user)->test(CustomerCarePage::class, []);

        $this->assertStringContainsString($customer->display_name, $component->html());
        $this->assertStringContainsString('fi-table', $component->html());

        $component->call('openCareWorkspace', $customer);

        $this->assertStringContainsString('fi-tabs', $component->html());
        $this->assertStringContainsString('customer-care-workspace', $component->html());
    }

    public function test_staff_sees_release_modal(): void
    {
        $this->prepareDb();
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $activeAssignment = CustomerAssignment::query()
            ->where('status', 'active')
            ->with('customer')
            ->first();
        $this->assertNotNull($activeAssignment);
        $staffUser = $activeAssignment->staff->user;
        $this->assertNotNull($staffUser);

        $component = Livewire::actingAs($staffUser)->test(CustomerCarePage::class, []);

        $component->call('openCareWorkspace', $activeAssignment->customer);

        $html = $component->html();
        $this->assertStringContainsString('release-customer-modal', $html);
        $this->assertStringContainsString('fi-tabs', $html);
    }

    public function test_tabs_render_content(): void
    {
        $this->prepareDb();
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $user = User::query()->firstOrFail();
        $customer = Customer::query()->firstOrFail();

        $component = Livewire::actingAs($user)->test(CustomerCarePage::class, []);
        $component->call('openCareWorkspace', $customer);

        $component->set('activeTab', 'email');
        $this->assertStringContainsString('wire:model="emailSubject"', $component->html());
        $this->assertStringContainsString('wire:model="emailBody"', $component->html());

        $component->set('activeTab', 'call');
        $this->assertStringContainsString('wire:model="callContent"', $component->html());

        $component->set('activeTab', 'quotation');
        $this->assertStringContainsString('/quotations/create', $component->html());

        $component->set('activeTab', 'timeline');
        $this->assertStringContainsString('fi-section', $component->html());
    }
}
