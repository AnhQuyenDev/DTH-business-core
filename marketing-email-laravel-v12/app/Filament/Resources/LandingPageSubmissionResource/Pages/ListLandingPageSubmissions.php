<?php

namespace App\Filament\Resources\LandingPageSubmissionResource\Pages;

use App\Filament\Resources\LandingPageSubmissionResource;
use Filament\Resources\Pages\ListRecords;

class ListLandingPageSubmissions extends ListRecords
{
    protected static string $resource =
        LandingPageSubmissionResource::class;

    public function getTitle(): string
    {
        return __('page.title.form_submissions');
    }
}
