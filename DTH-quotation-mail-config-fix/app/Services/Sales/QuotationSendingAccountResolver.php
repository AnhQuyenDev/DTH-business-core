<?php

namespace App\Services\Sales;

use App\Models\Marketing\SendingAccount;
use App\Models\Sales\Quotation;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class QuotationSendingAccountResolver
{
    public function resolve(
        Quotation $quotation,
        ?User $actor = null,
        ?int $preferredSendingAccountId = null,
    ): SendingAccount {
        $quotation->loadMissing([
            'assignedStaff.department',
            'opportunity.assignedStaff.department',
        ]);

        $departmentId = $quotation->assignedStaff?->department_id
            ?? $quotation->opportunity?->assignedStaff?->department_id
            ?? $actor?->staff?->department_id;

        if ($departmentId === null) {
            throw ValidationException::withMessages([
                'sending_account' => 'Không xác định được phòng ban của người phụ trách báo giá để chọn tài khoản gửi email.',
            ]);
        }

        $query = SendingAccount::query()
            ->where('department_id', $departmentId)
            ->where('status', 'active');

        if ($preferredSendingAccountId !== null) {
            $account = (clone $query)
                ->whereKey($preferredSendingAccountId)
                ->first();

            if (! $account) {
                throw ValidationException::withMessages([
                    'sending_account' => 'Tài khoản gửi email được chọn không thuộc phòng ban phụ trách hoặc đang không hoạt động.',
                ]);
            }

            return $account;
        }

        $accounts = $query->orderBy('id')->get();

        if ($accounts->isEmpty()) {
            throw ValidationException::withMessages([
                'sending_account' => 'Phòng ban phụ trách chưa có tài khoản gửi email đang hoạt động.',
            ]);
        }

        if ($accounts->count() > 1) {
            throw ValidationException::withMessages([
                'sending_account' => 'Phòng ban phụ trách đang có nhiều tài khoản gửi email hoạt động. Hãy chỉ định tài khoản gửi khi gửi báo giá.',
            ]);
        }

        return $accounts->first();
    }
}
