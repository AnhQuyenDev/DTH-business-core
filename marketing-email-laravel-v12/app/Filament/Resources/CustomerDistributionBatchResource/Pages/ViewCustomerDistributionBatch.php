<?php

namespace App\Filament\Resources\CustomerDistributionBatchResource\Pages;

use App\Filament\Resources\CustomerDistributionBatchResource;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomerDistributionBatch extends ViewRecord
{
    protected static string $resource = CustomerDistributionBatchResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make(__('section.batch_details'))->schema([
                Grid::make(3)->schema([
                    TextEntry::make('batch_code')->label(__('field.batch_code')),
                    TextEntry::make('type')->label(__('field.type'))->badge()
                        ->formatStateUsing(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'label') ? $state->label() : ($state ?? ''))
                        ->color(fn ($state): string => match ($state instanceof \BackedEnum ? $state->value : $state) {
                            'initial', 'new_customer', 'staff_return' => 'info',
                            'staff_absence', 'rebalance', 'manual' => 'warning',
                            default => 'gray',
                        }),
                    TextEntry::make('status')->label(__('field.status'))->badge()
                        ->formatStateUsing(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'label') ? $state->label() : ($state ?? ''))
                        ->color(fn ($state): string => match ($state instanceof \BackedEnum ? $state->value : $state) {
                            'draft' => 'gray',
                            'processing' => 'info',
                            'completed' => 'success',
                            'failed', 'reverted' => 'danger',
                            default => 'gray',
                        }),
                    TextEntry::make('strategy')->label(__('field.strategy'))->badge()
                        ->formatStateUsing(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'label') ? $state->label() : ($state ?? ''))
                        ->color(fn ($state): string => match ($state instanceof \BackedEnum ? $state->value : $state) {
                            'round_robin', 'least_loaded', 'weighted' => 'info',
                            'manual' => 'warning',
                            default => 'gray',
                        }),
                    TextEntry::make('sourceStaff.full_name')->label(__('field.source_staff')),
                    TextEntry::make('total_customers')->label(__('field.total_customers')),
                    TextEntry::make('total_assigned')->label(__('field.total_assigned')),
                    TextEntry::make('initiatedBy.name')->label(__('field.initiated_by')),
                    TextEntry::make('note')->label(__('field.note'))->columnSpanFull(),
                    TextEntry::make('effective_from')->label(__('field.effective_from'))->dateTime(),
                    TextEntry::make('effective_until')->label(__('field.effective_until'))->dateTime(),
                    TextEntry::make('completed_at')->label(__('field.completed_at'))->dateTime(),
                    TextEntry::make('created_at')->label(__('field.created_at'))->dateTime(),
                ]),
            ]),
        ]);
    }
}
