<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MarketingCampaignResource\Pages;
use App\Models\Marketing\LandingPage;
use App\Models\Marketing\MarketingCampaign;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
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
            TextInput::make('name')->label(__('field.name'))->required()->maxLength(255),
            TextInput::make('slug')->label(__('field.slug'))->required()->maxLength(255)->unique(ignoreRecord: true),
            Textarea::make('description')->label(__('field.description'))->rows(3),
            Select::make('status')->options([
                'draft' => __('field.status_draft'),
                'active' => __('field.status_active'),
                'paused' => __('enum.campaign_status.paused'),
                'completed' => __('field.status_completed'),
            ])->default('draft')->required(),
            DatePicker::make('start_date')->label(__('field.start_date')),
            DatePicker::make('end_date')->label(__('field.end_date')),
            TextInput::make('budget')->label(__('field.budget'))->numeric(),
            Select::make('landing_page_ids')
                ->label(__('field.landing_pages'))
                ->multiple()
                ->searchable()
                ->preload()
                ->options(LandingPage::query()->orderBy('name')->pluck('name', 'id'))
                ->dehydrated(false)
                ->afterStateHydrated(function (Select $component, ?MarketingCampaign $record): void {
                    if ($record) {
                        $component->state($record->landingPages()->pluck('id')->all());
                    }
                }),
            Textarea::make('notes')->label(__('field.note'))->rows(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label(__('field.name'))->searchable()->sortable(),
            TextColumn::make('slug')->label(__('field.slug'))->searchable(),
            TextColumn::make('status')->label(__('field.status'))->badge()
                ->formatStateUsing(fn (?string $state): string => $state ? __('field.status_' . $state) : '—')
                ->color(fn (?string $state): string => match ($state) {
                    'active' => 'success',
                    'paused' => 'warning',
                    'completed' => 'info',
                    default => 'gray',
                }),
            TextColumn::make('landingPages_count')->label(__('field.landing_pages'))->counts('landingPages'),
            TextColumn::make('start_date')->label(__('field.start_date'))->date()->sortable(),
            TextColumn::make('end_date')->label(__('field.end_date'))->date()->sortable(),
            TextColumn::make('budget')->label(__('field.budget'))->money('VND')->sortable(),
        ])
            ->actions([ActionGroup::make([
                ViewAction::make(),
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
            'index' => Pages\ListMarketingCampaigns::route('/'),
            'create' => Pages\CreateMarketingCampaign::route('/create'),
            'edit' => Pages\EditMarketingCampaign::route('/{record}/edit'),
        ];
    }
}
