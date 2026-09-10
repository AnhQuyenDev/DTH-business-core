<?php

namespace Dth\Email\Services;

use Dth\Email\DTO\SmtpAccountData;
use Dth\Email\Models\SendingAccount;
use Dth\Email\Models\SendingDomain;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class SendingAccountService
{
    public function create(array $attributes, SmtpAccountData $smtp): SendingAccount
    {
        $this->validateBusinessRules($attributes);

        return SendingAccount::query()->create([
            ...Arr::only($attributes, [
                'sending_domain_id',
                'name',
                'provider',
                'from_name',
                'from_email',
                'reply_to',
                'daily_limit',
                'hourly_limit',
                'status',
            ]),
            'provider' => $attributes['provider'] ?? 'smtp',
            'encrypted_config' => $smtp->toArray(),
        ]);
    }

    public function update(SendingAccount $account, array $attributes, SmtpAccountData $smtp): SendingAccount
    {
        $this->validateBusinessRules($attributes, $account);

        $account->fill([
            ...Arr::only($attributes, [
                'sending_domain_id',
                'name',
                'provider',
                'from_name',
                'from_email',
                'reply_to',
                'daily_limit',
                'hourly_limit',
                'status',
            ]),
            'provider' => $attributes['provider'] ?? $account->provider ?? 'smtp',
            'encrypted_config' => $smtp->toArray(),
        ])->save();

        return $account->refresh();
    }

    private function validateBusinessRules(array $attributes, ?SendingAccount $account = null): void
    {
        $provider = $attributes['provider'] ?? $account?->provider ?? 'smtp';

        if ($provider !== 'smtp') {
            throw new InvalidArgumentException('Phase 1 currently supports SMTP sending accounts only.');
        }

        $fromEmail = mb_strtolower(trim((string) ($attributes['from_email'] ?? $account?->from_email)));

        if (! filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('A valid From email address is required.');
        }

        $domainId = $attributes['sending_domain_id'] ?? $account?->sending_domain_id;

        if ($domainId) {
            $sendingDomain = SendingDomain::query()->findOrFail($domainId);
            $fromDomain = mb_strtolower((string) str($fromEmail)->afterLast('@'));
            $registered = mb_strtolower($sendingDomain->domain);

            if ($fromDomain !== $registered && ! str_ends_with($fromDomain, '.'.$registered)) {
                throw new InvalidArgumentException("From email domain [{$fromDomain}] does not belong to sending domain [{$registered}].");
            }
        }
    }
}
