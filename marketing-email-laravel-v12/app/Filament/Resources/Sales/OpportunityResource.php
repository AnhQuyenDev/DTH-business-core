<?php

namespace App\Filament\Resources\Sales;

use App\Enums\Sales\OpportunityStage;
use App\Filament\Resources\Sales\OpportunityResource\Pages;
use App\Filament\Resources\Sales\OpportunityResource\RelationManagers\ContactsRelationManager;
use App\Filament\Resources\Sales\OpportunityResource\RelationManagers\InteractionsRelationManager;
use App\Filament\Resources\Sales\OpportunityResource\RelationManagers\QuotationsRelationManager;
use App\Models\Crm\Lead;
use App\Models\Marketing\Contact;
use App\Models\Sales\Opportunity;
use App\Services\Sales\OpportunityContactService;
use App\Services\Sales\OpportunityWorkflowService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
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
use Illuminate\Validation\ValidationException;

class OpportunityResource extends Resource
{
    protected static ?string $model = Opportunity::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?int $navigationSort = 30;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.sales');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.opportunities');
    }

    public static function getModelLabel(): string
    {
        return __('field.opportunity');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.opportunities');
    }

    public static function canViewAny(): bool
    {
        return config('business_flow.v2_enabled')
            && (
                auth()->user()?->can(
                    'viewAny',
                    Opportunity::class
                ) ?? false
            );
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canCreateOpportunities(): bool
    {
        return Gate::allows('sales.create-opportunities');
    }

    public static function canProcessOpportunity(
        ?Opportunity $opportunity = null,
    ): bool {
        return $opportunity !== null
            && (
                auth()->user()?->can(
                    'process',
                    $opportunity
                ) ?? false
            );
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                /*
                * 1. THÔNG TIN CƠ HỘI
                */
                Section::make('Thông tin cơ hội')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('opportunity_code')
                            ->label('Mã cơ hội'),

                        TextEntry::make('title')
                            ->label('Tiêu đề'),

                        TextEntry::make('lead.lead_code')
                            ->label('Mã Lead'),

                        TextEntry::make('company.legal_name')
                            ->label('Công ty')
                            ->placeholder('—'),

                        TextEntry::make('assignedStaff.full_name')
                            ->label('Sales phụ trách')
                            ->placeholder('—'),

                        TextEntry::make('stage')
                            ->label('Giai đoạn')
                            ->badge()
                            ->formatStateUsing(
                                fn (OpportunityStage $state): string => $state->label()
                            )
                            ->color(
                                fn (OpportunityStage $state): string => $state->color()
                            ),

                        /*
                        * DB tiếp tục lưu mã kỹ thuật PPH02.
                        * Sales nhìn thấy tên gói.
                        */
                        TextEntry::make('service_interest')
                            ->label('Dịch vụ quan tâm')
                            ->formatStateUsing(
                                function ($state, Opportunity $record): string {
                                    return data_get(
                                        $record->lead?->metadata,
                                        'service_context.display_label'
                                    )
                                        ?? data_get(
                                            $record->lead?->metadata,
                                            'service_interest_label'
                                        )
                                        ?? $state
                                        ?? '—';
                                }
                            ),

                        TextEntry::make('estimated_value')
                            ->label('Giá trị ước tính')
                            ->money('VND'),

                        TextEntry::make('probability')
                            ->label('Xác suất')
                            ->suffix('%'),

                        TextEntry::make('expected_close_date')
                            ->label('Ngày dự kiến chốt')
                            ->date('d/m/Y'),

                        TextEntry::make('won_at')
                            ->label('Ngày thành công')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('—'),

                        TextEntry::make('lost_at')
                            ->label('Ngày thất bại')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('—'),

                        TextEntry::make('lost_reason')
                            ->label('Lý do thất bại')
                            ->placeholder('—'),

                        TextEntry::make('created_at')
                            ->label('Tạo lúc')
                            ->dateTime('d/m/Y H:i'),
                    ]),

                /*
                * 2. NGƯỜI SALES PHẢI LIÊN HỆ
                */
                Section::make('Người liên hệ chính')
                    ->description(
                        'Thông tin Sales sử dụng để liên hệ trực tiếp với khách hàng.'
                    )
                    ->columns(2)
                    ->schema([
                        TextEntry::make('primaryContact.full_name')
                            ->label('Họ và tên')
                            ->placeholder('—'),

                        TextEntry::make(
                            'primaryContact.businessProfile.contact_position'
                        )
                            ->label('Chức vụ')
                            ->placeholder('—'),

                        TextEntry::make('primaryContact.phone')
                            ->label('Số điện thoại')
                            ->copyable()
                            ->placeholder('—'),

                        TextEntry::make('primaryContact.email')
                            ->label('Email')
                            ->copyable()
                            ->placeholder('—'),

                        TextEntry::make('company.legal_name')
                            ->label('Công ty')
                            ->placeholder('—'),

                        TextEntry::make(
                            'primaryContact.businessProfile.tax_code'
                        )
                            ->label('Mã số thuế')
                            ->placeholder('—'),
                    ]),

                /*
                * 3. THÔNG TIN CSKH ĐÃ XÁC MINH
                */
                Section::make('Thông tin bàn giao từ CSKH')
                    ->description(
                        'Kết quả xác minh Lead trước khi bàn giao cho bộ phận Kinh doanh.'
                    )
                    ->columns(2)
                    ->schema([
                        TextEntry::make(
                            'lead.qualification.budget_status'
                        )
                            ->label('Tình trạng ngân sách')
                            ->badge()
                            ->formatStateUsing(
                                fn (?string $state): string => match ($state) {
                                    'confirmed_fit' =>
                                        'Đã xác nhận - phù hợp',

                                    'confirmed_unfit' =>
                                        'Đã xác nhận - chưa phù hợp',

                                    'unknown' =>
                                        'Chưa xác định',

                                    default => '—',
                                }
                            ),

                        TextEntry::make(
                            'lead.qualification.budget_amount'
                        )
                            ->label('Ngân sách dự kiến')
                            ->money('VND')
                            ->placeholder('—'),

                        TextEntry::make(
                            'lead.qualification.purchase_timeline'
                        )
                            ->label('Thời gian dự kiến mua')
                            ->formatStateUsing(
                                fn (?string $state): string => match ($state) {
                                    'within_7_days' =>
                                        'Trong 7 ngày',

                                    'within_30_days' =>
                                        'Trong 30 ngày',

                                    'within_3_months' =>
                                        'Trong 3 tháng',

                                    'over_3_months' =>
                                        'Trên 3 tháng',

                                    'unknown' =>
                                        'Chưa xác định',

                                    default => '—',
                                }
                            ),

                        TextEntry::make(
                            'lead.qualification.decision_role'
                        )
                            ->label('Vai trò người liên hệ')
                            ->formatStateUsing(
                                fn (?string $state): string => match ($state) {
                                    'decision_maker' =>
                                        'Người quyết định',

                                    'influencer' =>
                                        'Người ảnh hưởng / đề xuất',

                                    'information_gatherer' =>
                                        'Người thu thập thông tin',

                                    'unknown' =>
                                        'Chưa xác định',

                                    default => '—',
                                }
                            ),

                        TextEntry::make('lead.qualification.score')
                            ->label('Điểm Lead')
                            ->placeholder('—'),

                        TextEntry::make('lead.qualification.priority')
                            ->label('Ưu tiên')
                            ->badge()
                            ->formatStateUsing(
                                function ($state): string {
                                    $value = $state instanceof \BackedEnum
                                        ? $state->value
                                        : $state;

                                    return match ($value) {
                                        'low' => 'Thấp',
                                        'normal' => 'Bình thường',
                                        'high' => 'Cao',
                                        'vip' => 'VIP',
                                        default => '—',
                                    };
                                }
                            ),

                        TextEntry::make(
                            'lead.qualification.qualifiedBy.full_name'
                        )
                            ->label('CSKH đánh giá')
                            ->placeholder('—'),

                        TextEntry::make(
                            'lead.qualification.qualified_at'
                        )
                            ->label('Đủ điều kiện lúc')
                            ->formatStateUsing(
                                fn ($state): string => $state
                                    ? $state
                                        ->copy()
                                        ->timezone(
                                            config('business_flow.timezone')
                                        )
                                        ->format('d/m/Y H:i')
                                    : '—'
                            ),

                        TextEntry::make(
                            'lead.qualification.qualification_note'
                        )
                            ->label('Ghi chú đánh giá')
                            ->columnSpanFull()
                            ->placeholder('—'),
                    ]),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('opportunity_code')
                ->label(__('field.opportunity_code'))
                ->disabled(),
            Select::make('company_id')
                ->label(__('field.company'))
                ->relationship('company', 'legal_name')
                ->searchable(),
            Select::make('primary_contact_id')
                ->label(__('field.primary_contact'))
                ->relationship('primaryContact', 'full_name')
                ->searchable()
                ->required(),
            Select::make('assigned_staff_id')
                ->label(__('field.assigned_staff'))
                ->relationship('assignedStaff', 'full_name')
                ->searchable(),
            TextInput::make('title')->label(__('field.title'))->required()->maxLength(255),
            TextInput::make('service_interest')->label(__('field.service_interest'))->maxLength(255),
            Select::make('stage')
                ->label(__('field.stage'))
                ->options(OpportunityStage::options())
                ->required(),
            TextInput::make('estimated_value')
                ->label(__('field.estimated_value'))
                ->numeric(),
            TextInput::make('probability')
                ->label(__('field.probability'))
                ->numeric()
                ->minValue(0)
                ->maxValue(100),
            DatePicker::make('expected_close_date')
                ->label(__('field.expected_close_date')),
        ]);
    }

    public static function interactionForm(): array
    {
        return [
            Select::make('interaction_type')
                ->label(__('field.interaction_type'))
                ->options([
                    'call' => __('enum.interaction_type.call'),
                    'email' => __('enum.interaction_type.email'),
                    'meeting' => __('enum.interaction_type.meeting'),
                    'message' => __('enum.interaction_type.message'),
                    'note' => __('enum.interaction_type.note'),
                ])
                ->required(),
            TextInput::make('subject')
                ->label(__('field.subject'))
                ->required()
                ->maxLength(255),
            Textarea::make('content')
                ->label(__('field.content'))
                ->rows(4),
            TextInput::make('outcome')
                ->label(__('field.outcome'))
                ->maxLength(255),
            DateTimePicker::make('interaction_at')
                ->label(__('field.interaction_at'))
                ->default(now())
                ->required(),
            DateTimePicker::make('next_follow_up_at')
                ->label(__('field.next_follow_up'))
                ->after('now'),
        ];
    }

    public static function stageForm(Opportunity $record): array
    {
        $workflow = app(OpportunityWorkflowService::class);

        $current = $record->stage instanceof OpportunityStage
            ? $record->stage
            : OpportunityStage::from((string) $record->stage);

        $options = collect($workflow->allowedTransitions($current))
            ->reject(
                fn (OpportunityStage $stage): bool => $stage === OpportunityStage::Won
            )
            ->mapWithKeys(
                fn (OpportunityStage $stage): array => [
                    $stage->value => $stage->label(),
                ]
            )
            ->all();

        return [
            Select::make('stage')
                ->label(__('field.stage'))
                ->options($options)
                ->required(),
            DatePicker::make('expected_close_date')
                ->label(__('field.expected_close_date')),
        ];
    }

    public static function lostForm(): array
    {
        return [
            Textarea::make('lost_reason')
                ->label(__('field.lost_reason'))
                ->required()
                ->rows(4)
                ->maxLength(1000),
        ];
    }

    public static function addContactForm(Opportunity $record): array
    {
        return [
            Select::make('contact_id')
                ->label(__('field.contact'))
                ->options(function () use ($record): array {
                    $query = Contact::query();

                    if ($record->company_id !== null) {
                        $query->whereHas(
                            'companies',
                            fn (Builder $query): Builder => $query
                                ->whereKey($record->company_id)
                        );
                    } else {
                        $query->whereKey($record->primary_contact_id);
                    }

                    return $query
                        ->orderBy('id')
                        ->get()
                        ->mapWithKeys(
                            fn (Contact $contact): array => [
                                $contact->id =>
                                    $contact->full_name
                                    ?: 'Contact #'.$contact->id,
                            ]
                        )
                        ->all();
                })
                ->searchable()
                ->preload()
                ->required(),

            Select::make('role')
                ->label(__('field.role'))
                ->options([
                    'primary_contact' =>
                        __('field.role.primary_contact'),

                    'decision_maker' =>
                        __('field.role.decision_maker'),

                    'influencer' =>
                        __('field.role.influencer'),

                    'technical_contact' =>
                        __('field.role.technical_contact'),

                    'other' =>
                        __('field.role.other'),
                ])
                ->default('other')
                ->required(),

            Toggle::make('is_primary')
                ->label(__('field.is_primary'))
                ->default(false),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('opportunity_code')
                    ->label(__('field.opportunity_code'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label(__('field.title'))
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('company.legal_name')
                    ->label(__('field.company'))
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('primaryContact.full_name')
                    ->label(__('field.primary_contact'))
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('assignedStaff.full_name')
                    ->label(__('field.assigned_staff'))
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('stage')
                    ->label(__('field.stage'))
                    ->badge()
                    ->formatStateUsing(
                        fn (OpportunityStage $state): string => $state->label()
                    )
                    ->color(
                        fn (OpportunityStage $state): string => $state->color()
                    ),
                Tables\Columns\TextColumn::make('estimated_value')
                    ->label(__('field.estimated_value'))
                    ->money('VND')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('probability')
                    ->label(__('field.probability'))
                    ->suffix('%')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('expected_close_date')
                    ->label(__('field.expected_close_date'))
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('field.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('stage')
                    ->label(__('field.stage'))
                    ->options(OpportunityStage::options()),
                Tables\Filters\SelectFilter::make('assigned_staff_id')
                    ->label(__('field.assigned_staff'))
                    ->relationship('assignedStaff', 'full_name'),
                Tables\Filters\SelectFilter::make('company_id')
                    ->label(__('field.company'))
                    ->relationship('company', 'legal_name'),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),

                    Action::make('create_quotation')
                        ->label(__('action.create_quotation'))
                        ->icon('heroicon-o-document-plus')
                        ->color('success')
                        ->url(
                            fn (Opportunity $record): string => QuotationResource::getUrl('create', [
                                'opportunity_id' => $record->id,
                            ])
                        )
                        ->visible(
                            fn (Opportunity $record): bool => config(
                                'business_flow.opportunity_quotation_enabled'
                            )
                                && (
                                    auth()->user()?->can(
                                        'createQuotation',
                                        $record
                                    ) ?? false
                                )
                                && in_array(
                                    $record->stage instanceof OpportunityStage
                                        ? $record->stage
                                        : OpportunityStage::from((string) $record->stage),
                                    [
                                        OpportunityStage::Qualified,
                                        OpportunityStage::Proposal,
                                        OpportunityStage::Negotiation,
                                    ],
                                    true,
                                )
                        ),

                    Action::make('record_interaction')
                        ->label(__('action.record_interaction'))
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->form(static::interactionForm())
                        ->visible(
                            fn (Opportunity $record): bool => static::canProcessOpportunity($record)
                                && ! $record->isTerminal()
                        )
                        ->action(function (Opportunity $record, array $data): void {
                            $record->interactions()->create(array_merge($data, [
                                'staff_id' => auth()->user()?->staff?->id,
                            ]));

                            Notification::make()
                                ->title(__('notification.opportunity_interaction_recorded'))
                                ->success()
                                ->send();
                        }),

                    Action::make('add_contact')
                        ->label(__('action.add_contact'))
                        ->icon('heroicon-o-user-plus')
                        ->form(
                            fn (Opportunity $record): array =>
                                static::addContactForm($record)
                        )
                        ->visible(
                            fn (Opportunity $record): bool => static::canProcessOpportunity($record)
                                && ! $record->isTerminal()
                        )
                        ->action(function (Opportunity $record, array $data): void {
                            $contactId = (int) $data['contact_id'];
                            if (
                                ! static::contactCanJoinOpportunity(
                                    $record,
                                    $contactId
                                )
                            ) {
                                throw ValidationException::withMessages([
                                    'contact_id' =>
                                        'Liên hệ này không thuộc công ty của cơ hội kinh doanh.',
                                ]);
                            }
                            app(OpportunityContactService::class)->upsert(
                                opportunity: $record,
                                contactId: (int) $data['contact_id'],
                                role: (string) ($data['role'] ?? 'other'),
                                isPrimary: (bool) ($data['is_primary'] ?? false),
                                actorUserId: auth()->id(),
                            );

                            Notification::make()
                                ->title(__('notification.opportunity_contact_added'))
                                ->success()
                                ->send();
                        }),

                    Action::make('change_stage')
                        ->label(__('action.change_stage'))
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->form(fn (Opportunity $record): array => static::stageForm($record))
                        ->visible(
                            fn (Opportunity $record): bool => static::canProcessOpportunity($record)
                                && ! $record->isTerminal()
                        )
                        ->action(function (Opportunity $record, array $data): void {
                            app(OpportunityWorkflowService::class)->transition(
                                opportunity: $record,
                                to: OpportunityStage::from($data['stage']),
                                data: $data,
                                actorUserId: auth()->id(),
                            );

                            $record->refresh();

                            Notification::make()
                                ->title(__('notification.opportunity_stage_changed'))
                                ->success()
                                ->send();
                        }),

                    Action::make('mark_lost')
                        ->label(__('action.mark_lost'))
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->form(static::lostForm())
                        ->visible(
                            fn (Opportunity $record): bool => static::canProcessOpportunity($record)
                                && ! $record->isTerminal()
                        )
                        ->action(function (Opportunity $record, array $data): void {
                            app(OpportunityWorkflowService::class)->transition(
                                opportunity: $record,
                                to: OpportunityStage::Lost,
                                data: $data,
                                actorUserId: auth()->id(),
                            );

                            $record->refresh();

                            Notification::make()
                                ->title(__('notification.opportunity_lost'))
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
            ContactsRelationManager::class,
            InteractionsRelationManager::class,
            QuotationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOpportunities::route('/'),
            'view' => Pages\ViewOpportunity::route('/{record}'),
        ];
    }

    public static function scopeForUser(Builder $query): Builder
    {
        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('0 = 1');
        }

        if (
            $user->isAdmin()
            || $user->isCustomerServiceManager()
            || $user->isSalesManager()
        ) {
            return $query;
        }

        if (
            $user->role !== 'sales_staff'
            || $user->staff?->id === null
        ) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where(
            'assigned_staff_id',
            $user->staff->id
        );
    }

    public static function qualifiedLeadOptions(): array
    {
        return Lead::query()
            ->whereHas(
                'qualification',
                fn (Builder $query) => $query->where('status', 'qualified')
            )
            ->whereDoesntHave('opportunity')
            ->with(['contact', 'company'])
            ->orderByDesc('id')
            ->get()
            ->mapWithKeys(fn (Lead $lead): array => [
                $lead->id => ($lead->contact?->full_name ?: 'Contact #'.$lead->id)
                    .($lead->company?->legal_name !== null
                        ? ' — '.$lead->company->legal_name
                        : '')
                    .' ('.$lead->lead_code.')',
            ])
            ->all();
    }

    public static function getEloquentQuery(): Builder
    {
        return static::scopeForUser(
            parent::getEloquentQuery()
        );
    }

    public static function canView($record): bool
    {
        return $record instanceof Opportunity
            && (auth()->user()?->can('view', $record) ?? false);
    }
}
