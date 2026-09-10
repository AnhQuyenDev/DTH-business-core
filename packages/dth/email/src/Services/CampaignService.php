<?php

namespace Dth\Email\Services;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Dth\Email\Enums\CampaignRecipientStatus;
use Dth\Email\Enums\EmailCampaignStatus;
use Dth\Email\Enums\EmailTemplateStatus;
use Dth\Email\Enums\SendingAccountStatus;
use Dth\Email\Jobs\SendCampaignEmailJob;
use Dth\Email\Models\EmailCampaign;
use Illuminate\Support\Facades\DB;
use LogicException;

class CampaignService
{
    public function __construct(
        private readonly CampaignVariableValidator $variables,
    ) {}

    public function schedule(
        EmailCampaign $campaign,
        DateTimeInterface $when,
    ): EmailCampaign {
        $campaign->refresh();

        if ($campaign->status !== EmailCampaignStatus::Draft) {
            throw new LogicException(
                'Only draft campaigns can be scheduled.'
            );
        }

        $scheduledAt = CarbonImmutable::instance($when);

        if ($scheduledAt->lessThanOrEqualTo(now())) {
            throw new LogicException(
                'Scheduled time must be in the future.'
            );
        }

        $this->assertReady($campaign);

        $campaign->update([
            'status' => EmailCampaignStatus::Scheduled,
            'scheduled_at' => $scheduledAt,
            'failed_at' => null,
            'failure_reason' => null,
        ]);

        return $campaign->refresh();
    }

    public function unschedule(
        EmailCampaign $campaign,
    ): EmailCampaign {
        $campaign->refresh();

        if ($campaign->status !== EmailCampaignStatus::Scheduled) {
            throw new LogicException(
                'Only scheduled campaigns can be unscheduled.'
            );
        }

        $campaign->update([
            'status' => EmailCampaignStatus::Draft,
            'scheduled_at' => null,
        ]);

        return $campaign->refresh();
    }

    public function start(
        EmailCampaign $campaign,
    ): EmailCampaign {
        $campaign = DB::transaction(
            function () use ($campaign): EmailCampaign {
                $locked = EmailCampaign::query()
                    ->lockForUpdate()
                    ->findOrFail($campaign->id);

                if (! in_array(
                    $locked->status,
                    [
                        EmailCampaignStatus::Draft,
                        EmailCampaignStatus::Scheduled,
                    ],
                    true,
                )) {
                    throw new LogicException(
                        'Campaign cannot be started from its current status.'
                    );
                }

                $this->assertReady($locked);

                $locked->update([
                    'status' => EmailCampaignStatus::Processing,
                    'started_at' => $locked->started_at ?? now(),
                    'scheduled_at' => null,
                    'completed_at' => null,
                    'failed_at' => null,
                    'failure_reason' => null,
                ]);

                return $locked->refresh();
            }
        );

        $chunk = (int) config(
            'dth-email.campaign_chunk_size',
            200,
        );

        $campaign->recipients()
            ->where(
                'status',
                CampaignRecipientStatus::Pending->value
            )
            ->chunkById(
                $chunk,
                function ($recipients): void {
                    foreach ($recipients as $recipient) {
                        $recipient->update([
                            'status' => CampaignRecipientStatus::Queued,
                        ]);

                        SendCampaignEmailJob::dispatch(
                            $recipient->id
                        )->onQueue(
                            config('dth-email.queue', 'emails')
                        );
                    }
                }
            );

        $this->refreshCompletion($campaign);

        return $campaign->refresh();
    }

    public function fail(
        EmailCampaign $campaign,
        string $reason,
    ): EmailCampaign {
        $campaign->update([
            'status' => EmailCampaignStatus::Failed,
            'failed_at' => now(),
            'failure_reason' => mb_substr($reason, 0, 5000),
        ]);

        return $campaign->refresh();
    }

    public function refreshCompletion(
        EmailCampaign $campaign,
    ): void {
        $hasWaitingRecipients = $campaign->recipients()
            ->whereIn('status', [
                CampaignRecipientStatus::Pending->value,
                CampaignRecipientStatus::Queued->value,
            ])
            ->exists();

        if (
            ! $hasWaitingRecipients
            && $campaign->status === EmailCampaignStatus::Processing
        ) {
            $campaign->update([
                'status' => EmailCampaignStatus::Completed,
                'completed_at' => now(),
            ]);
        }
    }

    private function assertReady(
        EmailCampaign $campaign,
    ): void {
        $campaign->loadMissing([
            'template',
            'sendingAccount',
        ]);

        if (! $campaign->template) {
            throw new LogicException(
                'Campaign has no email template.'
            );
        }

        if (
            $campaign->template->status
            !== EmailTemplateStatus::Active
        ) {
            throw new LogicException(
                'Email template must be active before sending.'
            );
        }

        if (! $campaign->sendingAccount) {
            throw new LogicException(
                'Campaign has no sending account.'
            );
        }

        if (
            $campaign->sendingAccount->status
            !== SendingAccountStatus::Active
        ) {
            throw new LogicException(
                'Sending account must be active before sending.'
            );
        }

        if (blank($campaign->subject)) {
            throw new LogicException(
                'Campaign subject is required.'
            );
        }

        if (blank($campaign->html_body)) {
            throw new LogicException(
                'Campaign email body is required.'
            );
        }

        if (
            ! $campaign->recipients()
                ->where(
                    'status',
                    CampaignRecipientStatus::Pending->value
                )
                ->exists()
        ) {
            throw new LogicException(
                'Campaign must contain at least one pending recipient.'
            );
        }

        $this->variables->assertRecipientsComplete($campaign);
    }
}