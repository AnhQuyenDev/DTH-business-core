<?php

namespace App\Filament\Resources\Sales\OpportunityResource\Pages;

use App\Enums\Sales\OpportunityStage;
use App\Filament\Resources\Sales\OpportunityResource;
use App\Models\Crm\Lead;
use App\Models\Crm\Staff;
use App\Services\Sales\OpportunityCreationService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListOpportunities extends ListRecords
{
    protected static string $resource = OpportunityResource::class;

    public function getTitle(): string
    {
        return __('page.title.opportunities');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_from_qualified_lead')
                ->label(__('action.create_opportunity'))
                ->icon('heroicon-o-plus')
                ->color('success')
                ->visible(fn (): bool => OpportunityResource::canCreateOpportunities())
                ->form([
                    Select::make('lead_id')
                        ->label(__('field.lead'))
                        ->options(OpportunityResource::qualifiedLeadOptions())
                        ->searchable()
                        ->required(),
                    Select::make('sales_staff_id')
                        ->label(__('field.sales_owner'))
                        ->options(function (): array {
                            $query = Staff::query()->eligibleForOpportunityOwnership();

                            if (! auth()->user()?->isSalesManager()) {
                                $query->whereKey(auth()->user()?->staff?->id ?? 0);
                            }

                            return $query->orderBy('full_name')->get()
                                ->mapWithKeys(fn (Staff $staff): array => [
                                    $staff->id => $staff->full_name.' ('.$staff->employee_code.')',
                                ])->all();
                        })
                        ->default(fn (): ?int => auth()->user()?->staff?->id)
                        ->searchable()
                        ->preload()
                        ->required(),
                    TextInput::make('title')
                        ->label(__('field.title'))
                        ->maxLength(255),
                    TextInput::make('service_interest')
                        ->label(__('field.service_interest'))
                        ->maxLength(255),
                    TextInput::make('estimated_value')
                        ->label(__('field.estimated_value'))
                        ->numeric()
                        ->minValue(0),
                    TextInput::make('probability')
                        ->label(__('field.probability'))
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100),
                    DatePicker::make('expected_close_date')
                        ->label(__('field.expected_close_date')),
                ])
                ->action(function (array $data): void {
                    $lead = Lead::query()->findOrFail($data['lead_id']);

                    $opportunity = app(OpportunityCreationService::class)
                        ->createFromQualifiedLead(
                            lead: $lead,
                            salesOwner: Staff::query()->findOrFail($data['sales_staff_id']),
                            data: $data,
                            actorUserId: (int) auth()->id(),
                        );

                    Notification::make()
                        ->title(__('notification.opportunity_created'))
                        ->success()
                        ->send();

                    $this->redirect(
                        OpportunityResource::getUrl('view', [
                            'record' => $opportunity,
                        ])
                    );
                }),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'overview' => Tab::make(__('action.all')),
            'discovery' => Tab::make(
                OpportunityStage::Discovery->label()
            )->modifyQueryUsing(
                fn (Builder $query): Builder => $query->where(
                    'stage',
                    OpportunityStage::Discovery->value
                )
            ),
            'qualified' => Tab::make(
                OpportunityStage::Qualified->label()
            )->modifyQueryUsing(
                fn (Builder $query): Builder => $query->where(
                    'stage',
                    OpportunityStage::Qualified->value
                )
            ),
            'proposal' => Tab::make(
                OpportunityStage::Proposal->label()
            )->modifyQueryUsing(
                fn (Builder $query): Builder => $query->where(
                    'stage',
                    OpportunityStage::Proposal->value
                )
            ),
            'negotiation' => Tab::make(
                OpportunityStage::Negotiation->label()
            )->modifyQueryUsing(
                fn (Builder $query): Builder => $query->where(
                    'stage',
                    OpportunityStage::Negotiation->value
                )
            ),
            'won' => Tab::make(
                OpportunityStage::Won->label()
            )->modifyQueryUsing(
                fn (Builder $query): Builder => $query->where(
                    'stage',
                    OpportunityStage::Won->value
                )
            ),
            'lost' => Tab::make(
                OpportunityStage::Lost->label()
            )->modifyQueryUsing(
                fn (Builder $query): Builder => $query->where(
                    'stage',
                    OpportunityStage::Lost->value
                )
            ),
        ];

        return $tabs;
    }
}
