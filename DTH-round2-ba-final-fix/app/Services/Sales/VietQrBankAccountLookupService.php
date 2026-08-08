<?php

namespace App\Services\Sales;

use App\Models\CompanySetting;
use App\Models\VnBank;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

final class VietQrBankAccountLookupService
{
    private const ENDPOINT = 'https://api.vietqr.io/v2/lookup';

    public function lookup(string $bankCode, string $accountNumber): string
    {
        $accountNumber = preg_replace('/\D+/', '', $accountNumber) ?? '';

        if (! preg_match('/^\d{6,19}$/', $accountNumber)) {
            throw ValidationException::withMessages([
                'account_number' => 'Số tài khoản phải gồm 6 đến 19 chữ số.',
            ]);
        }

        $bank = VnBank::query()
            ->where('code', strtoupper($bankCode))
            ->first();

        if ($bank === null || ! preg_match('/^\d{6}$/', (string) $bank->bin)) {
            throw ValidationException::withMessages([
                'bank_code' => 'Ngân hàng chưa có mã BIN hợp lệ. Hãy đồng bộ danh sách ngân hàng.',
            ]);
        }

        $settings = CompanySetting::firstOrCreateDefault();

        if (blank($settings->vietqr_client_id) || blank($settings->vietqr_api_key)) {
            throw ValidationException::withMessages([
                'account_number' => 'Chưa cấu hình VietQR Client ID/API Key để tra cứu tên tài khoản.',
            ]);
        }

        $response = Http::timeout(10)
            ->withHeaders([
                'x-client-id' => $settings->vietqr_client_id,
                'x-api-key' => $settings->vietqr_api_key,
                'Content-Type' => 'application/json',
            ])
            ->post(self::ENDPOINT, [
                'bin' => (int) $bank->bin,
                'accountNumber' => $accountNumber,
            ]);

        if (! $response->ok() || $response->json('code') !== '00') {
            throw ValidationException::withMessages([
                'account_number' => $response->json('desc')
                    ?: 'Không thể xác thực tài khoản ngân hàng qua VietQR.',
            ]);
        }

        $name = trim((string) $response->json('data.accountName'));

        if ($name === '') {
            throw ValidationException::withMessages([
                'account_number' => 'VietQR không trả về tên chủ tài khoản.',
            ]);
        }

        return $name;
    }
}
