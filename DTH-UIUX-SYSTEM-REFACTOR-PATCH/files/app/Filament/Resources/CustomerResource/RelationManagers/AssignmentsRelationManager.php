<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Enums\Crm\CustomerAssignmentStatus;
use App\Enums\Crm\CustomerAssignmentType;
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
                TextColumn::make('assignment_type')->label(__('field.assignment_type'))->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'label') ? $state->label() : ($state ?? ''))
                    ->color(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'color') ? $state->color() : (CustomerAssignmentType::tryFrom((string) $state)?->color() ?? 'gray')),
                TextColumn::make('status')->label(__('field.status'))->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'label') ? $state->label() : ($state ?? ''))
                    ->color(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'color') ? $state->color() : (CustomerAssignmentStatus::tryFrom((string) $state)?->color() ?? 'gray')),
                TextColumn::make('staff.full_name')->label(__('field.staff'))->searchable(),
                TextColumn::make('reason')->label(__('field.reason')),
                TextColumn::make('starts_at')->label(__('field.starts_at'))->dateTime('d/m/Y H:i'),
                TextColumn::make('ends_at')->label(__('field.ends_at'))->dateTime('d/m/Y H:i'),
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
