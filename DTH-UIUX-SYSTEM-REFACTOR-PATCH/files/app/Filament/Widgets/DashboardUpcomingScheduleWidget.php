<?php

namespace App\Filament\Widgets;

use App\Models\Crm\CustomerInteraction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class DashboardUpcomingScheduleWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                CustomerInteraction::query()
                    ->where('status', 'scheduled')
                    ->whereNotNull('next_follow_up_at')
                    ->where('next_follow_up_at', '>=', now()->subDay())
                    ->orderBy('next_follow_up_at')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('customer.display_name')
                    ->label(__('field.customer'))
                    ->searchable()
                    ->placeholder(__('common.not_available')),
                Tables\Columns\TextColumn::make('staff.full_name')
                    ->label(__('field.staff'))
                    ->placeholder(__('common.not_available')),
                Tables\Columns\TextColumn::make('interaction_type')
                    ->label(__('field.type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => __("enum.interaction_type.{$state}")),
                Tables\Columns\TextColumn::make('subject')
                    ->label(__('field.subject'))
                    ->limit(40),
                Tables\Columns\TextColumn::make('next_follow_up_at')
                    ->label(__('field.follow_up'))
                    ->dateTime('d/m/Y H:i')
                    ->color(fn ($record) => $record->next_follow_up_at?->isPast() ? 'danger' : 'success'),
            ])
            ->emptyStateHeading(__('dashboard.no_schedule_found'))
            ->emptyStateDescription(__('dashboard.all_schedule_processed'));
    }
}
