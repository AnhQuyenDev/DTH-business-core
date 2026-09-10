<?php

namespace Dth\Email\Console\Commands;

use Dth\Email\DTO\AnalyticsRange;
use Dth\Email\DTO\EmailAnalyticsFilters;
use Dth\Email\Services\EmailAnalyticsService;
use Illuminate\Console\Command;

class CheckEmailAnalyticsCommand extends Command
{
    protected $signature = 'email:analytics:check
        {--days=30 : Number of days in the current analytics period}
        {--account= : Optional sending account ID}
        {--campaign= : Optional campaign ID}
        {--no-compare : Disable previous-period comparison}';

    protected $description = 'Run a read-only sanity check against the DTH Email analytics data layer.';

    public function handle(EmailAnalyticsService $analytics): int
    {
        $days = max(1, (int) $this->option('days'));
        $accountId = $this->positiveIntOrNull($this->option('account'));
        $campaignId = $this->positiveIntOrNull($this->option('campaign'));
        $compare = ! (bool) $this->option('no-compare');

        $filters = new EmailAnalyticsFilters(
            range: AnalyticsRange::lastDays($days, withComparison: $compare),
            sendingAccountId: $accountId,
            campaignId: $campaignId,
            comparePrevious: $compare,
        );

        $overview = $analytics->overview($filters);
        $current = $overview->current;
        $previous = $overview->previous;

        $this->info('DTH Email Analytics Data Layer');
        $this->line(sprintf(
            'Range: %s -> %s',
            $overview->range->start->format('Y-m-d H:i:s'),
            $overview->range->end->format('Y-m-d H:i:s'),
        ));
        $this->line('Providers: '.implode(', ', $overview->capabilities->providers));

        $this->newLine();
        $this->table(
            ['Metric', 'Current', 'Previous'],
            [
                ['Campaigns', $current->campaigns, $previous?->campaigns ?? '—'],
                ['Recipients', $current->recipients, $previous?->recipients ?? '—'],
                ['Sent', $current->sent, $previous?->sent ?? '—'],
                ['Unique opens', $current->uniqueOpened, $previous?->uniqueOpened ?? '—'],
                ['Total opens', $current->totalOpens, $previous?->totalOpens ?? '—'],
                ['Unique clicks', $current->uniqueClicked, $previous?->uniqueClicked ?? '—'],
                ['Total clicks', $current->totalClicks, $previous?->totalClicks ?? '—'],
                ['Open rate', $this->percent($current->openRate), $previous ? $this->percent($previous->openRate) : '—'],
                ['Click rate', $this->percent($current->clickRate), $previous ? $this->percent($previous->clickRate) : '—'],
                ['CTOR', $this->percent($current->clickToOpenRate), $previous ? $this->percent($previous->clickToOpenRate) : '—'],
                ['Unsubscribe rate', $this->percent($current->unsubscribeRate), $previous ? $this->percent($previous->unsubscribeRate) : '—'],
                ['Failure rate', $this->percent($current->failureRate), $previous ? $this->percent($previous->failureRate) : '—'],
                ['Delivery rate', $current->deliveryRate === null ? 'N/A' : $this->percent($current->deliveryRate), $previous?->deliveryRate === null ? 'N/A' : $this->percent($previous->deliveryRate)],
            ],
        );

        if (! $overview->capabilities->delivery) {
            $this->warn('Delivery/bounce/complaint provider events are unavailable for the selected transport cohort; they must be shown as N/A in reporting.');
        }

        $this->newLine();
        $this->info('Analytics data layer check completed successfully.');

        return self::SUCCESS;
    }

    private function positiveIntOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = (int) $value;

        return $value > 0 ? $value : null;
    }

    private function percent(float $value): string
    {
        return number_format($value, 1).'%';
    }
}
