<?php

namespace Dth\Marketing\Filament\Resources;

use Dth\Marketing\Enums\FormAudienceType;
use Dth\Marketing\Enums\FormFieldType;
use Dth\Marketing\Enums\FormTemplateStatus;
use Dth\Marketing\Filament\Navigation\MarketingNavigationGroup;
use Dth\Marketing\Filament\Resources\FormTemplateResource\Pages;
use Dth\Marketing\Filament\Support\StatusColor;
use Dth\Marketing\Models\FormTemplate;
use Dth\Marketing\Services\FormMappingRegistry;
use Dth\Marketing\Services\FormTemplateLifecycleService;
use Dth\Marketing\Support\UiText;
use Filament\Actions;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

class FormTemplateResource extends Resource
{
    protected static ?string $model = FormTemplate::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';
    protected static string|\UnitEnum|null $navigationGroup = MarketingNavigationGroup::Marketing;
    protected static ?int $navigationSort = 30;
    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.form_templates', 'Form Templates', context: 'navigation');
    }

    public static function getModelLabel(): string
    {
        return UiText::get('models.form_template', 'Form Template', context: 'model');
    }

    public static function getPluralModelLabel(): string
    {
        return UiText::get('models.form_templates', 'Form Templates', context: 'model');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(UiText::get('form.details', 'Form Template'))
                ->icon('heroicon-o-document-duplicate')
                ->schema([
                    TextInput::make('name')
                        ->label(UiText::get('common.fields.name', 'Name'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('slug')
                        ->label(UiText::get('form.slug', 'Slug'))
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    Select::make('audience_type')
                        ->label(UiText::get('form.audience_type', 'Audience type'))
                        ->options(FormAudienceType::options())
                        ->default(FormAudienceType::Personal->value)
                        ->required()
                        ->native(false)
                        ->disabled(fn (?FormTemplate $record): bool => $record?->status === FormTemplateStatus::Active)
                        ->live(),
                    TextInput::make('submit_button_text')
                        ->label(UiText::get('form.submit_button', 'Submit button'))
                        ->default(UiText::get('form.submit_default', 'Submit'))
                        ->required()
                        ->maxLength(100),
                    Textarea::make('description')
                        ->label(UiText::get('common.fields.description', 'Description'))
                        ->rows(3)
                        ->columnSpanFull(),
                    TextInput::make('success_message')
                        ->label(UiText::get('form.success_message', 'Success message'))
                        ->maxLength(255),
                    TextInput::make('redirect_url')
                        ->label(UiText::get('form.redirect_url', 'Redirect URL'))
                        ->url()
                        ->maxLength(255),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make(UiText::get('form.fields', 'Fields'))
                ->icon('heroicon-o-list-bullet')
                ->schema([
                    Repeater::make('fields')
                        ->relationship('fields')
                        ->label(UiText::get('form.fields', 'Fields'))
                        ->addActionLabel(UiText::get('form.add_field', 'Add field'))
                        ->defaultItems(0)
                        ->orderColumn('sort_order')
                        ->reorderable()
                        ->collapsed()
                        ->itemLabel(function (array $state): string {
                            $label = trim((string) ($state['label'] ?? ''));
                            $key = trim((string) ($state['field_key'] ?? ''));

                            return $label !== ''
                                ? $label.($key !== '' ? ' · '.$key : '')
                                : UiText::get('form.field', 'Field');
                        })
                        ->schema([
                            TextInput::make('label')
                                ->label(UiText::get('form.field_label', 'Label'))
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (?string $state, Set $set, Get $get): void {
                                    if (blank($get('field_key'))) {
                                        $key = Str::snake(Str::ascii((string) $state));
                                        $set('field_key', $key !== '' ? $key : 'field_'.Str::lower(Str::random(6)));
                                    }
                                }),
                            TextInput::make('field_key')
                                ->label(UiText::get('form.field_key', 'Key'))
                                ->required()
                                ->regex('/^[a-z][a-z0-9_]*$/')
                                ->maxLength(100),
                            Select::make('field_type')
                                ->label(UiText::get('form.field_type', 'Type'))
                                ->options(FormFieldType::options())
                                ->default(FormFieldType::Text->value)
                                ->required()
                                ->native(false)
                                ->live(),
                            TextInput::make('placeholder')
                                ->label(UiText::get('form.placeholder', 'Placeholder'))
                                ->maxLength(255),
                            KeyValue::make('options')
                                ->label(UiText::get('form.options', 'Options'))
                                ->keyLabel(UiText::get('form.option_value', 'Value'))
                                ->valueLabel(UiText::get('form.option_label', 'Label'))
                                ->addActionLabel(UiText::get('form.add_option', 'Add option'))
                                ->visible(fn (Get $get): bool => in_array((string) $get('field_type'), [
                                    FormFieldType::Select->value,
                                    FormFieldType::Checkbox->value,
                                ], true))
                                ->columnSpanFull(),
                            TextInput::make('default_value')
                                ->label(UiText::get('form.default_value', 'Default value'))
                                ->maxLength(1000),
                            Toggle::make('is_required')
                                ->label(UiText::get('form.required', 'Required'))
                                ->default(false),
                            Select::make('contact_mapping')
                                ->label(UiText::get('form.contact_mapping', 'CRM mapping'))
                                ->options(fn (Get $get): array => app(FormMappingRegistry::class)->options(
                                    (string) ($get('../../audience_type') ?? ''),
                                ))
                                ->searchable()
                                ->native(false)
                                ->visible(fn (): bool => app(FormMappingRegistry::class)->available())
                                ->dehydrated(fn (): bool => app(FormMappingRegistry::class)->available()),
                            TextInput::make('validation_rules')
                                ->label(UiText::get('form.validation', 'Advanced validation'))
                                ->placeholder('min:2|max:255')
                                ->maxLength(1000)
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),

            Section::make(UiText::get('form.imported_html', 'Imported HTML'))
                ->icon('heroicon-o-code-bracket-square')
                ->description(UiText::get('form.imported_html_description', 'Imported visual HTML is preserved. Field controls are replaced in-place from the Builder while the original wrappers, classes and presentation assets remain intact.'))
                ->collapsible()
                ->collapsed()
                ->schema([
                    CodeEditor::make('html_body')
                        ->label(UiText::get('form.html', 'HTML'))
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(UiText::get('common.fields.name', 'Name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('audience_type')
                    ->label(UiText::get('form.audience_type', 'Audience type'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => FormAudienceType::labelFor($state)),
                TextColumn::make('status')
                    ->label(UiText::get('common.fields.status', 'Status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => UiText::status($state))
                    ->color(fn ($state): string => StatusColor::for($state)),
                TextColumn::make('version')
                    ->label(UiText::get('form.version', 'Version'))
                    ->formatStateUsing(fn ($state): string => 'v'.(int) $state)
                    ->sortable(),
                TextColumn::make('fields_count')
                    ->label(UiText::get('form.field_count', 'Fields'))
                    ->counts('fields'),
                TextColumn::make('updated_at')
                    ->label(UiText::get('form.updated_at', 'Updated'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                Actions\Action::make('preview')
                    ->label(UiText::get('form.preview', 'Preview'))
                    ->icon('heroicon-o-eye')
                    ->url(fn (FormTemplate $record): string => route(
                        'marketing.form-templates.preview',
                        ['formTemplate' => $record],
                    ))
                    ->openUrlInNewTab(),
                Actions\EditAction::make()
                    ->label(UiText::get('common.actions.edit', 'Edit'))
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn (FormTemplate $record): bool => $record->status !== FormTemplateStatus::Archived),
                self::statusAction('activate', FormTemplateStatus::Active, 'heroicon-o-check-badge', 'success'),
                self::statusAction('archive', FormTemplateStatus::Archived, 'heroicon-o-archive-box', 'gray', true),
                Actions\DeleteAction::make()
                    ->label(UiText::get('common.actions.delete', 'Delete'))
                    ->icon('heroicon-o-trash'),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()
                        ->label(UiText::get('common.actions.delete', 'Delete')),
                ]),
            ]);
    }

    private static function statusAction(
        string $name,
        FormTemplateStatus $target,
        string $icon,
        string $color,
        bool $confirmation = false,
    ): Actions\Action {
        $action = Actions\Action::make($name)
            ->label(UiText::get('form.actions.'.$name, ucfirst($name)))
            ->icon($icon)
            ->color($color)
            ->visible(function (FormTemplate $record) use ($target): bool {
                $from = $record->status instanceof \BackedEnum ? $record->status->value : (string) $record->status;

                return in_array($target->value, app(FormTemplateLifecycleService::class)->transitions()[$from] ?? [], true);
            })
            ->action(function (FormTemplate $record) use ($target): void {
                try {
                    app(FormTemplateLifecycleService::class)->transition($record, $target);
                    Notification::make()->title(UiText::get('form.status_updated', 'Form Template status updated'))->success()->send();
                } catch (Throwable $exception) {
                    Notification::make()->title(UiText::get('form.status_failed', 'Form Template status could not be changed'))->body($exception->getMessage())->danger()->send();
                }
            });

        return $confirmation ? $action->requiresConfirmation() : $action;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFormTemplates::route('/'),
            'create' => Pages\CreateFormTemplate::route('/create'),
            'edit' => Pages\EditFormTemplate::route('/{record}/edit'),
        ];
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof FormTemplate
            && $record->status !== FormTemplateStatus::Archived;
    }

    public static function canDelete(Model $record): bool
    {
        // Delete is a real database delete. Published Landing Pages still guard
        // against removing a Form Template that is currently in public use.
        return $record instanceof FormTemplate
            && ! $record->isUsedByPublishedLandingPage();
    }
}
