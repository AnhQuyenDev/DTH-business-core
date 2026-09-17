<?php

namespace Dth\Crm\Filament\Resources\CustomerDistributionBatchResource\Pages;

use Dth\Crm\Filament\Resources\CustomerDistributionBatchResource;
use Dth\Crm\Filament\Support\CrmPageUi;
use Dth\Crm\Models\CustomerDistributionBatch;
use Dth\Crm\Support\CrmOptions;
use Dth\Crm\Support\UiText;
use Filament\Actions\Action;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;

class ListCustomerDistributionBatchs extends ListRecords
{
    protected static string $resource = CustomerDistributionBatchResource::class;

    public function getTitle(): string|Htmlable
    {
        return CrmPageUi::title(UiText::get('pages.distribution.title', 'Customer distribution'), 'distribution', 'blue');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.distribution.subheading', 'Create and monitor customer distribution batches while preserving the existing assignment strategy.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createDistributionBatch')
                ->label(UiText::get('pages.distribution.create', 'Create distribution batch'))
                ->icon('heroicon-o-arrows-right-left')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-crm-entry-action dth-crm-entry-action--blue'])
                ->modalHeading(UiText::get('pages.distribution.create', 'Create distribution batch'))
                ->modalIcon('heroicon-o-arrows-right-left')
                ->modalWidth(Width::ThreeExtraLarge)
                ->modalSubmitAction(fn (Action $action): Action => $action->label(UiText::get('common.actions.save', 'Save'))->icon('heroicon-o-check'))
                ->modalCancelAction(fn (Action $action): Action => $action->label(UiText::get('common.actions.cancel', 'Cancel'))->icon('heroicon-o-x-mark')->color('gray'))
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('batch_type')
                            ->label(UiText::get('fields.batch_type', 'Batch type'))
                            ->options(CrmOptions::batchTypes())
                            ->native(false)
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Chọn loại batch phân phối phù hợp với mục đích nghiệp vụ.'),
                        Select::make('strategy')
                            ->label(UiText::get('fields.strategy', 'Strategy'))
                            ->options(CrmOptions::distributionStrategies())
                            ->native(false)
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Chiến lược CRM sẽ dùng để phân phối khách hàng trong đợt này.'),
                        Select::make('status')
                            ->label(UiText::get('common.fields.status', 'Status'))
                            ->options(CrmOptions::batchStatuses())
                            ->native(false)
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Trạng thái vận hành hiện tại của đợt phân phối khách hàng.'),
                    ]),
                ])
                ->action(function (array $data): void {
                    CustomerDistributionBatch::query()->create($data);
                    Notification::make()->success()->title(UiText::get('notifications.distribution_created', 'Distribution batch created'))->send();
                })
                ->visible(fn (): bool => CustomerDistributionBatchResource::canCreate()),
        ];
    }
}
