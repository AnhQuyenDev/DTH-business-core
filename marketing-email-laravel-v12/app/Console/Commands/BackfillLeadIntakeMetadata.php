<?php

namespace App\Console\Commands;

use App\Models\Crm\Lead;
use App\Services\Crm\LeadFormAnswerSnapshotService;
use Illuminate\Console\Command;

final class BackfillLeadIntakeMetadata extends Command
{
    protected $signature = 'crm:backfill-lead-intake
        {--dry-run : Chỉ thống kê, không ghi dữ liệu}
        {--chunk=200 : Số Lead xử lý mỗi chunk}';

    protected $description =
        'Backfill snapshot biểu mẫu và trạng thái sẵn sàng phân phối cho Lead cũ';

    public function handle(
        LeadFormAnswerSnapshotService $snapshotService,
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $chunkSize = max(1, (int) $this->option('chunk'));
        $stats = [
            'scanned' => 0,
            'updated' => 0,
            'ready' => 0,
            'needs_review' => 0,
            'service_recovered' => 0,
        ];

        Lead::query()
            ->whereNotNull('submission_id')
            ->with([
                'submission.formTemplate.fields',
                'submission.landingPage.marketingCampaign',
                'qualification',
            ])
            ->orderBy('id')
            ->chunkById(
                $chunkSize,
                function ($leads) use (
                    $snapshotService,
                    $dryRun,
                    &$stats,
                ): void {
                    foreach ($leads as $lead) {
                        $stats['scanned']++;
                        $submission = $lead->submission;

                        if ($submission === null) {
                            continue;
                        }

                        $formAnswers = $snapshotService->build(
                            $submission->formTemplate,
                            $submission->data ?? [],
                        );
                        $serviceAnswer = collect($formAnswers)->first(
                            fn (array $answer): bool => in_array(
                                $answer['mapping'] ?? null,
                                [
                                    'lead.service_interest',
                                    'personal.service_interest',
                                    'business.service_interest',
                                ],
                                true
                            ) || in_array(
                                $answer['key'] ?? null,
                                ['service_interest', 'service'],
                                true
                            )
                        );
                        $serviceInterest = $lead->service_interest;

                        if (
                            blank($serviceInterest)
                            && is_array($serviceAnswer)
                            && filled($serviceAnswer['value'] ?? null)
                        ) {
                            $value = $serviceAnswer['value'];
                            $serviceInterest = is_array($value)
                                ? implode(', ', array_filter($value))
                                : (string) $value;
                            $stats['service_recovered']++;
                        }

                        $resolvedType = data_get(
                            $submission->data,
                            'customer_type',
                            $submission->submission_type,
                        );
                        $issues = [];

                        if (blank($serviceInterest)) {
                            $issues[] = 'missing_service_interest';
                        }

                        if ($lead->contact_id === null) {
                            $issues[] = 'missing_contact';
                        }

                        if (
                            $resolvedType === 'business'
                            && $lead->company_id === null
                        ) {
                            $issues[] = 'company_resolution_pending';
                        }

                        $metadata = array_merge($lead->metadata ?? [], [
                            'service_interest_label' => is_array($serviceAnswer)
                                ? ($serviceAnswer['display_value']
                                    ?? $serviceInterest)
                                : $serviceInterest,
                            'submission_type' => $resolvedType,
                            'landing_page_id' => $submission->landing_page_id,
                            'landing_page_name' => $submission
                                ->landingPage?->name,
                            // campaign_id là Email Campaign attribution cũ.
                            'campaign_id' => $submission->campaign_id,
                            'marketing_campaign_id' => $submission
                                ->marketing_campaign_id,
                            'marketing_campaign_name' => $submission
                                ->landingPage?->marketingCampaign?->name,
                            'form_template_id' => $submission
                                ->landing_form_template_id,
                            'captured_at' => $submission->submitted_at
                                ?->toIso8601String(),
                            'intake_ready' => $issues === [],
                            'intake_issues' => $issues,
                            'form_answers' => $formAnswers,
                        ]);

                        $issues === []
                            ? $stats['ready']++
                            : $stats['needs_review']++;

                        if ($dryRun) {
                            continue;
                        }

                        $lead->update([
                            'service_interest' => $serviceInterest,
                            'metadata' => $metadata,
                        ]);

                        if (
                            $lead->qualification !== null
                            && blank($lead->qualification->service_interest)
                            && filled($serviceInterest)
                        ) {
                            $lead->qualification->update([
                                'service_interest' => $serviceInterest,
                            ]);
                        }

                        $stats['updated']++;
                    }
                }
            );

        $this->table(
            ['Chỉ số', 'Số lượng'],
            collect($stats)
                ->map(fn (int $value, string $key): array => [$key, $value])
                ->values()
                ->all()
        );

        $dryRun
            ? $this->warn('DRY-RUN: không có dữ liệu nào được ghi.')
            : $this->info('Backfill Lead intake hoàn tất.');

        return self::SUCCESS;
    }
}
