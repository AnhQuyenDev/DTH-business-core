<?php

namespace App\Filament\Resources;

use App\Enums\Crm\StaffEmploymentStatus;
use App\Filament\Resources\StaffResource\Pages;
use App\Filament\Resources\StaffResource\RelationManagers\AvailabilitiesRelationManager;
use App\Filament\Resources\StaffResource\RelationManagers\InteractionsRelationManager;
use App\Filament\Resources\StaffResource\RelationManagers\ScheduleRelationManager;
use App\Filament\Resources\StaffResource\RelationManagers\WorkScheduleRelationManager;
use App\Models\Crm\Department;
use App\Models\Crm\Position;
use App\Models\Crm\Staff;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class StaffResource extends Resource
{
    protected static ?string $model = Staff::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 40;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.configuration');
    }

    public static function getNavigationLabel(): string
    {
        return __('resource.staff.singular');
    }

    public static function getModelLabel(): string
    {
        return __('resource.staff.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.staff.plural');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('user_id')->label(__('field.user'))->relationship('user', 'email')->searchable()->required(),
            Select::make('department_id')
                ->label(__('field.department'))
                ->options(Department::options())
                ->searchable()
                ->required()
                ->live()
                ->afterStateUpdated(function (Set $set, Get $get): void {
                    $positionId = $get('position_id');
                    if (! $positionId) {
                        return;
                    }

                    $position = Position::find((int) $positionId);

                    if (! $position || (int) $position->department_id !== (int) $get('department_id')) {
                        $set('position_id', null);
                    }
                }),
            Select::make('position_id')
                ->label(__('field.position'))
                ->options(fn (Get $get): array => Position::query()
                    ->where('department_id', (int) $get('department_id'))
                    ->orderBy('title')
                    ->pluck('title', 'id')
                    ->all())
                ->searchable()
                ->rules([
                    fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                        if (! filled($value)) {
                            return;
                        }

                        $position = Position::find((int) $value);

                        if (! $position || (int) $position->department_id !== (int) $get('department_id')) {
                            $fail(__('field.position_department_mismatch'));
                        }
                    },
                ]),
            TextInput::make('employee_code')->label(__('field.employee_code'))->required()->maxLength(50)->unique(ignoreRecord: true),
            TextInput::make('full_name')->label(__('field.full_name'))->required()->maxLength(255),
            TextInput::make('phone')->label(__('field.phone'))->maxLength(30),
            Select::make('employment_status')->label(__('field.employment_status'))->options(StaffEmploymentStatus::options())->required(),
            Toggle::make('can_receive_customers')->label(__('field.can_receive_customers'))->default(true),
            TextInput::make('customer_capacity')->label(__('field.customer_capacity'))->numeric(),
            TextInput::make('distribution_weight')->label(__('field.distribution_weight'))->numeric()->default(1),
            DatePicker::make('started_at')->label(__('field.started_at')),
            DatePicker::make('ended_at')->label(__('field.ended_at')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('employee_code')->label(__('field.employee_code'))->searchable()->sortable(),
            TextColumn::make('full_name')->label(__('field.full_name'))->searchable()->sortable(),
            TextColumn::make('department.name')->label(__('field.department'))->badge()
                ->color(fn ($record): string => match ($record->department?->code) {
                    'admin' => 'danger',
                    'marketing' => 'info',
                    'customer_service' => 'warning',
                    'sales' => 'success',
                    'technical' => 'gray',
                    'email_service' => 'purple',
                    default => 'gray',
                }),
            TextColumn::make('position.title')->label(__('field.position'))->toggleable(),
            TextColumn::make('employment_status')
                ->label(__('field.employment_status'))
                ->badge()
                ->color(fn (StaffEmploymentStatus $state): string => $state->color()),
            IconColumn::make('can_receive_customers')->label(__('field.can_receive_customers'))->boolean(),
            TextColumn::make('created_at')->label(__('field.created_at'))->dateTime()->sortable(),
        ])
            ->actions([ActionGroup::make([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])->icon('heroicon-o-ellipsis-vertical')->iconButton()])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])->filters([
                SelectFilter::make('department_id')->label(__('field.department'))->relationship('department', 'name'),
                SelectFilter::make('employment_status')->options(StaffEmploymentStatus::options()),
                SelectFilter::make('can_receive_customers')->options([
                    1 => __('field.yes'),
                    0 => __('field.no'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AvailabilitiesRelationManager::class,
            InteractionsRelationManager::class,
            ScheduleRelationManager::class,
            WorkScheduleRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStaff::route('/'),
            'create' => Pages\CreateStaff::route('/create'),
            'edit' => Pages\EditStaff::route('/{record}/edit'),
        ];
    }
}
