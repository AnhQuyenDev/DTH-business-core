<?php

namespace Dth\Crm\Filament\Resources;

use Dth\Crm\Filament\Navigation\CrmNavigationGroup;
use Dth\Crm\Filament\Resources\CompanyMatchCandidateResource\Pages;
use Dth\Crm\Models\CompanyMatchCandidate;
use Dth\Crm\Services\CompanyMatchReviewService;
use Dth\Crm\Support\CrmOptions;
use Dth\Crm\Support\StatusColor;
use Dth\Crm\Support\UiText;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;

class CompanyMatchCandidateResource extends Resource
{
    protected static ?string $model = CompanyMatchCandidate::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-link';
    protected static string|\UnitEnum|null $navigationGroup = CrmNavigationGroup::Crm;
    protected static ?int $navigationSort = 90;

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.match_candidates', 'Company matching', context: 'navigation');
    }

    public static function getModelLabel(): string
    {
        return UiText::get('models.match_candidate', 'Company match candidate', context: 'model');
    }

    public static function getPluralModelLabel(): string
    {
        return UiText::get('models.match_candidates', 'Company match candidates', context: 'model');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('submission_reference')
                    ->label(UiText::get('fields.submission_reference', 'Submission reference'))
                    ->searchable(),
                TextColumn::make('contact.display_name')
                    ->label(UiText::get('models.contact', 'Contact')),
                TextColumn::make('suggestedCompany.legal_name')
                    ->label(UiText::get('fields.suggested_company', 'Suggested company')),
                TextColumn::make('matched_by')
                    ->label(UiText::get('fields.matched_by', 'Matched by'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => CrmOptions::label('match_method', $state))
                    ->color('info'),
                TextColumn::make('confidence')
                    ->label(UiText::get('fields.confidence', 'Confidence'))
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('status')
                    ->label(UiText::get('common.fields.status', 'Status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => CrmOptions::label('match_status', $state))
                    ->color(fn ($state): string => StatusColor::for($state, 'match_status')),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(UiText::get('common.fields.status', 'Status'))
                    ->options(CrmOptions::matchStatuses()),
            ], layout: FiltersLayout::AboveContent)
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\Action::make('accept')
                        ->label(UiText::get('actions.accept_match', 'Accept match'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (CompanyMatchCandidate $record): bool => $record->status === 'pending')
                        ->action(function (CompanyMatchCandidate $record): void {
                            app(CompanyMatchReviewService::class)->accept($record, auth()->id());

                            Notification::make()
                                ->success()
                                ->title(UiText::get('notifications.company_match_accepted', 'Company match accepted'))
                                ->send();
                        }),
                    Actions\Action::make('reject')
                        ->label(UiText::get('actions.reject_match', 'Reject match'))
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalSubmitAction(fn ($action) => $action->icon('heroicon-o-x-circle'))
                        ->modalCancelAction(fn ($action) => $action->icon('heroicon-o-x-mark')->color('gray'))
                        ->visible(fn (CompanyMatchCandidate $record): bool => $record->status === 'pending')
                        ->action(function (CompanyMatchCandidate $record): void {
                            app(CompanyMatchReviewService::class)->reject($record, auth()->id());

                            Notification::make()
                                ->success()
                                ->title(UiText::get('notifications.company_match_rejected', 'Company match rejected'))
                                ->send();
                        }),
                    Actions\ViewAction::make()->icon('heroicon-o-eye')
                        ->label(UiText::get('common.actions.view', 'View')),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanyMatchCandidates::route('/'),
            'view' => Pages\ViewCompanyMatchCandidate::route('/{record}'),
        ];
    }
}
