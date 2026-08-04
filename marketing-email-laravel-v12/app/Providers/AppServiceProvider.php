<?php

namespace App\Providers;

use App\Contracts\Tax\TaxCodeVerificationProvider;
use App\Listeners\LogSuccessfulLogin;
use App\Models\Crm\CustomerAssignment;
use App\Models\Crm\Staff;
use App\Models\User;
use App\Observers\Crm\CustomerAssignmentObserver;
use App\Observers\Crm\StaffObserver;
use App\Services\Crm\FakeTaxVerificationProvider;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TaxCodeVerificationProvider::class, FakeTaxVerificationProvider::class);
    }

    public function boot(): void
    {
        // ─── Observers ──────────────────────────────────────────────────
        Staff::observe(StaffObserver::class);
        CustomerAssignment::observe(CustomerAssignmentObserver::class);

        // ─── Event Listeners ────────────────────────────────────────────
        Event::listen(Login::class, LogSuccessfulLogin::class);

        // ─── Marketing View Gates ───────────────────────────────────────
        Gate::define('marketing.view-contacts', fn (User $user) => $user->isAnyMarketingUser());
        Gate::define('marketing.view-tags', fn (User $user) => $user->isAnyMarketingUser());
        Gate::define('marketing.view-lists', fn (User $user) => $user->isAnyMarketingUser());
        Gate::define('marketing.view-segments', fn (User $user) => $user->isAnyMarketingUser());
        Gate::define('marketing.view-custom-fields', fn (User $user) => $user->isAnyMarketingUser());
        Gate::define('marketing.view-templates', fn (User $user) => $user->isAnyMarketingUser());
        Gate::define('marketing.view-campaigns', fn (User $user) => $user->isAnyMarketingUser());
        Gate::define('marketing.view-reports', fn (User $user) => $user->isAnyMarketingUser());
        Gate::define('marketing.view-audit', fn (User $user) => $user->isAdmin());

        // ─── Marketing Manage Gates ─────────────────────────────────────
        Gate::define('marketing.manage-contacts', fn (User $user) => $user->isMarketingStaff());
        Gate::define('marketing.manage-tags', fn (User $user) => $user->isMarketingStaff());
        Gate::define('marketing.manage-lists', fn (User $user) => $user->isMarketingStaff());
        Gate::define('marketing.manage-segments', fn (User $user) => $user->isMarketingStaff());
        Gate::define('marketing.manage-custom-fields', fn (User $user) => $user->isMarketingStaff());
        Gate::define('marketing.manage-templates', fn (User $user) => $user->isMarketingStaff());
        Gate::define('marketing.manage-campaigns', fn (User $user) => $user->isMarketingStaff());
        Gate::define('marketing.send-campaigns', fn (User $user) => $user->isMarketingManager());
        Gate::define('marketing.manage-sending', fn (User $user) => $user->isAdmin());
        Gate::define('marketing.manage-suppression', fn (User $user) => $user->isMarketingManager());
        Gate::define('marketing.export-data', fn (User $user) => $user->isMarketingManager());

        // ─── Landing Page Gates ─────────────────────────────────────────
        Gate::define('marketing.view-landing-pages', fn (User $user) => $user->isAnyMarketingUser());
        Gate::define('marketing.manage-landing-pages', fn (User $user) => $user->isMarketingStaff());
        Gate::define('marketing.view-landing-page-submissions', fn (User $user) => $user->isAnyMarketingUser());
        Gate::define('marketing.manage-landing-form-templates', fn (User $user) => $user->isMarketingStaff());

        // ─── CRM Gates ──────────────────────────────────────────────────
        Gate::define('crm.view-contacts', fn (User $user) => $user->isAnyMarketingUser());
        Gate::define('crm.manage-contact-qualification', fn (User $user) => $user->isMarketingStaff() || $user->isCustomerServiceStaff());
        Gate::define('crm.assign-contact', fn (User $user) => $user->isMarketingManager() || $user->isCustomerServiceManager() || $user->isAdmin());
        Gate::define('crm.convert-contact', fn (User $user) => $user->isMarketingManager() || $user->isCustomerServiceManager() || $user->isAdmin());
        Gate::define('crm.view-customers', fn (User $user) => $user->isAnyMarketingUser());
        Gate::define('crm.view-owned-customers', fn (User $user) => $user->isMarketingStaff() || $user->isCustomerServiceStaff());
        Gate::define('crm.view-supported-customers', fn (User $user) => $user->isMarketingStaff() || $user->isCustomerServiceStaff());
        Gate::define('crm.manage-customer-interactions', fn (User $user) => $user->isMarketingStaff() || $user->isCustomerServiceStaff());
        Gate::define('crm.manage-staff', fn (User $user) => $user->isAdmin());
        Gate::define('crm.manage-staff-availability', fn (User $user) => $user->isAdmin());
        Gate::define('crm.distribute-customers', fn (User $user) => $user->isAdmin());
        Gate::define('crm.transfer-customer-owner', fn (User $user) => $user->isAdmin());
        Gate::define('crm.end-temporary-support', fn (User $user) => $user->isAdmin());
        Gate::define('crm.rebalance-customers', fn (User $user) => $user->isAdmin());
        Gate::define('crm.verify-business-tax', fn (User $user) => $user->isAdmin());
        Gate::define('crm.view-all-customers', fn (User $user) => $user->isMarketingManager() || $user->isCustomerServiceManager() || $user->isAdmin());

        // ─── Sales / Quotation Gates ────────────────────────────────────
        Gate::define('sales.view-services', fn (User $user) => $user->isAnyMarketingUser());
        Gate::define('sales.manage-services', fn (User $user) => $user->isAdmin());

        Gate::define('sales.view-service-packages', fn (User $user) => $user->isAnyMarketingUser());
        Gate::define('sales.manage-service-packages', fn (User $user) => $user->isAdmin());

        Gate::define('sales.view-price-books', fn (User $user) => $user->isAnyMarketingUser());
        Gate::define('sales.manage-price-books', fn (User $user) => $user->isAdmin());
        Gate::define('sales.approve-price-books', fn (User $user) => $user->isAdmin() || $user->isCustomerServiceManager());

        Gate::define('sales.view-bank-accounts', fn (User $user) => $user->isAnyMarketingUser());
        Gate::define('sales.manage-bank-accounts', fn (User $user) => $user->isAdmin());

        Gate::define('sales.view-quotations', fn (User $user) => $user->isAnyMarketingUser());
        Gate::define('sales.create-quotations', fn (User $user) => $user->isAdmin() || $user->isCustomerServiceStaff() || $user->isCustomerServiceManager());
        Gate::define('sales.send-quotations', fn (User $user) => $user->isAdmin() || $user->isCustomerServiceManager());
        Gate::define('sales.approve-quotations', fn (User $user) => $user->isAdmin() || $user->isCustomerServiceManager());
        Gate::define('sales.revise-quotations', fn (User $user) => $user->isAdmin());
        Gate::define('sales.cancel-quotations', fn (User $user) => $user->isAdmin() || $user->isCustomerServiceManager());
        Gate::define('sales.export-quotations', fn (User $user) => $user->isAnyMarketingUser());
    }
}
