<?php

namespace Dth\Crm\Filament\Resources\CompanyMatchCandidateResource\Pages;

use Dth\Crm\Filament\Resources\CompanyMatchCandidateResource;
use Dth\Crm\Filament\Support\CrmPageUi;
use Dth\Crm\Support\UiText;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListCompanyMatchCandidates extends ListRecords
{
    protected static string $resource = CompanyMatchCandidateResource::class;

    public function getTitle(): string|Htmlable
    {
        return CrmPageUi::title(UiText::get('pages.match_candidates.title', 'Company matching'), 'match', 'blue');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.match_candidates.subheading', 'Review suggested company matches and confirm the correct relationship for CRM contacts.');
    }
}
