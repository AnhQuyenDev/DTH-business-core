<?php

namespace App\Filament\Resources\CompanyResource\RelationManagers;

use App\Enums\Crm\StaffEmploymentStatus;
use App\Models\Crm\CompanyAssignment;
use App\Models\Crm\Staff;
use App\Services\Crm\CompanyOwnershipService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class AssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'assignments';

    public static function getTitle(
        Model $ownerRecord,
        string $pageClass
    ): string {
        return __('relation.title.assignments');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('assignment_type')
                    ->label(__('field.assignment_type'))
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('field.status'))
                    ->badge(),
                TextColumn::make('staff.full_name')
                    ->label(__('field.staff')),
                TextColumn::make('reason')
                    ->label(__('field.reason'))
                    ->wrap(),
                TextColumn::make('starts_at')
                    ->label(__('field.starts_at'))
                    ->dateTime('d/m/Y H:i'),
                TextColumn::make('ends_at')
                    ->label(__('field.ends_at'))
                    ->dateTime('d/m/Y H:i'),
            ])
            ->defaultSort('starts_at', 'desc')
            ->headerActions([
                Action::make('assign_owner')
                    ->label(fn (): string => $this->getOwnerRecord()
                        ->account_owner_staff_id === null
                            ? __('action.assign_account_owner')
                            : __('action.transfer_account_owner'))
                    ->icon('heroicon-o-user-plus')
                    ->form([
                        Select::make('staff_id')
                            ->label(__('field.staff'))
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
                            ->preload()
                            ->required(),
                        Textarea::make('reason')
                            ->label(__('field.reason'))
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->action(function (array $data): void {
                        app(CompanyOwnershipService::class)->assignOwner(
                            company: $this->getOwnerRecord(),
                            staff: Staff::query()->findOrFail(
                                $data['staff_id']
                            ),
                            reason: $data['reason'],
                            assignedByUserId: auth()->id(),
                        );

                        Notification::make()
                            ->title(__('notification.company_owner_updated'))
                            ->success()
                            ->send();
                    })
                    ->visible(
                        fn (): bool => Gate::allows(
                            'crm.manage-company-owner'
                        )
                    ),
            ])
            ->actions([
                Action::make('end_assignment')
                    ->label(__('action.end_assignment'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('reason')
                            ->label(__('field.reason'))
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->visible(
                        fn (CompanyAssignment $record): bool => $record->status === 'active'
                            && Gate::allows('crm.manage-company-owner')
                    )
                    ->action(function (
                        CompanyAssignment $record,
                        array $data,
                    ): void {
                        app(CompanyOwnershipService::class)
                            ->endAssignment(
                                assignment: $record,
                                reason: $data['reason'],
                                endedByUserId: auth()->id(),
                            );

                        Notification::make()
                            ->title(__('notification.assignment_ended'))
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
