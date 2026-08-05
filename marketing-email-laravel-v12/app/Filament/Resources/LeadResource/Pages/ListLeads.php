<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Crm\DistributionStrategy;
use App\Enums\Crm\StaffEmploymentStatus;
use App\Filament\Resources\LeadResource;
use App\Models\Crm\Staff;
use App\Models\Marketing\LandingPage;
use App\Services\Crm\LeadDistributionService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Gate;

class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;

    public function getTitle(): string
    {
        return __('page.title.leads');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('auto_distribute')
                ->label(__('action.auto_distribute'))
                ->icon('heroicon-o-share')
                ->color('success')
                ->visible(
                    fn (): bool => Gate::allows('crm.assign-lead')
                )
                ->form([
                    Forms\Components\Select::make('strategy')
                        ->label(__('action.distribute_strategy'))
                        ->options([
                            DistributionStrategy::LeastLoaded->value => DistributionStrategy::LeastLoaded->label(),
                            DistributionStrategy::RoundRobin->value => DistributionStrategy::RoundRobin->label(),
                            DistributionStrategy::Weighted->value => DistributionStrategy::Weighted->label(),
                        ])
                        ->default(
                            DistributionStrategy::LeastLoaded->value
                        )
                        ->required(),

                    Forms\Components\Select::make('staff_ids')
                        ->label(__('action.distribute_select_staff'))
                        ->multiple()
                        ->options(
                            fn (): array => Staff::query()
                                ->where(
                                    'employment_status',
                                    StaffEmploymentStatus::Active->value
                                )
                                ->where('can_receive_customers', true)
                                ->whereDoesntHave(
                                    'availabilities',
                                    fn ($query) => $query
                                        ->active()
                                        ->where(
                                            'can_receive_new_customers',
                                            false
                                        )
                                )
                                ->orderBy('full_name')
                                ->get()
                                ->mapWithKeys(fn (Staff $staff): array => [
                                    $staff->id => "{$staff->full_name} "
                                        ."({$staff->employee_code})",
                                ])
                                ->all()
                        )
                        ->searchable()
                        ->preload(),

                    Forms\Components\Select::make('landing_page_id')
                        ->label(
                            __('action.distribute_select_landing_page')
                        )
                        ->options(
                            LandingPage::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                        )
                        ->searchable()
                        ->placeholder(__('action.all')),
                ])
                ->action(function (array $data): void {
                    $result = app(LeadDistributionService::class)
                        ->distributeUnassigned(
                            strategy: DistributionStrategy::from(
                                $data['strategy']
                            ),
                            staffIds: filled($data['staff_ids'] ?? null)
                                ? $data['staff_ids']
                                : null,
                            landingPageId: filled($data['landing_page_id'] ?? null)
                                    ? (int) $data['landing_page_id']
                                    : null,
                            assignedByUserId: auth()->id(),
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
                            [
                                $result['assigned'],
                                $result['total'],
                                $result['skipped'],
                            ],
                            __('notification.distribute_result')
                        ))
                        ->status(
                            $result['skipped'] > 0
                                ? 'warning'
                                : 'success'
                        )
                        ->send();
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'overview' => Tab::make(__('action.all')),

            'not_contacted' => Tab::make(
                __('enum.qualification.not_contacted')
            )->modifyQueryUsing(fn ($query) => $query->whereHas(
                'qualification',
                fn ($query) => $query->where(
                    'status',
                    ContactQualificationStatus::New->value
                )
            )),

            'in_progress' => Tab::make(
                __('enum.qualification.in_progress')
            )->modifyQueryUsing(fn ($query) => $query->whereHas(
                'qualification',
                fn ($query) => $query->whereIn('status', [
                    ContactQualificationStatus::Assigned->value,
                    ContactQualificationStatus::Contacting->value,
                    ContactQualificationStatus::FollowUp->value,
                ])
            )),

            'qualified' => Tab::make(
                __('enum.qualification.qualified')
            )->modifyQueryUsing(fn ($query) => $query->whereHas(
                'qualification',
                fn ($query) => $query->where(
                    'status',
                    ContactQualificationStatus::Qualified->value
                )
            )),

            'closed' => Tab::make(
                __('enum.qualification.unqualified')
            )->modifyQueryUsing(fn ($query) => $query->whereHas(
                'qualification',
                fn ($query) => $query->whereIn('status', [
                    ContactQualificationStatus::Unqualified->value,
                    ContactQualificationStatus::Duplicate->value,
                    ContactQualificationStatus::Spam->value,
                    ContactQualificationStatus::Archived->value,
                ])
            )),

            'converted' => Tab::make(
                __('enum.qualification.converted')
            )->modifyQueryUsing(fn ($query) => $query->whereHas(
                'qualification',
                fn ($query) => $query->where(
                    'status',
                    ContactQualificationStatus::Converted->value
                )
            )),
        ];
    }
}
