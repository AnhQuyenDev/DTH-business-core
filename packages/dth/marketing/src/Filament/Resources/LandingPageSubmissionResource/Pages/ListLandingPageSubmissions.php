<?php

namespace Dth\Marketing\Filament\Resources\LandingPageSubmissionResource\Pages;

use Dth\Marketing\Filament\Resources\LandingPageSubmissionResource;
use Dth\Marketing\Filament\Support\MarketingPageUi;
use Dth\Marketing\Support\UiText;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListLandingPageSubmissions extends ListRecords
{
    protected static string $resource = LandingPageSubmissionResource::class;

    public function getTitle(): string|Htmlable
    {
        return MarketingPageUi::title(UiText::get('pages.submissions.title', 'Landing Page Submissions'), 'submission', 'green');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.submissions.subheading', 'Review captured identities, processing status, attribution data, and CRM lead synchronization.');
    }
}
