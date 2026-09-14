<?php

namespace Dth\Crm\Support;

final class StatusColor
{
    public static function for(mixed $status, ?string $context = null): string
    {
        $value = $status instanceof \BackedEnum ? $status->value : (string) $status;

        $contextColor = match ($context) {
            'contact_type' => match ($value) {
                'personal' => 'info',
                'business' => 'success',
                default => null,
            },
            'company_lifecycle' => match ($value) {
                'prospect' => 'info',
                'qualified', 'customer' => 'success',
                'inactive' => 'gray',
                default => null,
            },
            'lead_status' => match ($value) {
                'new', 'active', 'qualifying' => 'info',
                'duplicate' => 'warning',
                'spam' => 'danger',
                'closed' => 'gray',
                'qualified', 'converted', 'converted_to_opportunity' => 'success',
                default => null,
            },
            'qualification_status' => match ($value) {
                'new', 'assigned', 'contacting' => 'info',
                'follow_up', 'duplicate' => 'warning',
                'qualified', 'converted' => 'success',
                'unqualified', 'spam' => 'danger',
                'archived' => 'gray',
                default => null,
            },
            'customer_status' => match ($value) {
                'potential' => 'info',
                'active' => 'success',
                'inactive' => 'gray',
                'churned' => 'danger',
                default => null,
            },
            'priority' => match ($value) {
                'low' => 'gray',
                'normal' => 'info',
                'high' => 'warning',
                'urgent' => 'danger',
                default => null,
            },
            'employment_status' => match ($value) {
                'active' => 'success',
                'inactive', 'resigned' => 'gray',
                default => null,
            },
            'availability_status' => match ($value) {
                'working' => 'success',
                'remote', 'leave' => 'info',
                'half_day' => 'warning',
                'absent', 'sick' => 'danger',
                default => null,
            },
            'batch_status' => match ($value) {
                'draft' => 'gray',
                'processing' => 'info',
                'completed' => 'success',
                'failed' => 'danger',
                default => null,
            },
            'assignment_status' => match ($value) {
                'active' => 'success',
                'ended', 'cancelled' => 'gray',
                default => null,
            },
            'match_status' => match ($value) {
                'pending' => 'warning',
                'accepted' => 'success',
                'rejected' => 'danger',
                default => null,
            },
            default => null,
        };

        if ($contextColor !== null) {
            return $contextColor;
        }

        return match ($value) {
            'active', 'working', 'qualified', 'converted', 'converted_to_opportunity',
            'customer', 'accepted', 'completed', 'verified', 'business' => 'success',

            'personal', 'new', 'assigned', 'contacting', 'prospect', 'potential',
            'processing', 'remote', 'reviewing', 'normal' => 'info',

            'follow_up', 'pending', 'duplicate', 'high', 'half_day' => 'warning',

            'draft', 'archived', 'inactive', 'closed', 'ended', 'cancelled',
            'resigned', 'low', 'leave' => 'gray',

            'spam', 'unqualified', 'rejected', 'failed', 'churned', 'urgent',
            'error', 'absent', 'sick' => 'danger',

            default => 'gray',
        };
    }
}
