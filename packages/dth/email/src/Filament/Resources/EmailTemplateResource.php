<?php

namespace Dth\Email\Filament\Resources;

use Dth\Email\Enums\EmailTemplateStatus;
use Dth\Email\Filament\Navigation\EmailNavigationGroup;
use Dth\Email\Filament\Resources\EmailTemplateResource\Pages;
use Dth\Email\Filament\Support\FormHelp;
use Dth\Email\Filament\Support\StatusColor;
use Dth\Email\Models\EmailTemplate;
use Dth\Email\Services\EmailTemplateService;
use Dth\Email\Services\HtmlTemplateImportService;
use Dth\Email\Services\TemplatePreviewDataFactory;
use Dth\Email\Services\TemplateVariableRegistry;
use Dth\Email\Support\UiText;
use Filament\Actions;
use Filament\Actions\Action as FormAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

class EmailTemplateResource extends Resource
{
    protected static ?string $model = EmailTemplate::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
    protected static string|\UnitEnum|null $navigationGroup = EmailNavigationGroup::Email;
    protected static ?int $navigationSort = 20;

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.templates', 'Templates', context: 'navigation');
    }

    public static function getModelLabel(): string
    {
        return UiText::get('models.template', 'Email Template', context: 'model');
    }

    public static function getPluralModelLabel(): string
    {
        return UiText::get('models.templates', 'Email Templates', context: 'model');
    }

    public static function form(Schema $schema): Schema
    {
        $mergeTags = app(TemplateVariableRegistry::class)->mergeTagLabels();

        return $schema
            ->columns(12)
            ->components([
                Section::make(UiText::get('template.basic_information', 'Basic information'))
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(UiText::get('common.fields.name', 'Name'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(['default' => 1, 'md' => 5]),
                        Forms\Components\Select::make('category_id')
                            ->label(UiText::get('template.category', 'Category'))
                            ->relationship('category', 'name', modifyQueryUsing: fn ($query) => $query->where('is_active', true))
                            ->searchable()
                            ->preload()
                            ->columnSpan(['default' => 1, 'md' => 4]),
                        Forms\Components\Select::make('status')
                            ->label(UiText::get('template.status', 'Status'))
                            ->options([
                                EmailTemplateStatus::Draft->value => UiText::status(EmailTemplateStatus::Draft),
                                EmailTemplateStatus::Active->value => UiText::status(EmailTemplateStatus::Active),
                                EmailTemplateStatus::Inactive->value => UiText::status(EmailTemplateStatus::Inactive),
                            ])
                            ->default(EmailTemplateStatus::Draft->value)
                            ->required()
                            ->native(false)
                            ->columnSpan(['default' => 1, 'md' => 3]),
                        Forms\Components\Textarea::make('description')
                            ->label(UiText::get('template.description', 'Description'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(['default' => 1, 'md' => 12])
                    ->columnSpanFull(),

                Section::make(UiText::get('template.content', 'Content'))
                    ->icon('heroicon-o-code-bracket-square')
                    ->description(UiText::get(
                        'template.content_description',
                        'Create the email visually, paste content, or import an existing HTML file. Personalization variables can be inserted from the UI.'
                    ))
                    ->schema([
                        Forms\Components\TextInput::make('subject')
                            ->label(UiText::get('template.subject', 'Subject'))
                            ->required()
                            ->maxLength(255)
                            ->suffixAction(self::insertVariableAction('subject'))
                            ->afterLabel([
                                FormHelp::icon(UiText::get(
                                    'template.subject_help',
                                    'Use the variable button to insert supported placeholders. Custom variables detected in imported HTML are listed below.'
                                )),
                            ])
                            ->columnSpan(['default' => 1, 'md' => 8]),
                        Forms\Components\TextInput::make('preheader')
                            ->label(UiText::get('template.preheader', 'Preheader'))
                            ->maxLength(255)
                            ->suffixAction(self::insertVariableAction('preheader'))
                            ->afterLabel([
                                FormHelp::icon(UiText::get(
                                    'template.preheader_help',
                                    'Optional preview text shown by many email clients next to or below the subject.'
                                )),
                            ])
                            ->columnSpan(['default' => 1, 'md' => 4]),
                        Forms\Components\FileUpload::make('html_import')
                            ->label(UiText::get('template.import_html', 'Import HTML'))
                            ->acceptedFileTypes(['text/html', 'application/xhtml+xml'])
                            ->maxSize(2048)
                            ->storeFiles(false)
                            ->dehydrated(false)
                            ->previewable(false)
                            ->afterLabel([
                                FormHelp::icon(UiText::get(
                                    'template.import_help',
                                    'Upload a .html file up to 2 MB. Variables already present as {{ variable }} are preserved and detected automatically.'
                                )),
                            ])
                            ->afterStateUpdated(function ($state, $set): void {
                                if (! $state instanceof TemporaryUploadedFile) {
                                    return;
                                }

                                try {
                                    $html = app(HtmlTemplateImportService::class)->fromPath($state->getRealPath());
                                    $set('html_body', $html);

                                    Notification::make()
                                        ->title(UiText::get('template.imported_title', 'HTML imported'))
                                        ->body(UiText::get(
                                            'template.imported_body',
                                            'Any {{ variable }} placeholders in the file were preserved. Review the detected variables before saving.'
                                        ))
                                        ->success()
                                        ->send();
                                } catch (Throwable $e) {
                                    Notification::make()
                                        ->title(UiText::get('template.import_failed', 'HTML import failed'))
                                        ->body($e->getMessage())
                                        ->danger()
                                        ->send();
                                }
                            })
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('html_body')
                            ->label(UiText::get('template.email_body', 'Email body'))
                            ->required()
                            ->mergeTags($mergeTags)
                            ->afterLabel([
                                FormHelp::icon(UiText::get(
                                    'template.body_help',
                                    'Click the merge-tags tool in the editor, or type {{ to search built-in personalization variables. Imported custom variables remain supported.'
                                )),
                            ])
                            ->columnSpanFull(),

                        SchemaActions::make([
                            self::variableReferenceAction(),
                        ])->columnSpanFull(),
                    ])
                    ->columns(['default' => 1, 'md' => 12])
                    ->columnSpanFull(),
            ]);
    }

    private static function variableReferenceAction(): FormAction
    {
        $variables = app(TemplateVariableRegistry::class)->definitions();

        return FormAction::make('variableReference')
            ->label(UiText::get('template.read_variables', 'Read more personalization variables'))
            ->icon('heroicon-o-information-circle')
            ->color('gray')
            ->link()
            ->modalHeading(UiText::get('template.variables_heading', 'Personalization variables'))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(UiText::get('common.actions.close', 'Close'))
            ->modalContent(fn () => view('dth-email::filament.variable-reference', [
                'variables' => $variables,
            ]));
    }

    private static function insertVariableAction(string $field): FormAction
    {
        return FormAction::make('insertVariable'.ucfirst($field))
            ->icon('heroicon-o-variable')
            ->tooltip(UiText::get('template.insert_variable', 'Insert personalization variable'))
            ->modalHeading(UiText::get('template.insert_variable', 'Insert personalization variable'))
            ->schema([
                Forms\Components\Select::make('variable')
                    ->label(UiText::get('template.variable', 'Variable'))
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
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(UiText::get('common.fields.name', 'Name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label(UiText::get('template.category', 'Category'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject')
                    ->label(UiText::get('template.subject', 'Subject'))
                    ->limit(60),
                Tables\Columns\TextColumn::make('status')
                    ->label(UiText::get('template.status', 'Status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => UiText::status($state))
                    ->sortable()
                    ->color(fn ($state): string => StatusColor::for($state)),
                Tables\Columns\TextColumn::make('template_key')
                    ->label(UiText::get('template.system_key', 'System key'))
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->actions([
                Actions\Action::make('preview')
                    ->label(UiText::get('template.preview', 'Preview'))
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (EmailTemplate $record): string => UiText::get(
                        'template.preview_title',
                        'Preview: :name',
                        ['name' => $record->name]
                    ))
                    ->modalWidth('5xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(UiText::get('common.actions.close', 'Close'))
                    ->modalContent(function (EmailTemplate $record) {
                        $templates = app(EmailTemplateService::class);
                        $variables = app(TemplatePreviewDataFactory::class)->make($record);

                        return view('dth-email::filament.template-preview', [
                            'preview' => $templates->render($record, $variables),
                        ]);
                    }),
                Actions\EditAction::make()
                    ->label(UiText::get('common.actions.edit', 'Edit'))
                    ->icon('heroicon-o-pencil-square'),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailTemplates::route('/'),
            'create' => Pages\CreateEmailTemplate::route('/create'),
            'edit' => Pages\EditEmailTemplate::route('/{record}/edit'),
        ];
    }
}
