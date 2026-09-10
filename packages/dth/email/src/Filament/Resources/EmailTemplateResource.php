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
use Filament\Actions;
use Filament\Actions\Action as FormAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
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
    protected static ?string $navigationLabel = 'Templates';
    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        $mergeTags = app(TemplateVariableRegistry::class)->mergeTagLabels();

        return $schema
            ->columns(12)
            ->components([
                Section::make('Basic information')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(['default' => 1, 'md' => 5]),
                        Forms\Components\Select::make('category_id')
                            ->label('Category')
                            ->relationship('category', 'name', modifyQueryUsing: fn ($query) => $query->where('is_active', true))
                            ->searchable()
                            ->preload()
                            ->columnSpan(['default' => 1, 'md' => 4]),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                EmailTemplateStatus::Draft->value => 'Draft',
                                EmailTemplateStatus::Active->value => 'Active',
                                EmailTemplateStatus::Inactive->value => 'Inactive',
                            ])
                            ->default(EmailTemplateStatus::Draft->value)
                            ->required()
                            ->native(false)
                            ->columnSpan(['default' => 1, 'md' => 3]),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(['default' => 1, 'md' => 12])
                    ->columnSpanFull(),

                Section::make('Content')
                    ->icon('heroicon-o-code-bracket-square')
                    ->description('Create the email visually, paste content, or import an existing HTML file. Personalization variables can be inserted from the UI.')
                    ->schema([
                        Forms\Components\TextInput::make('subject')
                            ->required()
                            ->maxLength(255)
                            ->suffixAction(self::insertVariableAction('subject'))
                            ->afterLabel([
                                FormHelp::icon('Use the variable button to insert supported placeholders. Custom variables detected in imported HTML are listed below.'),
                            ])
                            ->columnSpan(['default' => 1, 'md' => 8]),
                        Forms\Components\TextInput::make('preheader')
                            ->label('Preheader')
                            ->maxLength(255)
                            ->suffixAction(self::insertVariableAction('preheader'))
                            ->afterLabel([
                                FormHelp::icon('Optional preview text shown by many email clients next to or below the subject.'),
                            ])
                            ->columnSpan(['default' => 1, 'md' => 4]),
                        Forms\Components\FileUpload::make('html_import')
                            ->label('Import HTML')
                            ->acceptedFileTypes(['text/html', 'application/xhtml+xml'])
                            ->maxSize(2048)
                            ->storeFiles(false)
                            ->dehydrated(false)
                            ->previewable(false)
                            ->afterLabel([
                                FormHelp::icon('Upload a .html file up to 2 MB. Variables already present as {{ variable }} are preserved and detected automatically.'),
                            ])
                            ->afterStateUpdated(function ($state, $set): void {
                                if (! $state instanceof TemporaryUploadedFile) {
                                    return;
                                }

                                try {
                                    $html = app(HtmlTemplateImportService::class)->fromPath($state->getRealPath());
                                    $set('html_body', $html);

                                    Notification::make()
                                        ->title('HTML imported')
                                        ->body('Any {{ variable }} placeholders in the file were preserved. Review the detected variables before saving.')
                                        ->success()
                                        ->send();
                                } catch (Throwable $e) {
                                    Notification::make()
                                        ->title('HTML import failed')
                                        ->body($e->getMessage())
                                        ->danger()
                                        ->send();
                                }
                            })
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('html_body')
                            ->label('Email body')
                            ->required()
                            ->mergeTags($mergeTags)
                            ->afterLabel([
                                FormHelp::icon('Click the merge-tags tool in the editor, or type {{ to search built-in personalization variables. Imported custom variables remain supported.'),
                            ])
                            ->columnSpanFull(),

                        SchemaActions::make([
                            self::variableReferenceAction(),
                        ])
                            ->columnSpanFull(),
                    ])
                    ->columns(['default' => 1, 'md' => 12])
                    ->columnSpanFull(),

            ]);
    }

    private static function variableReferenceAction(): FormAction
    {
        $variables = app(TemplateVariableRegistry::class)->definitions();

        return FormAction::make('variableReference')
            ->label('Read more personalization variables')
            ->icon('heroicon-o-information-circle')
            ->color('gray')
            ->link()
            ->modalHeading('Personalization variables')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->modalContent(fn () => view('dth-email::filament.variable-reference', [
                'variables' => $variables,
            ]));
    }

    private static function insertVariableAction(string $field): FormAction
    {
        return FormAction::make('insertVariable'.ucfirst($field))
            ->icon('heroicon-o-variable')
            ->tooltip('Insert personalization variable')
            ->modalHeading('Insert personalization variable')
            ->schema([
                Forms\Components\Select::make('variable')
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('category.name')->label('Category')->sortable(),
                Tables\Columns\TextColumn::make('subject')->limit(60),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->color(fn ($state): string => StatusColor::for($state)),
                Tables\Columns\TextColumn::make('template_key')
                    ->label('System key')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->actions([
                Actions\Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (EmailTemplate $record): string => 'Preview: '.$record->name)
                    ->modalWidth('5xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(function (EmailTemplate $record) {
                        $templates = app(EmailTemplateService::class);
                        $variables = app(TemplatePreviewDataFactory::class)->make($record);

                        return view('dth-email::filament.template-preview', [
                            'preview' => $templates->render($record, $variables),
                        ]);
                    }),
                Actions\EditAction::make()
                    ->label('Edit')
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
