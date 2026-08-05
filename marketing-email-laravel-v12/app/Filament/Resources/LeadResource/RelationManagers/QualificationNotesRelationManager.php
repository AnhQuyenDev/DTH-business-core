<?php

namespace App\Filament\Resources\LeadResource\RelationManagers;

use App\Models\Crm\ContactQualificationNote;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class QualificationNotesRelationManager extends RelationManager
{
    protected static string $relationship = 'qualificationNotes';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('note_type')->label(__('field.note_type')),
                Tables\Columns\TextColumn::make('staff.full_name')->label(__('field.owner')),
                Tables\Columns\TextColumn::make('content')->label(__('field.note'))->limit(80),
                Tables\Columns\TextColumn::make('contacted_at')->label(__('field.contacted_at'))->dateTime('d/m/Y H:i'),
                Tables\Columns\TextColumn::make('next_follow_up_at')->label(__('field.next_follow_up'))->dateTime('d/m/Y H:i'),
            ])
            ->recordTitle(
                fn (ContactQualificationNote $record): string => $record->content
                    ?: __('field.note').' #'.$record->id
            );
    }
}
