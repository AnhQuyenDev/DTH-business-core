<?php

namespace Dth\Marketing\Filament\Resources\ContactListResource\RelationManagers;

use Dth\Marketing\Filament\Support\StatusColor;
use Dth\Marketing\Enums\FormAudienceType;
use Dth\Marketing\Models\ContactListMember;
use Dth\Marketing\Support\MarketingAuthorizationService;
use Dth\Marketing\Support\UiText;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return UiText::get('audience.members', 'Members');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('display_name')
                ->label(UiText::get('audience.member_name', 'Name'))
                ->maxLength(255),
            TextInput::make('normalized_email')
                ->label(UiText::get('audience.member_email', 'Email'))
                ->email()
                ->maxLength(255),
            TextInput::make('normalized_phone')
                ->label(UiText::get('audience.member_phone', 'Phone'))
                ->tel()
                ->maxLength(50),
            TextInput::make('contact_reference')
                ->label(UiText::get('audience.contact_reference', 'External contact reference'))
                ->maxLength(255),
            Select::make('audience_type')
                ->label(UiText::get('audience.customer_type', 'Customer type'))
                ->options([
                    'personal' => UiText::get('form.audience.personal', 'Personal'),
                    'business' => UiText::get('form.audience.business', 'Business'),
                ])
                ->default('personal')
                ->required()
                ->native(false),
            Select::make('status')
                ->label(UiText::get('common.fields.status', 'Status'))
                ->options([
                    'subscribed' => UiText::get('audience.subscribed', 'Subscribed'),
                    'unsubscribed' => UiText::get('audience.unsubscribed', 'Unsubscribed'),
                ])
                ->default('subscribed')
                ->required()
                ->native(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('display_name')
            ->columns([
                TextColumn::make('display_name')
                    ->label(UiText::get('audience.member_name', 'Name'))
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('normalized_email')
                    ->label(UiText::get('audience.member_email', 'Email'))
                    ->searchable()
                    ->copyable()
                    ->placeholder('—'),
                TextColumn::make('normalized_phone')
                    ->label(UiText::get('audience.member_phone', 'Phone'))
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('audience_type')
                    ->label(UiText::get('audience.customer_type', 'Customer type'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => FormAudienceType::labelFor($state))
                    ->color(fn ($state): string => StatusColor::for($state)),
                TextColumn::make('status')
                    ->label(UiText::get('common.fields.status', 'Status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => UiText::status($state))
                    ->color(fn ($state): string => StatusColor::for($state)),
                TextColumn::make('sourceSubmission.landingPage.name')
                    ->label(UiText::get('audience.source_landing', 'Source Landing'))
                    ->placeholder(UiText::get('audience.manual', 'Manual'))
                    ->toggleable(),
                TextColumn::make('subscribed_at')
                    ->label(UiText::get('audience.subscribed_at', 'Subscribed'))
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(UiText::get('common.fields.status', 'Status'))
                    ->options([
                        'subscribed' => UiText::get('audience.subscribed', 'Subscribed'),
                        'unsubscribed' => UiText::get('audience.unsubscribed', 'Unsubscribed'),
                    ]),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(1)
            ->deferFilters(false)
            ->hiddenFilterIndicators()
            ->headerActions([
                Actions\CreateAction::make()
                    ->label(UiText::get('audience.add_member', 'Add member'))
                    ->icon('heroicon-o-user-plus')
                    ->visible(fn (): bool => app(MarketingAuthorizationService::class)->manage(auth()->user())),
            ])
            ->recordActions([
                \Filament\Actions\ActionGroup::make([
                    Actions\EditAction::make()
                        ->label(UiText::get('common.actions.edit', 'Edit'))
                        ->visible(fn (): bool => app(MarketingAuthorizationService::class)->manage(auth()->user())),
                    Actions\DeleteAction::make()
                        ->label(UiText::get('common.actions.delete', 'Delete'))
                        ->visible(fn (): bool => app(MarketingAuthorizationService::class)->manage(auth()->user())),
            
                ]),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('subscribe')
                        ->visible(fn (): bool => app(MarketingAuthorizationService::class)->manage(auth()->user()))
                        ->label(UiText::get('audience.subscribe', 'Subscribe'))
                        ->icon('heroicon-o-check-circle')
                        ->action(function ($records): void {
                            ContactListMember::query()->whereKey($records->modelKeys())->update([
                                'status' => 'subscribed',
                                'subscribed_at' => now(),
                                'unsubscribed_at' => null,
                            ]);
                        }),
                    Actions\BulkAction::make('unsubscribe')
                        ->visible(fn (): bool => app(MarketingAuthorizationService::class)->manage(auth()->user()))
                        ->label(UiText::get('audience.unsubscribe', 'Unsubscribe'))
                        ->icon('heroicon-o-no-symbol')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            ContactListMember::query()->whereKey($records->modelKeys())->update([
                                'status' => 'unsubscribed',
                                'unsubscribed_at' => now(),
                            ]);
                        }),
                    Actions\DeleteBulkAction::make()
                        ->label(UiText::get('common.actions.delete', 'Delete'))
                        ->visible(fn (): bool => app(MarketingAuthorizationService::class)->manage(auth()->user()))
                        ->authorizeIndividualRecords(),
                ]),
            ]);
    }
}
