<?php

namespace App\Filament\Resources;

use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Crm\ContactType;
use App\Enums\Crm\LeadIntakeStatus;
use App\Enums\Crm\StaffEmploymentStatus;
use App\Filament\Resources\LeadResource\Pages;
use App\Filament\Resources\LeadResource\RelationManagers\LeadActivitiesRelationManager;
use App\Filament\Resources\LeadResource\RelationManagers\QualificationNotesRelationManager;
use App\Models\Crm\Lead;
use App\Models\Crm\Staff;
use App\Services\Crm\LeadAssignmentService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.crm');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.leads');
    }

    public static function getModelLabel(): string
    {
        return __('field.lead');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.leads');
    }

    public static function canViewAny(): bool
    {
        return config('business_flow.v2_enabled')
            && (auth()->user()?->can('viewAny', Lead::class) ?? false);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canView($record): bool
    {
        return $record instanceof Lead
            && (auth()->user()?->can('view', $record) ?? false);
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
            || $user->isMarketingManager()
            || $user->role === 'marketing_staff'
            || $user->isCustomerServiceManager()
        ) {
            return $query;
        }

        if (
            $user->role !== 'customer_service_staff'
            || $user->staff?->id === null
        ) {
            return $query->whereRaw('0 = 1');
        }

        $staffId = $user->staff->id;

        return $query->where(function (Builder $query) use ($staffId): void {
            $query
                ->where('assigned_staff_id', $staffId)
                ->orWhereHas(
                    'company',
                    fn (Builder $companyQuery): Builder => $companyQuery
                        ->where('account_owner_staff_id', $staffId)
                );
        });
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make()->schema([
                    TextEntry::make('lead_code')->label(__('field.lead_code')),
                    TextEntry::make('contact.full_name')->label(__('field.contact')),
                    TextEntry::make('company.legal_name')->label(__('field.company')),
                    TextEntry::make('contact.contact_type')
                        ->label(__('field.contact_type'))
                        ->badge()
                        ->formatStateUsing(fn (?ContactType $state): string => $state?->label() ?? '—'),
                    TextEntry::make('intake_status')
                        ->label(__('field.intake_status'))
                        ->badge()
                        ->formatStateUsing(fn (LeadIntakeStatus $state): string => $state->label()),
                    TextEntry::make('source')->label(__('field.source')),
                    TextEntry::make('source_detail')->label(__('field.source_detail')),
                    TextEntry::make('title')->label(__('field.title')),
                    TextEntry::make('service_interest')
                        ->label(__('field.service_interest'))
                        ->formatStateUsing(
                            fn ($state, Lead $record): string => (string) (
                                data_get(
                                    $record->metadata,
                                    'service_interest_label'
                                ) ?? $state ?? '—'
                            )
                        ),
                    TextEntry::make('metadata.intake_ready')
                        ->label('Sẵn sàng phân phối')
                        ->badge()
                        ->formatStateUsing(
                            fn ($state): string => $state
                                ? 'Đủ dữ liệu'
                                : 'Cần kiểm tra'
                        )
                        ->color(
                            fn ($state): string => $state
                                ? 'success'
                                : 'warning'
                        ),
                    TextEntry::make('metadata.intake_issues')
                        ->label('Vấn đề dữ liệu')
                        ->formatStateUsing(
                            fn ($state): string => collect($state ?? [])
                                ->map(fn (string $issue): string => match ($issue) {
                                    'missing_service_interest' => 'Thiếu dịch vụ quan tâm',
                                    'missing_contact' => 'Thiếu liên hệ',
                                    'company_resolution_pending' => 'Chờ đối chiếu công ty',
                                    default => $issue,
                                })
                                ->implode(', ')
                        )
                        ->placeholder('Không có'),
                    TextEntry::make('estimated_value')->label(__('field.estimated_value'))->money('VND'),
                    TextEntry::make('assignedStaff.full_name')->label(__('field.assigned_staff')),
                    TextEntry::make('qualification.status')
                        ->label(__('field.status'))
                        ->badge()
                        ->formatStateUsing(fn ($state): string => $state instanceof ContactQualificationStatus
                            ? $state->label()
                            : ContactQualificationStatus::tryFrom((string) $state)?->label() ?? __('action.not_applicable')),
                    TextEntry::make('qualification.next_follow_up_at')
                        ->label(__('field.next_follow_up'))
                        ->dateTime('d/m/Y H:i')
                        ->color(fn ($state): string => $state && $state->isPast() ? 'danger' : 'gray'
                        ),
                    TextEntry::make('qualification.first_contacted_at')
                        ->label(__('field.first_contacted_at'))
                        ->dateTime('d/m/Y H:i'),

                    TextEntry::make('qualification.last_contacted_at')
                        ->label(__('field.last_contacted_at'))
                        ->dateTime('d/m/Y H:i'),

                    TextEntry::make('qualification.qualification_result')
                        ->label(__('field.qualification_result'))
                        ->badge()
                        ->formatStateUsing(
                            fn ($state): string => $state?->label()
                                ?? __('action.not_applicable')
                        ),

                    TextEntry::make('qualification.score')
                        ->label(__('field.score')),

                    TextEntry::make('qualification.priority')
                        ->label(__('field.priority'))
                        ->badge(),
                    TextEntry::make('created_at')->label(__('field.created_at'))->dateTime('d/m/Y H:i'),
                ])->columns(2),
                Section::make('Thông tin nhu cầu từ biểu mẫu')
                    ->description(
                        'Snapshot dữ liệu khách đã gửi tại thời điểm phát sinh Lead.'
                    )
                    ->schema([
                        RepeatableEntry::make('metadata.form_answers')
                            ->label('')
                            ->schema([
                                TextEntry::make('label')
                                    ->label('Trường'),
                                TextEntry::make('display_value')
                                    ->label('Giá trị')
                                    ->placeholder('—'),
                            ])
                            ->columns(2),
                    ])
                    ->visible(
                        fn (Lead $record): bool => ! empty(
                            data_get($record->metadata, 'form_answers', [])
                        )
                    ),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('lead_code')->label(__('field.lead_code'))->disabled(),
            Select::make('contact_id')
                ->label(__('field.contact'))
                ->relationship('contact', 'full_name')
                ->searchable()
                ->required(),
            Select::make('company_id')
                ->label(__('field.company'))
                ->relationship('company', 'legal_name')
                ->searchable(),
            Select::make('intake_status')
                ->label(__('field.intake_status'))
                ->options(LeadIntakeStatus::options())
                ->required(),
            TextInput::make('title')->label(__('field.title'))->required()->maxLength(255),
            TextInput::make('service_interest')->label(__('field.service_interest'))->maxLength(255),
            TextInput::make('source')->label(__('field.source'))->maxLength(100),
            TextInput::make('source_detail')->label(__('field.source_detail'))->maxLength(255),
            TextInput::make('estimated_value')
                ->label(__('field.estimated_value'))
                ->numeric(),
            Select::make('assigned_staff_id')
                ->label(__('field.assigned_staff'))
                ->relationship('assignedStaff', 'full_name')
                ->searchable(),
        ]);
    }

    public static function assignmentForm(
        bool $isReassignment = false
    ): array {
        return [
            Select::make('staff_id')
                ->label(__('field.assigned_staff'))
                ->options(function (?Lead $record) use ($isReassignment): array {
                    $query = Staff::query()
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
                        );

                    if (
                        ! $isReassignment
                        && $record?->company?->account_owner_staff_id !== null
                    ) {
                        $query->whereKey(
                            $record->company->account_owner_staff_id
                        );
                    }

                    return $query
                        ->orderBy('full_name')
                        ->get()
                        ->mapWithKeys(fn (Staff $staff): array => [
                            $staff->id => "{$staff->full_name} "
                                ."({$staff->employee_code})",
                        ])
                        ->all();
                })
                ->searchable()
                ->preload()
                ->required(),

            Textarea::make('reason')
                ->label(__('field.reason'))
                ->required($isReassignment)
                ->maxLength(1000),

            Toggle::make('transfer_company_owner')
                ->label(__('field.transfer_company_owner'))
                ->helperText(__('help.transfer_company_owner'))
                ->default(false)
                ->visible($isReassignment),
        ];
    }

    public static function canAssignLeads(): bool
    {
        return Gate::allows('crm.assign-lead');
    }

    public static function canReassignLeads(): bool
    {
        return Gate::allows('crm.reassign-lead');
    }

    public static function canProcessLead(Lead $lead): bool
    {
        return auth()->user()?->can('process', $lead) ?? false;
    }

    public static function canArchiveLead(Lead $lead): bool
    {
        return auth()->user()?->can('archive', $lead) ?? false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('lead_code')->label(__('field.lead_code'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('contact.full_name')->label(__('field.contact'))->searchable(),
                Tables\Columns\TextColumn::make('company.legal_name')->label(__('field.company'))->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('company.accountOwner.full_name')
                    ->label(__('field.account_owner'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('contact.contact_type')
                    ->label(__('field.contact_type'))
                    ->badge()
                    ->formatStateUsing(fn (?ContactType $state): string => $state?->label() ?? '—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('source')->label(__('field.source'))->toggleable(),
                Tables\Columns\TextColumn::make('service_interest')
                    ->label(__('field.service_interest'))
                    ->formatStateUsing(
                        fn ($state, Lead $record): string => (string) (
                            data_get(
                                $record->metadata,
                                'service_interest_label'
                            ) ?? $state ?? '—'
                        )
                    )
                    ->searchable(),
                Tables\Columns\IconColumn::make('metadata.intake_ready')
                    ->label('Đủ dữ liệu')
                    ->boolean()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('assignedStaff.full_name')->label(__('field.assigned_staff'))->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('assigned_at')
                    ->label(__('field.assigned_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('qualification.status')
                    ->label(__('field.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof ContactQualificationStatus
                        ? $state->label()
                        : ContactQualificationStatus::tryFrom((string) $state)?->label() ?? (string) $state)
                    ->color(fn ($state): string => match (is_string($state) ? $state : $state?->value) {
                        'new' => 'gray',
                        'assigned' => 'info',
                        'contacting' => 'warning',
                        'follow_up' => 'primary',
                        'qualified' => 'success',
                        'converted' => 'success',
                        'unqualified', 'spam', 'duplicate', 'archived' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('qualification.next_follow_up_at')
                    ->label(__('field.next_follow_up'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable()
                    ->color(fn ($state): string => $state && $state->isPast() ? 'danger' : 'gray'
                    ),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('field.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('intake_status')
                    ->label(__('field.intake_status'))
                    ->options(LeadIntakeStatus::options()),
                Tables\Filters\SelectFilter::make('assigned_staff_id')
                    ->label(__('field.assigned_staff'))
                    ->relationship('assignedStaff', 'full_name'),
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('field.status'))
                    ->options(ContactQualificationStatus::options())
                    ->query(fn (Builder $query, array $data) => $query
                        ->when(
                            filled($data['value'] ?? null),
                            fn (Builder $query, string $value) => $query->whereHas(
                                'qualification',
                                fn (Builder $query) => $query->where('status', $value)
                            )
                        )),
                Tables\Filters\Filter::make('overdue_follow_up')
                    ->label(__('filter.overdue_follow_up'))
                    ->query(fn (Builder $query): Builder => $query->whereHas(
                        'qualification',
                        fn (Builder $query): Builder => $query
                            ->whereNotNull('next_follow_up_at')
                            ->where('next_follow_up_at', '<', now())
                    )),

                Tables\Filters\Filter::make('stale_review_required')
                    ->label(__('filter.stale_review_required'))
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNotNull('metadata->stale_review_required_at')),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),

                    Action::make('assign')
                        ->label(__('action.assign_lead'))
                        ->icon('heroicon-o-user-plus')
                        ->form(static::assignmentForm())
                        ->visible(
                            fn (Lead $record): bool => $record->assigned_staff_id === null
                                && static::canAssignLeads()
                        )
                        ->action(function (Lead $record, array $data): void {
                            app(LeadAssignmentService::class)->assign(
                                lead: $record,
                                staff: Staff::query()->findOrFail(
                                    $data['staff_id']
                                ),
                                assignedByUserId: auth()->id(),
                                reason: $data['reason'] ?? null,
                            );

                            Notification::make()
                                ->title(__('notification.lead_assigned'))
                                ->success()
                                ->send();
                        }),

                    Action::make('reassign')
                        ->label(__('action.reassign_lead'))
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->form(static::assignmentForm(true))
                        ->visible(
                            fn (Lead $record): bool => $record->assigned_staff_id !== null
                                && static::canReassignLeads()
                        )
                        ->action(function (Lead $record, array $data): void {
                            app(LeadAssignmentService::class)->assign(
                                lead: $record,
                                staff: Staff::query()->findOrFail(
                                    $data['staff_id']
                                ),
                                assignedByUserId: auth()->id(),
                                reason: $data['reason'],
                                force: true,
                                transferCompanyOwner: (bool) ($data['transfer_company_owner'] ?? false),
                            );

                            Notification::make()
                                ->title(__('notification.lead_reassigned'))
                                ->success()
                                ->send();
                        }),
                ])
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->iconButton(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            LeadActivitiesRelationManager::class,
            QualificationNotesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeads::route('/'),
            'view' => Pages\ViewLead::route('/{record}'),
        ];
    }
}
