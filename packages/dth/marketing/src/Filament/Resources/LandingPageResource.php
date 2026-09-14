<?php

namespace Dth\Marketing\Filament\Resources;

use Dth\Marketing\Enums\FormAudienceType;
use Dth\Marketing\Enums\FormTemplateStatus;
use Dth\Marketing\Enums\LandingPageStatus;
use Dth\Marketing\Enums\MarketingCampaignStatus;
use Dth\Marketing\Filament\Navigation\MarketingNavigationGroup;
use Dth\Marketing\Filament\Resources\LandingPageResource\Pages;
use Dth\Marketing\Filament\Support\StatusColor;
use Dth\Marketing\Models\FormTemplate;
use Dth\Marketing\Models\LandingPage;
use Dth\Marketing\Models\MarketingCampaign;
use Dth\Marketing\Services\FormTemplateHtmlImportService;
use Dth\Marketing\Services\LandingPageCatalogService;
use Dth\Marketing\Services\LandingPageHtmlImportService;
use Dth\Marketing\Services\LandingPageLifecycleService;
use Dth\Marketing\Services\LandingPageUtmService;
use Dth\Marketing\Services\UtmReportService;
use Dth\Marketing\Support\UiText;
use Filament\Actions;
use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

class LandingPageResource extends Resource
{
    protected static ?string $model = LandingPage::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-window';
    protected static string|\UnitEnum|null $navigationGroup = MarketingNavigationGroup::Marketing;
    protected static ?int $navigationSort = 20;
    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.landing_pages', 'Landing Pages', context: 'navigation');
    }

    public static function getModelLabel(): string
    {
        return UiText::get('models.landing_page', 'Landing Page', context: 'model');
    }

    public static function getPluralModelLabel(): string
    {
        return UiText::get('models.landing_pages', 'Landing Pages', context: 'model');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(UiText::get('landing.details', 'Landing Page'))
                ->icon('heroicon-o-window')
                ->schema([
                    Select::make('marketing_campaign_id')
                        ->label(UiText::get('landing.campaign', 'Marketing Campaign'))
                        ->options(fn (): array => MarketingCampaign::query()
                            ->whereNotIn('status', [
                                MarketingCampaignStatus::Completed->value,
                                MarketingCampaignStatus::Cancelled->value,
                            ])
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Set $set): void {
                            $set('service_reference', null);
                            $set('package_references', []);
                        }),
                    TextInput::make('name')
                        ->label(UiText::get('common.fields.name', 'Name'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('slug')
                        ->label(UiText::get('landing.slug', 'Public slug'))
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    TextInput::make('page_title')
                        ->label(UiText::get('landing.page_title', 'Page title'))
                        ->maxLength(255),
                    TextInput::make('headline')
                        ->label(UiText::get('landing.headline', 'Headline'))
                        ->maxLength(255),
                    TextInput::make('subheadline')
                        ->label(UiText::get('landing.subheadline', 'Subheadline'))
                        ->maxLength(255),
                    TextInput::make('cta_text')
                        ->label(UiText::get('landing.cta', 'CTA'))
                        ->maxLength(255),
                    Textarea::make('content')
                        ->label(UiText::get('landing.content', 'Fallback content'))
                        ->rows(4)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make(UiText::get('landing.catalog', 'Service context'))
                ->icon('heroicon-o-squares-2x2')
                ->description(fn (): string => app(LandingPageCatalogService::class)->available()
                    ? UiText::get('landing.catalog_ready', 'Service and package choices are read through CatalogProvider.')
                    : UiText::get('landing.catalog_na', 'Sales catalog is unavailable. Catalog-dependent choices remain N/A.'))
                ->schema([
                    Select::make('service_reference')
                        ->label(UiText::get('landing.service', 'Primary service'))
                        ->options(fn (Get $get): array => app(LandingPageCatalogService::class)->serviceOptions(
                            filled($get('marketing_campaign_id')) ? (int) $get('marketing_campaign_id') : null,
                        ))
                        ->searchable()
                        ->preload()
                        ->live()
                        ->visible(fn (): bool => app(LandingPageCatalogService::class)->available())
                        ->dehydrated(fn (): bool => app(LandingPageCatalogService::class)->available())
                        ->afterStateUpdated(fn (Set $set): mixed => $set('package_references', [])),
                    Select::make('package_references')
                        ->label(UiText::get('landing.packages', 'Allowed packages'))
                        ->multiple()
                        ->options(fn (Get $get): array => app(LandingPageCatalogService::class)->packageOptions(
                            filled($get('service_reference')) ? (string) $get('service_reference') : null,
                        ))
                        ->searchable()
                        ->preload()
                        ->visible(fn (): bool => app(LandingPageCatalogService::class)->available())
                        ->dehydrated(fn (): bool => app(LandingPageCatalogService::class)->available())
                        ->disabled(fn (Get $get): bool => blank($get('service_reference'))),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make(UiText::get('landing.forms', 'Form Templates'))
                ->icon('heroicon-o-document-duplicate')
                ->schema([
                    Select::make('personal_form_template_id')
                        ->label(UiText::get('landing.personal_form', 'Personal form'))
                        ->options(fn (Get $get): array => self::formTemplateOptions(
                            FormAudienceType::Personal,
                            filled($get('personal_form_template_id')) ? (int) $get('personal_form_template_id') : null,
                        ))
                        ->searchable()
                        ->preload(),
                    Select::make('business_form_template_id')
                        ->label(UiText::get('landing.business_form', 'Business form'))
                        ->options(fn (Get $get): array => self::formTemplateOptions(
                            FormAudienceType::Business,
                            filled($get('business_form_template_id')) ? (int) $get('business_form_template_id') : null,
                        ))
                        ->searchable()
                        ->preload(),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make(UiText::get('landing.editor', 'HTML and CSS'))
                ->icon('heroicon-o-code-bracket-square')
                ->description(UiText::get('landing.editor_description', 'Import existing HTML or edit the stored markup directly. Imported scripts and inline event handlers are removed.'))
                ->schema([
                    FileUpload::make('html_import')
                        ->label(UiText::get('landing.import_html', 'Import HTML'))
                        ->acceptedFileTypes(['text/html', 'application/xhtml+xml', 'text/plain'])
                        ->maxSize(2048)
                        ->storeFiles(false)
                        ->dehydrated(false)
                        ->previewable(false)
                        ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                            if (! $state instanceof TemporaryUploadedFile) {
                                return;
                            }

                            try {
                                $import = app(LandingPageHtmlImportService::class)->fromPath($state->getRealPath());
                                $set('html_body', $import['html']);

                                if ($import['title']) {
                                    $set('page_title', $import['title']);
                                }

                                foreach ($import['theme'] as $key => $value) {
                                    $set('theme_tokens.'.$key, $value);
                                }

                                $baseName = trim((string) ($get('name') ?: $import['title'] ?: 'Imported Landing Page'));
                                $createdByAudience = [];
                                $createdNames = [];

                                foreach ($import['embedded_forms'] as $index => $embeddedForm) {
                                    $audience = FormAudienceType::tryFrom((string) ($embeddedForm['audience_type'] ?? ''))
                                        ?? FormAudienceType::Personal;
                                    $audienceLabel = FormAudienceType::labelFor($audience);
                                    $sequence = ($createdByAudience[$audience->value] ?? 0) + 1;
                                    $createdByAudience[$audience->value] = $sequence;
                                    $templateName = $baseName.' - '.$audienceLabel.' Form'.($sequence > 1 ? ' '.$sequence : '');

                                    $template = app(FormTemplateHtmlImportService::class)->import([
                                        'name' => $templateName,
                                        'audience_type' => $audience->value,
                                    ], (string) $embeddedForm['html'], auth()->id());

                                    $createdNames[] = $template->name;

                                    // The first Personal/Business form is attached immediately
                                    // as Draft. Source-parity rendering removes the imported form
                                    // section and appends one canonical forms section at the end of
                                    // the Landing Page. Publish still requires Active templates.
                                    if ($sequence === 1) {
                                        $set(
                                            $audience === FormAudienceType::Business
                                                ? 'business_form_template_id'
                                                : 'personal_form_template_id',
                                            $template->getKey(),
                                        );
                                    }
                                }

                                $body = $createdNames === []
                                    ? UiText::get('landing.imported_no_form', 'The complete HTML document and its styles were preserved.')
                                    : UiText::get(
                                        'landing.imported_forms',
                                        ':count embedded form(s) were extracted into Draft Form Templates and mapped automatically. Review and activate them before publishing.',
                                        ['count' => count($createdNames)],
                                    );

                                Notification::make()
                                    ->title(UiText::get('landing.imported', 'HTML imported'))
                                    ->body($body)
                                    ->success()
                                    ->send();
                            } catch (Throwable $exception) {
                                report($exception);

                                Notification::make()
                                    ->title(UiText::get('landing.import_failed', 'HTML import failed'))
                                    ->body($exception->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->columnSpanFull(),
                    CodeEditor::make('html_body')
                        ->label(UiText::get('landing.html', 'HTML'))
                        ->columnSpanFull(),
                    CodeEditor::make('css_body')
                        ->label(UiText::get('landing.css', 'CSS'))
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),

            Section::make(UiText::get('automation.title', 'Post-submission automation'))
                ->icon('heroicon-o-bolt')
                ->collapsible()
                ->collapsed()
                ->description(UiText::get('automation.description', 'Optional actions run only after a submission is processed successfully.'))
                ->schema([
                    Toggle::make('auto_create_tags')
                        ->label(UiText::get('automation.enable_tags', 'Add tags'))
                        ->live(),
                    TagsInput::make('auto_tag_names')
                        ->label(UiText::get('automation.tags', 'Tags'))
                        ->visible(fn (Get $get): bool => (bool) $get('auto_create_tags')),
                    Toggle::make('auto_create_lists')
                        ->label(UiText::get('automation.enable_lists', 'Add to marketing lists'))
                        ->live(),
                    TagsInput::make('auto_list_names')
                        ->label(UiText::get('automation.lists', 'Marketing list names'))
                        ->visible(fn (Get $get): bool => (bool) $get('auto_create_lists')),
                    Toggle::make('auto_create_segment')
                        ->label(UiText::get('automation.create_segment', 'Create automatic Landing Page segment')),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make(UiText::get('landing.theme', 'Theme'))
                ->icon('heroicon-o-swatch')
                ->collapsible()
                ->schema([
                    ColorPicker::make('theme_tokens.primary')->label(UiText::get('landing.primary', 'Primary'))->default('#2563eb'),
                    ColorPicker::make('theme_tokens.primary_hover')->label(UiText::get('landing.primary_hover', 'Primary hover'))->default('#1d4ed8'),
                    ColorPicker::make('theme_tokens.background')->label(UiText::get('landing.background', 'Background'))->default('#f8fafc'),
                    ColorPicker::make('theme_tokens.surface')->label(UiText::get('landing.surface', 'Surface'))->default('#ffffff'),
                    ColorPicker::make('theme_tokens.text')->label(UiText::get('landing.text', 'Text'))->default('#0f172a'),
                    ColorPicker::make('theme_tokens.muted_text')->label(UiText::get('landing.muted_text', 'Muted text'))->default('#64748b'),
                    ColorPicker::make('theme_tokens.border')->label(UiText::get('landing.border', 'Border'))->default('#cbd5e1'),
                    TextInput::make('theme_tokens.radius')->label(UiText::get('landing.radius', 'Radius'))->default('12px')->maxLength(20),
                ])
                ->columns(4)
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
                TextColumn::make('status')
                    ->label(UiText::get('common.fields.status', 'Status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => UiText::status($state))
                    ->color(fn ($state): string => StatusColor::for($state)),
                TextColumn::make('marketingCampaign.name')
                    ->label(UiText::get('landing.campaign', 'Marketing Campaign'))
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('service_reference')
                    ->label(UiText::get('landing.service', 'Primary service'))
                    ->formatStateUsing(fn ($state, LandingPage $record): string => self::serviceLabel($record))
                    ->placeholder('N/A'),
                TextColumn::make('published_at')
                    ->label(UiText::get('landing.published_at', 'Published'))
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                \Filament\Actions\ActionGroup::make([
                    Actions\Action::make('preview')
                        ->label(UiText::get('landing.preview', 'Preview'))
                        ->icon('heroicon-o-eye')
                        ->url(fn (LandingPage $record): string => route('marketing.landing-pages.preview', ['landingPage' => $record]))
                        ->openUrlInNewTab(),
                    Actions\Action::make('public')
                        ->label(UiText::get('landing.public_link', 'Public'))
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->url(fn (LandingPage $record): string => $record->publicUrl())
                        ->openUrlInNewTab()
                        ->visible(fn (LandingPage $record): bool => $record->status === LandingPageStatus::Published),
                    Actions\Action::make('copy_link')
                        ->label(UiText::get('landing.copy_link', 'Copy link'))
                        ->icon('heroicon-o-clipboard-document')
                        ->modalHeading(UiText::get('landing.copy_link', 'Copy link'))
                        ->modalWidth('2xl')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel(UiText::get('common.actions.close', 'Close'))
                        ->modalContent(fn (LandingPage $record) => view('dth-marketing::filament.copy-link', [
                            'url' => $record->publicUrl(),
                        ]))
                        ->visible(fn (LandingPage $record): bool => $record->status === LandingPageStatus::Published),
                    Actions\Action::make('generate_utm')
                        ->label(UiText::get('landing.generate_utm', 'Generate UTM'))
                        ->icon('heroicon-o-link')
                        ->modalHeading(UiText::get('landing.generate_utm', 'Generate UTM'))
                        ->schema([
                            TextInput::make('name')
                                ->label(UiText::get('landing.utm_name', 'Link name'))
                                ->placeholder(UiText::get('landing.utm_name_placeholder', 'Ví dụ: Facebook - VPS tháng 9'))
                                ->helperText(UiText::get('landing.utm_name_help', 'Tên nội bộ để dễ tìm và phân biệt liên kết UTM.'))
                                ->hintIcon('heroicon-m-question-mark-circle', UiText::get('landing.utm_name_help', 'Tên nội bộ để dễ tìm và phân biệt liên kết UTM.'))
                                ->maxLength(255),
                            Select::make('source')
                                ->label(UiText::get('landing.utm_source', 'Source'))
                                ->options([
                                    'facebook' => 'Facebook',
                                    'instagram' => 'Instagram',
                                    'youtube' => 'YouTube',
                                    'google' => 'Google',
                                    'tiktok' => 'TikTok',
                                    'linkedin' => 'LinkedIn',
                                    'email' => 'Email',
                                    'zalo' => 'Zalo',
                                    'website' => UiText::get('landing.utm_source_website', 'Website'),
                                    'other' => UiText::get('landing.utm_source_other', 'Khác'),
                                ])
                                ->placeholder(UiText::get('landing.utm_source_placeholder', 'Chọn nguồn truy cập'))
                                ->helperText(UiText::get('landing.utm_source_help', 'Nguồn đưa người dùng đến Landing Page, ví dụ Facebook hoặc Google.'))
                                ->hintIcon('heroicon-m-question-mark-circle', UiText::get('landing.utm_source_help', 'Nguồn đưa người dùng đến Landing Page, ví dụ Facebook hoặc Google.'))
                                ->required()
                                ->native(false),
                            Select::make('medium')
                                ->label(UiText::get('landing.utm_medium', 'Medium'))
                                ->options([
                                    'social' => UiText::get('landing.utm_medium_social', 'Mạng xã hội'),
                                    'cpc' => 'CPC / quảng cáo trả phí',
                                    'display' => UiText::get('landing.utm_medium_display', 'Quảng cáo hiển thị'),
                                    'email' => 'Email',
                                    'organic' => UiText::get('landing.utm_medium_organic', 'Tìm kiếm tự nhiên'),
                                    'referral' => UiText::get('landing.utm_medium_referral', 'Giới thiệu từ website khác'),
                                    'other' => UiText::get('landing.utm_medium_other', 'Khác'),
                                ])
                                ->placeholder(UiText::get('landing.utm_medium_placeholder', 'Chọn cách người dùng truy cập'))
                                ->helperText(UiText::get('landing.utm_medium_help', 'Kênh hoặc hình thức tiếp cận, ví dụ mạng xã hội, quảng cáo trả phí hoặc email.'))
                                ->hintIcon('heroicon-m-question-mark-circle', UiText::get('landing.utm_medium_help', 'Kênh hoặc hình thức tiếp cận, ví dụ mạng xã hội, quảng cáo trả phí hoặc email.'))
                                ->required()
                                ->native(false),
                            TextInput::make('campaign')
                                ->label(UiText::get('landing.utm_campaign', 'Campaign'))
                                ->placeholder(UiText::get('landing.utm_campaign_placeholder', 'Ví dụ: khuyen-mai-vps-thang-9'))
                                ->helperText(UiText::get('landing.utm_campaign_help', 'Tên chiến dịch đang chạy để gom các lượt truy cập cùng một chương trình.'))
                                ->hintIcon('heroicon-m-question-mark-circle', UiText::get('landing.utm_campaign_help', 'Tên chiến dịch đang chạy để gom các lượt truy cập cùng một chương trình.'))
                                ->maxLength(150),
                            TextInput::make('content')
                                ->label(UiText::get('landing.utm_content', 'Content'))
                                ->placeholder(UiText::get('landing.utm_content_placeholder', 'Ví dụ: banner-header hoặc nut-dang-ky'))
                                ->helperText(UiText::get('landing.utm_content_help', 'Phân biệt các nội dung hoặc vị trí quảng cáo trong cùng một chiến dịch.'))
                                ->hintIcon('heroicon-m-question-mark-circle', UiText::get('landing.utm_content_help', 'Phân biệt các nội dung hoặc vị trí quảng cáo trong cùng một chiến dịch.'))
                                ->maxLength(150),
                            TextInput::make('term')
                                ->label(UiText::get('landing.utm_term', 'Term'))
                                ->placeholder(UiText::get('landing.utm_term_placeholder', 'Ví dụ: hosting-vps'))
                                ->helperText(UiText::get('landing.utm_term_help', 'Từ khóa dùng cho quảng cáo tìm kiếm; có thể để trống nếu không chạy tìm kiếm.'))
                                ->hintIcon('heroicon-m-question-mark-circle', UiText::get('landing.utm_term_help', 'Từ khóa dùng cho quảng cáo tìm kiếm; có thể để trống nếu không chạy tìm kiếm.'))
                                ->maxLength(150),
                        ])
                        ->action(function (LandingPage $record, array $data): void {
                            $utm = app(LandingPageUtmService::class)->create($record, $data, auth()->id());

                            Notification::make()
                                ->title(UiText::get('landing.utm_generated', 'UTM URL generated'))
                                ->body($utm->url)
                                ->success()
                                ->persistent()
                                ->send();
                        })
                        ->visible(fn (LandingPage $record): bool => app(\Dth\Marketing\Support\MarketingAuthorizationService::class)->manage(auth()->user()) && $record->status === LandingPageStatus::Published),
                    Actions\Action::make('utm_report')
                        ->label(UiText::get('landing.utm_report', 'UTM report'))
                        ->icon('heroicon-o-chart-bar')
                        ->modalHeading(UiText::get('landing.utm_report', 'UTM report'))
                        ->modalWidth('6xl')
                        ->modalSubmitAction(false)
                        ->modalContent(fn (LandingPage $record) => view('dth-marketing::filament.utm-report', [
                            'report' => app(UtmReportService::class)->forLandingPage($record),
                            'links' => $record->utmUrls()->latest()->limit(100)->get(),
                        ]))
                        ->visible(fn (LandingPage $record): bool => config('dth-marketing.features.utm', false)),
                    Actions\EditAction::make()
                        ->label(UiText::get('common.actions.edit', 'Edit'))
                        ->icon('heroicon-o-pencil-square')
                        ->visible(fn (LandingPage $record): bool => app(\Dth\Marketing\Support\MarketingAuthorizationService::class)->manage(auth()->user()) && $record->status === LandingPageStatus::Draft),
                    self::statusAction('publish', LandingPageStatus::Published, 'heroicon-o-check-badge', 'success'),
                    self::statusAction('unpublish', LandingPageStatus::Draft, 'heroicon-o-no-symbol', 'warning'),
                    self::statusAction('archive', LandingPageStatus::Archived, 'heroicon-o-archive-box', 'gray', true),
                    Actions\DeleteAction::make()
                        ->label(UiText::get('common.actions.delete', 'Delete'))
                        ->icon('heroicon-o-trash')
                        ->visible(fn (LandingPage $record): bool => app(\Dth\Marketing\Support\MarketingAuthorizationService::class)->manage(auth()->user()) && $record->status === LandingPageStatus::Draft),
            
                ]),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()
                        ->label(UiText::get('common.actions.delete', 'Delete'))
                        ->authorizeIndividualRecords(),
                ]),
            ]);
    }

    private static function statusAction(
        string $name,
        LandingPageStatus $target,
        string $icon,
        string $color,
        bool $confirmation = false,
    ): Actions\Action {
        $action = Actions\Action::make($name)
            ->label(UiText::get('landing.actions.'.$name, ucfirst($name)))
            ->icon($icon)
            ->color($color)
            ->visible(function (LandingPage $record) use ($target): bool {
                if (! app(\Dth\Marketing\Support\MarketingAuthorizationService::class)->manage(auth()->user())) {
                    return false;
                }

                $from = $record->status instanceof \BackedEnum ? $record->status->value : (string) $record->status;

                return in_array($target->value, app(LandingPageLifecycleService::class)->transitions()[$from] ?? [], true);
            })
            ->action(function (LandingPage $record) use ($target): void {
                try {
                    app(LandingPageLifecycleService::class)->transition($record, $target);
                    Notification::make()->title(UiText::get('landing.status_updated', 'Landing Page status updated'))->success()->send();
                } catch (Throwable $exception) {
                    Notification::make()->title(UiText::get('landing.status_failed', 'Landing Page status could not be changed'))->body($exception->getMessage())->danger()->send();
                }
            });

        return $confirmation ? $action->requiresConfirmation() : $action;
    }

    private static function serviceLabel(LandingPage $record): string
    {
        $name = data_get($record->catalog_snapshot, 'service.name');

        return filled($name) ? (string) $name : (string) ($record->service_reference ?: 'N/A');
    }

    /** @return array<int|string, string> */
    private static function formTemplateOptions(FormAudienceType $audience, ?int $currentId): array
    {
        return FormTemplate::query()
            ->where('audience_type', $audience->value)
            ->where(function ($query) use ($currentId): void {
                $query->where('status', FormTemplateStatus::Active->value);

                if ($currentId !== null) {
                    $query->orWhere($query->getModel()->getQualifiedKeyName(), $currentId);
                }
            })
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLandingPages::route('/'),
            'create' => Pages\CreateLandingPage::route('/create'),
            'edit' => Pages\EditLandingPage::route('/{record}/edit'),
        ];
    }

    public static function canEdit(Model $record): bool
    {
        return app(\Dth\Marketing\Support\MarketingAuthorizationService::class)->manage(auth()->user())
            && $record instanceof LandingPage
            && $record->status === LandingPageStatus::Draft;
    }

    public static function canDelete(Model $record): bool
    {
        return static::canEdit($record);
    }
}
