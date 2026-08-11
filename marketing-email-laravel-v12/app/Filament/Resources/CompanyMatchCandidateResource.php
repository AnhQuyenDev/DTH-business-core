<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyMatchCandidateResource\Pages;
use App\Models\Crm\CompanyMatchCandidate;
use App\Services\Crm\CompanyMatchReviewService;
use App\Support\Ui\BadgePalette;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CompanyMatchCandidateResource extends Resource
{
    protected static ?string $model = CompanyMatchCandidate::class;

    protected static ?string $navigationIcon =
        'heroicon-o-document-magnifying-glass';

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.crm');
    }

    public static function getNavigationLabel(): string
    {
        return __('resource.company_match_candidate.singular');
    }

    public static function getModelLabel(): string
    {
        return __('resource.company_match_candidate.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.company_match_candidate.plural');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return config('business_flow.v2_enabled')
            && (auth()->user()?->isAdmin()
                || auth()->user()?->isSalesManager());
    }

    public static function canViewAny(): bool
    {
        return static::shouldRegisterNavigation();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('status')
                    ->label(__('field.status'))
                    ->badge()
                    ->color(fn (string $state): string => BadgePalette::status($state)),
                TextColumn::make('contact.full_name')
                    ->label(__('field.contact_person')),
                TextColumn::make('suggestedCompany.legal_name')
                    ->label(__('field.match_company'))
                    ->wrap(),
                TextColumn::make('confidence_score')
                    ->label(__('field.confidence'))
                    ->suffix('%'),
                TextColumn::make('matched_by')
                    ->label(__('field.match_basis'))
                    ->badge(),
                TextColumn::make('evidence.submitted_company_name')
                    ->label(__('field.input_name'))
                    ->wrap(),
                TextColumn::make('evidence.submitted_tax_code')
                    ->label(__('field.input_tax_code')),
                TextColumn::make('created_at')
                    ->label(__('field.created_at'))
                    ->dateTime('d/m/Y H:i'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => __('dashboard.sales.pending_approval'),
                        'accepted' => __('enum.sales.quotation_status.accepted'),
                        'rejected' => __('enum.sales.quotation_status.rejected'),
                    ])
                    ->default('pending'),
            ])
            ->actions([
                Action::make('accept')
                    ->label(__('action.accept_match'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(
                        fn (CompanyMatchCandidate $record): bool => $record->status === 'pending'
                            && (auth()->user()?->isSalesManager() ?? false)
                    )
                    ->action(function (
                        CompanyMatchCandidate $record
                    ): void {
                        app(CompanyMatchReviewService::class)->accept(
                            $record,
                            auth()->id()
                        );
                    }),
                Action::make('reject')
                    ->label(__('action.reject_match'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(
                        fn (CompanyMatchCandidate $record): bool => $record->status === 'pending'
                            && (auth()->user()?->isSalesManager() ?? false)
                    )
                    ->action(function (
                        CompanyMatchCandidate $record
                    ): void {
                        app(CompanyMatchReviewService::class)->reject(
                            $record,
                            auth()->id()
                        );
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanyMatchCandidates::route('/'),
        ];
    }
}
