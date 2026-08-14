<?php

namespace App\Livewire;

use App\Enums\Crm\StaffEmploymentStatus;
use App\Filament\Resources\StaffResource;
use App\Filament\Resources\UserResource;
use App\Models\Crm\Staff;
use App\Support\Ui\BadgePalette;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

class DepartmentStaffTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public int $departmentId = 0;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Staff::query()
                    ->where('department_id', $this->departmentId)
                    ->with(['position', 'user', 'businessFunctions'])
            )
            ->columns([
                TextColumn::make('employee_code')
                    ->label(__('configuration.staff.employee_code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('full_name')
                    ->label(__('configuration.staff.full_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('position.title')
                    ->label(__('configuration.staff.position'))
                    ->searchable()
                    ->placeholder(__('common.not_available')),
                TextColumn::make('user.email')
                    ->label(__('configuration.staff.account'))
                    ->placeholder(__('common.not_available')),
                TextColumn::make('employment_status')
                    ->label(__('configuration.staff.employment_status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => ($state instanceof StaffEmploymentStatus
                        ? $state
                        : StaffEmploymentStatus::tryFrom((string) $state))?->label() ?? __('common.not_available'))
                    ->color(fn ($state): string => BadgePalette::status($state instanceof StaffEmploymentStatus ? $state->value : (string) $state, category: 'crm.staff_employment_status')),
                IconColumn::make('can_receive_customers')
                    ->label(__('configuration.staff.can_receive_customers'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('full_name')
            ->actions([
                ActionGroup::make([
                    Action::make('provision_account')
                        ->label(__('configuration.staff.provision_account'))
                        ->icon('heroicon-o-user-plus')
                        ->url(fn (Staff $record): string => UserResource::getUrl('create', [
                            'staff_id' => $record->id,
                        ]))
                        ->visible(fn (Staff $record): bool => $record->user_id === null),
                    Action::make('edit')
                        ->label(__('configuration.staff.edit'))
                        ->icon('heroicon-o-pencil-square')
                        ->url(fn (Model $record): string => StaffResource::getUrl('edit', ['record' => $record])),
                    DeleteAction::make()->label(__('configuration.staff.delete')),
                ])->icon('heroicon-o-ellipsis-vertical')->iconButton(),
            ])
            ->headerActions([
                Action::make('create_staff')
                    ->label(__('configuration.staff.create'))
                    ->icon('heroicon-o-plus')
                    ->url(fn (): string => StaffResource::getUrl('create', [
                        'department_id' => $this->departmentId,
                    ])),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label(__('configuration.staff.delete')),
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.department-staff-table');
    }
}
