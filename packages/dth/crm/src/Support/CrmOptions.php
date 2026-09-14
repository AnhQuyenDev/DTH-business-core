<?php

namespace Dth\Crm\Support;

final class CrmOptions
{
    /** @return array<string, string> */
    public static function contactTypes(): array
    {
        return self::localized('contact_type', [
            'personal' => 'Personal',
            'business' => 'Business',
        ]);
    }

    /** @return array<string, string> */
    public static function companyLifecycleStages(): array
    {
        return self::localized('company_lifecycle', [
            'prospect' => 'Prospect',
            'qualified' => 'Qualified',
            'customer' => 'Customer',
            'inactive' => 'Inactive',
        ]);
    }

    /** @return array<string, string> */
    public static function leadStatuses(): array
    {
        return self::localized('lead_status', [
            'new' => 'New',
            'active' => 'In progress',
            'duplicate' => 'Duplicate',
            'spam' => 'Spam',
            'closed' => 'Closed',
            'qualifying' => 'Qualifying',
            'qualified' => 'Qualified',
            'converted' => 'Converted',
            'converted_to_opportunity' => 'Converted to opportunity',
        ]);
    }

    /** @return array<string, string> */
    public static function qualificationStatuses(): array
    {
        return self::localized('qualification_status', [
            'new' => 'New',
            'assigned' => 'Assigned',
            'contacting' => 'Contacting',
            'follow_up' => 'Follow up',
            'qualified' => 'Qualified',
            'unqualified' => 'Unqualified',
            'converted' => 'Converted',
            'duplicate' => 'Duplicate',
            'spam' => 'Spam',
            'archived' => 'Archived',
        ]);
    }

    /** @return array<string, string> */
    public static function customerStatuses(): array
    {
        return self::localized('customer_status', [
            'potential' => 'Potential',
            'active' => 'Active',
            'inactive' => 'Inactive',
            'churned' => 'Churned',
        ]);
    }

    /** @return array<string, string> */
    public static function budgetStatuses(): array
    {
        return self::localized('budget_status', [
            'confirmed' => 'Confirmed',
            'estimated' => 'Estimated',
            'unknown' => 'Unknown',
        ]);
    }

    /** @return array<string, string> */
    public static function purchaseTimelines(): array
    {
        return self::localized('purchase_timeline', [
            'immediate' => 'Immediate',
            'within_1_month' => 'Within 1 month',
            'within_3_months' => 'Within 3 months',
            'within_6_months' => 'Within 6 months',
            'later' => 'Later',
            'unknown' => 'Unknown',
        ]);
    }

    /** @return array<string, string> */
    public static function batchTypes(): array
    {
        return self::localized('batch_type', [
            'customer' => 'Customer',
        ]);
    }

    /** @return array<string, string> */
    public static function priorities(): array
    {
        return self::localized('priority', [
            'low' => 'Low',
            'normal' => 'Normal',
            'high' => 'High',
            'urgent' => 'Urgent',
        ]);
    }

    /** @return array<string, string> */
    public static function employmentStatuses(): array
    {
        return self::localized('employment_status', [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'resigned' => 'Resigned',
        ]);
    }

    /** @return array<string, string> */
    public static function availabilityStatuses(): array
    {
        return self::localized('availability_status', [
            'working' => 'Working',
            'absent' => 'Absent',
            'leave' => 'On leave',
            'sick' => 'Sick leave',
            'remote' => 'Remote',
            'half_day' => 'Half day',
        ]);
    }

    /** @return array<string, string> */
    public static function distributionStrategies(): array
    {
        return self::localized('distribution_strategy', [
            'round_robin' => 'Round robin',
            'least_loaded' => 'Least loaded',
            'manual' => 'Manual',
        ]);
    }

    /** @return array<string, string> */
    public static function batchStatuses(): array
    {
        return self::localized('batch_status', [
            'draft' => 'Draft',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'failed' => 'Failed',
        ]);
    }

    /** @return array<string, string> */
    public static function assignmentTypes(): array
    {
        return self::localized('assignment_type', [
            'owner' => 'Owner',
            'support' => 'Support',
        ]);
    }

    /** @return array<string, string> */
    public static function assignmentStatuses(): array
    {
        return self::localized('assignment_status', [
            'active' => 'Active',
            'ended' => 'Ended',
            'cancelled' => 'Cancelled',
        ]);
    }

    /** @return array<string, string> */
    public static function decisionRoles(): array
    {
        return self::localized('decision_role', [
            'decision_maker' => 'Decision maker',
            'influencer' => 'Influencer',
            'technical_contact' => 'Technical contact',
            'billing_contact' => 'Billing contact',
            'end_user' => 'End user',
            'other' => 'Other',
        ]);
    }

    /** @return array<string, string> */
    public static function interactionTypes(): array
    {
        return self::localized('interaction_type', [
            'call' => 'Call',
            'email' => 'Email',
            'meeting' => 'Meeting',
            'message' => 'Message',
            'support' => 'Support',
            'note' => 'Note',
        ]);
    }

    /** @return array<string, string> */
    public static function activityTypes(): array
    {
        return self::localized('activity_type', [
            'call' => 'Call',
            'email' => 'Email',
            'meeting' => 'Meeting',
            'message' => 'Message',
            'note' => 'Note',
            'other' => 'Other',
        ]);
    }

    /** @return array<string, string> */
    public static function genders(): array
    {
        return self::localized('gender', [
            'male' => 'Male',
            'female' => 'Female',
            'other' => 'Other',
        ]);
    }

    /** @return array<string, string> */
    public static function matchMethods(): array
    {
        return self::localized('match_method', [
            'normalized_name' => 'Normalized company name',
            'tax_code' => 'Tax code',
            'email_domain' => 'Email domain',
            'phone' => 'Phone',
        ]);
    }

    /** @return array<string, string> */
    public static function matchStatuses(): array
    {
        return self::localized('match_status', [
            'pending' => 'Pending review',
            'accepted' => 'Accepted',
            'rejected' => 'Rejected',
        ]);
    }

    public static function label(string $group, mixed $value): string
    {
        $value = $value instanceof \BackedEnum ? $value->value : (string) $value;
        $method = match ($group) {
            'contact_type' => 'contactTypes',
            'company_lifecycle' => 'companyLifecycleStages',
            'lead_status' => 'leadStatuses',
            'qualification_status' => 'qualificationStatuses',
            'customer_status' => 'customerStatuses',
            'budget_status' => 'budgetStatuses',
            'purchase_timeline' => 'purchaseTimelines',
            'batch_type' => 'batchTypes',
            'priority' => 'priorities',
            'employment_status' => 'employmentStatuses',
            'availability_status' => 'availabilityStatuses',
            'distribution_strategy' => 'distributionStrategies',
            'batch_status' => 'batchStatuses',
            'assignment_type' => 'assignmentTypes',
            'assignment_status' => 'assignmentStatuses',
            'decision_role' => 'decisionRoles',
            'interaction_type' => 'interactionTypes',
            'activity_type' => 'activityTypes',
            'gender' => 'genders',
            'match_method' => 'matchMethods',
            'match_status' => 'matchStatuses',
            default => null,
        };

        if ($method !== null) {
            $options = self::{$method}();

            if (isset($options[$value])) {
                return $options[$value];
            }
        }

        return UiText::get(
            'options.'.$group.'.'.$value,
            str($value)->replace(['_', '-'], ' ')->headline()->toString(),
            context: 'option',
        );
    }

    /**
     * @param array<string, string> $defaults
     * @return array<string, string>
     */
    private static function localized(string $group, array $defaults): array
    {
        $result = [];

        foreach ($defaults as $value => $default) {
            $result[$value] = UiText::get(
                'options.'.$group.'.'.$value,
                $default,
                context: 'option',
            );
        }

        return $result;
    }
}
