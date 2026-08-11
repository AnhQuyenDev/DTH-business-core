<?php

namespace App\Services\Security;

use App\Models\User;

class RbacAuthorizationService
{
    public function allows(User $user, string $permission): bool
    {
        if ($user->isSuperAdmin()) return true;

        try {
            if (app(RbacSyncService::class)->ready()) {
                return $user->hasPermissionTo($permission, 'web');
            }
        } catch (\Throwable) {
            // Use legacy compatibility while the RBAC tables are being installed.
        }

        return $this->legacyFallback($user, $permission);
    }

    private function legacyFallback(User $user, string $permission): bool
    {
        if (str_starts_with($permission, 'marketing.view-')) return $user->canViewMarketingModule();
        if ($permission === 'marketing.send-campaigns') return $user->isMarketingManager();
        if ($permission === 'marketing.manage-sending') return $user->isAdmin();
        if (in_array($permission, ['marketing.view-suppression','marketing.manage-suppression','marketing.export-data'], true)) return $user->isMarketingManager();
        if (str_starts_with($permission, 'marketing.manage-')) return $user->isMarketingStaff();

        if (in_array($permission, ['crm.view-companies','crm.view-leads'], true)) return $user->canViewCrmModule() || $user->canViewSalesModule();
        if (in_array($permission, ['crm.assign-lead','crm.reassign-lead','crm.manage-company-owner','crm.archive-lead'], true)) return $user->isSalesManager();
        if ($permission === 'crm.process-lead') return $user->isSalesStaff();
        if (in_array($permission, ['crm.manage-staff','crm.manage-staff-availability'], true)) return $user->isAdmin();

        if (str_starts_with($permission, 'sales.view-')) return $user->canViewSalesModule() || $user->isMarketingStaff();
        if (in_array($permission, ['sales.manage-services','sales.manage-products','sales.manage-service-packages','sales.manage-price-books','sales.approve-price-books'], true)) return $user->isSalesManager();
        if ($permission === 'sales.manage-bank-accounts') return $user->isFinanceStaff();
        if (in_array($permission, ['sales.create-opportunities','sales.process-opportunities','sales.create-quotations','sales.send-quotations','sales.revise-quotations','sales.record-customer-response','sales.export-quotations'], true)) return $user->isSalesStaff();
        if (in_array($permission, ['sales.approve-quotations','sales.cancel-quotations'], true)) return $user->isSalesManager();
        if ($permission === 'sales.verify-payments') return $user->isFinanceStaff();

        if ($permission === 'customer-care.view' || $permission === 'customer-care.view-tickets') return $user->canViewCustomerCareModule();
        if (in_array($permission, ['customer-care.interact','customer-care.manage-tickets'], true)) return $user->isCustomerServiceStaff();
        if (in_array($permission, ['customer-care.manage-assignments','customer-care.distribute','customer-care.rebalance'], true)) return $user->isCustomerServiceManager();

        if (str_starts_with($permission, 'system.')) return $user->isAdmin();

        return false;
    }
}
