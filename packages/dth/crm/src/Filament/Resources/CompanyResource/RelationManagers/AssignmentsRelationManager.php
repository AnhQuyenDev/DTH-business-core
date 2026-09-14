<?php

namespace Dth\Crm\Filament\Resources\CompanyResource\RelationManagers;

use Dth\Crm\Models\Staff;
use Dth\Crm\Support\CrmOptions;
use Dth\Crm\Support\StatusColor;
use Dth\Crm\Support\UiText;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'assignments';

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return UiText::get('relations.company_assignments', 'Company assignments');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('staff_id')
                ->label(UiText::get('fields.staff_name', 'Staff member'))
                ->options(fn (): array => Staff::query()
                    ->where('employment_status', 'active')
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all())
                ->required()
                ->searchable()
                ->preload(),
            Select::make('assignment_type')
                ->label(UiText::get('fields.assignment_type', 'Assignment type'))
                ->options(CrmOptions::assignmentTypes())
                ->default('owner')
                ->native(false),
            Select::make('status')
                ->label(UiText::get('common.fields.status', 'Status'))
                ->options(CrmOptions::assignmentStatuses())
                ->default('active')
                ->native(false),
            Textarea::make('reason')
                ->label(UiText::get('common.fields.reason', 'Reason')),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('staff.name')
                    ->label(UiText::get('fields.staff_name', 'Staff member')),
                TextColumn::make('assignment_type')
                    ->label(UiText::get('fields.assignment_type', 'Assignment type'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => CrmOptions::label('assignment_type', $state))
                    ->color('info'),
                TextColumn::make('status')
                    ->label(UiText::get('common.fields.status', 'Status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => CrmOptions::label('assignment_status', $state))
                    ->color(fn ($state): string => StatusColor::for($state, 'assignment_status')),
                TextColumn::make('starts_at')
                    ->label(UiText::get('fields.started_at', 'Started at'))
                    ->dateTime('d/m/Y H:i'),
            ])
            ->headerActions([
                Actions\CreateAction::make()
                    ->label(UiText::get('actions.add_assignment', 'Add assignment'))
                    ->icon('heroicon-o-user-plus')
                    ->mutateDataUsing(fn (array $data): array => $data + [
                        'assigned_by_user_id' => auth()->id(),
                        'starts_at' => now(),
                    ]),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\EditAction::make()
                        ->label(UiText::get('common.actions.edit', 'Edit')),
                    Actions\DeleteAction::make()
                        ->label(UiText::get('common.actions.delete', 'Delete')),
                ]),
            ]);
    }
}
