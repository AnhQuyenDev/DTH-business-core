<?php

namespace Dth\Email\Filament\Widgets\Dashboard;

use Dth\Email\Filament\Resources\SendingAccountResource;
use Dth\Email\Filament\Resources\SendingDomainResource;
use Dth\Email\Filament\Support\StatusColor;
use Dth\Email\Filament\Widgets\Concerns\UsesEmailDashboardFilters;
use Dth\Email\Services\EmailAnalyticsService;
use Dth\Email\Support\UiText;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EmailInfrastructureOverview extends StatsOverviewWidget
{
    use UsesEmailDashboardFilters;

    protected static bool $isLazy = false;
    protected ?string $pollingInterval = null;
    protected int|string|array $columnSpan = 'full';
    protected int|array|null $columns = [
        'md' => 2,
        'xl' => 3,
    ];

    protected function getHeading(): ?string
    {
        return UiText::get('dashboard.infrastructure.heading', 'Email infrastructure');
    }

    protected function getDescription(): ?string
    {
        return null;
    }

    protected function getStats(): array
    {
        $health = app(EmailAnalyticsService::class)->systemHealth();
        $accountErrors = $health->errorSendingAccounts;
        $domainErrors = $health->failedSendingDomains;

        return [
            Stat::make(
                UiText::get('dashboard.infrastructure.scheduler', 'Scheduler'),
                UiText::status($health->schedulerHealth),
            )
                ->icon('heroicon-o-clock')
                ->description($this->lastSeenText($health->schedulerLastSeenAt))
                ->color(StatusColor::for($health->schedulerHealth)),

            Stat::make(
                UiText::get('dashboard.infrastructure.queue_worker', 'Queue worker'),
                UiText::status($health->queueWorkerHealth),
            )
                ->icon('heroicon-o-cpu-chip')
                ->description($health->queueWorkerHealth === 'not_required'
                    ? UiText::get('dashboard.infrastructure.sync_queue', 'Sync queue')
                    : $this->lastSeenText($health->queueWorkerLastSeenAt))
                ->color(StatusColor::for($health->queueWorkerHealth)),

            Stat::make(
                UiText::get('dashboard.infrastructure.accounts', 'Sending accounts'),
                $health->activeSendingAccounts.'/'.$health->sendingAccounts,
            )
                ->icon('heroicon-o-paper-airplane')
                ->description(UiText::get('dashboard.infrastructure.account_errors', ':count accounts in error', ['count' => $accountErrors]))
                ->color($accountErrors > 0 ? 'danger' : 'success')
                ->url(SendingAccountResource::getUrl('index')),

            Stat::make(
                UiText::get('dashboard.infrastructure.domains', 'Verified domains'),
                $health->verifiedSendingDomains.'/'.$health->sendingDomains,
            )
                ->icon('heroicon-o-globe-alt')
                ->description(UiText::get('dashboard.infrastructure.domain_errors', ':count domains failed verification', ['count' => $domainErrors]))
                ->color($domainErrors > 0 ? 'danger' : 'success')
                ->url(SendingDomainResource::getUrl('index')),

            Stat::make(
                UiText::get('dashboard.infrastructure.pending_jobs', 'Pending email jobs'),
                $health->pendingEmailJobs === null ? 'N/A' : number_format($health->pendingEmailJobs),
            )
                ->icon('heroicon-o-queue-list')
                ->color(($health->pendingEmailJobs ?? 0) > 0 ? 'warning' : 'success'),

            Stat::make(
                UiText::get('dashboard.infrastructure.failed_jobs', 'Failed jobs'),
                $health->failedJobs === null ? 'N/A' : number_format($health->failedJobs),
            )
                ->icon('heroicon-o-exclamation-circle')
                ->color(($health->failedJobs ?? 0) > 0 ? 'danger' : 'success'),
        ];
    }

    private function lastSeenText(mixed $at): string
    {
        if (! $at) {
            return UiText::get('dashboard.infrastructure.never_seen', 'No heartbeat recorded');
        }

        return UiText::get('dashboard.infrastructure.last_seen', 'Last seen :time', [
            'time' => $at->diffForHumans(),
        ]);
    }
}
