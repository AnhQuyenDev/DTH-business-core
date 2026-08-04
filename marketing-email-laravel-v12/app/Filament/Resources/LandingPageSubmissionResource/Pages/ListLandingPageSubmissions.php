<?php

namespace App\Filament\Resources\LandingPageSubmissionResource\Pages;

use App\Enums\Crm\DistributionStrategy;
use App\Filament\Resources\LandingPageSubmissionResource;
use App\Models\Crm\Staff;
use App\Models\Marketing\LandingPage;
use App\Services\Crm\LeadDistributionService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListLandingPageSubmissions extends ListRecords
{
    protected static string $resource = LandingPageSubmissionResource::class;

    public function getTitle(): string
    {
        return __('page.title.form_submissions');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('auto_distribute')
                ->label(__('action.auto_distribute'))
                ->icon('heroicon-o-share')
                ->color('success')
                ->form([
                    Forms\Components\Select::make('strategy')
                        ->label(__('action.distribute_strategy'))
                        ->options([
                            DistributionStrategy::LeastLoaded->value => __('action.distribute_strategy.least_loaded'),
                            DistributionStrategy::RoundRobin->value => __('action.distribute_strategy.round_robin'),
                            DistributionStrategy::Weighted->value => __('action.distribute_strategy.weighted'),
                        ])
                        ->default(DistributionStrategy::LeastLoaded->value)
                        ->required(),
                    Forms\Components\Select::make('staff_ids')
                        ->label(__('action.distribute_select_staff'))
                        ->multiple()
                        ->options(
                            Staff::query()
                                ->where('employment_status', 'active')
                                ->where('can_receive_customers', true)
                                ->orderBy('full_name')
                                ->get()
                                ->mapWithKeys(fn (Staff $s) => [
                                    $s->id => "{$s->full_name} ({$s->employee_code})",
                                ])
                        )
                        ->searchable(),
                    Forms\Components\Select::make('landing_page_id')
                        ->label(__('action.distribute_select_landing_page'))
                        ->options(LandingPage::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->placeholder(__('action.all')),
                ])
                ->action(function (array $data): void {
                    $service = app(LeadDistributionService::class);
                    $result = $service->distributeUnassigned(
                        strategy: DistributionStrategy::from($data['strategy']),
                        staffIds: filled($data['staff_ids']) ? $data['staff_ids'] : null,
                        landingPageId: filled($data['landing_page_id']) ? (int) $data['landing_page_id'] : null,
                    );

                    if ($result['total'] === 0) {
                        Notification::make()
                            ->title(__('notification.no_unassigned'))
                            ->info()
                            ->send();
                        return;
                    }

                    Notification::make()
                        ->title(__('notification.distribute_complete'))
                        ->body(str_replace(
                            ['{assigned}', '{total}', '{skipped}'],
                            [$result['assigned'], $result['total'], $result['skipped']],
                            __('notification.distribute_result')
                        ))
                        ->success()
                        ->send();
                }),
        ];
    }
}
