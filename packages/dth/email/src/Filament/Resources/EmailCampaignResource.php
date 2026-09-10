<?php

namespace Dth\Email\Filament\Resources;

use Dth\Email\Enums\EmailCampaignStatus;
use Dth\Email\Enums\EmailTemplateStatus;
use Dth\Email\Enums\SendingAccountStatus;
use Dth\Email\Filament\Navigation\EmailNavigationGroup;
use Dth\Email\Filament\Resources\EmailCampaignResource\Pages;
use Dth\Email\Filament\Resources\EmailCampaignResource\RelationManagers\RecipientsRelationManager;
use Dth\Email\Filament\Support\FormHelp;
use Dth\Email\Filament\Support\StatusColor;
use Dth\Email\Models\EmailCampaign;
use Dth\Email\Models\EmailTemplate;
use Dth\Email\Models\SendingAccount;
use Dth\Email\Services\CampaignService;
use Dth\Email\Services\TemplateVariableRegistry;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\Hidden;
use Throwable;

class EmailCampaignResource extends Resource
{
    protected static ?string $model = EmailCampaign::class;

    protected static string|\BackedEnum|null $navigationIcon =
        'heroicon-o-envelope-open';

    protected static string|\UnitEnum|null $navigationGroup =
        EmailNavigationGroup::Email;

    protected static ?string $navigationLabel =
        'Campaigns';

    protected static ?int $navigationSort = 50;

    protected static ?string $recordTitleAttribute =
        'name';

    public static function form(
        Schema $schema,
    ): Schema {
        $mergeTags = app(TemplateVariableRegistry::class)->mergeTagLabels();

        return $schema->components([
            Section::make('Campaign')
                ->icon('heroicon-o-envelope-open')
                ->schema([
                    TextInput::make('name')
                        ->label('Name')
                        ->required()
                        ->maxLength(255),

                    Select::make('sending_account_id')
                        ->label('Sending account')
                        ->options(
                            fn (): array =>
                                SendingAccount::query()
                                    ->where(
                                        'status',
                                        SendingAccountStatus::Active->value
                                    )
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all()
                        )
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('email_template_id')
                        ->label('Template')
                        ->options(
                            fn (): array =>
                                EmailTemplate::query()
                                    ->where(
                                        'status',
                                        EmailTemplateStatus::Active->value
                                    )
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all()
                        )
                        ->searchable()
                        ->preload()
                        ->live()
                        ->required()
                        ->afterLabel([
                            FormHelp::icon(
                                'Template content is copied into this campaign. Editing the campaign will not modify the original template.'
                            ),
                        ])
                        ->afterStateUpdated(
                            function (
                                $state,
                                Set $set,
                            ): void {
                                if (! $state) {
                                    return;
                                }

                                $template =
                                    EmailTemplate::query()
                                        ->find($state);

                                if (! $template) {
                                    return;
                                }

                                $set(
                                    'subject',
                                    $template->subject
                                );

                                $set(
                                    'preheader',
                                    $template->preheader
                                );

                                $set(
                                    'html_body',
                                    $template->html_body
                                );

                                $set(
                                    'text_body',
                                    $template->text_body
                                );
                            }
                        ),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make('Content')
                ->icon('heroicon-o-code-bracket-square')
                ->description('Campaign content is frozen once it leaves Draft. Variables are validated against recipient data before Send or Schedule.')
                ->schema([
                    TextInput::make('subject')
                        ->label('Subject')
                        ->required()
                        ->maxLength(255)
                        ->suffixAction(self::insertVariableAction('subject')),

                    TextInput::make('preheader')
                        ->label('Preheader')
                        ->maxLength(255)
                        ->suffixAction(self::insertVariableAction('preheader')),

                    RichEditor::make('html_body')
                        ->label('Email body')
                        ->required()
                        ->mergeTags($mergeTags)
                        ->afterLabel([
                            FormHelp::icon('Use the merge-tags tool to insert built-in variables. Custom variables imported with the template are detected below.'),
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
            ->tooltip('Insert personalization variable')
            ->modalHeading('Insert personalization variable')
            ->schema([
                Select::make('variable')
                    ->label('Variable')
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

    public static function table(
        Table $table,
    ): Table {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Campaign')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('template.name')
                    ->label('Template')
                    ->toggleable(),

                TextColumn::make('sendingAccount.name')
                    ->label('Account')
                    ->toggleable(),

                TextColumn::make('recipients_count')
                    ->label('Recipients')
                    ->counts('recipients'),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(
                        fn ($state): string =>
                            ucfirst(
                                $state instanceof EmailCampaignStatus
                                    ? $state->value
                                    : (string) $state
                            )
                    )
                    ->color(
                        fn ($state): string =>
                            StatusColor::for($state)
                    ),

                TextColumn::make('scheduled_at')
                    ->label('Scheduled')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('completed_at')
                    ->label('Completed')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                ViewAction::make()
                    ->label('View')
                    ->icon('heroicon-o-eye'),

                EditAction::make()
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->visible(
                        fn (EmailCampaign $record): bool =>
                            $record->status
                            === EmailCampaignStatus::Draft
                    ),

                Action::make('send')
                    ->label('Send')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(
                        fn (EmailCampaign $record): bool =>
                            in_array(
                                $record->status,
                                [
                                    EmailCampaignStatus::Draft,
                                    EmailCampaignStatus::Scheduled,
                                ],
                                true,
                            )
                    )
                    ->action(
                        function (
                            EmailCampaign $record,
                        ): void {
                            try {
                                app(CampaignService::class)
                                    ->start($record);

                                Notification::make()
                                    ->title(
                                        'Campaign queued'
                                    )
                                    ->success()
                                    ->send();
                            } catch (Throwable $e) {
                                Notification::make()
                                    ->title(
                                        'Campaign cannot be sent'
                                    )
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }
                    ),

                Action::make('schedule')
                    ->label('Schedule')
                    ->icon('heroicon-o-calendar-days')
                    ->visible(
                        fn (EmailCampaign $record): bool =>
                            $record->status
                            === EmailCampaignStatus::Draft
                    )
                    ->schema([
                        DateTimePicker::make(
                            'scheduled_at'
                        )
                            ->label('Send at')
                            ->required()
                            ->seconds(false)
                            ->minDate(now()->startOfMinute()),
                    ])
                    ->action(
                        function (
                            EmailCampaign $record,
                            array $data,
                        ): void {
                            try {
                                app(CampaignService::class)
                                    ->schedule(
                                        $record,
                                        new \DateTimeImmutable(
                                            $data['scheduled_at']
                                        ),
                                    );

                                Notification::make()
                                    ->title(
                                        'Campaign scheduled'
                                    )
                                    ->success()
                                    ->send();
                            } catch (Throwable $e) {
                                Notification::make()
                                    ->title(
                                        'Campaign cannot be scheduled'
                                    )
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }
                    ),

                Action::make('unschedule')
                    ->label('Unschedule')
                    ->icon(
                        'heroicon-o-arrow-uturn-left'
                    )
                    ->visible(
                        fn (EmailCampaign $record): bool =>
                            $record->status
                            === EmailCampaignStatus::Scheduled
                    )
                    ->action(
                        function (
                            EmailCampaign $record,
                        ): void {
                            app(CampaignService::class)
                                ->unschedule($record);

                            Notification::make()
                                ->title(
                                    'Campaign returned to draft'
                                )
                                ->success()
                                ->send();
                        }
                    ),

                DeleteAction::make()
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->visible(
                        fn (EmailCampaign $record): bool =>
                            $record->status === EmailCampaignStatus::Draft
                    ),
            ]);
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
            'index' =>
                Pages\ListEmailCampaigns::route('/'),

            'create' =>
                Pages\CreateEmailCampaign::route('/create'),

            'view' =>
                Pages\ViewEmailCampaign::route('/{record}'),

            'edit' =>
                Pages\EditEmailCampaign::route(
                    '/{record}/edit'
                ),
        ];
    }

    public static function canEdit(
        Model $record,
    ): bool {
        return $record instanceof EmailCampaign
            && $record->status
                === EmailCampaignStatus::Draft;
    }

    public static function canDelete(
        Model $record,
    ): bool {
        return static::canEdit($record);
    }
}
