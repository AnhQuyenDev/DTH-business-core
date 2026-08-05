<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyMatchCandidateResource\Pages;
use App\Models\Crm\CompanyMatchCandidate;
use App\Services\Crm\CompanyMatchReviewService;
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

    protected static ?string $navigationGroup = 'CRM';

    protected static ?string $navigationLabel =
        'Đối chiếu doanh nghiệp';

    protected static ?string $modelLabel =
        'Đối chiếu doanh nghiệp';

    public static function shouldRegisterNavigation(): bool
    {
        return config('business_flow.v2_enabled')
            && (auth()->user()?->isAdmin()
                || auth()->user()?->isCustomerServiceManager());
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
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('contact.full_name')
                    ->label('Người liên hệ'),
                TextColumn::make('suggestedCompany.legal_name')
                    ->label('Company đề xuất')
                    ->wrap(),
                TextColumn::make('confidence_score')
                    ->label('Độ tin cậy')
                    ->suffix('%'),
                TextColumn::make('matched_by')
                    ->label('Đối chiếu theo')
                    ->badge(),
                TextColumn::make('evidence.submitted_company_name')
                    ->label('Tên đã nhập')
                    ->wrap(),
                TextColumn::make('evidence.submitted_tax_code')
                    ->label('MST đã nhập'),
                TextColumn::make('created_at')
                    ->label('Tạo lúc')
                    ->dateTime(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Chờ duyệt',
                        'accepted' => 'Đã chấp nhận',
                        'rejected' => 'Đã từ chối',
                    ])
                    ->default('pending'),
            ])
            ->actions([
                Action::make('accept')
                    ->label('Chấp nhận ghép')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(
                        fn (CompanyMatchCandidate $record): bool => $record->status === 'pending'
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
                    ->label('Từ chối')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(
                        fn (CompanyMatchCandidate $record): bool => $record->status === 'pending'
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
