<?php

namespace Dth\Email\Filament\Widgets\Dashboard;

use Dth\Email\Filament\Resources\SendingAccountResource;
use Dth\Email\Filament\Resources\SendingDomainResource;
use Dth\Email\Services\EmailAnalyticsService;
use Dth\Email\Support\UiText;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EmailInfrastructureOverview extends StatsOverviewWidget
{
    protected static bool $isLazy = false;
    protected ?string $pollingInterval = null;
    protected int|string|array $columnSpan = [
        'md' => 6,
        'xl' => 4,
    ];
    protected int|array|null $columns = [
        'md' => 2,
    ];

    protected function getHeading(): ?string
    {
        return UiText::get('dashboard.infrastructure.heading', 'Email infrastructure');
    }

    protected function getDescription(): ?string
    {
        return UiText::get(
            'dashboard.infrastructure.description',
            'A lightweight readiness snapshot. Worker heartbeat monitoring is added in Step I.'
        );
    }

    protected function getStats(): array
    {
        $health = app(EmailAnalyticsService::class)->systemHealth();
        $accountErrors = $health->errorSendingAccounts;
        $domainErrors = $health->failedSendingDomains;

        return [
            Stat::make(
                UiText::get('dashboard.infrastructure.accounts', 'Sending accounts'),
                $health->activeSendingAccounts.'/'.$health->sendingAccounts,
            )
                ->icon('heroicon-o-paper-airplane')
                ->description(UiText::get(
                    'dashboard.infrastructure.account_errors',
                    ':count accounts in error',
                    ['count' => $accountErrors],
                ))
                ->color($accountErrors > 0 ? 'danger' : 'success')
                ->url(SendingAccountResource::getUrl('index')),

            Stat::make(
                UiText::get('dashboard.infrastructure.domains', 'Verified domains'),
                $health->verifiedSendingDomains.'/'.$health->sendingDomains,
            )
                ->icon('heroicon-o-globe-alt')
                ->description(UiText::get(
                    'dashboard.infrastructure.domain_errors',
                    ':count domains failed verification',
                    ['count' => $domainErrors],
                ))
                ->color($domainErrors > 0 ? 'danger' : 'success')
                ->url(SendingDomainResource::getUrl('index')),

            Stat::make(
                UiText::get('dashboard.infrastructure.pending_jobs', 'Pending email jobs'),
                $health->pendingEmailJobs === null ? 'N/A' : number_format($health->pendingEmailJobs),
            )
                ->icon('heroicon-o-queue-list')
                ->description(UiText::get(
                    'dashboard.infrastructure.queue_note',
                    'Queue depth for the configured email queue.'
                ))
                ->color(($health->pendingEmailJobs ?? 0) > 0 ? 'warning' : 'gray'),

            Stat::make(
                UiText::get('dashboard.infrastructure.failed_jobs', 'Failed jobs'),
                $health->failedJobs === null ? 'N/A' : number_format($health->failedJobs),
            )
                ->icon('heroicon-o-exclamation-circle')
                ->description(UiText::get(
                    'dashboard.infrastructure.failed_jobs_note',
                    'Failed queue jobs requiring operational review.'
                ))
                ->color(($health->failedJobs ?? 0) > 0 ? 'danger' : 'success'),
        ];
    }
}
