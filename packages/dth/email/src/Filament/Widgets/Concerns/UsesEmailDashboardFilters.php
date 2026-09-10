<?php

namespace Dth\Email\Filament\Widgets\Concerns;

use Dth\Email\DTO\EmailAnalyticsFilters;
use Dth\Email\Services\EmailDashboardFilterResolver;

trait UsesEmailDashboardFilters
{
    /** @var array<string, mixed>|null */
    public ?array $dashboardFilters = null;

    protected function analyticsFilters(): EmailAnalyticsFilters
    {
        return app(EmailDashboardFilterResolver::class)->resolve($this->dashboardFilters);
    }
}
