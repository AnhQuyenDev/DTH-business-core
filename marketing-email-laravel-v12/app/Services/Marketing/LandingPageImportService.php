<?php

namespace App\Services\Marketing;

use App\Models\Marketing\LandingPage;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class LandingPageImportService
{
    public function __construct(
        private readonly LandingPageRenderService $renderer,
        private readonly LandingPageThemeService $themeService,
    ) {}

    public function import(
        array $data,
        string $sourceHtml,
        ?int $userId
    ): LandingPage {
        if (blank(trim($sourceHtml))) {
            throw new RuntimeException(
                'File HTML Landing Page không có nội dung.'
            );
        }

        $preparedHtml = $this->renderer
            ->prepareImportedLandingPageHtml($sourceHtml);

        $themeTokens = $this->themeService
            ->detectFromHtml($preparedHtml);

        return DB::transaction(function () use (
            $data,
            $preparedHtml,
            $themeTokens,
            $userId
        ): LandingPage {
            return LandingPage::query()->create([
                'name' => (string) $data['name'],
                'slug' => (string) $data['slug'],
                'marketing_campaign_id' => $data['marketing_campaign_id'] ?? null,
                'campaign_id' => $data['campaign_id'] ?? null,
                'html_body' => $preparedHtml,
                'theme_tokens' => $themeTokens,
                'status' => 'draft',
                'created_by' => $userId,
            ]);
        });
    }
}
