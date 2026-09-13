<?php

namespace Dth\Marketing\Filament\Resources;

use Dth\Marketing\Enums\MarketingCampaignStatus;
use Dth\Marketing\Filament\Navigation\MarketingNavigationGroup;
use Dth\Marketing\Filament\Resources\MarketingCampaignResource\Pages;
use Dth\Marketing\Filament\Resources\MarketingCampaignResource\RelationManagers\EmailCampaignsRelationManager;
use Dth\Marketing\Filament\Support\StatusColor;
use Dth\Marketing\Models\MarketingCampaign;
use Dth\Marketing\Services\CampaignServiceScopeService;
use Dth\Marketing\Services\EmailMarketingLinkService;
use Dth\Marketing\Services\MarketingCampaignLifecycleService;
use Dth\Marketing\Support\UiText;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class MarketingCampaignResource extends Resource
{
    protected static ?string $model = MarketingCampaign::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';
    protected static string|\UnitEnum|null $navigationGroup = MarketingNavigationGroup::Marketing;
    protected static ?int $navigationSort = 10;
    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.campaigns', 'Campaigns', context: 'navigation');
    }

    public static function getModelLabel(): string
    {
        return UiText::get('models.campaign', 'Marketing Campaign', context: 'model');
    }

    public static function getPluralModelLabel(): string
    {
        return UiText::get('models.campaigns', 'Marketing Campaigns', context: 'model');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(UiText::get('campaign.identity', 'Campaign'))
                ->icon('heroicon-o-megaphone')
                ->schema([
                    TextInput::make('name')
                        ->label(UiText::get('common.fields.name', 'Name'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('slug')
                        ->label(UiText::get('campaign.slug', 'Slug'))
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    Textarea::make('description')
                        ->label(UiText::get('common.fields.description', 'Description'))
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make(UiText::get('campaign.scope', 'Service scope'))
                ->icon('heroicon-o-squares-2x2')
                ->description(fn (): string => app(CampaignServiceScopeService::class)->available()
                    ? UiText::get('campaign.scope_ready', 'Services are read through CatalogProvider; no Sales model is referenced directly.')
                    : UiText::get('campaign.scope_na', 'Sales catalog is unavailable. Service scope is capability-aware and remains N/A.'))
                ->schema([
                    Select::make('service_references')
                        ->label(UiText::get('campaign.promoted_services', 'Promoted services'))
                        ->multiple()
                        ->options(fn (): array => app(CampaignServiceScopeService::class)->options())
                        ->searchable()
                        ->preload()
                        ->visible(fn (): bool => app(CampaignServiceScopeService::class)->available())
                        ->dehydrated(fn (): bool => app(CampaignServiceScopeService::class)->available()),
                ])
                ->columnSpanFull(),

            Section::make(UiText::get('campaign.timing', 'Timing and budget'))
                ->icon('heroicon-o-calendar-days')
                ->schema([
                    DatePicker::make('start_date')
                        ->label(UiText::get('campaign.start_date', 'Start date')),
                    DatePicker::make('end_date')
                        ->label(UiText::get('campaign.end_date', 'End date'))
                        ->afterOrEqual('start_date'),
                    TextInput::make('budget')
                        ->label(UiText::get('campaign.budget', 'Budget'))
                        ->numeric()
                        ->minValue(0),
                    Select::make('currency')
                        ->label(UiText::get('campaign.currency', 'Currency'))
                        ->options([
                            'VND' => 'VND',
                            'USD' => 'USD',
                        ])
                        ->default('VND')
                        ->required()
                        ->native(false),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make(UiText::get('campaign.notes', 'Notes'))
                ->collapsible()
                ->collapsed()
                ->schema([
                    Textarea::make('notes')
                        ->label(UiText::get('common.fields.notes', 'Notes'))
                        ->rows(4),
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
                TextColumn::make('status')
                    ->label(UiText::get('common.fields.status', 'Status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => UiText::status($state))
                    ->color(fn ($state): string => StatusColor::for($state))
                    ->sortable(),
                TextColumn::make('service_scope')
                    ->label(UiText::get('campaign.promoted_services', 'Promoted services'))
                    ->state(fn (MarketingCampaign $record): string => self::serviceScopeLabel($record))
                    ->wrap(),
                TextColumn::make('budget')
                    ->label(UiText::get('campaign.budget', 'Budget'))
                    ->formatStateUsing(fn ($state, MarketingCampaign $record): string => $state === null
                        ? '—'
                        : number_format((float) $state, 0, '.', ',').' '.$record->currency)
                    ->sortable(),
                TextColumn::make('start_date')
                    ->label(UiText::get('campaign.start_date', 'Start date'))
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label(UiText::get('campaign.end_date', 'End date'))
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                Actions\Action::make('report')
                    ->label(UiText::get('analytics.campaign_report', 'Report'))
                    ->icon('heroicon-o-presentation-chart-line')
                    ->url(fn (MarketingCampaign $record): string => static::getUrl('view', ['record' => $record]))
                    ->visible(fn (): bool => app(\Dth\Marketing\Support\MarketingAuthorizationService::class)->reports(auth()->user())),
                Actions\Action::make('email_campaigns')
                    ->label(UiText::get('email_bridge.campaigns', 'Email Campaigns'))
                    ->icon('heroicon-o-envelope')
                    ->modalHeading(UiText::get('email_bridge.campaigns', 'Email Campaigns'))
                    ->modalWidth('5xl')
                    ->modalSubmitAction(false)
                    ->modalContent(function (MarketingCampaign $record) {
                        $service = app(EmailMarketingLinkService::class);
                        if ($service->available()) {
                            try {
                                $service->refreshFromBridge($record);
                            } catch (Throwable $exception) {
                                report($exception);
                            }
                        }

                        return view('dth-marketing::filament.email-campaign-links', [
                            'links' => $record->emailLinks()->latest()->get(),
                            'service' => $service,
                            'available' => $service->available(),
                        ]);
                    })
                    ->visible(fn (): bool => config('dth-marketing.features.email_bridge', false) && app(\Dth\Marketing\Support\MarketingAuthorizationService::class)->view(auth()->user())),
                Actions\EditAction::make()
                    ->label(UiText::get('common.actions.edit', 'Edit'))
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn (MarketingCampaign $record): bool => app(\Dth\Marketing\Support\MarketingAuthorizationService::class)->manage(auth()->user()) && ! $record->isTerminal()),
                self::transitionAction('activate', MarketingCampaignStatus::Active, 'heroicon-o-play', 'success'),
                self::transitionAction('pause', MarketingCampaignStatus::Paused, 'heroicon-o-pause', 'warning'),
                self::transitionAction('complete', MarketingCampaignStatus::Completed, 'heroicon-o-check-circle', 'success'),
                self::transitionAction('cancel', MarketingCampaignStatus::Cancelled, 'heroicon-o-x-circle', 'danger', true),
                Actions\DeleteAction::make()
                    ->label(UiText::get('common.actions.delete', 'Delete'))
                    ->icon('heroicon-o-trash')
                    ->visible(fn (MarketingCampaign $record): bool => app(\Dth\Marketing\Support\MarketingAuthorizationService::class)->manage(auth()->user()) && $record->status === MarketingCampaignStatus::Draft),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()
                        ->label(UiText::get('common.actions.delete', 'Delete'))
                        ->authorizeIndividualRecords(),
                ]),
            ]);
    }

    private static function transitionAction(
        string $name,
        MarketingCampaignStatus $target,
        string $icon,
        string $color,
        bool $confirmation = false,
    ): Actions\Action {
        $action = Actions\Action::make($name)
            ->label(UiText::get('campaign.actions.'.$name, ucfirst($name)))
            ->icon($icon)
            ->color($color)
            ->visible(fn (MarketingCampaign $record): bool => app(\Dth\Marketing\Support\MarketingAuthorizationService::class)->manage(auth()->user()) && in_array(
                $target,
                app(MarketingCampaignLifecycleService::class)->allowedTargets($record),
                true,
            ))
            ->action(function (MarketingCampaign $record) use ($target): void {
                try {
                    app(MarketingCampaignLifecycleService::class)->transition($record, $target);

                    Notification::make()
                        ->title(UiText::get('campaign.status_updated', 'Campaign status updated'))
                        ->success()
                        ->send();
                } catch (Throwable $exception) {
                    Notification::make()
                        ->title(UiText::get('campaign.status_failed', 'Campaign status could not be changed'))
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });

        return $confirmation ? $action->requiresConfirmation() : $action;
    }

    private static function serviceScopeLabel(MarketingCampaign $record): string
    {
        $names = collect((array) ($record->service_snapshot ?? []))
            ->pluck('name')
            ->filter()
            ->implode(', ');

        if ($names !== '') {
            return $names;
        }

        $references = collect((array) ($record->service_references ?? []))
            ->filter()
            ->implode(', ');

        return $references !== '' ? $references : 'N/A';
    }

    public static function getRelations(): array
    {
        return [
            EmailCampaignsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMarketingCampaigns::route('/'),
            'create' => Pages\CreateMarketingCampaign::route('/create'),
            'view' => Pages\ViewMarketingCampaignReport::route('/{record}'),
            'edit' => Pages\EditMarketingCampaign::route('/{record}/edit'),
        ];
    }

    public static function canEdit(Model $record): bool
    {
        return app(\Dth\Marketing\Support\MarketingAuthorizationService::class)->manage(auth()->user())
            && $record instanceof MarketingCampaign
            && ! $record->isTerminal();
    }

    public static function canDelete(Model $record): bool
    {
        return app(\Dth\Marketing\Support\MarketingAuthorizationService::class)->manage(auth()->user())
            && $record instanceof MarketingCampaign
            && $record->status === MarketingCampaignStatus::Draft;
    }
}
