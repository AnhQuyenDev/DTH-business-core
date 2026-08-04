<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SegmentResource\Pages;
use App\Models\Crm\Customer;
use App\Models\Marketing\ContactList;
use App\Models\Marketing\Segment;
use App\Models\Marketing\Tag;
use App\Services\Crm\SegmentQueryService as CrmSegmentQueryService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SegmentResource extends Resource
{
    protected static ?string $model = Segment::class;

    protected static ?int $navigationSort = 60;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.marketing');
    }
    public static function getNavigationLabel(): string
    {
        return __('resource.segment.singular');
    }

    public static function getModelLabel(): string
    {
        return __('resource.segment.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.segment.plural');
    }

    protected static ?string $navigationIcon = 'heroicon-o-funnel';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('marketing.view-segments') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('marketing.manage-segments') ?? false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->can('marketing.manage-segments') ?? false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->can('marketing.manage-segments') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('section.segment_details'))->schema([
                TextInput::make('name')->label(__('field.name'))->required()->maxLength(255),
                TextInput::make('slug')->required()->unique(ignoreRecord: true)->maxLength(255),
                Textarea::make('description')->rows(3)->maxLength(65535),
                Select::make('status')
                    ->options([
                        'active' => __('field.status_active'),
                        'inactive' => __('field.status_inactive'),
                    ])
                    ->default('active')
                    ->required(),
            ]),
            Section::make(__('section.rules'))->schema([
                Repeater::make('rules.conditions')
                    ->label(__('field.conditions'))
                    ->addActionLabel(__('action.add_condition'))
                    ->schema([
                        Grid::make(4)->schema([
                            Select::make('field')
                                ->label(__('field.field'))
                                ->options([
                                    'customer_status_equals' => __('field.customer_status'),
                                    'customer_lifecycle_equals' => __('field.customer_lifecycle'),
                                    'customer_type_equals' => __('field.customer_type'),
                                    'customer_owner_equals' => __('field.customer_owner'),
                                    'has_customer_tag' => __('field.customer_has_tag'),
                                    'in_customer_list' => __('field.customer_in_list'),
                                    'consent_status_equals' => __('field.consent_status'),
                                    'converted_within_days' => __('field.converted_within'),
                                    'converted_between_dates' => __('field.converted_between'),
                                    'business_tax_verified' => __('field.tax_verified'),
                                ])
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn (\Filament\Forms\Set $set) => $set('value', null)),
                            Select::make('operator')
                                ->label(__('field.operator'))
                                ->options([
                                    'equals' => __('field.operator_equals'),
                                    'not_equals' => __('field.operator_not_equals'),
                                    'gte' => __('field.operator_gte'),
                                    'lte' => __('field.operator_lte'),
                                ])
                                ->default('equals')
                                ->required(),
                            TextInput::make('value')
                                ->label(__('field.days'))
                                ->numeric()
                                ->minValue(1)
                                ->visible(fn (\Filament\Forms\Get $get) => $get('field') === 'converted_within_days')
                                ->required(fn (\Filament\Forms\Get $get) => $get('field') === 'converted_within_days'),
                            Select::make('value')
                                ->label(fn (\Filament\Forms\Get $get) => match ($get('field')) {
                                    'has_customer_tag' => __('field.tags'),
                                    'in_customer_list' => __('field.lists'),
                                    'customer_status_equals' => __('field.status'),
                                    'customer_lifecycle_equals' => __('field.lifecycle_stage'),
                                    'customer_type_equals' => __('field.customer_type'),
                                    'customer_owner_equals' => __('field.owner_staff'),
                                    'consent_status_equals' => __('field.consent_status'),
                                    'business_tax_verified' => __('field.verified'),
                                    default => __('field.value'),
                                })
                                ->options(fn (\Filament\Forms\Get $get) => match ($get('field')) {
                                    'has_customer_tag' => Tag::query()->orderBy('name')->pluck('name', 'id'),
                                    'in_customer_list' => ContactList::query()->orderBy('name')->pluck('name', 'id'),
                                    'customer_status_equals' => ['potential' => __('enum.status.potential'), 'active' => __('enum.status.active'), 'inactive' => __('enum.status.inactive'), 'blocked' => __('enum.status.blocked'), 'archived' => __('enum.status.archived')],
                                    'customer_lifecycle_equals' => ['new_customer' => __('enum.lifecycle.new_customer'), 'engaging' => __('enum.lifecycle.engaging'), 'negotiating' => __('enum.lifecycle.negotiating'), 'active_customer' => __('enum.lifecycle.active_customer'), 'dormant' => __('enum.lifecycle.dormant'), 'lost' => __('enum.lifecycle.lost')],
                                    'customer_type_equals' => ['personal' => __('enum.customer_type.personal'), 'business' => __('enum.customer_type.business')],
                                    'customer_owner_equals' => \App\Models\Crm\Staff::query()->orderBy('full_name')->pluck('full_name', 'id'),
                                    'consent_status_equals' => ['subscribed' => __('enum.consent.subscribed'), 'unsubscribed' => __('enum.consent.unsubscribed'), 'pending' => __('enum.consent.pending'), 'bounced' => __('enum.consent.bounced')],
                                    'business_tax_verified' => ['verified' => __('enum.tax.verified'), 'unverified' => __('enum.tax.unverified')],
                                    default => [],
                                })
                                ->searchable(fn (\Filament\Forms\Get $get) => in_array($get('field'), ['has_customer_tag', 'in_customer_list', 'customer_owner_equals']))
                                ->visible(fn (\Filament\Forms\Get $get) => in_array($get('field'), ['has_customer_tag', 'in_customer_list', 'customer_status_equals', 'customer_lifecycle_equals', 'customer_type_equals', 'customer_owner_equals', 'consent_status_equals', 'business_tax_verified']))
                                ->required(fn (\Filament\Forms\Get $get) => in_array($get('field'), ['has_customer_tag', 'in_customer_list', 'customer_status_equals', 'customer_lifecycle_equals', 'customer_type_equals', 'customer_owner_equals', 'consent_status_equals', 'business_tax_verified']))
                                ->columnSpan(2),
                            DatePicker::make('value_from')
                                ->label(__('field.from'))
                                ->visible(fn (\Filament\Forms\Get $get) => $get('field') === 'converted_between_dates')
                                ->required(fn (\Filament\Forms\Get $get) => $get('field') === 'converted_between_dates'),
                            DatePicker::make('value_to')
                                ->label(__('field.to'))
                                ->visible(fn (\Filament\Forms\Get $get) => $get('field') === 'converted_between_dates')
                                ->required(fn (\Filament\Forms\Get $get) => $get('field') === 'converted_between_dates'),
                        ])->columns(4),
                    ])
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label(__('field.name'))->searchable()->sortable(),
            TextColumn::make('slug')->label(__('field.slug'))->searchable()->toggleable(),
            TextColumn::make('status')->label(__('field.status'))->badge()
                ->formatStateUsing(fn (?string $state): string => $state ? __('field.status_' . $state) : __('common.not_available'))
                ->color(fn (?string $state): string => match ($state) {
                    'active' => 'success',
                    'inactive' => 'danger',
                    default => 'gray',
                }),
            TextColumn::make('created_at')->label(__('field.created_at'))->dateTime()->sortable(),
        ])
            ->headerActions([
                \Filament\Tables\Actions\Action::make('preview_count')
                    ->label(__('action.preview_count'))
                    ->icon('heroicon-o-eye')
                    ->form([
                        Select::make('segment_id')
                            ->label(__('field.segment'))
                            ->options(Segment::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $segment = Segment::findOrFail((int) $data['segment_id']);
                        $count = app(CrmSegmentQueryService::class)->countForCustomerSegment($segment);

                        Notification::make()
                            ->title(__('notification.segment_preview'))
                            ->body(__('notification.segment_preview_body', ['count' => $count]))
                            ->success()
                            ->send();
                    }),
                \Filament\Tables\Actions\Action::make('preview_sample')
                    ->label(__('action.preview_sample'))
                    ->icon('heroicon-o-list-bullet')
                    ->form([
                        Select::make('segment_id')
                            ->label(__('field.segment'))
                            ->options(Segment::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $segment = Segment::findOrFail((int) $data['segment_id']);
                        $sample = app(CrmSegmentQueryService::class)->sampleForCustomerSegment($segment);

                        Notification::make()
                            ->title(__('notification.segment_sample'))
                            ->body(__('notification.segment_sample_body', ['emails' => collect($sample)->pluck('email')->implode(', ')]))
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSegments::route('/'),
            'create' => Pages\CreateSegment::route('/create'),
            'edit' => Pages\EditSegment::route('/{record}/edit'),
        ];
    }
}
