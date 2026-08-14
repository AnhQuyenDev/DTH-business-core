<?php

namespace App\Providers;

use App\Contracts\Billing\ElectronicInvoiceProvider;
use App\Contracts\Tax\TaxCodeVerificationProvider;
use App\Events\Crm\ContactQualificationTransitioned;
use App\Events\Crm\LeadAssigned;
use App\Listeners\Crm\RecordContactQualificationTransitionAudit;
use App\Listeners\Crm\RecordLeadAssignmentAudit;
use App\Listeners\LogSuccessfulLogin;
use App\Models\Crm\Company;
use App\Models\Crm\Customer;
use App\Models\Crm\CustomerAssignment;
use App\Models\Crm\CustomerInteraction;
use App\Models\Crm\Lead;
use App\Models\Crm\Staff;
use App\Models\Marketing\LandingPageSubmission;
use App\Models\Sales\Opportunity;
use App\Models\Sales\PriceBook;
use App\Models\Sales\Quotation;
use App\Models\Sales\Service;
use App\Models\Sales\ServicePackage;
use App\Models\User;
use App\Observers\Crm\CustomerAssignmentObserver;
use App\Observers\Crm\LeadObserver;
use App\Observers\Crm\StaffObserver;
use App\Observers\Marketing\LandingPageSubmissionObserver;
use App\Policies\CompanyPolicy;
use App\Policies\CustomerAssignmentPolicy;
use App\Policies\CustomerInteractionPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\LeadPolicy;
use App\Policies\Sales\OpportunityPolicy;
use App\Policies\Sales\PriceBookPolicy;
use App\Policies\Sales\QuotationPolicy;
use App\Policies\Sales\ServicePackagePolicy;
use App\Policies\Sales\ServicePolicy;
use App\Services\Billing\NullElectronicInvoiceProvider;
use App\Services\Crm\FakeTaxVerificationProvider;
use App\Services\Security\RbacAuthorizationService;
use App\Services\Security\RbacDefinition;
use App\Services\Security\RbacSyncService;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TaxCodeVerificationProvider::class, FakeTaxVerificationProvider::class);
        $this->app->bind(ElectronicInvoiceProvider::class, NullElectronicInvoiceProvider::class);

        // Navigation and policy discovery can evaluate dozens of permissions in
        // one request. Reusing these services prevents the RBAC readiness check
        // from querying information_schema/permissions for every menu item.
        $this->app->singleton(RbacSyncService::class);
        $this->app->singleton(RbacAuthorizationService::class);
    }

    public function boot(): void
    {
        Staff::observe(StaffObserver::class);
        CustomerAssignment::observe(CustomerAssignmentObserver::class);
        LandingPageSubmission::observe(LandingPageSubmissionObserver::class);
        Lead::observe(LeadObserver::class);

        Event::listen(Login::class, LogSuccessfulLogin::class);
        Event::listen(LeadAssigned::class, RecordLeadAssignmentAudit::class);
        Event::listen(ContactQualificationTransitioned::class, RecordContactQualificationTransitionAudit::class);

        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(Lead::class, LeadPolicy::class);
        Gate::policy(Opportunity::class, OpportunityPolicy::class);
        Gate::policy(Service::class, ServicePolicy::class);
        Gate::policy(ServicePackage::class, ServicePackagePolicy::class);
        Gate::policy(PriceBook::class, PriceBookPolicy::class);
        Gate::policy(Quotation::class, QuotationPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(CustomerAssignment::class, CustomerAssignmentPolicy::class);
        Gate::policy(CustomerInteraction::class, CustomerInteractionPolicy::class);

        // Super Admin is the only unconditional business/system bypass.
        Gate::before(fn (User $user): ?bool => $user->isSuperAdmin() ? true : null);

        // Named capabilities are stored in DB through Spatie Permission. The
        // fallback keeps the V1 app usable during the first migration/sync.
        foreach (array_keys(RbacDefinition::permissions()) as $permission) {
            Gate::define($permission, fn (User $user): bool => app(RbacAuthorizationService::class)->allows($user, $permission));
        }
    }
}
