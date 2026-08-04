<?php

namespace App\Filament\Widgets;

use App\Models\Marketing\LandingPage;
use App\Services\Marketing\UtmReportService;
use Filament\Widgets\Widget;
use Illuminate\Support\HtmlString;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MarketingUtmReportWidget extends Widget
{
    protected static string $view = 'filament.widgets.utm-report-widget';

    protected int | string | array $columnSpan = 'full';

    public ?int $landingPageId = null;

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function getLandingPageOptions(): array
    {
        return LandingPage::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    public function getReport(): HtmlString
    {
        return app(UtmReportService::class)->render($this->landingPageId);
    }

    public function export(): ?StreamedResponse
    {
        if (! $this->landingPageId) {
            return null;
        }

        return app(UtmReportService::class)->exportCsv($this->landingPageId);
    }
}
