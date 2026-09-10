<?php

namespace Dth\Email\Filament\Widgets\Concerns;

use Dth\Email\DTO\EmailAnalyticsFilters;
use Dth\Email\Services\EmailDashboardFilterResolver;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

trait UsesEmailDashboardFilters
{
    use InteractsWithPageFilters;

    protected function analyticsFilters(): EmailAnalyticsFilters
    {
        return app(EmailDashboardFilterResolver::class)->resolve($this->pageFilters);
    }
}
