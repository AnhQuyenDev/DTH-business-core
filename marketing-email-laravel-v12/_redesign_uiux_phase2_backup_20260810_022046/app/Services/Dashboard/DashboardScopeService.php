<?php

namespace App\Services\Dashboard;

use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Finance\Payment;
use App\Models\Marketing\LandingPage;
use App\Models\Marketing\LandingPageSubmission;
use App\Models\Marketing\MarketingCampaign;
use App\Models\Sales\Opportunity;
use App\Models\Sales\Quotation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * A single place for dashboard/report data scoping.
 *
 * Manager dashboards receive department-level data while staff dashboards are
 * restricted to records they own/are assigned to. Admin/Executive/Viewer may
 * read across the business but workflows remain protected by policies/Gates.
 */
final class DashboardScopeService
{
    public function isCrossBusiness(User $user): bool
    {
        return $user->isAdmin() || $user->canReadAcrossBusiness();
    }

    public function marketingCampaigns(User $user): Builder
    {
        $query = MarketingCampaign::query();

        if ($this->isCrossBusiness($user) || $user->isMarketingManager()) {
            return $query;
        }

        return $query->where('created_by', $user->id);
    }

    public function landingPages(User $user): Builder
    {
        $query = LandingPage::query();

        if ($this->isCrossBusiness($user) || $user->isMarketingManager()) {
            return $query;
        }

        return $query->where('created_by', $user->id);
    }

    public function submissions(User $user): Builder
    {
        $query = LandingPageSubmission::query();

        if ($this->isCrossBusiness($user) || $user->isMarketingManager()) {
            return $query;
        }

        return $query->whereHas('landingPage', fn (Builder $q): Builder => $q->where('created_by', $user->id));
    }

    public function leads(User $user): Builder
    {
        $query = Lead::query();

        if ($this->isCrossBusiness($user) || $user->isCustomerServiceManager() || $user->isMarketingManager()) {
            return $query;
        }

        if ($user->isCustomerServiceStaff() && $user->staff) {
            return $query->where('assigned_staff_id', $user->staff->id);
        }

        if ($user->isMarketingStaff()) {
            return $query->whereHas('submission.landingPage', fn (Builder $q): Builder => $q->where('created_by', $user->id));
        }

        return $query->whereRaw('1 = 0');
    }

    public function opportunities(User $user): Builder
    {
        $query = Opportunity::query();

        if ($this->isCrossBusiness($user) || $user->isSalesManager()) {
            return $query;
        }

        if ($user->isSalesStaff() && $user->staff) {
            return $query->where('assigned_staff_id', $user->staff->id);
        }

        return $query->whereRaw('1 = 0');
    }

    public function quotations(User $user): Builder
    {
        $query = Quotation::query();

        if ($this->isCrossBusiness($user) || $user->isSalesManager() || $user->isFinanceStaff()) {
            return $query;
        }

        if ($user->isSalesStaff() && $user->staff) {
            return $query->where('assigned_staff_id', $user->staff->id);
        }

        return $query->whereRaw('1 = 0');
    }

    public function payments(User $user): Builder
    {
        $query = Payment::query();

        if ($this->isCrossBusiness($user) || $user->isFinanceStaff() || $user->isSalesManager() || $user->isMarketingManager()) {
            return $query;
        }

        if ($user->isSalesStaff() && $user->staff) {
            return $query->where('sales_staff_id', $user->staff->id);
        }

        if ($user->isMarketingStaff()) {
            return $query->whereHas('attribution.landingPage', fn (Builder $q): Builder => $q->where('created_by', $user->id));
        }

        return $query->whereRaw('1 = 0');
    }

    public function customers(User $user): Builder
    {
        $query = Customer::query();

        if ($this->isCrossBusiness($user) || $user->isCustomerServiceManager()) {
            return $query;
        }

        if ($user->isCustomerServiceStaff() && $user->staff) {
            return $query->whereHas('assignments', fn (Builder $q): Builder => $q
                ->where('staff_id', $user->staff->id)
                ->where('status', 'active'));
        }

        return $query->whereRaw('1 = 0');
    }
}
