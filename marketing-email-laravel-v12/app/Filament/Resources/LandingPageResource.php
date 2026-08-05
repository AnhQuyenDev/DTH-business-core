<?php

namespace App\Filament\Resources;

use App\Enums\Marketing\FormAudienceType;
use App\Enums\Marketing\FormTemplateStatus;
use App\Enums\Marketing\LandingPageStatus;
use App\Filament\Resources\LandingPageResource\Pages;
use App\Filament\Resources\LandingPageResource\RelationManagers\UtmUrlsRelationManager;
use App\Models\Marketing\FormTemplate;
use App\Models\Marketing\LandingPage;
use App\Models\Marketing\LandingPageUtmUrl;
use App\Support\UtmOptions;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
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
            Section::make(__('section.landing_page_forms'))
                ->description(
                    'Mỗi Landing Page cần một Form cá nhân và một Form doanh nghiệp. '
                    .'Hai form sẽ được hiển thị dưới dạng tab ở cuối Landing Page.'
                )
                ->schema([
                    Tabs::make('landing_page_form_tabs')
                        ->tabs([
                            Tabs\Tab::make(
                                __('field.form_type.personal')
                            )
                                ->icon('heroicon-o-user')
                                ->schema([
                                    Select::make(
                                        'personal_form_template_id'
                                    )
                                        ->label('Mẫu Form cá nhân')
                                        ->options(
                                            fn (): array => FormTemplate::query()
                                                ->where(
                                                    'audience_type',
                                                    FormAudienceType::Personal->value
                                                )
                                                ->where(
                                                    'status',
                                                    FormTemplateStatus::Active->value
                                                )
                                                ->orderBy('name')
                                                ->pluck('name', 'id')
                                                ->all()
                                        )
                                        ->searchable()
                                        ->preload()
                                        ->helperText(
                                            'Chỉ hiển thị các mẫu cá nhân đang hoạt động.'
                                        ),
                                ]),

                            Tabs\Tab::make(
                                __('field.form_type.business')
                            )
                                ->icon('heroicon-o-building-office')
                                ->schema([
                                    Select::make(
                                        'business_form_template_id'
                                    )
                                        ->label('Mẫu Form doanh nghiệp')
                                        ->options(
                                            fn (): array => FormTemplate::query()
                                                ->where(
                                                    'audience_type',
                                                    FormAudienceType::Business->value
                                                )
                                                ->where(
                                                    'status',
                                                    FormTemplateStatus::Active->value
                                                )
                                                ->orderBy('name')
                                                ->pluck('name', 'id')
                                                ->all()
                                        )
                                        ->searchable()
                                        ->preload()
                                        ->helperText(
                                            'Chỉ hiển thị các mẫu doanh nghiệp đang hoạt động.'
                                        ),
                                ]),
                        ])
                        ->columnSpanFull(),
                ]),
            Section::make('Tone màu Form')
                ->description(
                    'Hệ thống tự nhận diện màu khi import. '
                    .'Bạn có thể điều chỉnh nếu kết quả chưa phù hợp.'
                )
                ->columns(3)
                ->schema([
                    ColorPicker::make('theme_tokens.primary')
                        ->label('Màu chính')
                        ->default('#2563eb'),

                    ColorPicker::make('theme_tokens.primary_hover')
                        ->label('Màu hover')
                        ->default('#1d4ed8'),

                    ColorPicker::make('theme_tokens.background')
                        ->label('Nền vùng form')
                        ->default('#f8fafc'),

                    ColorPicker::make('theme_tokens.surface')
                        ->label('Nền khung form')
                        ->default('#ffffff'),

                    ColorPicker::make('theme_tokens.text')
                        ->label('Màu chữ')
                        ->default('#0f172a'),

                    ColorPicker::make('theme_tokens.muted_text')
                        ->label('Màu chữ phụ')
                        ->default('#64748b'),

                    ColorPicker::make('theme_tokens.border')
                        ->label('Màu viền')
                        ->default('#cbd5e1'),

                    ColorPicker::make('theme_tokens.danger')
                        ->label('Màu báo lỗi')
                        ->default('#dc2626'),

                    TextInput::make('theme_tokens.radius')
                        ->label('Bo góc')
                        ->default('12px')
                        ->placeholder('12px'),
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
                            $hasPersonalForm = $record->forms()
                                ->where('form_type', 'personal')
                                ->where('status', 'active')
                                ->whereHas('formTemplate', function ($query): void {
                                    $query->where('status', 'active');
                                })
                                ->exists();

                            $hasBusinessForm = $record->forms()
                                ->where('form_type', 'business')
                                ->where('status', 'active')
                                ->whereHas('formTemplate', function ($query): void {
                                    $query->where('status', 'active');
                                })
                                ->exists();

                            if (! $hasPersonalForm || ! $hasBusinessForm) {
                                $missing = [];

                                if (! $hasPersonalForm) {
                                    $missing[] = 'Form cá nhân';
                                }

                                if (! $hasBusinessForm) {
                                    $missing[] = 'Form doanh nghiệp';
                                }

                                Notification::make()
                                    ->title('Chưa thể xuất bản Landing Page')
                                    ->body(
                                        'Thiếu: '.implode(', ', $missing).'.'
                                    )
                                    ->danger()
                                    ->send();

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
                            $generatedUrl = $base.($query ? ('?'.$query) : '');

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
