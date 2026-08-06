<?php

namespace App\Filament\Resources\Sales;

use App\Enums\Sales\DiscountType;
use App\Enums\Sales\PaymentStatus;
use App\Enums\Sales\QuotationStatus;
use App\Filament\Resources\Sales\QuotationResource\Pages;
use App\Filament\Resources\Sales\QuotationResource\RelationManagers\ApprovalsRelationManager;
use App\Filament\Resources\Sales\QuotationResource\RelationManagers\ConfirmationsRelationManager;
use App\Filament\Resources\Sales\QuotationResource\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\Sales\QuotationResource\RelationManagers\EmailLogsRelationManager;
use App\Filament\Resources\Sales\QuotationResource\RelationManagers\ItemsRelationManager;
use App\Models\Crm\Customer;
use App\Models\Marketing\Contact;
use App\Models\Marketing\EmailTemplate;
use App\Models\Sales\Opportunity;
use App\Models\Sales\PriceBook;
use App\Models\Sales\PriceBookItem;
use App\Models\Sales\Quotation;
use App\Services\Sales\PriceBookAccessService;
use App\Services\Sales\PriceBookResolverService;
use App\Services\Sales\QuotationApprovalService;
use App\Services\Sales\QuotationMailService;
use App\Services\Sales\QuotationTemplateRenderer;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class QuotationResource extends Resource
{
    protected static ?string $model = Quotation::class;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.sales');
    }

    public static function getModelLabel(): string
    {
        return __('resource.quotation.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.quotation.plural');
    }

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('sales.view-quotations') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('sales.create-quotations') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('sales.create-quotations') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('sales.cancel-quotations') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('section.quotation_details'))->schema([
                Grid::make(2)->schema([
                    Select::make('opportunity_id')
                        ->label(__('field.opportunity'))
                        ->options(function (): array {
                            $query = Opportunity::query()
                                ->whereIn('stage', [
                                    'qualified',
                                    'proposal',
                                    'negotiation',
                                ])
                                ->with(['company', 'primaryContact'])
                                ->orderByDesc('created_at');

                            $user = auth()->user();

                            if (
                                ! $user?->isAdmin()
                                && ! $user?->isCustomerServiceManager()
                            ) {
                                $query->where(
                                    'assigned_staff_id',
                                    $user?->staff?->id
                                );
                            }

                            return $query->get()->mapWithKeys(
                                fn (Opportunity $opportunity): array => [
                                    $opportunity->id => sprintf(
                                        '%s — %s — %s',
                                        $opportunity->opportunity_code,
                                        $opportunity->title,
                                        $opportunity->company?->legal_name
                                            ?? $opportunity->primaryContact?->full_name
                                            ?? __('common.not_available'),
                                    ),
                                ]
                            )->all();
                        })
                        ->searchable()
                        ->preload()
                        ->live()
                        ->default(
                            fn (): ?int => request()->integer(
                                'opportunity_id'
                            ) ?: null
                        )
                        ->required(
                            fn (): bool => config(
                                'business_flow.opportunity_quotation_enabled'
                            )
                        )
                        ->visible(
                            fn (): bool => config(
                                'business_flow.opportunity_quotation_enabled'
                            )
                        )
                        ->afterStateUpdated(function (
                            Set $set,
                            ?string $state,
                        ): void {
                            $set('price_book_id', null);
                            $set('items', []);

                            if (! $state) {
                                $set('party_preview', null);

                                return;
                            }

                            $opportunity = Opportunity::query()
                                ->with(['company', 'primaryContact'])
                                ->find($state);

                            $set(
                                'title',
                                $opportunity
                                    ? 'Báo giá '.$opportunity->title
                                    : null
                            );

                            $set(
                                'party_preview',
                                $opportunity?->company?->legal_name
                                    ?? $opportunity?->primaryContact?->full_name
                            );
                        }),

                    TextInput::make('party_preview')
                        ->label(__('field.quotation_recipient'))
                        ->disabled()
                        ->dehydrated(false)
                        ->visible(
                            fn (): bool => config(
                                'business_flow.opportunity_quotation_enabled'
                            )
                        ),

                    Select::make('customer_id')
                        ->label(__('field.customer'))
                        ->relationship('customer', 'display_name')
                        ->searchable()
                        ->required(
                            fn (): bool => ! config(
                                'business_flow.opportunity_quotation_enabled'
                            )
                        )
                        ->visible(
                            fn (): bool => ! config(
                                'business_flow.opportunity_quotation_enabled'
                            )
                        ),

                    Select::make('price_book_id')
                        ->label(__('resource.price_book.singular'))
                        ->options(function (Get $get): array {
                            $user = auth()->user();
                            $partyType = 'personal';

                            if (
                                config('business_flow.opportunity_quotation_enabled')
                                && filled($get('opportunity_id'))
                            ) {
                                $opportunity = Opportunity::find(
                                    $get('opportunity_id')
                                );
                                $partyType = $opportunity?->company_id
                                    ? 'business'
                                    : 'personal';
                            } elseif (filled($get('customer_id'))) {
                                $partyType = Customer::find(
                                    $get('customer_id')
                                )?->customer_type ?? 'personal';
                            }

                            return app(PriceBookAccessService::class)
                                ->getAccessiblePriceBooks(
                                    $user,
                                    $partyType,
                                )
                                ->pluck('name', 'id')
                                ->all();
                        })
                        ->live()
                        ->afterStateUpdated(function (
                            Set $set,
                            ?string $state,
                        ): void {
                            $set(
                                'items',
                                static::loadItemsFromPriceBook(
                                    (int) $state
                                )
                            );
                        })
                        ->required(),

                    Select::make('bank_account_id')
                        ->label(__('resource.bank_account.singular'))
                        ->relationship('bankAccount', 'account_name')
                        ->searchable(),

                    TextInput::make('title')
                        ->label(__('field.title'))
                        ->required()
                        ->maxLength(255),

                    DatePicker::make('quotation_date')
                        ->label(__('field.quotation_date'))
                        ->required()
                        ->default(now()),

                    DatePicker::make('valid_until')
                        ->label(__('field.valid_until'))
                        ->required()
                        ->default(now()->addDays(30)),
                ]),
            ]),

            Section::make(__('section.quotation_items'))->schema([
                Repeater::make('items')
                    ->label(__('field.quotation_items'))
                    ->schema([
                        Hidden::make('price_book_item_id'),
                        Grid::make(4)->schema([
                            TextInput::make('service_name_snapshot')->label(__('field.service_name'))->required(),
                            TextInput::make('package_name_snapshot')->label(__('field.package_name')),
                            TextInput::make('unit')->label(__('field.unit'))->default('tháng'),
                            TextInput::make('quantity')->label(__('field.quantity'))->numeric()->default(1)->required(),
                        ]),
                        Grid::make(4)->schema([
                            TextInput::make('unit_price')->label(__('field.unit_price'))->numeric()->required()->prefix('VND'),
                            Select::make('discount_type')->label(__('field.discount_type'))
                                ->options(['' => __('field.none'), ...DiscountType::options()]),
                            TextInput::make('discount_value')->label(__('field.discount_value'))->numeric()->default(0),
                            TextInput::make('vat_rate')->label(__('field.vat_rate'))->numeric()->default(10)->suffix('%'),
                        ]),
                        Textarea::make('description_snapshot')->label(__('field.description'))->rows(2)->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->defaultItems(1)
                    ->addActionLabel(__('action.add_item'))
                    ->deleteAction(fn (\Filament\Forms\Components\Actions\Action $action) => $action->label(__('action.delete_item')))
                    ->addable()
                    ->deletable(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quotation_code')->label(__('field.quotation_code'))->searchable()->sortable(),
                TextColumn::make('version')->label(__('field.version'))->sortable(),
                TextColumn::make('opportunity.opportunity_code')
                    ->label(__('field.opportunity'))
                    ->placeholder(__('common.legacy'))
                    ->url(
                        fn (Quotation $record): ?string => $record->opportunity
                                ? OpportunityResource::getUrl('view', [
                                    'record' => $record->opportunity,
                                ])
                                : null
                    )
                    ->toggleable(),
                TextColumn::make('party_display_name')
                    ->label(__('field.quotation_recipient'))
                    ->getStateUsing(
                        fn (Quotation $record): string => $record->party_display_name
                    )
                    ->description(
                        fn (Quotation $record): ?string => $record->party_email
                    )
                    ->searchable(query: function (
                        Builder $query,
                        string $search,
                    ): Builder {
                        return $query->where(function (Builder $query) use (
                            $search
                        ): void {
                            $query->whereHas(
                                'customer',
                                fn (Builder $customerQuery) => $customerQuery->where(
                                    'display_name',
                                    'like',
                                    "%{$search}%"
                                )
                            )->orWhereHas(
                                'company',
                                fn (Builder $companyQuery) => $companyQuery->where(
                                    'legal_name',
                                    'like',
                                    "%{$search}%"
                                )
                            )->orWhereHas(
                                'contact',
                                fn (Builder $contactQuery) => $contactQuery->whereKey(
                                    Contact::query()
                                        ->whereHas(
                                            'personalProfile',
                                            fn (Builder $profileQuery) => $profileQuery->where(
                                                'first_name',
                                                'like',
                                                "%{$search}%"
                                            )
                                        )
                                        ->orWhereHas(
                                            'personalProfile',
                                            fn (Builder $profileQuery) => $profileQuery->where(
                                                'last_name',
                                                'like',
                                                "%{$search}%"
                                            )
                                        )
                                        ->select('id')
                                )
                            );
                        });
                    }),
                TextColumn::make('quotation_origin')
                    ->label(__('field.origin'))
                    ->badge()
                    ->getStateUsing(
                        fn (Quotation $record): string => $record->isOpportunityQuotation()
                                ? 'opportunity'
                                : 'legacy_customer'
                    )
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'opportunity' => __('quotation.origin.opportunity'),
                        default => __('quotation.origin.legacy_customer'),
                    })
                    ->color(fn (string $state): string => $state === 'opportunity' ? 'info' : 'gray'
                    ),
                TextColumn::make('title')->label(__('field.title'))->searchable()->limit(30),
                TextColumn::make('grand_total')->label(__('field.grand_total'))->money('VND')->sortable(),
                TextColumn::make('status')->label(__('field.status'))->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'label') ? $state->label() : ($state ?? ''))
                    ->color(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'color') ? $state->color() : 'gray'),
                TextColumn::make('payment_status')->label(__('field.payment_status'))->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'label') ? $state->label() : ($state ?? ''))
                    ->color(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'color') ? $state->color() : 'gray'),
                TextColumn::make('assignedStaff.full_name')->label(__('field.assigned_staff')),
                TextColumn::make('quotation_date')->label(__('field.quotation_date'))->date()->sortable(),
                TextColumn::make('valid_until')->label(__('field.valid_until'))->date()->sortable(),
                TextColumn::make('view_count')->label(__('field.views_count'))->sortable(),
                TextColumn::make('created_at')->label(__('field.created_at'))->dateTime()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(QuotationStatus::options()),
                SelectFilter::make('payment_status')->options(PaymentStatus::options()),
                SelectFilter::make('assigned_staff_id')->label(__('field.assigned_staff'))
                    ->relationship('assignedStaff', 'full_name'),
            ])
            ->actions([ActionGroup::make([
                Action::make('view')->label(__('action.view'))->icon('heroicon-o-eye')
                    ->url(fn (Quotation $q): string => route('filament.admin.resources.sales.quotations.view', $q)),
                Action::make('edit')->label(__('action.edit'))->icon('heroicon-o-pencil')
                    ->url(fn (Quotation $q): string => route('filament.admin.resources.sales.quotations.edit', $q))
                    ->visible(fn (Quotation $q): bool => $q->status->isEditable() && auth()->user()->can('sales.create-quotations')),
                Action::make('send')->label(__('action.send'))->icon('heroicon-o-paper-airplane')
                    ->form([
                        TextInput::make('party_name')
                            ->label(__('field.quotation_recipient'))
                            ->default(
                                fn (Quotation $record): string => $record->party_display_name
                            )
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('recipient_email')
                            ->label(__('field.recipient_email'))
                            ->email()
                            ->required()
                            ->default(
                                fn (Quotation $record): ?string => $record->party_email
                            ),
                        Select::make('template_id')
                            ->label(__('field.email_template'))
                            ->options(EmailTemplate::query()->whereHas('categoryRelation', fn ($q) => $q->where('slug', 'quotation'))->where('status', 'active')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->helperText(__('field.email_template_helper'))
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state, Quotation $record) {
                                if (! $state) {
                                    return;
                                }
                                $template = EmailTemplate::find($state);
                                if (! $template) {
                                    return;
                                }
                                $rendered = app(QuotationTemplateRenderer::class)->render($template, $record);
                                $set('subject', $rendered['subject']);
                                $set('body', $rendered['body']);
                            }),
                        TextInput::make('subject')
                            ->label(__('field.subject'))
                            ->default(fn (Quotation $record) => sprintf('[%s] %s', $record->quotation_code, $record->title)),
                        Textarea::make('body')
                            ->label(__('field.body'))
                            ->rows(12),
                    ])
                    ->action(function (array $data, Quotation $q) {
                        app(QuotationMailService::class)->send($q, auth()->user(), $data['recipient_email'], [
                            'subject' => $data['subject'] ?? null,
                            'body' => $data['body'] ?? null,
                        ]);
                        Notification::make()->success()->title(__('notification.email_queued'))->send();
                    })
                    ->visible(fn (Quotation $q): bool => filled($q->party_email) && $q->status->canSend() && auth()->user()->can('sales.send-quotations')),
                Action::make('cancel')->label(__('action.cancel'))->icon('heroicon-o-x-circle')->color('danger')
                    ->action(fn (Quotation $q) => app(QuotationApprovalService::class)->logCancellation($q, auth()->user()))
                    ->visible(fn (Quotation $q): bool => ! $q->status->isTerminal()),
            ])->icon('heroicon-o-ellipsis-vertical')->iconButton(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label(__('action.bulk_delete'))
                        ->modalHeading(__('action.bulk_delete'))
                        ->successNotificationTitle(__('notification.bulk_deleted'))
                        ->requiresConfirmation(),
                    BulkAction::make('bulk_cancel')
                        ->label(__('action.bulk_cancel'))
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading(__('action.bulk_cancel'))
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn (): bool => auth()->user()->can('sales.cancel-quotations'))
                        ->action(function (Collection $records): void {
                            $service = app(QuotationApprovalService::class);
                            foreach ($records as $q) {
                                if (! $q->status->isTerminal()) {
                                    $service->logCancellation($q, auth()->user());
                                }
                            }
                        }),
                    BulkAction::make('bulk_mark_paid')
                        ->label(__('action.bulk_mark_paid'))
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading(__('action.bulk_mark_paid'))
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn (): bool => ! config('business_flow.opportunity_quotation_enabled') && auth()->user()->can('sales.approve-quotations'))
                        ->action(function (Collection $records): void {
                            foreach ($records as $record) {
                                if ($record->status === QuotationStatus::Accepted && $record->payment_status !== PaymentStatus::Paid) {
                                    $record->update([
                                        'payment_status' => PaymentStatus::Paid,
                                        'metadata' => array_merge($record->metadata ?? [], ['paid_at' => now()->toDateTimeString(), 'paid_by' => auth()->id()]),
                                    ]);
                                }
                            }
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
            ConfirmationsRelationManager::class,
            EmailLogsRelationManager::class,
            DocumentsRelationManager::class,
            ApprovalsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuotations::route('/'),
            'create' => Pages\CreateQuotation::route('/create'),
            'view' => Pages\ViewQuotation::route('/{record}'),
            'edit' => Pages\EditQuotation::route('/{record}/edit'),
        ];
    }

    public static function loadItemsFromPriceBook(int $priceBookId): array
    {
        if ($priceBookId <= 0) {
            return [];
        }

        $priceBook = PriceBook::find($priceBookId);
        if (! $priceBook) {
            return [];
        }

        $items = app(PriceBookResolverService::class)->getItemsForPriceBook($priceBook);

        if ($items->isEmpty()) {
            Notification::make()
                ->danger()
                ->title(__('notification.price_book_no_items'))
                ->send();

            return [];
        }

        return $items->map(fn (PriceBookItem $pbi): array => [
            'price_book_item_id' => $pbi->id,
            'service_name_snapshot' => $pbi->servicePackage?->service?->name ?? '',
            'package_name_snapshot' => $pbi->servicePackage?->name ?? '',
            'unit' => $pbi->servicePackage?->unit ?? 'tháng',
            'quantity' => $pbi->servicePackage?->default_quantity ?? 1,
            'unit_price' => (float) $pbi->unit_price,
            'discount_type' => $pbi->default_discount_type?->value,
            'discount_value' => (float) ($pbi->default_discount_value ?? 0),
            'vat_rate' => (float) ($pbi->vat_rate ?? 10),
            'description_snapshot' => $pbi->description,
        ])->all();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user->isAdmin() || $user->isCustomerServiceManager()) {
            return $query;
        }

        $staff = $user->staff;
        if (! $staff) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where('assigned_staff_id', $staff->id)
            ->orWhere('created_by', $user->id);
    }
}
