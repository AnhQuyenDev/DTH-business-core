<?php

namespace App\Support\Audit;

use App\Models\Marketing\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

final class AuditActivityPresenter
{
    public static function module(AuditLog $log): string
    {
        $action = (string) $log->action;

        $key = match (true) {
            $action === 'login' => 'auth',
            Str::startsWith($action, ['campaign.', 'template.', 'sending_account.', 'suppression.']) => 'email',
            Str::startsWith($action, ['landing_page.', 'form.', 'segment.', 'tag.', 'contact.']) => 'marketing',
            Str::startsWith($action, ['lead.', 'distribution.']) => 'crm',
            Str::startsWith($action, ['opportunity.', 'quotation.']) => 'sales',
            Str::startsWith($action, ['payment.']) => 'finance',
            Str::startsWith($action, ['customer.']) => 'customer_care',
            default => 'system',
        };

        return __('uiux.audit.module.'.$key);
    }

    public static function moduleKey(AuditLog $log): string
    {
        $action = (string) $log->action;

        return match (true) {
            $action === 'login' => 'auth',
            Str::startsWith($action, ['campaign.', 'template.', 'sending_account.', 'suppression.']) => 'email',
            Str::startsWith($action, ['landing_page.', 'form.', 'segment.', 'tag.', 'contact.']) => 'marketing',
            Str::startsWith($action, ['lead.', 'distribution.']) => 'crm',
            Str::startsWith($action, ['opportunity.', 'quotation.']) => 'sales',
            Str::startsWith($action, ['payment.']) => 'finance',
            Str::startsWith($action, ['customer.']) => 'customer_care',
            default => 'system',
        };
    }

    public static function activity(AuditLog $log): string
    {
        $key = 'uiux.audit.action.'.(string) $log->action;
        $translated = __($key);

        if ($translated !== $key) {
            return $translated;
        }

        return __('uiux.audit.other_activity');
    }

    public static function actor(AuditLog $log): string
    {
        return $log->user?->name ?: __('uiux.audit.system');
    }

    public static function subject(AuditLog $log): string
    {
        $model = $log->auditable;
        $type = class_basename((string) $log->auditable_type);
        $friendlyType = self::friendlyType($type);

        if (! $model instanceof Model) {
            return $friendlyType;
        }

        foreach ([
            'quotation_code', 'opportunity_code', 'lead_code', 'customer_code', 'batch_code',
            'payment_code', 'receipt_code', 'service_code', 'package_code', 'employee_code',
            'name', 'full_name', 'title', 'email', 'domain',
        ] as $attribute) {
            $value = $model->getAttribute($attribute);
            if (filled($value)) {
                return $friendlyType.' - '.$value;
            }
        }

        return $friendlyType;
    }

    public static function description(AuditLog $log): string
    {
        $actor = self::actor($log);
        $activity = Str::lower(self::activity($log));
        $subject = self::subject($log);

        if ((string) $log->action === 'login') {
            return $actor.' - '.self::activity($log);
        }

        if ($log->user_id === null) {
            return __('uiux.audit.system').' - '.self::activity($log).' - '.$subject;
        }

        return $actor.' - '.$activity.' - '.$subject;
    }

    public static function changes(AuditLog $log): array
    {
        $old = is_array($log->old_values) ? $log->old_values : [];
        $new = is_array($log->new_values) ? $log->new_values : [];
        $keys = array_values(array_unique(array_merge(array_keys($old), array_keys($new))));

        $hidden = [
            'password', 'remember_token', 'config_encrypted', 'smtp_password', 'token',
            'public_token', 'otp', 'otp_hash', 'metadata', 'user_agent',
        ];

        $rows = [];
        foreach ($keys as $key) {
            if (in_array($key, $hidden, true) || Str::contains($key, ['password', 'token', 'secret'])) {
                continue;
            }

            $before = Arr::get($old, $key);
            $after = Arr::get($new, $key);

            if ($before === $after) {
                continue;
            }

            $rows[] = [
                'field' => self::fieldLabel((string) $key),
                'before' => self::formatValue($before),
                'after' => self::formatValue($after),
            ];
        }

        return $rows;
    }

    private static function fieldLabel(string $field): string
    {
        $translationKey = 'field.'.$field;
        $translated = __($translationKey);

        return $translated !== $translationKey
            ? $translated
            : __('uiux.audit.other_field');
    }

    private static function formatValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if (is_bool($value)) {
            return $value ? __('common.yes') : __('common.no');
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '-';
        }

        return Str::limit((string) $value, 160);
    }

    private static function friendlyType(string $type): string
    {
        $map = [
            'User' => __('resource.user.singular'),
            'Contact' => __('resource.contact.singular'),
            'Lead' => __('resource.lead.singular'),
            'Customer' => __('resource.customer.singular'),
            'Company' => __('resource.company.singular'),
            'Opportunity' => __('resource.opportunity.singular'),
            'Quotation' => __('resource.quotation.singular'),
            'Payment' => __('finance.payment_history'),
            'Campaign' => __('resource.campaign.singular'),
            'EmailTemplate' => __('resource.email_template.singular'),
            'SendingAccount' => __('resource.sending_account.singular'),
        ];

        return $map[$type] ?? __('uiux.audit.business_record');
    }
}
