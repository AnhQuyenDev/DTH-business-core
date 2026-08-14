<?php

namespace App\Filament\Resources;

use App\Filament\Actions\QuickViewAction;
use App\Filament\Resources\MarketingCampaignResource\Pages;
use App\Models\Marketing\MarketingCampaign;
use App\Services\Marketing\MarketingCampaignServiceScopeService;
use App\Support\Ui\BadgePalette;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MarketingCampaignResource extends Resource
{
    protected static ?string $model = MarketingCampaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-speaker-wave';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.marketing');
    }

    public static function getNavigationLabel(): string
    {
        return __('resource.marketing_campaign.singular');
    }

    public static function getModelLabel(): string
    {
        return __('resource.marketing_campaign.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.marketing_campaign.plural');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('marketing.view-campaigns') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('marketing.manage-campaigns') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('marketing.manage-campaigns') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('section.marketing_campaign_identity'))
                ->description(__('helper.marketing_campaign_identity'))
                ->columns(['default' => 1, 'md' => 2])
                ->schema([
                    TextInput::make('name')
                        ->label(__('field.name'))
                        ->required()
                        ->maxLength(255),
                    Hidden::make('slug'),
                    Select::make('status')
                        ->label(__('field.status'))
                        ->options([
                            'draft' => __('field.status_draft'),
                            'active' => __('field.status_active'),
                            'paused' => __('enum.campaign_status.paused'),
                            'completed' => __('field.status_completed'),
                        ])
                        ->default('draft')
                        ->required(),
                    Textarea::make('description')
                        ->label(__('field.description'))
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            Section::make(__('section.marketing_campaign_scope'))
                ->description(__('helper.marketing_campaign_scope'))
                ->columns(['default' => 1, 'md' => 2])
                ->schema([
                    Select::make('service_ids')
                        ->label(__('field.promoted_services'))
                        ->multiple()
                        ->required()
                        ->minItems(1)
                        ->searchable()
                        ->preload()
                        ->live()
                        ->options(
                            fn (): array => app(
                                MarketingCampaignServiceScopeService::class
                            )->activeServiceOptions()
                        )
                        ->dehydrated(false)
                        ->afterStateHydrated(
                            function (Select $component, ?MarketingCampaign $record): void {
                                if ($record !== null) {
                                    $component->state(
                                        $record->services()->pluck('services.id')->all()
                                    );
                                }
                            }
                        )
                        ->helperText(__('helper.marketing_campaign_scope')),

                    Select::make('landing_page_ids')
                        ->label(__('field.landing_pages'))
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(
                            function (Get $get, ?MarketingCampaign $record): array {
                                return app(
                                    MarketingCampaignServiceScopeService::class
                                )->compatibleLandingPageOptions(
                                    (array) ($get('service_ids') ?? []),
                                    $record?->id,
                                );
                            }
                        )
                        ->dehydrated(false)
                        ->afterStateHydrated(
                            function (Select $component, ?MarketingCampaign $record): void {
                                if ($record !== null) {
                                    $component->state(
                                        $record->landingPages()->pluck('id')->all()
                                    );
                                }
                            }
                        )
                        ->helperText(__('helper.marketing_campaign_landing_pages')),
                ]),

            Section::make(__('section.marketing_campaign_timing'))
                ->description(__('helper.marketing_campaign_timing'))
                ->columns(['default' => 1, 'md' => 2, 'xl' => 3])
                ->schema([
                    DatePicker::make('start_date')
                        ->label(__('field.start_date')),
                    DatePicker::make('end_date')
                        ->label(__('field.end_date'))
                        ->afterOrEqual('start_date'),
                    TextInput::make('budget')
                        ->label(__('field.budget'))
                        ->numeric()
                        ->minValue(0)
                        ->prefix('₫'),
                ]),

            Section::make(__('section.marketing_campaign_notes'))
                ->description(__('helper.marketing_campaign_notes'))
                ->collapsed()
                ->schema([
                    Textarea::make('notes')
                        ->label(__('field.note'))
                        ->rows(3),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (Builder $query): Builder => $query->with([
                    'services',
                    'landingPages',
                ])
            )
            ->columns([
                TextColumn::make('name')
                    ->label(__('field.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('field.status'))
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string => $state
                            ? __('field.status_'.$state)
                            : '—'
                    )
                    ->color(fn (?string $state): string => BadgePalette::status($state, category: 'marketing.marketing_campaign_status')),
                TextColumn::make('promoted_services')
                    ->label(__('field.promoted_services'))
                    ->getStateUsing(
                        fn (MarketingCampaign $record): string => $record
                            ->services
                            ->pluck('name')
                            ->implode(', ')
                            ?: '—'
                    )
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('landing_pages')
                    ->label(__('field.landing_pages'))
                    ->getStateUsing(
                        fn (MarketingCampaign $record): string => $record
                            ->landingPages
                            ->pluck('name')
                            ->filter()
                            ->implode(', ')
                            ?: '—'
                    )
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('start_date')
                    ->label(__('field.start_date'))
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label(__('field.end_date'))
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('budget')
                    ->label(__('field.budget'))
                    ->money('VND')
                    ->sortable(),
            ])
            ->actions([
                ActionGroup::make([
                    QuickViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ])
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->iconButton(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMarketingCampaigns::route('/'),
            'create' => Pages\CreateMarketingCampaign::route('/create'),
            'edit' => Pages\EditMarketingCampaign::route('/{record}/edit'),
        ];
    }
}
