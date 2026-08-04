<?php

namespace App\Livewire;

use App\Enums\Crm\StaffEmploymentStatus;
use App\Filament\Resources\StaffResource;
use App\Models\Crm\Position;
use App\Models\Crm\Staff;
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
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
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
                TextColumn::make('employment_status')
                    ->label(__('field.employment_status'))
                    ->badge()
                    ->color(fn (StaffEmploymentStatus $state): string => $state->color()),
                IconColumn::make('can_receive_customers')->label(__('field.can_receive_customers'))->boolean(),
                TextColumn::make('created_at')->label(__('field.created_at'))->dateTime()->sortable(),
            ])
            ->defaultSort('full_name')
            ->actions([ActionGroup::make([
                Action::make('manage')
                    ->label(__('action.manage_staff'))
                    ->icon('heroicon-o-identification')
                    ->url(fn (Model $record): string => StaffResource::getUrl('edit', ['record' => $record])),
                EditAction::make(),
                DeleteAction::make(),
            ])->icon('heroicon-o-ellipsis-vertical')->iconButton()])
            ->headerActions([
                CreateAction::make()
                    ->label(__('action.create_staff'))
                    ->icon('heroicon-o-plus')
                    ->form($this->staffForm())
                    ->mutateFormDataUsing(fn (array $data): array => [
                        'department_id' => $this->departmentId,
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
                ->label(__('field.user'))
                ->relationship('user', 'email')
                ->searchable()
                ->required(),
            Select::make('position_id')
                ->label(__('field.position'))
                ->options(Position::query()->where('department_id', $departmentId)->orderBy('title')->pluck('title', 'id'))
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
            TextInput::make('employee_code')->label(__('field.employee_code'))->required()->maxLength(50)->unique(ignoreRecord: true),
            TextInput::make('full_name')->label(__('field.full_name'))->required()->maxLength(255),
            TextInput::make('phone')->label(__('field.phone'))->maxLength(30),
            Select::make('employment_status')
                ->label(__('field.employment_status'))
                ->options(StaffEmploymentStatus::options())
                ->default(StaffEmploymentStatus::Active)
                ->required(),
            Toggle::make('can_receive_customers')->label(__('field.can_receive_customers'))->default(true),
            TextInput::make('customer_capacity')->label(__('field.customer_capacity'))->numeric(),
            TextInput::make('distribution_weight')->label(__('field.distribution_weight'))->numeric()->default(1),
            DatePicker::make('started_at')->label(__('field.started_at')),
            DatePicker::make('ended_at')->label(__('field.ended_at')),
        ];
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.department-staff-table');
    }
}
