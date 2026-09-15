<?php

namespace Dth\Email\Filament\Resources;

use Dth\Email\Enums\EmailCampaignStatus;
use Dth\Email\Enums\EmailTemplateStatus;
use Dth\Email\Enums\SendingAccountStatus;
use Dth\Email\Filament\Navigation\EmailNavigationGroup;
use Dth\Email\Filament\Resources\EmailCampaignResource\Pages;
use Dth\Email\Filament\Resources\EmailCampaignResource\RelationManagers\RecipientsRelationManager;
use Dth\Email\Filament\Support\FormHelp;
use Dth\Email\Models\EmailCampaign;
use Dth\Email\Models\EmailTemplate;
use Dth\Email\Models\SendingAccount;
use Dth\Email\Services\CampaignService;
use Dth\Email\Services\TemplateVariableRegistry;
use Dth\Email\Support\UiText;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class EmailCampaignResource extends Resource
{
    protected static ?string $model = EmailCampaign::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope-open';
    protected static string|\UnitEnum|null $navigationGroup = EmailNavigationGroup::Email;
    protected static ?int $navigationSort = 10;
    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.campaigns', 'Campaign', context: 'navigation');
    }

    public static function getModelLabel(): string
    {
        return UiText::get('models.campaign', 'Campaign', context: 'model');
    }

    public static function getPluralModelLabel(): string
    {
        return UiText::get('models.campaigns', 'Campaign', context: 'model');
    }

    public static function form(Schema $schema): Schema
    {
        $mergeTags = app(TemplateVariableRegistry::class)->mergeTagLabels();

        return $schema->components([
            Section::make(UiText::get('campaign.section', 'Campaign'))
                ->icon('heroicon-o-envelope-open')
                ->schema([
                    TextInput::make('name')
                        ->label(UiText::get('campaign.name', 'Name'))
                        ->required()
                        ->maxLength(255),

                    Select::make('sending_account_id')
                        ->label(UiText::get('campaign.sending_account', 'Sending account'))
                        ->options(
                            fn (): array => SendingAccount::query()
                                ->where('status', SendingAccountStatus::Active->value)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all()
                        )
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('email_template_id')
                        ->label(UiText::get('campaign.template', 'Email Template'))
                        ->options(
                            fn (): array => EmailTemplate::query()
                                ->where('status', EmailTemplateStatus::Active->value)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all()
                        )
                        ->searchable()
                        ->preload()
                        ->live()
                        ->required()
                        ->afterLabel([
                            FormHelp::icon(UiText::get(
                                'campaign.template_help',
                                'Template content is copied into the campaign. Editing the campaign does not change the original template.'
                            )),
                        ])
                        ->afterStateUpdated(function ($state, Set $set): void {
                            if (! $state) {
                                return;
                            }

                            $template = EmailTemplate::query()->find($state);

                            if (! $template) {
                                return;
                            }

                            $set('subject', $template->subject);
                            $set('preheader', $template->preheader);
                            $set('html_body', $template->html_body);
                            $set('text_body', $template->text_body);
                        }),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make(UiText::get('campaign.content', 'Content'))
                ->icon('heroicon-o-code-bracket-square')
                ->description(UiText::get(
                    'campaign.content_description',
                    'Campaign content is locked after leaving Draft. Variables are validated against recipient data before sending or scheduling.'
                ))
                ->schema([
                    TextInput::make('subject')
                        ->label(UiText::get('campaign.subject', 'Subject'))
                        ->required()
                        ->maxLength(255)
                        ->suffixAction(self::insertVariableAction('subject')),

                    TextInput::make('preheader')
                        ->label(UiText::get('campaign.preheader', 'Preheader'))
                        ->maxLength(255)
                        ->suffixAction(self::insertVariableAction('preheader')),

                    RichEditor::make('html_body')
                        ->label(UiText::get('campaign.email_body', 'Email body'))
                        ->required()
                        ->mergeTags($mergeTags)
                        ->afterLabel([
                            FormHelp::icon(UiText::get(
                                'campaign.body_help',
                                'Use the merge-tag tool to insert supported variables. Custom variables imported from templates are still recognized.'
                            )),
                        ])
                        ->columnSpanFull(),

                    Hidden::make('text_body'),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    private static function insertVariableAction(string $field): Action
    {
        return Action::make('insertVariable'.ucfirst($field))
            ->icon('heroicon-o-variable')
            ->tooltip(UiText::get('campaign.insert_variable', 'Insert personalization variable'))
            ->modalHeading(UiText::get('campaign.insert_variable', 'Insert personalization variable'))
            ->schema([
                Select::make('variable')
                    ->label(UiText::get('campaign.variable', 'Variable'))
                    ->options(fn (): array => app(TemplateVariableRegistry::class)->mergeTagLabels())
                    ->searchable()
                    ->native(false)
                    ->required(),
            ])
            ->action(function (array $data, Get $schemaGet, Set $schemaSet) use ($field): void {
                $token = '{{ '.$data['variable'].' }}';
                $current = (string) ($schemaGet($field) ?? '');
                $separator = $current === '' || str_ends_with($current, ' ') ? '' : ' ';

                $schemaSet($field, $current.$separator.$token);
            });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['template:id,name', 'sendingAccount:id,name'])
                ->withCount([
                    'recipients',
                    'recipients as sent_recipients_count' => fn (Builder $query): Builder => $query->whereNotNull('sent_at'),
                    'recipients as opened_recipients_count' => fn (Builder $query): Builder => $query->whereNotNull('opened_at'),
                    'recipients as clicked_recipients_count' => fn (Builder $query): Builder => $query->whereNotNull('clicked_at'),
                ]))
            ->columns([
                TextColumn::make('name')
                    ->label(UiText::get('campaign.campaign_column', 'Campaign'))
                    ->description(fn (EmailCampaign $record): ?string => filled($record->subject) ? $record->subject : null)
                    ->weight(FontWeight::SemiBold)
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('template.name')
                    ->label(UiText::get('campaign.template', 'Email Template'))
                    ->searchable()
                    ->placeholder('—')
                    ->wrap(),

                TextColumn::make('sendingAccount.name')
                    ->label(UiText::get('campaign.sending_account', 'Sending account'))
                    ->searchable()
                    ->placeholder('—')
                    ->wrap(),

                TextColumn::make('recipients_count')
                    ->label(UiText::get('analytics.recipients', 'Recipients'))
                    ->numeric(decimalPlaces: 0)
                    ->sortable(),

                ViewColumn::make('status')
                    ->label(UiText::get('common.fields.status', 'Status'))
                    ->view('dth-email::filament.tables.columns.campaign-status'),

                TextColumn::make('scheduled_at')
                    ->label(UiText::get('campaign.scheduled', 'Scheduled'))
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('completed_at')
                    ->label(UiText::get('campaign.completed', 'Completed'))
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),

                ViewColumn::make('performance')
                    ->label(UiText::get('reports.performance_trend', 'Performance'))
                    ->view('dth-email::filament.tables.columns.campaign-performance'),
            ])
            ->filters([
                Filter::make('campaign_controls')
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 3,
                        ])->schema([
                            Select::make('sending_account_id')
                                ->label(UiText::get('campaign.sending_account', 'Sending account'))
                                ->placeholder(UiText::get('dashboard.filters.all_accounts', 'All sending accounts'))
                                ->options(fn (): array => SendingAccount::query()
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->searchable()
                                ->preload(),

                            DatePicker::make('from')
                                ->label(UiText::get('dashboard.filters.start_date', 'Start date'))
                                ->native(false)
                                ->displayFormat('d/m/Y'),

                            DatePicker::make('until')
                                ->label(UiText::get('dashboard.filters.end_date', 'End date'))
                                ->native(false)
                                ->displayFormat('d/m/Y'),

                            ToggleButtons::make('status')
                                ->label(UiText::get('common.fields.status', 'Status'))
                                ->options(fn (): array => self::campaignStatusFilterOptions())
                                ->default('all')
                                ->inline()
                                ->columnSpanFull(),
                        ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['sending_account_id'] ?? null,
                                fn (Builder $query, $accountId): Builder => $query->where('sending_account_id', $accountId),
                            )
                            ->when(
                                filled($data['status'] ?? null) && ($data['status'] ?? null) !== 'all',
                                fn (Builder $query): Builder => $query->where('status', $data['status']),
                            )
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(1)
            ->deferFilters(false)
            ->hiddenFilterIndicators()
            ->searchPlaceholder(UiText::get('models.campaigns', 'Campaigns'))
            ->defaultSort('id', 'desc')
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50])
            ->recordActions([
                \Filament\Actions\ActionGroup::make([
                    ViewAction::make()
                        ->label(UiText::get('common.actions.view', 'Xem'))
                        ->icon('heroicon-o-eye'),

                    EditAction::make()
                        ->label(UiText::get('common.actions.edit', 'Edit'))
                        ->icon('heroicon-o-pencil-square')
                        ->visible(fn (EmailCampaign $record): bool => $record->status === EmailCampaignStatus::Draft),

                    Action::make('send')
                        ->label(UiText::get('campaign.send', 'Send'))
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (EmailCampaign $record): bool => in_array(
                            $record->status,
                            [EmailCampaignStatus::Draft, EmailCampaignStatus::Scheduled],
                            true,
                        ))
                        ->action(function (EmailCampaign $record): void {
                            try {
                                app(CampaignService::class)->start($record);

                                Notification::make()
                                    ->title(UiText::get('campaign.queued_title', 'Campaign queued'))
                                    ->success()
                                    ->send();
                            } catch (Throwable $e) {
                                Notification::make()
                                    ->title(UiText::get('campaign.cannot_send', 'Campaign cannot be sent'))
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('schedule')
                        ->label(UiText::get('campaign.schedule', 'Schedule'))
                        ->icon('heroicon-o-calendar-days')
                        ->visible(fn (EmailCampaign $record): bool => $record->status === EmailCampaignStatus::Draft)
                        ->schema([
                            DateTimePicker::make('scheduled_at')
                                ->label(UiText::get('campaign.send_at', 'Send at'))
                                ->required()
                                ->seconds(false)
                                ->minDate(now()->startOfMinute()),
                        ])
                        ->action(function (EmailCampaign $record, array $data): void {
                            try {
                                app(CampaignService::class)->schedule(
                                    $record,
                                    new \DateTimeImmutable($data['scheduled_at']),
                                );

                                Notification::make()
                                    ->title(UiText::get('campaign.scheduled_title', 'Campaign scheduled'))
                                    ->success()
                                    ->send();
                            } catch (Throwable $e) {
                                Notification::make()
                                    ->title(UiText::get('campaign.cannot_schedule', 'Campaign cannot be scheduled'))
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('unschedule')
                        ->label(UiText::get('campaign.unschedule', 'Unschedule'))
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->visible(fn (EmailCampaign $record): bool => $record->status === EmailCampaignStatus::Scheduled)
                        ->action(function (EmailCampaign $record): void {
                            app(CampaignService::class)->unschedule($record);

                            Notification::make()
                                ->title(UiText::get('campaign.returned_draft', 'Campaign returned to Draft'))
                                ->success()
                                ->send();
                        }),

                    DeleteAction::make()
                        ->label(UiText::get('campaign.delete', 'Delete'))
                        ->icon('heroicon-o-trash')
                        ->visible(fn (EmailCampaign $record): bool => $record->status === EmailCampaignStatus::Draft),
                ]),
            ]);
    }

    /** @return array<string, string> */
    private static function campaignStatusFilterOptions(): array
    {
        $counts = EmailCampaign::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(static fn ($value): int => (int) $value)
            ->all();

        $total = array_sum($counts);

        return [
            'all' => UiText::get('dashboard.filters.all_statuses', 'All statuses').' ('.$total.')',
            EmailCampaignStatus::Draft->value => \Dth\Email\Filament\Support\CampaignUi::statusLabel(EmailCampaignStatus::Draft).' ('.($counts[EmailCampaignStatus::Draft->value] ?? 0).')',
            EmailCampaignStatus::Scheduled->value => \Dth\Email\Filament\Support\CampaignUi::statusLabel(EmailCampaignStatus::Scheduled).' ('.($counts[EmailCampaignStatus::Scheduled->value] ?? 0).')',
            EmailCampaignStatus::Processing->value => \Dth\Email\Filament\Support\CampaignUi::statusLabel(EmailCampaignStatus::Processing).' ('.($counts[EmailCampaignStatus::Processing->value] ?? 0).')',
            EmailCampaignStatus::Completed->value => \Dth\Email\Filament\Support\CampaignUi::statusLabel(EmailCampaignStatus::Completed).' ('.($counts[EmailCampaignStatus::Completed->value] ?? 0).')',
            EmailCampaignStatus::Failed->value => \Dth\Email\Filament\Support\CampaignUi::statusLabel(EmailCampaignStatus::Failed).' ('.($counts[EmailCampaignStatus::Failed->value] ?? 0).')',
        ];
    }

    public static function getRelations(): array
    {
        return [
            RecipientsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailCampaigns::route('/'),
            'create' => Pages\CreateEmailCampaign::route('/create'),
            'view' => Pages\ViewEmailCampaign::route('/{record}'),
            'edit' => Pages\EditEmailCampaign::route('/{record}/edit'),
        ];
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof EmailCampaign
            && $record->status === EmailCampaignStatus::Draft;
    }

    public static function canDelete(Model $record): bool
    {
        return static::canEdit($record);
    }
}
