<?php

namespace App\Filament\Resources\Sales\QuotationResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('relation.title.quotation_documents');
    }

    public function getLabel(): string
    {
        return __('relation.title.quotation_documents');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('document_type')->label(__('field.type')),
                TextColumn::make('file_name')->label(__('field.file_name')),
                TextColumn::make('file_size')->label(__('field.file_size'))->formatStateUsing(fn ($state) => $state ? round($state / 1024, 1) . ' KB' : ''),
                TextColumn::make('generated_at')->label(__('field.generated_at'))->dateTime(),
            ])
            ->actions([])
            ->headerActions([]);
    }
}
