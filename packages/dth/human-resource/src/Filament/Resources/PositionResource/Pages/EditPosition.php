<?php

namespace Dth\HumanResource\Filament\Resources\PositionResource\Pages;

use Dth\HumanResource\Filament\Resources\PositionResource;
use Dth\HumanResource\Filament\Support\HumanResourcePageUi;
use Dth\HumanResource\Support\UiText;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditPosition extends EditRecord
{
    protected static string $resource = PositionResource::class;

    public function getTitle(): string|Htmlable
    {
        return HumanResourcePageUi::title(
            UiText::get('pages.position_edit.title', 'Edit job title'),
            'position',
            'amber',
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label(UiText::get('common.actions.delete', 'Delete'))
                ->icon('heroicon-o-trash'),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label(UiText::get('common.actions.save', 'Save'))
                ->icon('heroicon-o-check-circle')
                ->extraAttributes(['class' => 'dth-hr-form-action dth-hr-form-action--primary']),
            $this->getCancelFormAction()
                ->label(UiText::get('common.actions.cancel', 'Cancel'))
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-hr-form-action dth-hr-form-action--secondary']),
        ];
    }
}
