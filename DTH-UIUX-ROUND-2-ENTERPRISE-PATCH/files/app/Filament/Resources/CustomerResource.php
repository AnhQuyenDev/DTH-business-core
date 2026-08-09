<?php

namespace App\Filament\Resources;

use App\Enums\Crm\CustomerAssignmentReason;
use App\Enums\Crm\CustomerConsentStatus;
use App\Enums\Crm\CustomerLifecycleStage;
use App\Enums\Crm\CustomerStatus;
use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers\AssignmentsRelationManager;
use App\Filament\Resources\CustomerResource\RelationManagers\InteractionsRelationManager;
use App\Filament\Resources\CustomerResource\RelationManagers\PaymentsRelationManager;
use App\Models\Crm\Customer;
use App\Models\Crm\CustomerAssignment;
use App\Models\Crm\Staff;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.customer_care');
    }

    public static function getModelLabel(): string
    {
        return __('resource.customer.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.customer.plural');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(
            'viewAny',
            Customer::class
        ) ?? false;
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof Customer
            && (auth()->user()?->can('view', $record) ?? false);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can(
            'create',
            Customer::class
        ) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof Customer
            && (auth()->user()?->can('update', $record) ?? false);
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof Customer
            && (auth()->user()?->can('delete', $record) ?? false);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('section.customer_details'))->schema([
                TextInput::make('customer_code')->label(__('field.customer_code'))->maxLength(50)->disabledOn('edit'),
                Select::make('customer_type')
                    ->label(__('field.customer_type'))
                    ->options([
                        'personal' => __('enum.customer_type.personal'),
                        'business' => __('enum.customer_type.business'),
                    ])
                    ->required()
                    ->default('personal'),
                TextInput::make('display_name')->label(__('field.customer_name'))->required()->maxLength(255),
                TextInput::make('email')->label(__('field.email'))->email()->maxLength(255),
                TextInput::make('phone')->label(__('field.phone'))->maxLength(30),
                TextInput::make('first_name')->label(__('field.first_name'))->maxLength(255),
                TextInput::make('last_name')->label(__('field.last_name'))->maxLength(255),
                TextInput::make('company_name')->label(__('field.company_name'))->maxLength(255),
                TextInput::make('tax_code')->label(__('field.tax_code'))->maxLength(50),
            ]),
            Section::make(__('section.settings'))->schema([
                Select::make('status')->label(__('field.status'))->options(CustomerStatus::options())->required()->default('potential'),
                Select::make('lifecycle_stage')->label(__('field.lifecycle_stage'))->options(CustomerLifecycleStage::options())->required()->default('new_customer'),
                Select::make('consent_status')->label(__('field.consent_status'))->options(CustomerConsentStatus::options())->required()->default('pending'),
                Select::make('priority')
                    ->label(__('field.priority'))
                    ->options([
                        'low' => __('field.priority.low'),
                        'normal' => __('field.priority.normal'),
                        'high' => __('field.priority.high'),
                        'vip' => __('field.priority.vip'),
                    ])
                    ->default('normal'),
                Select::make('converted_by_staff_id')->label(__('field.converted_by_staff'))->options(Staff::query()->orderBy('full_name')->pluck('full_name', 'id'))->searchable(),
                DatePicker::make('date_of_birth')->label(__('field.date_of_birth')),
                DatePicker::make('converted_at')->label(__('field.converted_at')),
                DatePicker::make('first_purchase_at')->label(__('field.first_purchase_at')),
                DatePicker::make('latest_purchase_at')->label(__('field.latest_purchase_at')),
                TagsInput::make('metadata.tags')->label(__('field.tags')),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer_code')
                    ->label(__('field.customer_code'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('display_name')
                    ->label(__('field.customer_name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('field.email'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label(__('field.phone')),
                Tables\Columns\TextColumn::make('customer_type')
                    ->label(__('field.customer_type'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => __('enum.customer_type.'.Str::lower((string) $state)))
                    ->color(fn ($state): string => \App\Support\Ui\BadgePalette::audience(Str::lower((string) $state))),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('field.status'))
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        if ($state instanceof CustomerStatus) {
                            return $state->label();
                        }

                        return CustomerStatus::tryFrom((string) $state)?->label() ?? (string) $state;
                    })
                    ->color(fn ($state): string => $state instanceof CustomerStatus
                        ? $state->color()
                        : (CustomerStatus::tryFrom((string) $state)?->color() ?? 'gray')),
                Tables\Columns\TextColumn::make('lifecycle_stage')
                    ->label(__('field.lifecycle_stage'))
                    ->badge()
                    ->formatStateUsing(function (?string $state): string {
                        if (blank($state)) {
                            return '—';
                        }

                        return CustomerLifecycleStage::tryFrom($state)?->label() ?? Str::of($state)->replace('_', ' ')->headline()->toString();
                    })
                    ->color(fn ($state): string => $state instanceof CustomerLifecycleStage
                        ? $state->color()
                        : (CustomerLifecycleStage::tryFrom((string) $state)?->color() ?? 'gray')),
                Tables\Columns\TextColumn::make('visibleOwner.full_name')->label(__('field.owner_staff'))->toggleable(),
                Tables\Columns\TextColumn::make('support_staff')
                    ->label(__('field.support_staff'))
                    ->getStateUsing(function (Customer $record): string {
                        $names = $record->currentSupportStaff()
                            ->filter()
                            ->pluck('full_name')
                            ->filter()
                            ->unique()
                            ->values();

                        return $names->isNotEmpty() ? $names->join(', ') : '—';
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('field.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(CustomerStatus::options()),
                SelectFilter::make('customer_type')->options([
                    'personal' => __('enum.customer_type.personal'),
                    'business' => __('enum.customer_type.business'),
                ]),
                SelectFilter::make('consent_status')->options(CustomerConsentStatus::options()),
            ])
            ->actions([ActionGroup::make([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Action::make('assign_owner')
                    ->label(__('action.assign_staff'))
                    ->icon('heroicon-o-user-plus')
                    ->visible(fn (Customer $record): bool => auth()->user()?->can('manageAssignments', $record) ?? false)
                    ->form([
                        Select::make('staff_id')
                            ->label(__('field.owner_staff'))
                            ->options(Staff::query()->orderBy('full_name')->pluck('full_name', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (Customer $record, array $data): void {
                        CustomerAssignment::query()
                            ->where('customer_id', $record->id)
                            ->where('assignment_type', 'owner')
                            ->where('status', 'active')
                            ->update([
                                'status' => 'ended',
                                'ended_at' => now(),
                                'ended_by_user_id' => auth()->id(),
                            ]);

                        CustomerAssignment::create([
                            'customer_id' => $record->id,
                            'staff_id' => $data['staff_id'],
                            'assignment_type' => 'owner',
                            'status' => 'active',
                            'starts_at' => now(),
                            'assigned_by_user_id' => auth()->id(),
                            'reason' => CustomerAssignmentReason::Manual->value,
                        ]);

                        Notification::make()->success()->title(__('notification.updated'))->send();
                    }),
                Action::make('assign_support')
                    ->label(__('field.support_staff'))
                    ->icon('heroicon-o-user-group')
                    ->visible(fn (Customer $record): bool => auth()->user()?->can('manageAssignments', $record) ?? false)
                    ->form([
                        Select::make('staff_id')
                            ->label(__('field.support_staff'))
                            ->options(Staff::query()->orderBy('full_name')->pluck('full_name', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (Customer $record, array $data): void {
                        CustomerAssignment::create([
                            'customer_id' => $record->id,
                            'staff_id' => $data['staff_id'],
                            'assignment_type' => 'support',
                            'status' => 'active',
                            'starts_at' => now(),
                            'assigned_by_user_id' => auth()->id(),
                            'reason' => CustomerAssignmentReason::Manual->value,
                        ]);

                        Notification::make()->success()->title(__('notification.updated'))->send();
                    }),
                Action::make('transfer_owner')
                    ->label(__('enum.assignment_reason.transfer'))
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('warning')
                    ->visible(fn (Customer $record): bool => auth()->user()?->can('manageAssignments', $record) ?? false)
                    ->form([
                        Select::make('staff_id')
                            ->label(__('field.owner_staff'))
                            ->options(Staff::query()->orderBy('full_name')->pluck('full_name', 'id'))
                            ->searchable()
                            ->required(),
                        TextInput::make('note')->label(__('field.note'))->maxLength(255),
                    ])
                    ->action(function (Customer $record, array $data): void {
                        DB::transaction(function () use ($record, $data): void {
                            $currentOwner = CustomerAssignment::query()
                                ->where('customer_id', $record->id)
                                ->where('assignment_type', 'owner')
                                ->where('status', 'active')
                                ->latest('id')
                                ->first();

                            if ($currentOwner && (int) $currentOwner->staff_id === (int) $data['staff_id']) {
                                return;
                            }

                            if ($currentOwner) {
                                $currentOwner->update([
                                    'status' => 'ended',
                                    'ended_at' => now(),
                                    'ended_by_user_id' => auth()->id(),
                                    'note' => $data['note'] ?? null,
                                ]);
                            }

                            CustomerAssignment::create([
                                'customer_id' => $record->id,
                                'staff_id' => $data['staff_id'],
                                'assignment_type' => 'owner',
                                'status' => 'active',
                                'starts_at' => now(),
                                'assigned_by_user_id' => auth()->id(),
                                'reason' => CustomerAssignmentReason::Transfer->value,
                                'original_owner_staff_id' => $currentOwner?->original_owner_staff_id ?? $currentOwner?->staff_id,
                                'note' => $data['note'] ?? null,
                            ]);
                        });

                        Notification::make()->success()->title(__('notification.updated'))->send();
                    }),
                Action::make('release_customer')
                    ->label(__('action.release_customer'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(fn (Customer $record): bool => auth()->user()?->can('manageAssignments', $record) ?? false)
                    ->requiresConfirmation()
                    ->modalHeading(__('action.release_customer_confirm'))
                    ->modalDescription(__('action.release_customer_desc'))
                    ->form([
                        TextInput::make('note')->label(__('field.note'))->maxLength(255),
                    ])
                    ->action(function (Customer $record, array $data): void {
                        CustomerAssignment::query()
                            ->where('customer_id', $record->id)
                            ->where('status', 'active')
                            ->update([
                                'status' => 'ended',
                                'ended_at' => now(),
                                'ended_by_user_id' => auth()->id(),
                                'reason' => CustomerAssignmentReason::Manual->value,
                                'note' => $data['note'] ?? __('audit.customer_released_to_pool'),
                            ]);

                        Notification::make()->success()->title(__('notification.updated'))->send();
                    }),
            ])->icon('heroicon-o-ellipsis-vertical')->iconButton()])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AssignmentsRelationManager::class,
            InteractionsRelationManager::class,
            PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view' => Pages\ViewCustomer::route('/{record}'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('0 = 1');
        }

        if (
            $user->isAdmin()
            || $user->canReadAcrossBusiness()
            || $user->isCustomerServiceManager()
        ) {
            return $query;
        }

        if (
            ! $user->isCustomerServiceStaff()
            || $user->staff?->id === null
        ) {
            return $query->whereRaw('0 = 1');
        }

        $staffId = $user->staff->id;

        return $query->whereHas(
            'assignments',
            function (Builder $assignmentQuery) use ($staffId): void {
                $assignmentQuery
                    ->where('staff_id', $staffId)
                    ->where('status', 'active');
            }
        );
    }
}
