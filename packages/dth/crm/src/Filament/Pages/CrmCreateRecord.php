<?php

namespace Dth\Crm\Filament\Pages;

use Dth\Crm\Filament\Support\CrmPageUi;
use Dth\Crm\Support\UiText;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

abstract class CrmCreateRecord extends CreateRecord
{
    public function getTitle(): string|Htmlable
    {
        $resource = static::getResource();
        $title = UiText::get('common.actions.add', 'Add').' '.$resource::getModelLabel();

        return CrmPageUi::title($title, $this->pageIcon(), $this->pageTone());
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label(UiText::get('common.actions.save', 'Save'))
                ->icon('heroicon-o-check-circle')
                ->color('primary')
                ->extraAttributes(['class' => 'dth-crm-form-action dth-crm-form-action--primary']),
            $this->getCancelFormAction()
                ->label(UiText::get('common.actions.cancel', 'Cancel'))
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-crm-form-action dth-crm-form-action--secondary']),
        ];
    }

    private function pageIcon(): string
    {
        $resource = static::getResource();

        return match (true) {
            str_contains($resource, 'Company') || str_contains($resource, 'BusinessContact') => 'company',
            str_contains($resource, 'Lead') => 'lead',
            str_contains($resource, 'Qualification') => 'qualification',
            str_contains($resource, 'CustomerDistribution') => 'distribution',
            str_contains($resource, 'Customer') => 'customer',
            str_contains($resource, 'AgentProfile') || str_contains($resource, 'Staff') => 'agent',
            default => 'contact',
        };
    }

    private function pageTone(): string
    {
        $resource = static::getResource();

        return match (true) {
            str_contains($resource, 'Company') || str_contains($resource, 'BusinessContact') => 'blue',
            str_contains($resource, 'Lead') => 'amber',
            str_contains($resource, 'Qualification') || str_contains($resource, 'AgentProfile') => 'violet',
            default => 'teal',
        };
    }
}
