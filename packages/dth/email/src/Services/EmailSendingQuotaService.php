<?php

namespace Dth\Email\Services;

use Dth\Email\Exceptions\EmailQuotaExceededException;
use Dth\Email\Models\EmailMessage;
use Dth\Email\Models\SendingAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class EmailSendingQuotaService
{
    public function reserve(EmailMessage $message, SendingAccount $account): void
    {
        if (! $this->isLimited($account) || ! Schema::hasTable('email_quota_reservations')) {
            return;
        }

        DB::transaction(function () use ($message, $account): void {
            SendingAccount::query()->whereKey($account->getKey())->lockForUpdate()->firstOrFail();

            $existing = DB::table('email_quota_reservations')
                ->where('email_message_id', $message->getKey())
                ->first();

            if ($existing && in_array($existing->status, ['reserved', 'consumed'], true)) {
                return;
            }

            $this->releaseStaleReservations($account);
            $this->assertAvailable($account);

            DB::table('email_quota_reservations')->updateOrInsert(
                ['email_message_id' => $message->getKey()],
                [
                    'sending_account_id' => $account->getKey(),
                    'status' => 'reserved',
                    'reserved_at' => now(),
                    'consumed_at' => null,
                    'released_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        });
    }

    public function consume(EmailMessage $message): void
    {
        if (! Schema::hasTable('email_quota_reservations')) {
            return;
        }

        DB::table('email_quota_reservations')
            ->where('email_message_id', $message->getKey())
            ->where('status', 'reserved')
            ->update([
                'status' => 'consumed',
                'consumed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function release(EmailMessage $message): void
    {
        if (! Schema::hasTable('email_quota_reservations')) {
            return;
        }

        DB::table('email_quota_reservations')
            ->where('email_message_id', $message->getKey())
            ->where('status', 'reserved')
            ->update([
                'status' => 'released',
                'released_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function remainingCapacity(SendingAccount $account): ?int
    {
        if (! $this->isLimited($account) || ! Schema::hasTable('email_quota_reservations')) {
            return null;
        }

        $this->releaseStaleReservations($account);
        $remaining = [];

        if ($account->hourly_limit) {
            $remaining[] = max(0, (int) $account->hourly_limit - $this->usedSince($account, now()->startOfHour()));
        }

        if ($account->daily_limit) {
            $remaining[] = max(0, (int) $account->daily_limit - $this->usedSince($account, now()->startOfDay()));
        }

        return $remaining === [] ? null : min($remaining);
    }

    private function assertAvailable(SendingAccount $account): void
    {
        if ($account->hourly_limit) {
            $used = $this->usedSince($account, now()->startOfHour());
            if ($used >= (int) $account->hourly_limit) {
                $retry = max(60, now()->diffInSeconds(now()->addHour()->startOfHour(), false));
                throw new EmailQuotaExceededException('Hourly sending limit reached for this account.', $retry);
            }
        }

        if ($account->daily_limit) {
            $used = $this->usedSince($account, now()->startOfDay());
            if ($used >= (int) $account->daily_limit) {
                $retry = max(60, now()->diffInSeconds(now()->addDay()->startOfDay(), false));
                throw new EmailQuotaExceededException('Daily sending limit reached for this account.', $retry);
            }
        }
    }

    private function usedSince(SendingAccount $account, mixed $since): int
    {
        return (int) DB::table('email_quota_reservations')
            ->where('sending_account_id', $account->getKey())
            ->whereIn('status', ['reserved', 'consumed'])
            ->where('reserved_at', '>=', $since)
            ->count();
    }

    private function releaseStaleReservations(SendingAccount $account): void
    {
        $minutes = max(5, (int) config('dth-email.operations.quota_reservation_ttl_minutes', 30));

        DB::table('email_quota_reservations')
            ->where('sending_account_id', $account->getKey())
            ->where('status', 'reserved')
            ->where('reserved_at', '<', now()->subMinutes($minutes))
            ->update([
                'status' => 'released',
                'released_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function isLimited(SendingAccount $account): bool
    {
        return filled($account->hourly_limit) || filled($account->daily_limit);
    }
}
