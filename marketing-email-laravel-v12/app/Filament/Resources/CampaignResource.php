<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CampaignResource\Pages;
use App\Models\Marketing\Campaign;
use App\Services\Marketing\CampaignAudienceService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.email_marketing');
    }

    public static function getModelLabel(): string
    {
        return __('resource.campaign.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.campaign.plural');
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
            Section::make(__('section.campaign_details'))
                ->columns(2)
                ->schema([
                    TextInput::make('name')->label(__('field.name'))->required()->maxLength(255),
                    Select::make('status')->label(__('field.status'))->options([
                        'draft' => __('enum.campaign_status.draft'),
                        'testing' => __('enum.campaign_status.testing'),
                        'scheduled' => __('enum.campaign_status.scheduled'),
                        'preparing' => __('enum.campaign_status.preparing'),
                        'sending' => __('enum.campaign_status.sending'),
                        'sent' => __('enum.campaign_status.sent'),
                        'paused' => __('enum.campaign_status.paused'),
                        'cancelled' => __('enum.campaign_status.cancelled'),
                        'failed' => __('enum.campaign_status.failed'),
                    ])->default('draft')->required(),
                    TextInput::make('subject')->label(__('field.subject'))->required()->maxLength(255),
                    TextInput::make('preheader')->label(__('field.preheader'))->maxLength(255),
                    Select::make('email_template_id')->label(__('field.email_template'))->relationship('template', 'name')->searchable(),
                    Select::make('sending_account_id')
                        ->label(__('field.sending_account'))
                        ->relationship('sendingAccount', 'name')
                        ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('department_id'))
                        ->helperText(__('field.sending_account_campaign_helper'))
                        ->searchable(),
                    Select::make('audience_type')
                        ->label(__('field.audience_type'))
                        ->options([
                            'all_subscribed' => __('field.audience_all_subscribed'),
                            'list' => __('enum.sales.audience_type.list'),
                            'tag' => __('enum.sales.audience_type.tag'),
                            'segment' => __('enum.sales.audience_type.segment'),
                            'qualified' => __('field.audience_qualified_customers'),
                        ])
                        ->default('all_subscribed')
                        ->required()
                        ->live(),
                    Select::make('audience_id')
                        ->label(fn (Get $get): string => match ((string) $get('audience_type')) {
                            'list' => __('field.audience_list'),
                            'tag' => __('field.audience_tag'),
                            'segment' => __('field.audience_segment'),
                            default => __('field.audience_id'),
                        })
                        ->options(fn (Get $get): array => app(CampaignAudienceService::class)->audienceOptions((string) $get('audience_type')))
                        ->searchable()
                        ->helperText(fn (Get $get): ?string => match ((string) $get('audience_type')) {
                            'list' => __('field.audience_id_list_helper'),
                            'tag' => __('field.audience_id_tag_helper'),
                            'segment' => __('field.audience_id_segment_helper'),
                            default => null,
                        })
                        ->required(fn (Get $get): bool => in_array($get('audience_type'), ['list', 'tag', 'segment'], true))
                        ->visible(fn (Get $get): bool => in_array($get('audience_type'), ['list', 'tag', 'segment'], true)),
                    Select::make('landing_page_id')
                        ->label(__('field.landing_page'))
                        ->relationship('landingPage', 'name')
                        ->searchable()
                        ->required(fn (Get $get): bool => $get('audience_type') === 'qualified')
                        ->helperText(__('field.audience_qualified_landing_page_helper')),
                    DateTimePicker::make('scheduled_at')->label(__('field.scheduled_at')),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label(__('field.name'))->searchable()->sortable(),
            TextColumn::make('subject')->label(__('field.subject'))->searchable()->limit(40),
            TextColumn::make('template.name')->label(__('field.email_template')),
            TextColumn::make('landingPage.name')->label(__('field.landing_page'))->toggleable(),
            TextColumn::make('status')->label(__('field.status'))->badge()
                ->formatStateUsing(fn (?string $state): string => $state ? __('enum.campaign_status.' . $state) : '')
                ->color(fn (?string $state): string => match ($state) {
                    'sent' => 'success',
                    'sending', 'preparing', 'scheduled', 'testing' => 'info',
                    'paused' => 'warning',
                    'failed', 'cancelled' => 'danger',
                    default => 'gray',
                }),
            TextColumn::make('scheduled_at')->label(__('field.scheduled_at'))->dateTime()->sortable(),
            TextColumn::make('sent_at')->label(__('field.sent_at'))->dateTime()->toggleable(),
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
            'index' => Pages\ListCampaigns::route('/'),
            'create' => Pages\CreateCampaign::route('/create'),
            'edit' => Pages\EditCampaign::route('/{record}/edit'),
        ];
    }
}
