<?php

namespace App\Filament\Pages;

use App\Services\Dashboard\WorkforceAnalyticsService;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WorkforceAnalyticsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static bool $shouldRegisterNavigation = false;
    protected static string $view = 'filament.pages.workforce-analytics';

    public string $period = '30d';

    public static function getNavigationLabel(): string
    {
        return __('analytics.workforce_analysis');
    }

    public function getTitle(): string
    {
        return __('analytics.workforce_analysis');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can('system.view-workforce');
    }

    protected function getViewData(): array
    {
        return [
            'analytics' => app(WorkforceAnalyticsService::class)->report(auth()->user(), $this->period),
        ];
    }

    public function exportCsv(): StreamedResponse
    {
        $data = app(WorkforceAnalyticsService::class)->report(auth()->user(), $this->period);
        $filename = 'workforce-analytics-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($data): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [__('analytics.workforce_analysis')]);
            fputcsv($out, [__('field.staff'), __('field.department'), __('field.position'), __('analytics.activity_index'), __('analytics.activity'), __('analytics.customers'), __('analytics.impact'), __('analytics.highlight')]);
            foreach ($data['staff'] as $row) {
                fputcsv($out, [$row['name'], $row['department'], $row['position'], $row['activity_index'], $row['activity_count'], $row['customers'], $row['impact_value'], $row['highlight']]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
