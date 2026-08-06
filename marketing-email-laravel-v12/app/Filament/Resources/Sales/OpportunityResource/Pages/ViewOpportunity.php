<?php

namespace App\Filament\Resources\Sales\OpportunityResource\Pages;

use App\Enums\Sales\OpportunityStage;
use App\Filament\Resources\Sales\OpportunityResource;
use App\Services\Sales\OpportunityContactService;
use App\Services\Sales\OpportunityWorkflowService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOpportunity extends ViewRecord
{
    protected static string $resource = OpportunityResource::class;

    public function getTitle(): string
    {
        return $this->record->opportunity_code;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('record_interaction')
                ->label(__('action.record_interaction'))
                ->icon('heroicon-o-chat-bubble-left-right')
                ->form(OpportunityResource::interactionForm())
                ->visible(
                    fn (): bool => OpportunityResource::canProcessOpportunity($this->record)
                        && ! $this->record->isTerminal()
                )
                ->action(function (array $data): void {
                    $this->record->interactions()->create(array_merge($data, [
                        'staff_id' => auth()->user()?->staff?->id,
                    ]));

                    $this->record->refresh();

                    Notification::make()
                        ->title(__('notification.opportunity_interaction_recorded'))
                        ->success()
                        ->send();
                }),

            Action::make('add_contact')
                ->label(__('action.add_contact'))
                ->icon('heroicon-o-user-plus')
                ->form(OpportunityResource::addContactForm())
                ->visible(
                    fn (): bool => OpportunityResource::canProcessOpportunity($this->record)
                        && ! $this->record->isTerminal()
                )
                ->action(function (array $data): void {
                    app(OpportunityContactService::class)->upsert(
                        opportunity: $this->record,
                        contactId: (int) $data['contact_id'],
                        role: (string) ($data['role'] ?? 'other'),
                        isPrimary: (bool) ($data['is_primary'] ?? false),
                        actorUserId: auth()->id(),
                    );

                    $this->record->refresh();

                    Notification::make()
                        ->title(__('notification.opportunity_contact_added'))
                        ->success()
                        ->send();
                }),

            Action::make('change_stage')
                ->label(__('action.change_stage'))
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->form(
                    fn (): array => OpportunityResource::stageForm($this->record)
                )
                ->visible(
                    fn (): bool => OpportunityResource::canProcessOpportunity($this->record)
                        && ! $this->record->isTerminal()
                )
                ->action(function (array $data): void {
                    app(OpportunityWorkflowService::class)->transition(
                        opportunity: $this->record,
                        to: OpportunityStage::from($data['stage']),
                        data: $data,
                        actorUserId: auth()->id(),
                    );

                    $this->record->refresh();

                    Notification::make()
                        ->title(__('notification.opportunity_stage_changed'))
                        ->success()
                        ->send();
                }),

            Action::make('mark_lost')
                ->label(__('action.mark_lost'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->form(OpportunityResource::lostForm())
                ->visible(
                    fn (): bool => OpportunityResource::canProcessOpportunity($this->record)
                        && ! $this->record->isTerminal()
                )
                ->action(function (array $data): void {
                    app(OpportunityWorkflowService::class)->transition(
                        opportunity: $this->record,
                        to: OpportunityStage::Lost,
                        data: $data,
                        actorUserId: auth()->id(),
                    );

                    $this->record->refresh();

                    Notification::make()
                        ->title(__('notification.opportunity_lost'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
