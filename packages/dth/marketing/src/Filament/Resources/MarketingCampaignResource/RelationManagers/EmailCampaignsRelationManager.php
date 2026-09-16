<?php

namespace Dth\Marketing\Filament\Resources\MarketingCampaignResource\RelationManagers;

use Dth\Marketing\Models\MarketingCampaignEmailLink;
use Dth\Marketing\Services\EmailMarketingLinkService;
use Dth\Marketing\Support\MarketingAuthorizationService;
use Dth\Marketing\Support\UiText;
use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EmailCampaignsRelationManager extends RelationManager
{
    protected static string $relationship = 'emailLinks';

    protected static ?string $title = 'Email Campaigns';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('email_campaign_reference')
                ->label(UiText::get('email_bridge.reference', 'Email Campaign reference'))
                ->required()
                ->maxLength(255),
            TextInput::make('display_name')
                ->label(UiText::get('common.fields.name', 'Name'))
                ->maxLength(255),
            TextInput::make('status_snapshot')
                ->label(UiText::get('common.fields.status', 'Status'))
                ->maxLength(255),
            TextInput::make('admin_url_snapshot')
                ->label(UiText::get('email_bridge.admin_url', 'Email Campaign URL'))
                ->url()
                ->maxLength(2000)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('display_name')
            ->columns([
                TextColumn::make('display_name')
                    ->label(UiText::get('common.fields.name', 'Name'))
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('email_campaign_reference')
                    ->label(UiText::get('email_bridge.reference', 'Reference'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('status_snapshot')
                    ->label(UiText::get('common.fields.status', 'Status'))
                    ->badge()
                    ->placeholder('N/A'),
                TextColumn::make('updated_at')
                    ->label(UiText::get('email_bridge.synced_at', 'Synced'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->headerActions([
                Actions\Action::make('refresh_bridge')
                    ->label(UiText::get('email_bridge.refresh', 'Refresh from Email'))
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn (): bool => app(MarketingAuthorizationService::class)->manage(auth()->user()) && app(EmailMarketingLinkService::class)->available())
                    ->action(function (): void {
                        $count = app(EmailMarketingLinkService::class)->refreshFromBridge($this->getOwnerRecord());
                        Notification::make()
                            ->title(UiText::get('email_bridge.refreshed', 'Email Campaign links refreshed'))
                            ->body(UiText::get('email_bridge.synced_count', 'Synced :count Email Campaign(s).', ['count' => $count]))
                            ->success()
                            ->send();
                    }),
                Actions\CreateAction::make()
                    ->label(UiText::get('email_bridge.manual_link', 'Add reference'))
                    ->icon('heroicon-o-link')
                    ->color('gray')
                    ->extraAttributes(['class' => 'dth-mkt-entry-action dth-mkt-entry-action--indigo'])
                    ->createAnother(false)
                    ->visible(fn (): bool => app(MarketingAuthorizationService::class)->manage(auth()->user()) && ! $this->getOwnerRecord()->isTerminal())
                    ->using(fn (array $data): MarketingCampaignEmailLink => app(EmailMarketingLinkService::class)
                        ->createManualLink($this->getOwnerRecord(), $data, auth()->id())),
            ])
            ->recordActions([
                \Filament\Actions\ActionGroup::make([
                    Actions\Action::make('open_email')
                        ->label(UiText::get('email_bridge.open', 'Open Email'))
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->url(fn (MarketingCampaignEmailLink $record): ?string => app(EmailMarketingLinkService::class)->adminUrl($record))
                        ->openUrlInNewTab()
                        ->visible(fn (MarketingCampaignEmailLink $record): bool => filled(app(EmailMarketingLinkService::class)->adminUrl($record))),
                    Actions\EditAction::make()
                        ->label(UiText::get('common.actions.edit', 'Edit'))
                        ->visible(fn (): bool => app(MarketingAuthorizationService::class)->manage(auth()->user()) && ! $this->getOwnerRecord()->isTerminal()),
                    Actions\DeleteAction::make()
                        ->label(UiText::get('common.actions.delete', 'Delete'))
                        ->visible(fn (): bool => app(MarketingAuthorizationService::class)->manage(auth()->user()) && ! $this->getOwnerRecord()->isTerminal()),
            
                ]),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()
                        ->label(UiText::get('common.actions.delete', 'Delete'))
                        ->visible(fn (): bool => app(MarketingAuthorizationService::class)->manage(auth()->user()) && ! $this->getOwnerRecord()->isTerminal())
                        ->authorizeIndividualRecords(),
                ]),
            ]);
    }
}
