<?php

namespace App\Livewire;

use App\Enums\Crm\StaffEmploymentStatus;
use App\Filament\Resources\StaffResource;
use App\Models\Crm\Department;
use App\Models\Crm\Position;
use App\Models\Crm\Staff;
use App\Models\User;
use App\Services\Organization\RoleDepartmentService;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
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
            ->query(Staff::query()->where('department_id', $this->departmentId))
            ->columns([
                TextColumn::make('employee_code')->label(__('field.employee_code'))->searchable()->sortable(),
                TextColumn::make('full_name')->label(__('field.full_name'))->searchable()->sortable(),
                TextColumn::make('position.title')->label(__('field.position'))->searchable(),
                TextColumn::make('user.email')->label(__('field.user_account'))->placeholder(__('common.not_available')),
                TextColumn::make('employment_status')
                    ->label(__('field.employment_status'))
                    ->badge()
                    ->formatStateUsing(fn (StaffEmploymentStatus $state): string => $state->label())
                    ->color(fn (StaffEmploymentStatus $state): string => $state->color()),
                IconColumn::make('can_receive_customers')->label(__('field.can_receive_customers'))->boolean(),
                TextColumn::make('created_at')->label(__('field.created_at'))->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->defaultSort('full_name')
            ->actions([ActionGroup::make([
                Action::make('provision_account')
                    ->label(__('action.provision_user_account'))
                    ->icon('heroicon-o-user-plus')
                    ->url(fn (Staff $record): string => \App\Filament\Resources\UserResource::getUrl('create', [
                        'staff_id' => $record->id,
                    ]))
                    ->visible(fn (Staff $record): bool => $record->user_id === null),
                Action::make('edit')
                    ->label(__('action.edit'))
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (Model $record): string => StaffResource::getUrl('edit', ['record' => $record])),
                DeleteAction::make(),
            ])->icon('heroicon-o-ellipsis-vertical')->iconButton()])
            ->headerActions([
                CreateAction::make()
                    ->label(__('action.create_staff'))
                    ->modalHeading(__('action.create_staff'))
                    ->modalSubmitActionLabel(__('action.create'))
                    ->icon('heroicon-o-plus')
                    ->form($this->staffForm())
                    ->mutateFormDataUsing(fn (array $data): array => [
                        'department_id' => $this->departmentId,
                        'employee_code' => Staff::nextEmployeeCode(),
                        ...$data,
                    ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private function staffForm(): array
    {
        $departmentId = $this->departmentId;

        return [
            Select::make('user_id')
                ->label(__('field.user_account'))
                ->options(
                    User::query()
                        ->whereDoesntHave('staff')
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn (User $user): array => [
                            $user->id => $user->name.' - '.$user->email,
                        ])
                        ->all()
                )
                ->searchable()
                ->nullable()
                ->rules([
                    fn (): Closure => function (string $attribute, mixed $value, Closure $fail) use ($departmentId): void {
                        if (! filled($value)) {
                            return;
                        }

                        $user = User::query()->find((int) $value);
                        $department = Department::query()->find($departmentId);

                        if (
                            $user
                            && ! app(RoleDepartmentService::class)->isCompatible(
                                $user->role,
                                $department,
                            )
                        ) {
                            $fail(__('validation.user_role_department_mismatch'));
                        }
                    },
                ])
                ->helperText(__('helper.staff_user_optional')),
            Select::make('position_id')
                ->label(__('field.position'))
                ->options(
                    Position::query()
                        ->where('department_id', $departmentId)
                        ->where('is_active', true)
                        ->orderBy('title')
                        ->pluck('title', 'id')
                )
                ->searchable()
                ->rules([
                    fn (): Closure => function (string $attribute, mixed $value, Closure $fail) use ($departmentId): void {
                        if (! filled($value)) {
                            return;
                        }

                        $position = Position::find((int) $value);

                        if (! $position || (int) $position->department_id !== $departmentId) {
                            $fail(__('field.position_department_mismatch'));
                        }
                    },
                ]),
            TextInput::make('full_name')->label(__('field.full_name'))->required()->maxLength(255),
            TextInput::make('phone')->label(__('field.phone'))->maxLength(30),
            Select::make('employment_status')
                ->label(__('field.employment_status'))
                ->options(StaffEmploymentStatus::options())
                ->default(StaffEmploymentStatus::Active->value)
                ->required(),
            Toggle::make('can_receive_customers')->label(__('field.can_receive_customers'))->default(true),
            TextInput::make('customer_capacity')->label(__('field.customer_capacity'))->numeric()->minValue(0),
            TextInput::make('distribution_weight')->label(__('field.distribution_weight'))->numeric()->minValue(0)->default(1),
            DatePicker::make('started_at')->label(__('field.started_at'))->native(false)->displayFormat('d/m/Y'),
            DatePicker::make('ended_at')->label(__('field.ended_at'))->native(false)->displayFormat('d/m/Y'),
        ];
    }

    public function render(): View
    {
        return view('livewire.department-staff-table');
    }
}
