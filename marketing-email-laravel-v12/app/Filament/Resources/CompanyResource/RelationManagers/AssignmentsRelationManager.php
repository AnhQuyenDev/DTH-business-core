<?php

namespace App\Filament\Resources\CompanyResource\RelationManagers;

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

class AssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'assignments';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('relation.title.assignments');
    }

    public function getLabel(): string
    {
        return __('relation.title.assignments');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('assignment_type')->label(__('field.assignment_type'))->badge(),
                TextColumn::make('status')->label(__('field.status'))->badge(),
                TextColumn::make('staff.full_name')->label(__('field.staff'))->searchable(),
                TextColumn::make('reason')->label(__('field.reason')),
                TextColumn::make('starts_at')->label(__('field.starts_at'))->dateTime(),
                TextColumn::make('ends_at')->label(__('field.ends_at'))->dateTime(),
            ])
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
