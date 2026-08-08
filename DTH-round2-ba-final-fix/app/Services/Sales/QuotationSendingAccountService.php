<?php

namespace App\Services\Sales;

use App\Models\Marketing\SendingAccount;
use App\Models\Sales\Quotation;
use App\Services\Marketing\EmailSendingService;
use Illuminate\Validation\ValidationException;

final class QuotationSendingAccountService
{
    public function __construct(
        private readonly EmailSendingService $emailSending,
    ) {}

    public function resolve(Quotation $quotation): SendingAccount
    {
        $quotation->loadMissing('assignedStaff.department');
        $departmentId = $quotation->assignedStaff?->department_id;

        if ($departmentId === null) {
            throw ValidationException::withMessages([
                'email' => 'Báo giá chưa có nhân viên/phòng ban Kinh doanh phụ trách.',
            ]);
        }

        $account = SendingAccount::query()
            ->where('department_id', $departmentId)
            ->where('status', 'active')
            ->orderBy('id')
            ->first();

        if ($account === null) {
            throw ValidationException::withMessages([
                'email' => 'Phòng Kinh doanh chưa có tài khoản gửi email SMTP đang hoạt động.',
            ]);
        }

        $config = $account->config_encrypted ?? [];
        if (
            $account->provider !== 'smtp'
            || blank($config['host'] ?? null)
            || blank($config['username'] ?? null)
            || blank($config['password'] ?? null)
        ) {
            throw ValidationException::withMessages([
                'email' => 'Tài khoản gửi email của phòng Kinh doanh chưa cấu hình SMTP đầy đủ.',
            ]);
        }

        return $account;
    }

    public function configureMailer(SendingAccount $account): string
    {
        $mailer = 'quotation_'.$account->id;

        $this->emailSending->configureSmtpMailer(
            mailer: $mailer,
            config: $account->config_encrypted ?? [],
            fromAddress: $account->from_email,
            fromName: $account->from_name,
        );

        return $mailer;
    }
}
