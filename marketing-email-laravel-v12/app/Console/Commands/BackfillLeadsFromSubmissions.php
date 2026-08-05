<?php

namespace App\Console\Commands;

use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Crm\LeadIntakeStatus;
use App\Models\Crm\ContactQualification;
use App\Models\Crm\Lead;
use App\Models\Marketing\LandingPageSubmission;
use App\Services\Crm\LeadCodeGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillLeadsFromSubmissions extends Command
{
    protected $signature = 'crm:backfill-leads
        {--dry-run : Chỉ thống kê, không ghi dữ liệu}
        {--chunk=200 : Số Submission xử lý mỗi chunk}';

    protected $description =
        'Backfill Lead từ Submission cũ và gắn Qualification vào Lead';

    public function handle(LeadCodeGenerator $codeGenerator): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunkSize = max(1, (int) $this->option('chunk'));

        $stats = [
            'submissions_without_lead' => 0,
            'leads_created' => 0,
            'legacy_qualifications_linked' => 0,
            'qualifications_created' => 0,
            'contacts_multiple_submissions' => 0,
        ];

        $submissionIds = LandingPageSubmission::query()
            ->whereDoesntHave('lead')
            ->whereIn('status', ['received', 'processed'])
            ->orderBy('id')
            ->pluck('id');

        $stats['submissions_without_lead'] = $submissionIds->count();

        if (! $dryRun) {
            foreach ($submissionIds->chunk($chunkSize) as $ids) {
                $submissions = LandingPageSubmission::query()
                    ->whereKey($ids)
                    ->with('landingPage')
                    ->orderBy('id')
                    ->get();

                foreach ($submissions as $submission) {
                    $stats['leads_created'] += DB::transaction(function () use (
                        $submission,
                        $codeGenerator,
                    ): int {
                        $lead = Lead::query()->firstOrCreate(
                            ['submission_id' => $submission->id],
                            [
                                'lead_code' => $codeGenerator->next(),
                                'contact_id' => $submission->contact_id,
                                'company_id' => $submission->company_id,
                                'source' => 'landing_page',
                                'source_detail' => $submission->landingPage?->name,
                                'title' => 'Yêu cầu tư vấn từ Landing Page',
                                'intake_status' => LeadIntakeStatus::New->value,
                            ]
                        );

                        return $lead->wasRecentlyCreated ? 1 : 0;
                    });
                }
            }

            $linked = $this->linkLegacyQualifications();
            $stats['legacy_qualifications_linked'] = $linked['linked'];
            $stats['qualifications_created'] = $linked['created'];
        }

        $multiContacts = LandingPageSubmission::query()
            ->select('contact_id', DB::raw('COUNT(*) as total'))
            ->whereNotNull('contact_id')
            ->groupBy('contact_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $stats['contacts_multiple_submissions'] = $multiContacts->count();

        if ($multiContacts->isNotEmpty()) {
            $this->newLine();
            $this->table(
                ['contact_id', 'Số submission'],
                $multiContacts
                    ->map(fn ($row): array => [$row->contact_id, $row->total])
                    ->all()
            );
        }

        $this->newLine();
        $this->table(
            ['Chỉ số', 'Số lượng'],
            collect($stats)
                ->map(fn (int $value, string $key): array => [$key, $value])
                ->values()
                ->all()
        );

        if ($dryRun) {
            $this->warn('DRY-RUN: không có dữ liệu nào được ghi.');
        } else {
            $this->info('Backfill Lead hoàn tất.');
        }

        return self::SUCCESS;
    }

    /**
     * @return array{linked: int, created: int}
     */
    private function linkLegacyQualifications(): array
    {
        $linked = 0;

        // Gắn Qualification cũ (lead_id null) vào Lead đang mở của Contact.
        ContactQualification::query()
            ->whereNull('lead_id')
            ->whereNotNull('contact_id')
            ->orderBy('id')
            ->chunkById(100, function ($legacyQualifications) use (&$linked): void {
                foreach ($legacyQualifications as $qualification) {
                    $lead = Lead::query()
                        ->where('contact_id', $qualification->contact_id)
                        ->whereIn('intake_status', [
                            LeadIntakeStatus::New->value,
                            LeadIntakeStatus::Active->value,
                        ])
                        ->whereDoesntHave('qualification')
                        ->orderByDesc('id')
                        ->first();

                    if ($lead === null) {
                        continue;
                    }

                    $qualification->update(['lead_id' => $lead->id]);
                    $linked++;
                }
            });

        // Tạo Qualification new cho các Lead còn lại chưa có.
        $created = 0;

        Lead::query()
            ->whereDoesntHave('qualification')
            ->chunkById(100, function ($leads) use (&$created): void {
                foreach ($leads as $lead) {
                    ContactQualification::query()->create([
                        'lead_id' => $lead->id,
                        'contact_id' => $lead->contact_id,
                        'status' => ContactQualificationStatus::New->value,
                        'priority' => 'normal',
                    ]);
                    $created++;
                }
            });

        return ['linked' => $linked, 'created' => $created];
    }
}
