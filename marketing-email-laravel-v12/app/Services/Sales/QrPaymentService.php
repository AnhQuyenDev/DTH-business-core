<?php

namespace App\Services\Sales;

use App\Models\CompanySetting;
use App\Models\VnBank;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class QrPaymentService
{
    private const API_ENDPOINT = 'https://api.vietqr.io/v2/generate';

    public function generateUrl(
        string $bankCode,
        string $accountNumber,
        ?float $amount = null,
        ?string $content = null,
        ?string $accountName = null,
    ): string {
        $bankCode = strtoupper($bankCode);
        $qrContent = "{$bankCode}-{$accountNumber}";

        $params = http_build_query(array_filter([
            'amount' => $amount > 0 ? (int) $amount : null,
            'addInfo' => $content ? $this->sanitizeContent($content) : null,
            'accountName' => $accountName,
        ]));

        $url = "https://img.vietqr.io/image/{$qrContent}-compact.png";
        if ($params) {
            $url .= '?' . $params;
        }

        return $url;
    }

    public function generateHtml(
        string $bankCode,
        string $accountNumber,
        ?float $amount = null,
        ?string $content = null,
        ?string $accountName = null,
        int $width = 200,
        ?string $template = null,
    ): string {
        $src = $this->generateDataUri($bankCode, $accountNumber, $amount, $content, $accountName, $template);
        $src ??= $this->generateUrl($bankCode, $accountNumber, $amount, $content, $accountName);

        return sprintf(
            '<img src="%s" alt="QR thanh toán" width="%d" style="width:%dpx;height:%dpx">',
            htmlspecialchars($src),
            $width,
            $width,
            $width,
        );
    }

    public function buildTransferContent(string $quotationCode, string $customerName): string
    {
        $cleanName = $this->sanitizeContent($customerName);
        $maxLen = 50 - strlen($quotationCode) - 1;
        if (mb_strlen($cleanName) > $maxLen) {
            $cleanName = mb_substr($cleanName, 0, $maxLen);
        }
        return "{$quotationCode} {$cleanName}";
    }

    /**
     * Generate a QR image via the VietQR v2 API. Returns a base64 data URI string
     * (ready to embed into HTML or PDF), or null so callers can fall back to the
     * public image endpoint.
     *
     * REQUIRES the bank's BIN (acqId) to be resolved from the synced bank list.
     * Credentials (x-client-id / x-api-key) are optional; the API currently
     * accepts unauthenticated requests.
     */
    private function generateDataUri(
        string $bankCode,
        string $accountNumber,
        ?float $amount = null,
        ?string $content = null,
        ?string $accountName = null,
        ?string $template = null,
    ): ?string {
        $bin = VnBank::query()->where('code', strtoupper($bankCode))->value('bin');

        if (!$bin || !preg_match('/^\d{6}$/', (string) $bin)) {
            return null;
        }

        $accountNo = preg_replace('/\D/', '', (string) $accountNumber);
        if (strlen($accountNo) < 6 || strlen($accountNo) > 19) {
            return null;
        }

        $template = $template ?: 'compact';
        $cacheKey = 'vietqr.v2.' . hash('sha256', implode('|', [
            $bankCode,
            $accountNo,
            (int) ($amount ?? 0),
            (string) $content,
            (string) ($accountName ?? ''),
            $template,
        ]));

        return Cache::remember($cacheKey, now()->addHours(12), function () use (
            $accountNo,
            $amount,
            $content,
            $accountName,
            $bin,
            $template,
        ): ?string {
            $settings = CompanySetting::firstOrCreateDefault();
            $clientId = $settings->vietqr_client_id;
            $apiKey = $settings->vietqr_api_key;

            $payload = array_merge([
                'accountNo' => $accountNo,
                'acqId' => $bin,
                'template' => $template,
            ], array_filter([
                'amount' => $amount > 0 ? (int) $amount : null,
                'addInfo' => $content ? $this->sanitizeAddInfo($content) : null,
                'accountName' => $accountName ? $this->sanitizeAccountName($accountName) : null,
            ]));

            $headers = ['Content-Type' => 'application/json'];
            if (filled($clientId)) {
                $headers['x-client-id'] = $clientId;
            }
            if (filled($apiKey)) {
                $headers['x-api-key'] = $apiKey;
            }

            try {
                $response = Http::timeout(8)
                    ->withHeaders($headers)
                    ->post(self::API_ENDPOINT, $payload);

                if ($response->ok() && $response->json('code') === '00') {
                    $data = $response->json('data') ?? [];
                    if (!empty($data['qrDataURL'])) {
                        return $data['qrDataURL'];
                    }
                }
            } catch (\Throwable) {
                // fall through to the image endpoint
            }

            return null;
        });
    }

    /**
     * Account name for the API: uppercase, Vietnamese diacritics removed,
     * no special characters, 5-50 characters.
     */
    private function sanitizeAccountName(string $accountName): string
    {
        $name = strtoupper(Str::ascii($accountName));
        $name = preg_replace('/[^A-Z0-9\s\.]/u', '', $name) ?? '';
        $name = trim(preg_replace('/\s+/', ' ', $name) ?? '');

        if (mb_strlen($name) < 5) {
            return mb_str_pad($name, 5, 'X');
        }

        return mb_substr($name, 0, 50);
    }

    /**
     * Transfer content for the API: max 25 chars, no Vietnamese accents,
     * only alphanumeric characters (spaces are kept).
     */
    private function sanitizeAddInfo(string $text): string
    {
        $text = Str::ascii($text);
        $text = preg_replace('/[^A-Za-z0-9 ]/', '', $text) ?? '';
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');

        return mb_substr($text, 0, 25);
    }

    private function sanitizeContent(string $text): string
    {
        $text = preg_replace('/[^A-Za-z0-9\s\.\-_\/]/u', '', $text);
        $text = trim(preg_replace('/\s+/', ' ', $text));
        return mb_substr($text, 0, 50);
    }
}