<?php

namespace App\Filament\Resources\Sales\OpportunityResource\Pages;

use App\Enums\Sales\OpportunityStage;
use App\Filament\Resources\Sales\OpportunityResource;
use App\Models\Crm\Lead;
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
                            data: $data,
                            createdByUserId: auth()->id(),
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

    protected function getEloquentQuery(): Builder
    {
        return OpportunityResource::scopeForUser(parent::getEloquentQuery());
    }
}
