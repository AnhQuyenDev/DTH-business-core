<?php

namespace App\Livewire;

use App\Models\Crm\Position;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
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
use Illuminate\Contracts\View\View;
use Livewire\Component;

class DepartmentPositionsTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public int $departmentId = 0;

    public function table(Table $table): Table
    {
        return $table
            ->query(Position::query()->where('department_id', $this->departmentId))
            ->columns([
                TextColumn::make('title')->label(__('field.title'))->searchable()->sortable(),
                TextColumn::make('staff_count')->label(__('field.staff_count'))->counts('staff'),
                IconColumn::make('is_active')->label(__('field.is_active'))->boolean(),
                TextColumn::make('created_at')->label(__('field.created_at'))->dateTime()->sortable(),
            ])
            ->defaultSort('title')
            ->actions([ActionGroup::make([
                EditAction::make(),
                DeleteAction::make(),
            ])->icon('heroicon-o-ellipsis-vertical')->iconButton()])
            ->headerActions([
                CreateAction::make()
                    ->label(__('action.create_position'))
                    ->icon('heroicon-o-plus')
                    ->form([
                        TextInput::make('title')->label(__('field.title'))->required()->maxLength(255),
                        Textarea::make('description')->label(__('field.description'))->rows(3),
                        Toggle::make('is_active')->label(__('field.is_active'))->default(true),
                    ])
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

    public function render(): View
    {
        return view('livewire.department-positions-table');
    }
}
