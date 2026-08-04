<?php

namespace App\Filament\Resources;

use App\Enums\Marketing\FormAudienceType;
use App\Enums\Marketing\FormFieldType;
use App\Enums\Marketing\FormTemplateStatus;
use App\Filament\Resources\FormTemplateResource\Pages;
use App\Models\Marketing\FormTemplate;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FormTemplateResource extends Resource
{
    protected static ?string $model = FormTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?int $navigationSort = 30;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.marketing');
    }

    public static function getNavigationLabel(): string
    {
        return __('resource.form_template.singular');
    }

    public static function getModelLabel(): string
    {
        return __('resource.form_template.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.form_template.plural');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isMarketingStaff() ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isMarketingStaff() ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->isMarketingStaff() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('section.form_template_details'))
                ->columns(2)
                ->schema([
                    TextInput::make('name')->label(__('field.name'))->required()->maxLength(255),
                    TextInput::make('slug')->label(__('field.slug'))->required()->maxLength(255)->unique(ignoreRecord: true),
                    Select::make('audience_type')->label(__('field.form_type'))->options(FormAudienceType::options())->default('generic')->required()->live(),
                    Select::make('status')->label(__('field.status'))->options(FormTemplateStatus::options())->default('draft')->required(),
                    TextInput::make('submit_button_text')->label(__('field.submit_button_text'))->maxLength(100)->default(__('field.submit')),
                    TextInput::make('success_message')->label(__('field.success_message'))->maxLength(255),
                    TextInput::make('redirect_url')->label(__('field.redirect_url'))->url()->maxLength(255),
                ]),
            Section::make(__('section.form_fields'))
                ->description(__('field.fields_count'))
                ->schema([
                    Repeater::make('fields')
                        ->relationship('fields')
                        ->label(__('field.fields'))
                        ->addActionLabel(__('action.add_form'))
                        ->defaultItems(0)
                        ->reorderableWithButtons()
                        ->collapsed()
                        ->schema([
                            TextInput::make('label')->label(__('field.field_label'))->required()->maxLength(255),
                            TextInput::make('field_key')->label(__('field.field_key'))->required()->maxLength(255),
                            Select::make('field_type')->label(__('field.field_type_text'))->options(FormFieldType::options())->required()->default(FormFieldType::Text->value)->live(),
                            TextInput::make('placeholder')->label(__('field.placeholder'))->maxLength(255),
                            TagsInput::make('options')
                                ->label(__('field.field_options'))
                                ->visible(fn (Get $get): bool => in_array((string) $get('field_type'), [FormFieldType::Select->value, FormFieldType::Checkbox->value], true)),
                            TextInput::make('default_value')->label(__('field.value'))->maxLength(255),
                            Toggle::make('is_required')->label(__('field.is_required'))->default(false),
                            Select::make('contact_mapping')
                                ->label(__('field.contact_mapping'))
                                ->options(fn (Get $get): array => FormTemplate::contactMappingOptions($get('../../audience_type')))
                                ->searchable(),
                            TextInput::make('validation_rules')->label(__('field.validation_rules'))->maxLength(255),
                            Toggle::make('tag_from_value')->label(__('field.auto_tag'))->default(false),
                            TextInput::make('sort_order')->label(__('field.sort_order'))->numeric()->default(0),
                            TextInput::make('position')->label(__('field.position'))->numeric()->default(0),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ]),
            Section::make(__('section.automation'))
                ->columns(1)
                ->schema([
                    TagsInput::make('auto_tag_names')->label(__('field.auto_tags')),
                    TagsInput::make('auto_list_names')->label(__('field.auto_lists')),
                    Toggle::make('auto_create_tags')->label(__('field.auto_create_tag'))->default(false),
                    Toggle::make('auto_create_lists')->label(__('field.auto_create_list'))->default(false),
                    Toggle::make('auto_create_segment')->label(__('field.auto_create_segment'))->default(false),
                ]),
            Section::make(__('section.custom_html'))
                ->collapsible()
                ->collapsed()
                ->schema([
                    RichEditor::make('html_body')->label(__('field.html_body'))->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label(__('field.name'))->searchable()->sortable(),
            TextColumn::make('slug')->label(__('field.slug'))->searchable(),
            TextColumn::make('audience_type')
                ->label(__('field.form_type'))
                ->badge()
                ->formatStateUsing(function ($state): string {
                    if ($state instanceof FormAudienceType) {
                        return $state->label();
                    }

                    $value = (string) $state;

                    return $value !== '' ? __('field.form_type.' . $value) : __('common.not_available');
                })
                ->color(function ($state): string {
                    $value = $state instanceof FormAudienceType ? $state->value : (string) $state;

                    return match ($value) {
                    'personal' => 'info',
                    'business' => 'warning',
                    'generic' => 'gray',
                    default => 'gray',
                    };
                }),
            TextColumn::make('status')
                ->label(__('field.status'))
                ->badge()
                ->formatStateUsing(function ($state): string {
                    if ($state instanceof FormTemplateStatus) {
                        return $state->label();
                    }

                    $value = (string) $state;

                    return $value !== '' ? __('enum.form_template_status.' . Str::lower($value)) : __('common.not_available');
                })
                ->color(function ($state): string {
                    $value = $state instanceof FormTemplateStatus ? $state->value : (string) $state;

                    return match ($value) {
                    'active' => 'success',
                    'draft' => 'gray',
                    'archived' => 'danger',
                    default => 'gray',
                    };
                }),
            TextColumn::make('version')->label(__('field.version'))->sortable(),
            TextColumn::make('created_at')->label(__('field.created_at'))->dateTime()->sortable(),
        ])
            ->actions([ActionGroup::make([
                EditAction::make(),
                DeleteAction::make(),
            ])->icon('heroicon-o-ellipsis-vertical')->iconButton()])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFormTemplates::route('/'),
            'create' => Pages\CreateFormTemplate::route('/create'),
            'edit' => Pages\EditFormTemplate::route('/{record}/edit'),
        ];
    }
}
