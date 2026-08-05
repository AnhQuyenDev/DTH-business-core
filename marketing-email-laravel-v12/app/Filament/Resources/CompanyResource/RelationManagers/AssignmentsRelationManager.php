<?php

namespace App\Filament\Resources\CompanyResource\RelationManagers;

use App\Models\Crm\CompanyAssignment;
use App\Models\Crm\Staff;
use App\Services\Crm\CompanyOwnershipService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

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
                    ->dateTime(),
                TextColumn::make('ends_at')
                    ->label(__('field.ends_at'))
                    ->dateTime(),
            ])
            ->headerActions([
                Action::make('assign_owner')
                    ->label('Giao Account Owner')
                    ->icon('heroicon-o-user-plus')
                    ->form([
                        Select::make('staff_id')
                            ->label(__('field.staff'))
                            ->options(
                                Staff::query()
                                    ->orderBy('full_name')
                                    ->pluck('full_name', 'id')
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
                    })
                    ->visible(
                        fn (): bool => auth()->user()?->isAdmin() ?? false
                    ),
            ])
            ->actions([
                Action::make('end_assignment')
                    ->label('Kết thúc')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->visible(
                        fn (CompanyAssignment $record): bool => $record->status === 'active'
                            && (auth()->user()?->isAdmin() ?? false)
                    )
                    ->action(
                        fn (CompanyAssignment $record): mixed => app(CompanyOwnershipService::class)
                            ->endAssignment($record)
                    ),
            ]);
    }
}
