<?php

namespace App\Services\Sales;

use App\Models\Sales\PriceBook;
use App\Models\Sales\PriceBookAccessRule;
use App\Models\Sales\PriceBookItem;
use App\Models\User;
use Illuminate\Support\Collection;

class PriceBookAccessService
{
    public function getAccessiblePriceBooks(User $user, string $customerType = 'personal'): Collection
    {
        if ($user->isAdmin()) {
            return PriceBook::where('status', 'active')
                ->whereDate('valid_from', '<=', now())
                ->where(function ($q) {
                    $q->whereDate('valid_until', '>=', now())
                        ->orWhereNull('valid_until');
                })
                ->whereIn('audience_type', [$customerType, 'both'])
                ->orderBy('name')
                ->get();
        }

        $staff = $user->staff;
        if (! $staff) {
            return collect();
        }

        $accessibleIds = PriceBookAccessRule::where(function ($q) use ($staff, $user) {
            $q->where('access_type', 'all')
                ->orWhere(function ($q) use ($staff) {
                    $q->where('access_type', 'staff')->where('staff_id', $staff->id);
                })
                ->orWhere(function ($q) use ($user) {
                    $q->where('access_type', 'role')
                        ->whereIn('role', $user->effectiveRoleKeys());
                })
                ->orWhere(function ($q) use ($staff) {
                    $q->where('access_type', 'department')->where('department', $staff->department?->code);
                });
        })->where('can_view', true)
            ->pluck('price_book_id');

        return PriceBook::whereIn('id', $accessibleIds)
            ->where('status', 'active')
            ->whereDate('valid_from', '<=', now())
            ->where(function ($q) {
                $q->whereDate('valid_until', '>=', now())->orWhereNull('valid_until');
            })
            ->whereIn('audience_type', [$customerType, 'both'])
            ->orderBy('name')
            ->get();
    }

    public function canCreateQuotation(User $user, PriceBook $priceBook): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $staff = $user->staff;
        if (! $staff) {
            return false;
        }

        return PriceBookAccessRule::where('price_book_id', $priceBook->id)
            ->where(function ($q) use ($staff, $user) {
                $q->where('access_type', 'all')
                    ->orWhere(function ($q) use ($staff) {
                        $q->where('access_type', 'staff')->where('staff_id', $staff->id);
                    })
                    ->orWhere(function ($q) use ($user) {
                        $q->where('access_type', 'role')
                            ->whereIn('role', $user->effectiveRoleKeys());
                    })
                    ->orWhere(function ($q) use ($staff) {
                        $q->where('access_type', 'department')->where('department', $staff->department?->code);
                    });
            })
            ->where('can_create_quotation', true)
            ->exists();
    }

    public function getDiscountLimit(User $user, PriceBookItem $item): ?float
    {
        if ($user->isAdmin()) {
            return null;
        }

        $staff = $user->staff;
        if (! $staff) {
            return 0;
        }

        $rule = PriceBookAccessRule::where('price_book_id', $item->price_book_id)
            ->where(function ($q) use ($staff, $user) {
                $q->where('access_type', 'all')
                    ->orWhere('staff_id', $staff->id)
                    ->orWhereIn('role', $user->effectiveRoleKeys())
                    ->orWhere('department', $staff->department?->code);
            })
            ->whereNotNull('discount_limit_value')
            ->first();

        return $rule?->discount_limit_value;
    }
}
