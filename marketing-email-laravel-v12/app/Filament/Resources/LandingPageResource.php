<?php

namespace App\Filament\Resources;

use App\Enums\Marketing\LandingPageStatus;
use App\Filament\Resources\LandingPageResource\Pages;
use App\Filament\Resources\LandingPageResource\RelationManagers\UtmUrlsRelationManager;
use App\Models\Marketing\FormTemplate;
use App\Models\Marketing\LandingPage;
use App\Models\Marketing\LandingPageUtmUrl;
use App\Support\UtmOptions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class LandingPageResource extends Resource
{
    protected static ?string $model = LandingPage::class;

    protected static ?string $navigationIcon = 'heroicon-o-window';

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.marketing');
    }

    public static function getNavigationLabel(): string
    {
        return __('resource.landing_page.singular');
    }

    public static function getModelLabel(): string
    {
        return __('resource.landing_page.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.landing_page.plural');
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
            Section::make(__('section.landing_page_details'))->columns(2)->schema([
                Select::make('marketing_campaign_id')->label(__('field.marketing_campaign'))->relationship('marketingCampaign', 'name')->searchable(),
                Select::make('campaign_id')->label(__('field.linked_email_campaign'))->relationship('defaultCampaign', 'name')->searchable(),
                TextInput::make('name')->label(__('field.name'))->required()->maxLength(255),
                TextInput::make('slug')->label(__('field.slug'))->required()->maxLength(255)->unique(ignoreRecord: true),
                TextInput::make('page_title')->label(__('field.page_title'))->maxLength(255),
                TextInput::make('headline')->label(__('field.headline'))->maxLength(255),
                TextInput::make('subheadline')->label(__('field.subheadline'))->maxLength(255),
                TextInput::make('cta_text')->label(__('field.cta_text'))->maxLength(255),
                Select::make('status')->label(__('field.status'))->options(LandingPageStatus::options())->default('draft')->required(),
                DateTimePicker::make('published_at')->label(__('field.published_at')),
            ]),
            Section::make(__('section.landing_page_forms'))->schema([
                Repeater::make('forms')
                    ->relationship('forms')
                    ->label(__('field.form_template'))
                    ->addActionLabel(__('action.add_form'))
                    ->defaultItems(0)
                    ->reorderableWithButtons()
                    ->schema([
                        Select::make('form_template_id')
                            ->label(__('field.form_template'))
                            ->options(FormTemplate::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Select::make('form_type')
                            ->label(__('field.form_type'))
                            ->options([
                                'personal' => __('field.form_type.personal'),
                                'business' => __('field.form_type.business'),
                                'generic' => __('field.form_type.generic'),
                            ])
                            ->required(),
                        TextInput::make('display_mode')->label(__('field.method'))->default('embedded')->maxLength(30),
                        TextInput::make('position_key')->label(__('field.position_key'))->maxLength(100),
                        Toggle::make('is_default')->label(__('field.is_default'))->default(false),
                        TextInput::make('sort_order')->label(__('field.sort_order'))->numeric()->default(0),
                        Select::make('status')
                            ->label(__('field.status'))
                            ->options([
                                'active' => __('field.status_active'),
                                'inactive' => __('field.status_inactive'),
                            ])
                            ->default('active')
                            ->required(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]),
            Section::make(__('section.automation'))->columns(1)->schema([
                TagsInput::make('auto_tag_names'),
                TagsInput::make('auto_list_names'),
                Toggle::make('auto_create_tags')->default(false),
                Toggle::make('auto_create_lists')->default(false),
                Toggle::make('auto_create_segment')->default(false),
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
            TextColumn::make('status')
                ->label(__('field.status'))
                ->badge()
                ->formatStateUsing(function ($state): string {
                    if ($state instanceof LandingPageStatus) {
                        return $state->label();
                    }

                    return LandingPageStatus::tryFrom((string) $state)?->label() ?? (string) $state;
                })
                ->color(function ($state): string {
                    $value = $state instanceof LandingPageStatus ? $state->value : (string) $state;

                    return match ($value) {
                        'published' => 'success',
                        'archived' => 'danger',
                        default => 'gray',
                    };
                }),
            TextColumn::make('defaultCampaign.name')->label(__('resource.campaign.singular'))->toggleable(),
            TextColumn::make('marketingCampaign.name')->label(__('resource.marketing_campaign.singular'))->toggleable(),
            TextColumn::make('published_at')->label(__('field.published_at'))->dateTime()->sortable(),
            TextColumn::make('created_at')->label(__('field.created_at'))->dateTime()->sortable(),
        ])
            ->actions([
                Action::make('links')
                    ->label(__('action.links'))
                    ->icon('heroicon-o-link')
                    ->modalHeading(__('relation.title.utm_urls'))
                    ->slideOver()
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('action.close'))
                    ->modalContent(fn (LandingPage $record) => view('filament.landing-pages.utm-links-modal', [
                        'record' => $record,
                        'utmUrls' => $record->utmUrls()->latest()->get(),
                    ])),
                ActionGroup::make([
                Action::make('publish')
                    ->label(__('action.publish'))
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (LandingPage $record): bool => ($record->status?->value ?? $record->status) !== 'published')
                    ->action(function (LandingPage $record): void {
                        if (! $record->campaign_id && ! $record->marketing_campaign_id) {
                            Notification::make()->title(__('notification.failed'))->body(__('notification.landing_page_requires_campaign'))->danger()->send();

                            return;
                        }

                        $record->update([
                            'status' => LandingPageStatus::Published->value,
                            'published_at' => now(),
                        ]);

                        Notification::make()->title(__('notification.landing_published'))->success()->send();
                    }),
                Action::make('unpublish')
                    ->label(__('action.unpublish'))
                    ->icon('heroicon-o-no-symbol')
                    ->color('warning')
                    ->visible(fn (LandingPage $record): bool => ($record->status?->value ?? $record->status) === 'published')
                    ->action(function (LandingPage $record): void {
                        $record->update([
                            'status' => LandingPageStatus::Draft->value,
                            'published_at' => null,
                        ]);

                        Notification::make()->title(__('notification.landing_unpublished'))->success()->send();
                    }),
                Action::make('generate_url')
                    ->label(__('action.generate_url'))
                    ->icon('heroicon-o-link')
                    ->form([
                        Select::make('utm_source')
                            ->label(__('field.utm_source'))
                            ->options(UtmOptions::source())
                            ->searchable()
                            ->required(),
                        Select::make('utm_medium')
                            ->label(__('field.utm_medium'))
                            ->options(UtmOptions::medium())
                            ->searchable()
                            ->required(),
                        TextInput::make('utm_campaign')->label(__('field.utm_campaign'))->required(),
                        TextInput::make('utm_content')->label(__('field.utm_content')),
                        TextInput::make('utm_term')->label(__('field.utm_term')),
                    ])
                    ->action(function (LandingPage $record, array $data): void {
                        $base = route('marketing.landing-pages.public.show', $record->slug);
                        $query = http_build_query(array_filter([
                            'utm_source' => $data['utm_source'] ?? null,
                            'utm_medium' => $data['utm_medium'] ?? null,
                            'utm_campaign' => $data['utm_campaign'] ?? null,
                            'utm_content' => $data['utm_content'] ?? null,
                            'utm_term' => $data['utm_term'] ?? null,
                        ]));
                        $generatedUrl = $base . ($query ? ('?' . $query) : '');

                        LandingPageUtmUrl::query()->create([
                            'landing_page_id' => $record->id,
                            'utm_source' => $data['utm_source'] ?? null,
                            'utm_medium' => $data['utm_medium'] ?? null,
                            'utm_campaign' => $data['utm_campaign'] ?? null,
                            'utm_content' => $data['utm_content'] ?? null,
                            'utm_term' => $data['utm_term'] ?? null,
                            'url' => $generatedUrl,
                        ]);

                        Notification::make()
                            ->title(__('notification.url_generated'))
                            ->body($generatedUrl)
                            ->success()
                            ->send();
                    }),
                Action::make('copy_link')
                    ->label(__('action.copy_link'))
                    ->icon('heroicon-o-link')
                    ->action(function (LandingPage $record): void {
                        $url = route('marketing.landing-pages.public.show', $record->slug);
                        Notification::make()
                            ->title(__('notification.link_copied'))
                            ->body($url)
                            ->success()
                            ->send();
                    }),
                Action::make('public_link')
                    ->label(__('action.public_link'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (LandingPage $record): string => route('marketing.landing-pages.public.show', $record->slug), shouldOpenInNewTab: true),
                Action::make('preview')
                    ->label(__('action.preview'))
                    ->icon('heroicon-o-eye')
                    ->url(fn (LandingPage $record): string => route('marketing.landing-pages.preview', $record), shouldOpenInNewTab: true),
                EditAction::make(),
                DeleteAction::make(),
            ])->icon('heroicon-o-ellipsis-vertical')->iconButton(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            UtmUrlsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLandingPages::route('/'),
            'create' => Pages\CreateLandingPage::route('/create'),
            'edit' => Pages\EditLandingPage::route('/{record}/edit'),
        ];
    }
}
