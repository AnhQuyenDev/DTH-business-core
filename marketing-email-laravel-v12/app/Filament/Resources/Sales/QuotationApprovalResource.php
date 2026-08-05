<?php

namespace App\Filament\Resources\Sales;

use App\Enums\Sales\ApprovalStatus;
use App\Filament\Resources\Sales\QuotationApprovalResource\Pages;
use App\Models\Sales\QuotationApproval;
use App\Services\Sales\QuotationApprovalService;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class QuotationApprovalResource extends Resource
{
    protected static ?string $model = QuotationApproval::class;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.sales');
    }

    public static function getModelLabel(): string
    {
        return __('resource.quotation_approval.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.quotation_approval.plural');
    }

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() || auth()->user()?->isCustomerServiceManager();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quotation.quotation_code')->label(__('field.quotation_code'))->searchable(),
                TextColumn::make('quotation.title')->label(__('field.title'))->limit(40),
                TextColumn::make('step')->label(__('field.step')),
                TextColumn::make('status')->label(__('field.status'))->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'label') ? $state->label() : ($state ?? ''))
                    ->color(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'color') ? $state->color() : 'gray'),
                TextColumn::make('reason')->label(__('field.reason'))->limit(50),
                TextColumn::make('requested_at')->label(__('field.requested_at'))->dateTime(),
                TextColumn::make('reviewed_at')->label(__('field.reviewed_at'))->dateTime(),
            ])
            ->actions([ActionGroup::make([
                Action::make('approve')
                    ->label(__('action.approve'))
                    ->action(fn (QuotationApproval $a) => app(QuotationApprovalService::class)->approve(
                        $a->quotation, auth()->user()
                    ))
                    ->visible(fn (QuotationApproval $a): bool => $a->status === ApprovalStatus::Pending),
                Action::make('reject')
                    ->label(__('action.reject'))
                    ->color('danger')
                    ->action(fn (QuotationApproval $a) => app(QuotationApprovalService::class)->reject(
                        $a->quotation, auth()->user(), __('note.reject_from_approval_list')
                    ))
                    ->visible(fn (QuotationApproval $a): bool => $a->status === ApprovalStatus::Pending),
            ])->icon('heroicon-o-ellipsis-vertical')->iconButton(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('field.status'))
                    ->options(ApprovalStatus::options()),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuotationApprovals::route('/'),
        ];
    }
}
