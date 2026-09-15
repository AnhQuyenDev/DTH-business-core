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
        return UiText::get('navigation.campaigns', 'Chiến dịch', context: 'navigation');
    }

    public static function getModelLabel(): string
    {
        return UiText::get('models.campaign', 'Chiến dịch', context: 'model');
    }

    public static function getPluralModelLabel(): string
    {
        return UiText::get('models.campaigns', 'Chiến dịch', context: 'model');
    }

    public static function form(Schema $schema): Schema
    {
        $mergeTags = app(TemplateVariableRegistry::class)->mergeTagLabels();

        return $schema->components([
            Section::make(UiText::get('campaign.section', 'Chiến dịch'))
                ->icon('heroicon-o-envelope-open')
                ->schema([
                    TextInput::make('name')
                        ->label(UiText::get('campaign.name', 'Tên'))
                        ->required()
                        ->maxLength(255),

                    Select::make('sending_account_id')
                        ->label(UiText::get('campaign.sending_account', 'Tài khoản gửi'))
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
                        ->label(UiText::get('campaign.template', 'Mẫu Email'))
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
                                'Nội dung mẫu được sao chép vào chiến dịch. Sửa chiến dịch sẽ không làm thay đổi mẫu gốc.'
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

            Section::make(UiText::get('campaign.content', 'Nội dung'))
                ->icon('heroicon-o-code-bracket-square')
                ->description(UiText::get(
                    'campaign.content_description',
                    'Nội dung chiến dịch bị khóa khi rời trạng thái Bản nháp. Các biến được kiểm tra với dữ liệu người nhận trước khi Gửi hoặc Lên lịch.'
                ))
                ->schema([
                    TextInput::make('subject')
                        ->label(UiText::get('campaign.subject', 'Tiêu đề'))
                        ->required()
                        ->maxLength(255)
                        ->suffixAction(self::insertVariableAction('subject')),

                    TextInput::make('preheader')
                        ->label(UiText::get('campaign.preheader', 'Preheader'))
                        ->maxLength(255)
                        ->suffixAction(self::insertVariableAction('preheader')),

                    RichEditor::make('html_body')
                        ->label(UiText::get('campaign.email_body', 'Nội dung email'))
                        ->required()
                        ->mergeTags($mergeTags)
                        ->afterLabel([
                            FormHelp::icon(UiText::get(
                                'campaign.body_help',
                                'Dùng công cụ merge-tag để chèn biến có sẵn. Biến tùy chỉnh từ mẫu nhập vào vẫn được nhận diện.'
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
            ->tooltip(UiText::get('campaign.insert_variable', 'Chèn biến cá nhân hóa'))
            ->modalHeading(UiText::get('campaign.insert_variable', 'Chèn biến cá nhân hóa'))
            ->schema([
                Select::make('variable')
                    ->label(UiText::get('campaign.variable', 'Biến'))
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
                    ->label(UiText::get('campaign.campaign_column', 'Chiến dịch'))
                    ->description(fn (EmailCampaign $record): ?string => filled($record->subject) ? $record->subject : null)
                    ->weight(FontWeight::SemiBold)
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('template.name')
                    ->label(UiText::get('campaign.template', 'Mẫu Email'))
                    ->searchable()
                    ->placeholder('—')
                    ->wrap(),

                TextColumn::make('sendingAccount.name')
                    ->label(UiText::get('campaign.sending_account', 'Tài khoản gửi'))
                    ->searchable()
                    ->placeholder('—')
                    ->wrap(),

                TextColumn::make('recipients_count')
                    ->label(UiText::get('campaign.list.recipients_count', 'Số người nhận'))
                    ->numeric(decimalPlaces: 0)
                    ->sortable(),

                ViewColumn::make('status')
                    ->label(UiText::get('common.fields.status', 'Trạng thái'))
                    ->view('dth-email::filament.tables.columns.campaign-status'),

                TextColumn::make('scheduled_at')
                    ->label(UiText::get('campaign.list.scheduled_at', 'Thời gian lên lịch'))
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('completed_at')
                    ->label(UiText::get('campaign.list.completed_at', 'Thời gian hoàn tất'))
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),

                ViewColumn::make('performance')
                    ->label(UiText::get('campaign.list.performance', 'Hiệu quả'))
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
                                ->label(UiText::get('campaign.list.filters.account', 'Tài khoản gửi'))
                                ->placeholder(UiText::get('campaign.list.filters.all_accounts', 'Tất cả tài khoản'))
                                ->options(fn (): array => SendingAccount::query()
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->searchable()
                                ->preload(),

                            DatePicker::make('from')
                                ->label(UiText::get('campaign.list.filters.from', 'Từ ngày'))
                                ->native(false)
                                ->displayFormat('d/m/Y'),

                            DatePicker::make('until')
                                ->label(UiText::get('campaign.list.filters.until', 'Đến ngày'))
                                ->native(false)
                                ->displayFormat('d/m/Y'),

                            ToggleButtons::make('status')
                                ->label(UiText::get('campaign.list.filters.status', 'Trạng thái'))
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
            ->searchPlaceholder(UiText::get('campaign.list.search_placeholder', 'Tìm kiếm chiến dịch, mẫu, tài khoản...'))
            ->defaultSort('id', 'desc')
            ->defaultPaginationPageOption(10)
            ->paginationPageOptions([10, 25, 50])
            ->recordActions([
                \Filament\Actions\ActionGroup::make([
                    ViewAction::make()
                        ->label(UiText::get('common.actions.view', 'Xem'))
                        ->icon('heroicon-o-eye'),

                    EditAction::make()
                        ->label(UiText::get('common.actions.edit', 'Chỉnh sửa'))
                        ->icon('heroicon-o-pencil-square')
                        ->visible(fn (EmailCampaign $record): bool => $record->status === EmailCampaignStatus::Draft),

                    Action::make('send')
                        ->label(UiText::get('campaign.send', 'Gửi'))
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
                                    ->title(UiText::get('campaign.queued_title', 'Chiến dịch đã được đưa vào hàng đợi'))
                                    ->success()
                                    ->send();
                            } catch (Throwable $e) {
                                Notification::make()
                                    ->title(UiText::get('campaign.cannot_send', 'Không thể gửi chiến dịch'))
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('schedule')
                        ->label(UiText::get('campaign.schedule', 'Lên lịch'))
                        ->icon('heroicon-o-calendar-days')
                        ->visible(fn (EmailCampaign $record): bool => $record->status === EmailCampaignStatus::Draft)
                        ->schema([
                            DateTimePicker::make('scheduled_at')
                                ->label(UiText::get('campaign.send_at', 'Gửi lúc'))
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
                                    ->title(UiText::get('campaign.scheduled_title', 'Đã lên lịch chiến dịch'))
                                    ->success()
                                    ->send();
                            } catch (Throwable $e) {
                                Notification::make()
                                    ->title(UiText::get('campaign.cannot_schedule', 'Không thể lên lịch chiến dịch'))
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('unschedule')
                        ->label(UiText::get('campaign.unschedule', 'Bỏ lịch'))
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->visible(fn (EmailCampaign $record): bool => $record->status === EmailCampaignStatus::Scheduled)
                        ->action(function (EmailCampaign $record): void {
                            app(CampaignService::class)->unschedule($record);

                            Notification::make()
                                ->title(UiText::get('campaign.returned_draft', 'Chiến dịch đã trở về Bản nháp'))
                                ->success()
                                ->send();
                        }),

                    DeleteAction::make()
                        ->label(UiText::get('campaign.delete', 'Xóa'))
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
            'all' => UiText::get('campaign.list.tabs.all', 'Tất cả').' ('.$total.')',
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
