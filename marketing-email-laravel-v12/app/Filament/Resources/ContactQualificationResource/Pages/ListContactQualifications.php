<?php

namespace App\Filament\Resources\ContactQualificationResource\Pages;

use App\Enums\Crm\ContactQualificationStatus;
use App\Filament\Resources\ContactQualificationResource;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListContactQualifications extends ListRecords
{
    protected static string $resource = ContactQualificationResource::class;

    public function getTitle(): string
    {
        return __('page.title.lead_pipeline');
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('action.all')),
            'not_contacted' => Tab::make(__('enum.qualification.not_contacted'))
                ->modifyQueryUsing(fn ($query) => $query->whereIn('status', [
                    ContactQualificationStatus::New->value,
                    ContactQualificationStatus::Assigned->value,
                ])),
            'in_progress' => Tab::make(__('enum.qualification.in_progress'))
                ->modifyQueryUsing(fn ($query) => $query->whereIn('status', [
                    ContactQualificationStatus::Contacting->value,
                    ContactQualificationStatus::FollowUp->value,
                ])),
            'qualified' => Tab::make(__('enum.qualification.qualified'))
                ->modifyQueryUsing(fn ($query) => $query->where('status', ContactQualificationStatus::Qualified->value)),
            'converted' => Tab::make(__('enum.qualification.converted'))
                ->modifyQueryUsing(fn ($query) => $query->where('status', ContactQualificationStatus::Converted->value)),
        ];
    }

}
