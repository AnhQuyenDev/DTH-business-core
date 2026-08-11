<?php

namespace App\Support\Ui;

use App\Enums\UserRole;
use App\Enums\Crm\DepartmentFunction;
use App\Models\System\UiBadgeStyle;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class BadgePalette
{
    private static ?bool $tableAvailable = null;

    public const COLORS = SystemColorPalette::COLORS;

    public static function color(string $category, string $key, string $fallback = 'gray'): string
    {
        $fallback = SystemColorPalette::normalize($fallback);

        try {
            self::$tableAvailable ??= Schema::hasTable('ui_badge_styles');

            if (! self::$tableAvailable) {
                return $fallback;
            }

            $cacheKey = 'ui.badge.'.$category.'.'.$key;

            $configured = Cache::remember($cacheKey, now()->addMinutes(10), fn (): ?string =>
                UiBadgeStyle::query()
                    ->where('category', $category)
                    ->where('key', $key)
                    ->value('color')
            );

            return SystemColorPalette::isSupported($configured) ? $configured : $fallback;
        } catch (\Throwable) {
            return $fallback;
        }
    }

    /**
     * Business statuses use a fixed semantic palette. Keeping status colors
     * deterministic prevents one screen from showing "Paid" as green while
     * another administrator changes the same meaning to an arbitrary color.
     */
    public static function status(string|\BackedEnum|null $state, ?string $fallback = null): string
    {
        $key = $state instanceof \BackedEnum ? (string) $state->value : (string) $state;

        return SystemColorPalette::normalize($fallback ?? self::defaultStatusColor($key));
    }

    public static function role(string|UserRole|null $role, string $fallback = 'gray'): string
    {
        $key = $role instanceof UserRole ? $role->value : (string) $role;

        return self::color('role', $key, $fallback);
    }

    public static function departmentFunction(string|DepartmentFunction|null $function, string $fallback = 'gray'): string
    {
        $key = $function instanceof DepartmentFunction ? $function->value : (string) $function;

        return self::color('department_function', $key, $fallback);
    }

    public static function audience(string|\BackedEnum|null $audience, ?string $fallback = null): string
    {
        $key = $audience instanceof \BackedEnum ? (string) $audience->value : (string) $audience;

        $fallback ??= match ($key) {
            'personal' => 'info',
            'business' => 'warning',
            'generic' => 'gray',
            default => 'gray',
        };

        return self::color('audience', $key, $fallback);
    }

    public static function defaultStatusColor(string $key): string
    {
        return match ($key) {
            // Neutral / not started.
            '', 'draft', 'new', 'inactive', 'archived', 'skipped', 'unknown' => 'gray',

            // Informational / assigned / observed.
            'assigned', 'viewed', 'sent', 'delivered', 'opened', 'clicked', 'in_progress', 'processing',
            'contacting', 'processed', 'received', 'rescheduled', 'initial', 'new_customer', 'staff_return' => 'info',

            // Positive / completed.
            'active', 'approved', 'accepted', 'qualified', 'converted', 'published', 'completed',
            'paid', 'verified', 'won', 'success', 'retained', 'subscribed', 'available', 'confirmed_fit' => 'success',

            // Attention / waiting.
            'pending', 'pending_approval', 'pending_verification', 'unpaid', 'testing', 'scheduled',
            'preparing', 'sending', 'paused', 'follow_up', 'onboarding', 'nurturing', 'at_risk',
            'queued', 'revision_requested', 'potential', 'staff_absence', 'rebalance', 'manual', 'confirmed_unfit' => 'warning',

            // Negative / stopped.
            'failed', 'rejected', 'cancelled', 'canceled', 'expired', 'lost', 'unqualified', 'spam',
            'duplicate', 'blocked', 'bounced', 'complained', 'unsubscribed', 'churned', 'no_show',
            'do_not_contact', 'mismatch', 'reverted' => 'danger',

            // Special states.
            'refunded' => 'warning',
            default => 'gray',
        };
    }

    /** @return array<string, string> */
    public static function colorOptions(): array
    {
        return SystemColorPalette::options();
    }

    /** @return array<string, string> */
    public static function categoryOptions(): array
    {
        return [
            'status' => __('configuration.appearance.categories.status'),
            'role' => __('configuration.appearance.categories.role'),
            'department_function' => __('configuration.appearance.categories.department_function'),
            'audience' => __('configuration.appearance.categories.audience'),
        ];
    }

    /** @return array<string, string> */
    public static function editableCategoryOptions(): array
    {
        return collect(self::categoryOptions())->except('status')->all();
    }

    /** @return array<string, string> */
    public static function keyOptions(string $category): array
    {
        return match ($category) {
            'role' => collect(UserRole::cases())
                ->mapWithKeys(fn (UserRole $role): array => [$role->value => $role->label()])
                ->all(),
            'department_function' => collect(DepartmentFunction::cases())
                ->mapWithKeys(fn (DepartmentFunction $function): array => [$function->value => $function->label()])
                ->all(),
            'audience' => [
                'personal' => __('uiux.audience.personal'),
                'business' => __('uiux.audience.business'),
                'generic' => __('uiux.audience.generic'),
            ],
            'status' => collect(self::knownStatuses())
                ->mapWithKeys(fn (string $status): array => [$status => self::statusLabel($status)])
                ->all(),
            default => [],
        };
    }

    /** @return array<int, string> */
    public static function knownStatuses(): array
    {
        return [
            'draft', 'new', 'inactive', 'archived', 'pending', 'pending_approval', 'pending_verification',
            'assigned', 'contacting', 'follow_up', 'in_progress', 'qualified', 'unqualified', 'converted',
            'active', 'testing', 'scheduled', 'preparing', 'sending', 'queued', 'sent', 'delivered', 'viewed',
            'opened', 'clicked', 'published', 'approved', 'accepted', 'revision_requested', 'completed', 'paid',
            'unpaid', 'verified', 'won', 'lost', 'potential', 'onboarding', 'nurturing', 'retained', 'at_risk',
            'failed', 'rejected', 'cancelled', 'expired', 'spam', 'duplicate', 'blocked', 'bounced', 'complained',
            'subscribed', 'unsubscribed', 'do_not_contact', 'churned', 'refunded', 'skipped', 'processed',
            'received', 'rescheduled', 'no_show', 'available', 'paused', 'mismatch', 'processing', 'reverted',
            'initial', 'new_customer', 'staff_return', 'staff_absence', 'rebalance', 'manual', 'confirmed_fit', 'confirmed_unfit',
        ];
    }

    public static function statusLabel(string $status): string
    {
        foreach ([
            'enum.quotation_status.', 'enum.payment_status.', 'enum.payment_notice_status.',
            'enum.campaign_status.', 'enum.campaign_recipient_status.', 'enum.landing_page_status.',
            'enum.form_template_status.', 'enum.sending_account_status.', 'enum.qualification.',
            'enum.status.', 'enum.customer_status.', 'enum.lifecycle.', 'enum.opportunity_stage.',
        ] as $prefix) {
            $key = $prefix.$status;
            $translated = __($key);
            if ($translated !== $key) {
                return $translated;
            }
        }

        return Str::of($status)->replace('_', ' ')->headline()->toString();
    }
}
