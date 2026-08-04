<?php

namespace App\Filament\Resources\Sales\QuotationResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('relation.title.quotation_items');
    }

    public function getLabel(): string
    {
        return __('relation.title.quotation_items');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('service_name_snapshot')->label(__('field.service_name')),
                TextColumn::make('package_name_snapshot')->label(__('field.package_name')),
                TextColumn::make('unit')->label(__('field.unit')),
                TextColumn::make('quantity')->label(__('field.quantity')),
                TextColumn::make('unit_price')->label(__('field.unit_price'))->money('VND'),
                TextColumn::make('discount_amount')->label(__('field.discount_total'))->money('VND'),
                TextColumn::make('vat_amount')->label(__('field.tax_total'))->money('VND'),
                TextColumn::make('line_total')->label(__('field.line_total'))->money('VND'),
            ])
            ->defaultSort('sort_order')
            ->actions([ActionGroup::make([
                EditAction::make(),
                DeleteAction::make(),
            ])->icon('heroicon-o-ellipsis-vertical')->iconButton()])
            ->headerActions([
                CreateAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
