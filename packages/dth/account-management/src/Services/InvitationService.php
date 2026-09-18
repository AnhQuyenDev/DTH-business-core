<?php
namespace Dth\AccountManagement\Services;

use Dth\AccountManagement\Enums\InvitationStatus;
use Dth\AccountManagement\Models\AccountInvitation;
use Dth\AccountManagement\Models\AccountUser;
use Dth\AccountManagement\Notifications\AccountInvitationNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

final class InvitationService
{
    public function issue(AccountInvitation $invitation, bool $send = true): string
    {
        $plainToken = Str::random(64);
        $invitation->forceFill([
            'token_hash' => hash('sha256', $plainToken),
            'status' => InvitationStatus::Pending->value,
            'expires_at' => $invitation->expires_at ?? now()->addHours((int) config('dth-account-management.invitations.expires_hours', 72)),
            'sent_at' => $send ? now() : $invitation->sent_at,
        ])->save();

        $url = route('dth.account.invitation.accept', ['token' => $plainToken]);
        if ($send && config('dth-account-management.invitations.send_mail', true)) {
            try {
                Notification::route('mail', $invitation->email)
                    ->notify(new AccountInvitationNotification($url, $invitation->expires_at->format('d/m/Y H:i')));
            } catch (Throwable) {
                // Keep invitation usable even if mail transport is unavailable.
            }
        }

        return $url;
    }

    public function findByToken(string $token): AccountInvitation
    {
        $invitation = AccountInvitation::query()->where('token_hash', hash('sha256', $token))->first();
        if (! $invitation || $invitation->status !== InvitationStatus::Pending || $invitation->expires_at->isPast()) {
            throw new RuntimeException('Lời mời không hợp lệ hoặc đã hết hạn.');
        }
        return $invitation;
    }

    public function accept(AccountInvitation $invitation, string $name, string $password): AccountUser
    {
        $user = AccountUser::query()->where('email', $invitation->email)->first();
        if ($user) {
            if (! Hash::check($password, (string) $user->password)) {
                throw ValidationException::withMessages([
                    'password' => 'Email này đã có tài khoản. Hãy nhập mật khẩu hiện tại để xác nhận lời mời.',
                ]);
            }
        } else {
            $user = AccountUser::query()->create([
                'name' => $name,
                'email' => $invitation->email,
                'password' => $password,
                'preferred_locale' => config('dth-account-management.security.default_locale', 'vi'),
                'timezone' => config('dth-account-management.security.default_timezone', 'Asia/Ho_Chi_Minh'),
                'account_status' => 'active',
                'must_change_password' => false,
            ]);
        }

        $user->roles()->syncWithoutDetaching($invitation->roles()->pluck('account_roles.id')->all());
        $user->groups()->syncWithoutDetaching($invitation->groups()->pluck('account_groups.id')->all());

        $invitation->forceFill([
            'status' => InvitationStatus::Accepted->value,
            'accepted_at' => now(),
            'accepted_by_user_id' => $user->id,
        ])->save();

        return $user;
    }
}
