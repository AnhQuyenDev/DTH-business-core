<?php

namespace App\Providers;

use App\Contracts\Tax\TaxCodeVerificationProvider;
use App\Enums\UserRole;
use App\Events\Crm\ContactQualificationTransitioned;
use App\Events\Crm\LeadAssigned;
use App\Listeners\Crm\RecordContactQualificationTransitionAudit;
use App\Listeners\Crm\RecordLeadAssignmentAudit;
use App\Listeners\LogSuccessfulLogin;
use App\Models\Crm\Company;
use App\Models\Crm\Customer;
use App\Models\Crm\CustomerAssignment;
use App\Models\Crm\Lead;
use App\Models\Crm\Staff;
use App\Models\Sales\Opportunity;
use App\Models\Sales\Quotation;
use App\Models\User;
use App\Models\Marketing\LandingPageSubmission;
use App\Observers\Crm\CustomerAssignmentObserver;
use App\Observers\Crm\StaffObserver;
use App\Observers\Crm\LeadObserver;
use App\Observers\Marketing\LandingPageSubmissionObserver;
use App\Policies\CompanyPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\LeadPolicy;
use App\Policies\Sales\OpportunityPolicy;
use App\Policies\Sales\QuotationPolicy;
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
        LandingPageSubmission::observe(
            LandingPageSubmissionObserver::class
        );
        Lead::observe(LeadObserver::class);

        // ─── Event Listeners ────────────────────────────────────────────
        Event::listen(Login::class, LogSuccessfulLogin::class);
        Event::listen(LeadAssigned::class, RecordLeadAssignmentAudit::class);
        Event::listen(ContactQualificationTransitioned::class, RecordContactQualificationTransitionAudit::class);

        // ─── Policy Registration ──────────────────────────────────────────
        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(Lead::class, LeadPolicy::class);
        Gate::policy(Opportunity::class, OpportunityPolicy::class);
        Gate::policy(Quotation::class, QuotationPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);

        // ─── Marketing View Gates ─────────────────────────────────────────
        Gate::define('marketing.view-contacts', fn (User $user) => $user->canViewMarketingModule());
        Gate::define('marketing.view-tags', fn (User $user) => $user->canViewMarketingModule());
        Gate::define('marketing.view-lists', fn (User $user) => $user->canViewMarketingModule());
        Gate::define('marketing.view-segments', fn (User $user) => $user->canViewMarketingModule());
        Gate::define('marketing.view-custom-fields', fn (User $user) => $user->canViewMarketingModule());
        Gate::define('marketing.view-templates', fn (User $user) => $user->canViewMarketingModule());
        Gate::define('marketing.view-campaigns', fn (User $user) => $user->canViewMarketingModule());
        Gate::define(
            'marketing.view-reports',
            fn (User $user): bool => $user->canViewMarketingModule()
                || $user->isViewer()
        );
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
        Gate::define('marketing.view-landing-pages', fn (User $user) => $user->canViewMarketingModule());
        Gate::define('marketing.manage-landing-pages', fn (User $user) => $user->isMarketingStaff());
        Gate::define('marketing.view-landing-page-submissions', fn (User $user) => $user->canViewMarketingModule());
        Gate::define('marketing.manage-landing-form-templates', fn (User $user) => $user->isMarketingStaff());

        // ─── CRM Gates ──────────────────────────────────────────────────
        Gate::define(
            'crm.view-companies',
            fn (User $user): bool => $user->canViewCrmModule()
                || $user->canViewSalesModule()
        );
        Gate::define('crm.view-leads', fn (User $user) => $user->canViewCrmModule());
        Gate::define('crm.assign-lead', fn (User $user) => $user->isCustomerServiceManager());
        Gate::define('crm.reassign-lead', fn (User $user) => $user->isCustomerServiceManager());
        Gate::define('crm.manage-company-owner', fn (User $user) => $user->isCustomerServiceManager());
        Gate::define('crm.process-lead', fn (User $user) => $user->isCustomerServiceStaff());
        Gate::define('crm.archive-lead', fn (User $user) => $user->isCustomerServiceManager());
        Gate::define('crm.manage-staff', fn (User $user) => $user->isAdmin());
        Gate::define('crm.manage-staff-availability', fn (User $user) => $user->isAdmin());

        // ─── Sales Gates ────────────────────────────────────────────────
        Gate::define(
            'sales.view-opportunities',
            fn (User $user): bool => $user->hasAnyRole([
                UserRole::Admin,
                UserRole::CustomerServiceManager,
                UserRole::SalesManager,
                UserRole::SalesStaff,
            ])
        );
        Gate::define(
            'sales.create-opportunities',
            fn (User $user): bool => $user->hasAnyRole([
                UserRole::Admin,
                UserRole::CustomerServiceManager,
                UserRole::SalesManager,
            ])
        );
        Gate::define(
            'sales.process-opportunities',
            fn (User $user): bool => $user->isSalesStaff()
        );

        Gate::define('sales.view-services', fn (User $user) => $user->isSalesStaff());
        Gate::define('sales.manage-services', fn (User $user) => $user->isSalesManager());

        Gate::define('sales.view-service-packages', fn (User $user) => $user->isSalesStaff());
        Gate::define('sales.manage-service-packages', fn (User $user) => $user->isSalesManager());

        Gate::define('sales.view-price-books', fn (User $user) => $user->isSalesStaff());
        Gate::define('sales.manage-price-books', fn (User $user) => $user->isSalesManager());
        Gate::define('sales.approve-price-books', fn (User $user) => $user->isSalesManager());

        Gate::define(
            'sales.view-bank-accounts',
            fn (User $user): bool => $user->isSalesManager()
                || $user->isFinanceStaff()
        );
        Gate::define('sales.manage-bank-accounts', fn (User $user) => $user->isFinanceStaff());

        Gate::define(
            'sales.view-quotations',
            fn (User $user): bool => $user->isSalesStaff()
                || $user->isFinanceStaff()
        );
        Gate::define('sales.create-quotations', fn (User $user) => $user->isSalesStaff());
        Gate::define('sales.send-quotations', fn (User $user) => $user->isSalesStaff());
        Gate::define('sales.approve-quotations', fn (User $user) => $user->isSalesManager());
        Gate::define('sales.revise-quotations', fn (User $user) => $user->isSalesManager());
        Gate::define('sales.cancel-quotations', fn (User $user) => $user->isSalesManager());
        Gate::define(
            'sales.export-quotations',
            fn (User $user): bool => $user->isSalesStaff()
                || $user->isFinanceStaff()
        );
        Gate::define('sales.verify-payments', fn (User $user) => $user->isFinanceStaff());

        // ─── Customer Care Gates ────────────────────────────────────────
        Gate::define('customer-care.view', fn (User $user) => $user->canViewCustomerCareModule());
        Gate::define('customer-care.interact', fn (User $user) => $user->isCustomerServiceStaff());
        Gate::define('customer-care.manage-assignments', fn (User $user) => $user->isCustomerServiceManager());
        Gate::define('customer-care.distribute', fn (User $user) => $user->isCustomerServiceManager());
        Gate::define('customer-care.rebalance', fn (User $user) => $user->isCustomerServiceManager());
    }
}
