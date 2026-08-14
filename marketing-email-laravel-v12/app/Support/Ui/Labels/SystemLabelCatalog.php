<?php

namespace App\Support\Ui\Labels;

use App\Enums\Crm\CompanyLifecycleStage;
use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Crm\CustomerAssignmentStatus;
use App\Enums\Crm\CustomerAssignmentType;
use App\Enums\Crm\CustomerConsentStatus;
use App\Enums\Crm\CustomerLifecycleStage;
use App\Enums\Crm\CustomerStatus;
use App\Enums\Crm\DistributionBatchStatus;
use App\Enums\Crm\DistributionBatchType;
use App\Enums\Crm\DistributionStrategy;
use App\Enums\Crm\InteractionStatus;
use App\Enums\Crm\LeadActivityStatus;
use App\Enums\Crm\LeadIntakeStatus;
use App\Enums\Crm\QualificationResult;
use App\Enums\Crm\StaffAvailabilityStatus;
use App\Enums\Crm\StaffEmploymentStatus;
use App\Enums\Crm\TaxVerificationStatus;
use App\Enums\Marketing\CampaignRecipientStatus;
use App\Enums\Marketing\CampaignStatus;
use App\Enums\Marketing\ContactConsentStatus;
use App\Enums\Marketing\ContactStatus;
use App\Enums\Marketing\DnsStatus;
use App\Enums\Marketing\FormTemplateStatus;
use App\Enums\Marketing\LandingPageStatus;
use App\Enums\Marketing\LandingPageSubmissionStatus;
use App\Enums\Marketing\SendingAccountStatus;
use App\Enums\Marketing\LandingPageContactAction;
use App\Enums\Marketing\SuppressionReason;
use App\Enums\Sales\ApprovalStatus;
use App\Enums\Sales\EmailStatus;
use App\Enums\Sales\OpportunityStage;
use App\Enums\Sales\PackageStatus;
use App\Enums\Sales\PaymentNoticeStatus;
use App\Enums\Sales\PaymentStatus;
use App\Enums\Sales\PriceBookStatus;
use App\Enums\Sales\QuotationEmailStatus;
use App\Enums\Sales\QuotationStatus;
use App\Enums\Sales\ServiceStatus;
use App\Enums\Support\TicketPriority;
use App\Enums\Support\TicketStatus;
use App\Models\Crm\BusinessContactProfile;
use App\Models\Crm\Company;
use App\Models\Crm\ContactQualification;
use App\Models\Crm\Customer;
use App\Models\Crm\CustomerAssignment;
use App\Models\Crm\CustomerDistributionBatch;
use App\Models\Crm\CustomerInteraction;
use App\Models\Crm\Lead;
use App\Models\Crm\LeadActivity;
use App\Models\Crm\Staff;
use App\Models\Crm\StaffAvailability;
use App\Models\Crm\CompanyMatchCandidate;
use App\Models\Crm\CustomerDistributionItem;
use App\Models\Finance\Payment;
use App\Models\Marketing\Campaign;
use App\Models\Marketing\CampaignRecipient;
use App\Models\Marketing\ContactList;
use App\Models\Marketing\FormTemplate;
use App\Models\Marketing\LandingPage;
use App\Models\Marketing\LandingPageSubmission;
use App\Models\Marketing\SendingAccount;
use App\Models\Marketing\SendingDomain;
use App\Models\Marketing\EmailTemplate;
use App\Models\Marketing\MarketingCampaign;
use App\Models\Marketing\Segment;
use App\Models\Marketing\SuppressionEntry;
use App\Models\Sales\BankAccount;
use App\Models\Sales\Opportunity;
use App\Models\Sales\PriceBook;
use App\Models\Sales\Quotation;
use App\Models\Sales\QuotationApproval;
use App\Models\Sales\QuotationEmailLog;
use App\Models\Sales\QuotationPaymentNotice;
use App\Models\Sales\Service;
use App\Models\Sales\ServicePackage;
use App\Models\Support\SupportTicket;

final class SystemLabelCatalog
{
    /**
     * The registry is explicit by design. New enums that display a colored label
     * must be added here so their business impact and usage locations are reviewed.
     *
     * @return array<class-string, array<string, mixed>>
     */
    public static function enumGroups(): array
    {
        /** @var array<class-string, array<string, mixed>>|null $groups */
        static $groups = null;

        return $groups ??= [
            // Marketing and email.
            CampaignStatus::class => self::status('marketing.campaign_status', 'marketing', 'campaign_status', ['campaigns'], [[Campaign::class, 'status']]),
            CampaignRecipientStatus::class => self::status('marketing.campaign_recipient_status', 'marketing', 'campaign_recipient_status', ['campaign_recipients'], [[CampaignRecipient::class, 'status']]),
            ContactConsentStatus::class => self::status('marketing.contact_consent_status', 'marketing', 'contact_consent_status', ['contacts', 'campaign_recipients']),
            ContactStatus::class => self::status('marketing.contact_status', 'marketing', 'contact_status', ['contact_lists'], [[ContactList::class, 'status']]),
            DnsStatus::class => self::status('marketing.dns_status', 'marketing', 'dns_status', ['sending_domains'], [
                [SendingDomain::class, 'status'],
                [SendingDomain::class, 'spf_status'],
                [SendingDomain::class, 'dkim_status'],
                [SendingDomain::class, 'dmarc_status'],
            ]),
            FormTemplateStatus::class => self::status('marketing.form_template_status', 'marketing', 'form_template_status', ['form_templates'], [[FormTemplate::class, 'status']]),
            LandingPageStatus::class => self::status('marketing.landing_page_status', 'marketing', 'landing_page_status', ['landing_pages', 'marketing_dashboard'], [[LandingPage::class, 'status']]),
            LandingPageSubmissionStatus::class => self::status('marketing.landing_page_submission_status', 'marketing', 'landing_page_submission_status', ['landing_page_submissions'], [[LandingPageSubmission::class, 'status']]),
            LandingPageContactAction::class => self::status('marketing.landing_page_contact_action', 'marketing', 'landing_page_contact_action', ['landing_page_submissions'], [[LandingPageSubmission::class, 'contact_action']]),
            SendingAccountStatus::class => self::status('marketing.sending_account_status', 'marketing', 'sending_account_status', ['sending_accounts'], [[SendingAccount::class, 'status']]),
            SuppressionReason::class => self::business('marketing.suppression_reason', 'marketing', 'suppression_reason', ['suppression_entries'], [[SuppressionEntry::class, 'reason']]),

            // CRM and workforce.
            CompanyLifecycleStage::class => self::status('crm.company_lifecycle', 'crm', 'company_lifecycle', ['companies'], [[Company::class, 'lifecycle_stage']]),
            ContactQualificationStatus::class => self::status('crm.contact_qualification_status', 'crm', 'contact_qualification_status', ['lead_qualification'], [[ContactQualification::class, 'status']]),
            CustomerAssignmentStatus::class => self::status('crm.customer_assignment_status', 'crm', 'customer_assignment_status', ['customer_assignments'], [[CustomerAssignment::class, 'status']]),
            CustomerAssignmentType::class => self::business('crm.customer_assignment_type', 'crm', 'customer_assignment_type', ['customer_assignments'], [[CustomerAssignment::class, 'assignment_type']]),
            CustomerConsentStatus::class => self::status('crm.customer_consent_status', 'crm', 'customer_consent_status', ['customers'], [[Customer::class, 'consent_status']]),
            CustomerLifecycleStage::class => self::status('crm.customer_lifecycle', 'crm', 'customer_lifecycle', ['customers', 'customer_care_dashboard'], [[Customer::class, 'lifecycle_stage']]),
            CustomerStatus::class => self::status('crm.customer_status', 'crm', 'customer_status', ['customers'], [[Customer::class, 'status']]),
            DistributionBatchStatus::class => self::status('crm.distribution_batch_status', 'crm', 'distribution_batch_status', ['distribution_batches'], [[CustomerDistributionBatch::class, 'status']]),
            DistributionBatchType::class => self::business('crm.distribution_batch_type', 'crm', 'distribution_batch_type', ['distribution_batches'], [[CustomerDistributionBatch::class, 'type']]),
            DistributionStrategy::class => self::business('crm.distribution_strategy', 'crm', 'distribution_strategy', ['distribution_batches'], [[CustomerDistributionBatch::class, 'strategy']]),
            InteractionStatus::class => self::status('crm.interaction_status', 'crm', 'interaction_status', ['customer_interactions'], [[CustomerInteraction::class, 'status']]),
            LeadActivityStatus::class => self::status('crm.lead_activity_status', 'crm', 'lead_activity_status', ['lead_activities'], [[LeadActivity::class, 'status']]),
            LeadIntakeStatus::class => self::status('crm.lead_intake_status', 'crm', 'lead_intake_status', ['leads', 'crm_dashboard'], [[Lead::class, 'intake_status']]),
            QualificationResult::class => self::status('crm.qualification_result', 'crm', 'qualification_result', ['lead_qualification'], [[ContactQualification::class, 'qualification_result']]),
            StaffAvailabilityStatus::class => self::status('crm.staff_availability_status', 'organization', 'staff_availability_status', ['staff_availability'], [[StaffAvailability::class, 'status']]),
            StaffEmploymentStatus::class => self::status('crm.staff_employment_status', 'organization', 'staff_employment_status', ['staff'], [[Staff::class, 'employment_status']]),
            TaxVerificationStatus::class => self::status('crm.tax_verification_status', 'crm', 'tax_verification_status', ['business_contacts'], [[BusinessContactProfile::class, 'tax_verification_status']]),

            // Sales and finance.
            ApprovalStatus::class => self::status('sales.approval_status', 'sales', 'approval_status', ['quotation_approvals'], [[QuotationApproval::class, 'status']]),
            EmailStatus::class => self::status('sales.email_status', 'sales', 'email_status', ['quotations'], [[Quotation::class, 'email_status']]),
            OpportunityStage::class => self::status('sales.opportunity_stage', 'sales', 'opportunity_stage', ['opportunities', 'sales_dashboard'], [[Opportunity::class, 'stage']]),
            PackageStatus::class => self::status('sales.package_status', 'sales', 'package_status', ['service_packages'], [[ServicePackage::class, 'status']]),
            PaymentNoticeStatus::class => self::status('sales.payment_notice_status', 'finance', 'payment_notice_status', ['payment_notices'], [[QuotationPaymentNotice::class, 'status']]),
            PaymentStatus::class => self::status('sales.payment_status', 'finance', 'payment_status', ['quotations', 'finance_dashboard'], [[Quotation::class, 'payment_status']]),
            PriceBookStatus::class => self::status('sales.price_book_status', 'sales', 'price_book_status', ['price_books'], [[PriceBook::class, 'status']]),
            QuotationEmailStatus::class => self::status('sales.quotation_email_status', 'sales', 'quotation_email_status', ['quotation_email_logs'], [[QuotationEmailLog::class, 'status']]),
            QuotationStatus::class => self::status('sales.quotation_status', 'sales', 'quotation_status', ['quotations', 'sales_dashboard'], [[Quotation::class, 'status']]),
            ServiceStatus::class => self::status('sales.service_status', 'sales', 'service_status', ['services'], [[Service::class, 'status']]),

            // Customer care.
            TicketPriority::class => self::status('support.ticket_priority', 'customer_care', 'ticket_priority', ['support_tickets'], [[SupportTicket::class, 'priority']]),
            TicketStatus::class => self::status('support.ticket_status', 'customer_care', 'ticket_status', ['support_tickets', 'customer_care_dashboard'], [[SupportTicket::class, 'status']]),
        ];
    }

    /**
     * Business labels that are stored as strings rather than enum casts. Each
     * group still receives an isolated category so identical keys such as
     * "active" never leak a color override into an unrelated workflow.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function valueGroups(): array
    {
        /** @var array<string, array<string, mixed>>|null $groups */
        static $groups = null;

        return $groups ??= [
            'crm.customer_priority' => self::values(
                module: 'customer_care',
                groupKey: 'customer_priority',
                usages: ['customer_care_dashboard', 'lead_qualification'],
                countSources: [[Customer::class, 'priority'], [ContactQualification::class, 'priority']],
                values: [
                    'low' => ['label_key' => 'field.priority.low', 'color' => 'gray'],
                    'normal' => ['label_key' => 'field.priority.normal', 'color' => 'info'],
                    'high' => ['label_key' => 'field.priority.high', 'color' => 'warning'],
                    'vip' => ['label_key' => 'field.priority.vip', 'color' => 'danger'],
                ],
            ),
            'crm.company_match_status' => self::values(
                module: 'crm',
                groupKey: 'company_match_status',
                usages: ['company_match_candidates'],
                countSources: [[CompanyMatchCandidate::class, 'status']],
                values: [
                    'pending' => ['label_key' => 'enum.status.pending', 'color' => 'warning'],
                    'accepted' => ['label_key' => 'enum.sales.quotation_status.accepted', 'color' => 'success'],
                    'rejected' => ['label_key' => 'enum.sales.quotation_status.rejected', 'color' => 'danger'],
                ],
            ),
            'crm.distribution_item_result' => self::values(
                module: 'crm',
                groupKey: 'distribution_item_result',
                usages: ['distribution_batches'],
                countSources: [[CustomerDistributionItem::class, 'result_status']],
                values: [
                    'pending' => ['label_key' => 'enum.status.pending', 'color' => 'warning'],
                    'success' => ['label_key' => 'field.success', 'color' => 'success'],
                    'skipped' => ['label_key' => 'field.skipped', 'color' => 'gray'],
                ],
            ),
            'crm.interaction_type' => self::values(
                module: 'crm',
                groupKey: 'interaction_type',
                usages: ['customer_interactions'],
                countSources: [[CustomerInteraction::class, 'interaction_type']],
                values: [
                    'call' => ['label_key' => 'enum.interaction_type.call', 'color' => 'info'],
                    'email' => ['label_key' => 'enum.interaction_type.email', 'color' => 'info'],
                    'message' => ['label_key' => 'enum.interaction_type.message', 'color' => 'info'],
                    'meeting' => ['label_key' => 'enum.interaction_type.meeting', 'color' => 'info'],
                    'note' => ['label_key' => 'enum.interaction_type.note', 'color' => 'gray'],
                    'support' => ['label_key' => 'enum.interaction_type.support', 'color' => 'warning'],
                    'follow_up' => ['label_key' => 'enum.interaction_type.follow_up', 'color' => 'warning'],
                    'system' => ['label_key' => 'configuration.system_labels.values.system', 'color' => 'gray'],
                    'quotation_sent' => ['label_key' => 'configuration.system_labels.values.quotation_sent', 'color' => 'info'],
                    'quotation_accepted' => ['label_key' => 'configuration.system_labels.values.quotation_accepted', 'color' => 'success'],
                    'quotation_rejected' => ['label_key' => 'configuration.system_labels.values.quotation_rejected', 'color' => 'danger'],
                    'quotation_revision_requested' => ['label_key' => 'configuration.system_labels.values.quotation_revision_requested', 'color' => 'warning'],
                    'payment_notice_submitted' => ['label_key' => 'configuration.system_labels.values.payment_notice_submitted', 'color' => 'warning'],
                ],
            ),
            'finance.payment_record_status' => self::values(
                module: 'finance',
                groupKey: 'payment_record_status',
                usages: ['payment_history'],
                countSources: [[Payment::class, 'status']],
                values: [
                    'verified' => ['label_key' => 'field.payment_status_verified', 'color' => 'success'],
                    'refunded' => ['label_key' => 'field.payment_status_refunded', 'color' => 'warning'],
                ],
            ),
            'marketing.marketing_campaign_status' => self::values(
                module: 'marketing',
                groupKey: 'marketing_campaign_status',
                usages: ['marketing_campaigns'],
                countSources: [[MarketingCampaign::class, 'status']],
                values: [
                    'draft' => ['label_key' => 'field.status_draft', 'color' => 'gray'],
                    'active' => ['label_key' => 'field.status_active', 'color' => 'success'],
                    'paused' => ['label_key' => 'enum.campaign_status.paused', 'color' => 'warning'],
                    'completed' => ['label_key' => 'field.status_completed', 'color' => 'success'],
                ],
            ),
            'marketing.contact_list_type' => self::values(
                module: 'marketing',
                groupKey: 'contact_list_type',
                usages: ['contact_lists'],
                countSources: [[ContactList::class, 'type']],
                values: [
                    'newsletter' => ['label_key' => 'configuration.system_labels.values.newsletter', 'color' => 'info'],
                    'service' => ['label_key' => 'configuration.system_labels.values.service', 'color' => 'info'],
                    'event' => ['label_key' => 'configuration.system_labels.values.event', 'color' => 'info'],
                ],
            ),
            'marketing.segment_status' => self::values(
                module: 'marketing',
                groupKey: 'segment_status',
                usages: ['segments'],
                countSources: [[Segment::class, 'status']],
                values: [
                    'active' => ['label_key' => 'field.status_active', 'color' => 'success'],
                    'inactive' => ['label_key' => 'field.status_inactive', 'color' => 'gray'],
                ],
            ),
            'marketing.email_template_status' => self::values(
                module: 'marketing',
                groupKey: 'email_template_status',
                usages: ['email_templates'],
                countSources: [[EmailTemplate::class, 'status']],
                values: [
                    'active' => ['label_key' => 'field.status_active', 'color' => 'success'],
                    'inactive' => ['label_key' => 'field.status_inactive', 'color' => 'gray'],
                    'draft' => ['label_key' => 'field.status_draft', 'color' => 'gray'],
                ],
            ),
            'marketing.landing_page_distribution_state' => self::values(
                module: 'marketing',
                groupKey: 'landing_page_distribution_state',
                usages: ['landing_page_submissions', 'work_distribution'],
                countSources: [],
                values: [
                    'assigned' => ['label_key' => 'distribution.assigned', 'color' => 'info'],
                    'unassigned' => ['label_key' => 'distribution.unassigned', 'color' => 'gray'],
                ],
            ),
            'sales.bank_account_status' => self::values(
                module: 'finance',
                groupKey: 'bank_account_status',
                usages: ['bank_accounts'],
                countSources: [[BankAccount::class, 'status']],
                values: [
                    'active' => ['label_key' => 'field.status_active', 'color' => 'success'],
                    'inactive' => ['label_key' => 'field.status_inactive', 'color' => 'gray'],
                ],
            ),
        ];
    }

    /** @return array<string, mixed>|null */
    public static function metadataForEnum(string $enumClass): ?array
    {
        return self::enumGroups()[$enumClass] ?? null;
    }

    public static function categoryForEnum(string $enumClass): ?string
    {
        return self::metadataForEnum($enumClass)['category'] ?? null;
    }

    /**
     * @param  array<int, string>  $usages
     * @param  array<int, array{0: class-string, 1: string}>  $countSources
     * @return array<string, mixed>
     */
    private static function status(string $category, string $module, string $groupKey, array $usages, array $countSources = []): array
    {
        return [
            'category' => $category,
            'module' => $module,
            'group_key' => $groupKey,
            'type' => 'semantic',
            'impact' => 'critical',
            'usages' => $usages,
            'count_sources' => $countSources,
        ];
    }

    /**
     * @param  array<int, string>  $usages
     * @param  array<int, array{0: class-string, 1: string}>  $countSources
     * @return array<string, mixed>
     */
    private static function business(string $category, string $module, string $groupKey, array $usages, array $countSources = []): array
    {
        return [
            'category' => $category,
            'module' => $module,
            'group_key' => $groupKey,
            'type' => 'identity',
            'impact' => 'business',
            'usages' => $usages,
            'count_sources' => $countSources,
        ];
    }

    /**
     * @param  array<int, string>  $usages
     * @param  array<int, array{0: class-string, 1: string}>  $countSources
     * @param  array<string, array{label_key: string, color: string}>  $values
     * @return array<string, mixed>
     */
    private static function values(
        string $module,
        string $groupKey,
        array $usages,
        array $countSources,
        array $values,
    ): array {
        return [
            'module' => $module,
            'group_key' => $groupKey,
            'type' => 'semantic',
            'impact' => 'critical',
            'usages' => $usages,
            'count_sources' => $countSources,
            'values' => $values,
        ];
    }
}
