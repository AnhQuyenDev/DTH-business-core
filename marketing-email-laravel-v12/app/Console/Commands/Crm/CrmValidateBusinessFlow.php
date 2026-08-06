<?php

namespace App\Console\Commands\Crm;

use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Sales\PaymentStatus;
use App\Enums\Sales\QuotationStatus;
use App\Models\Crm\Company;
use App\Models\Crm\ContactQualification;
use App\Models\Crm\Lead;
use App\Models\Sales\Quotation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CrmValidateBusinessFlow extends Command
{
    protected $signature = 'crm:validate-business-flow
        {--fail-on-error : Exit non-zero when integrity errors found}';

    protected $description =
        'Validate toàn bộ luồng CRM → Sales → Customer để phát hiện orphan và legacy drift';

    public function handle(): int
    {
        $failOnError = (bool) $this->option('fail-on-error');

        $errors = [];

        /*
         * 1. Submission đã processed nhưng không có Lead.
         */
        $submissionsWithoutLead = DB::table('landing_page_submissions')
            ->leftJoin('leads', 'leads.submission_id', '=', 'landing_page_submissions.id')
            ->whereIn('landing_page_submissions.status', ['received', 'processed'])
            ->whereNull('leads.id')
            ->count();

        if ($submissionsWithoutLead > 0) {
            $errors[] = sprintf(
                'Submission đã processed/received chưa có Lead: %d',
                $submissionsWithoutLead
            );
        }

        /*
         * 2. Lead không có Qualification.
         */
        $leadsWithoutQualification = DB::table('leads')
            ->leftJoin('contact_qualifications', 'contact_qualifications.lead_id', '=', 'leads.id')
            ->whereNull('contact_qualifications.id')
            ->count();

        if ($leadsWithoutQualification > 0) {
            $errors[] = sprintf(
                'Lead không có ContactQualification: %d',
                $leadsWithoutQualification
            );
        }

        /*
         * 3. Business Lead (có BusinessProfile) mà không gán Company.
         */
        $businessLeadRows = Lead::query()
            ->whereNull('company_id')
            ->whereHas('contact.businessProfile', fn ($q) => $q->whereNotNull('company_name'))
            ->count();

        if ($businessLeadRows > 0) {
            $errors[] = sprintf(
                'Business Lead không có Company: %d',
                $businessLeadRows
            );
        }

        /*
         * 4. Company có nhiều active Account Owner assignment.
         */
        $multiOwnerCompanies = Company::query()
            ->whereHas('assignments', fn ($q) => $q
                ->where('assignment_type', 'owner')
                ->where('status', 'active'))
            ->withCount(['assignments as active_owner_count' => fn ($q) => $q
                ->where('assignment_type', 'owner')
                ->where('status', 'active')])
            ->having('active_owner_count', '>', 1)
            ->pluck('active_owner_count', 'id');

        if ($multiOwnerCompanies->isNotEmpty()) {
            $errors[] = 'Company có nhiều active Account Owner: '.implode(',', $multiOwnerCompanies->keys()->all());
        }

        /*
         * 5. Quotation mới không có Opportunity (status đã gửi/accepted nhưng chưa link opportunity).
         */
        $quotationsWithoutOpportunity = Quotation::query()
            ->whereNull('opportunity_id')
            ->whereIn('status', [
                QuotationStatus::Sent,
                QuotationStatus::Viewed,
                QuotationStatus::Accepted,
            ])
            ->count();

        if ($quotationsWithoutOpportunity > 0) {
            $errors[] = sprintf(
                'Quotation đã gửi/accepted không có Opportunity: %d',
                $quotationsWithoutOpportunity
            );
        }

        /*
         * 6. Paid quotation không có Customer.
         */
        $paidWithoutCustomer = Quotation::query()
            ->where('payment_status', PaymentStatus::Paid)
            ->whereNull('customer_id')
            ->count();

        if ($paidWithoutCustomer > 0) {
            $errors[] = sprintf(
                'Quotation đã thanh toán không có Customer: %d',
                $paidWithoutCustomer
            );
        }

        /*
         * 7. Converted qualification không có converted_customer_id.
         */
        $convertedWithoutCustomer = ContactQualification::query()
            ->where('status', ContactQualificationStatus::Converted)
            ->whereNull('converted_customer_id')
            ->count();

        if ($convertedWithoutCustomer > 0) {
            $errors[] = sprintf(
                'ContactQualification converted không có converted_customer_id: %d',
                $convertedWithoutCustomer
            );
        }

        $this->newLine();
        if (empty($errors)) {
            $this->info('✓ Toàn bộ luồng dữ liệu hợp lệ. Không có lỗi nghiêm trọng.');

            return self::SUCCESS;
        }

        $this->error('✗ Có lỗi toàn thời gian kinh doanh:');
        foreach ($errors as $error) {
            $this->error('  - '.$error);
        }

        return $failOnError ? self::INVALID : self::SUCCESS;
    }
}
