<?php

namespace App\Filament\Widgets;

use App\Models\Crm\Staff;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class DashboardStaffDetailTableWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '120s';

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('dashboard.staff_detail_heading'))
            ->query(
                Staff::query()
                    ->withCount([
                        'assignments as managing_count' => fn ($q) => $q->where('assignment_type', 'owner')->where('status', 'active'),
                        'assignments as supporting_count' => fn ($q) => $q->where('assignment_type', 'support')->where('status', 'active'),
                    ])
                    ->orderBy('managing_count', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label(__('field.staff'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('field.email'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('employment_status')
                    ->label(__('field.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label()),
                Tables\Columns\TextColumn::make('managing_count')
                    ->label(__('dashboard.staff_managing_customers'))
                    ->numeric()
                    ->sortable()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('supporting_count')
                    ->label(__('dashboard.staff_supporting_customers'))
                    ->numeric()
                    ->sortable()
                    ->color('info'),
            ])
            ->emptyStateHeading(__('dashboard.no_staff_found'))
            ->defaultSort('managing_count', 'desc');
    }
}
