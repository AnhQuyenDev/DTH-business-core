<?php

namespace App\Filament\Pages;

use App\Enums\Sales\QuotationStatus;
use App\Models\Sales\Quotation;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class SalesDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.sales-dashboard';

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.sales');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.sales_dashboard');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && (
            $user->isAdmin()
            || $user->canReadAcrossBusiness()
            || $user->isSalesManager()
        );
    }

    public function getTitle(): string
    {
        return __('navigation.sales_dashboard');
    }

    protected function getViewData(): array
    {
        return [
            'summary' => $this->getSummaryStats(),
            'conversion' => $this->getConversionRates(),
            'followUp' => $this->getStaffFollowUpNeeded(),
        ];
    }

    public function getSummaryStats(): array
    {
        $query = Quotation::query();

        $total = (clone $query)->count();
        $totalValue = (clone $query)->sum('grand_total');

        return [
            'total' => $total,
            'draft' => (clone $query)->where('status', QuotationStatus::Draft)->count(),
            'pending_approval' => (clone $query)->where('status', QuotationStatus::PendingApproval)->count(),
            'approved' => (clone $query)->where('status', QuotationStatus::Approved)->count(),
            'sent' => (clone $query)->where('status', QuotationStatus::Sent)->count(),
            'viewed' => (clone $query)->where('status', QuotationStatus::Viewed)->count(),
            'accepted' => (clone $query)->where('status', QuotationStatus::Accepted)->count(),
            'rejected' => (clone $query)->where('status', QuotationStatus::Rejected)->count(),
            'expired' => (clone $query)->where('status', QuotationStatus::Expired)->count(),
            'cancelled' => (clone $query)->where('status', QuotationStatus::Cancelled)->count(),
            'total_value' => $totalValue,
            'accepted_value' => (clone $query)->where('status', QuotationStatus::Accepted)->sum('grand_total'),
            'accepted_unpaid' => (clone $query)
                ->where('status', QuotationStatus::Accepted)
                ->where('payment_status', 'unpaid')
                ->count(),
            'expiring_soon' => (clone $query)
                ->whereIn('status', [QuotationStatus::Approved, QuotationStatus::Sent, QuotationStatus::Viewed])
                ->whereDate('valid_until', '<=', now()->addDays(7))
                ->whereDate('valid_until', '>=', now())
                ->count(),
        ];
    }

    public function getConversionRates(): array
    {
        $query = Quotation::query();
        $sent = (clone $query)->whereIn('status', [QuotationStatus::Sent, QuotationStatus::Viewed, QuotationStatus::Accepted, QuotationStatus::Rejected])->count();
        $viewed = (clone $query)->whereIn('status', [QuotationStatus::Viewed, QuotationStatus::Accepted])->count();
        $accepted = (clone $query)->where('status', QuotationStatus::Accepted)->count();

        return [
            'sent_to_viewed' => $sent > 0 ? round(($viewed / $sent) * 100, 1) : 0,
            'viewed_to_accepted' => $viewed > 0 ? round(($accepted / $viewed) * 100, 1) : 0,
        ];
    }

    public function getStaffFollowUpNeeded(): array
    {
        return Quotation::query()
            ->select('assigned_staff_id', DB::raw('count(*) as count'))
            ->whereIn('status', [
                QuotationStatus::Sent,
                QuotationStatus::Viewed,
                QuotationStatus::RevisionRequested,
            ])
            ->orWhere(function ($q) {
                $q->where('status', QuotationStatus::Accepted)
                    ->where('payment_status', 'unpaid');
            })
            ->groupBy('assigned_staff_id')
            ->with('assignedStaff.user')
            ->get()
            ->map(fn ($item) => [
                'staff' => $item->assignedStaff?->full_name ?? __('common.not_available'),
                'count' => $item->count,
            ])
            ->toArray();
    }
}
