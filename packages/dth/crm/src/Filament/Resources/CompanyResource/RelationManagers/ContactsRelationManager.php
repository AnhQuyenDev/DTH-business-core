<?php

namespace Dth\Crm\Filament\Resources\CompanyResource\RelationManagers;

use Dth\Crm\Support\CrmOptions;
use Dth\Crm\Support\UiText;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'contacts';

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return UiText::get('relations.company_contacts', 'Company contacts');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components(self::pivotFields());
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('display_name')
                    ->label(UiText::get('fields.display_name', 'Name')),
                TextColumn::make('email')
                    ->label(UiText::get('common.fields.email', 'Email')),
                TextColumn::make('phone')
                    ->label(UiText::get('fields.phone', 'Phone')),
                TextColumn::make('pivot.job_title')
                    ->label(UiText::get('fields.job_title', 'Job title')),
                TextColumn::make('pivot.decision_role')
                    ->label(UiText::get('fields.decision_role', 'Decision role'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => CrmOptions::label('decision_role', $state))
                    ->color('info'),
                TextColumn::make('pivot.is_primary')
                    ->label(UiText::get('fields.primary_contact', 'Primary'))
                    ->formatStateUsing(fn ($state): string => UiText::get(
                        $state ? 'common.yes' : 'common.no',
                        $state ? 'Yes' : 'No',
                        context: 'boolean',
                    ))
                    ->badge()
                    ->color(fn ($state): string => $state ? 'success' : 'gray'),
            ])
            ->headerActions([
                Actions\AttachAction::make()
                    ->label(UiText::get('actions.attach_contact', 'Attach contact'))
                    ->icon('heroicon-o-link')
                    ->preloadRecordSelect()
                    ->form(fn (Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
                        ...self::pivotFields(),
                    ]),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\EditAction::make()
                        ->label(UiText::get('common.actions.edit', 'Edit')),
                    Actions\DetachAction::make()
                        ->label(UiText::get('actions.detach_contact', 'Detach contact')),
                ]),
            ]);
    }

    /** @return array<int, mixed> */
    private static function pivotFields(): array
    {
        return [
            TextInput::make('job_title')
                ->label(UiText::get('fields.job_title', 'Job title')),
            TextInput::make('department')
                ->label(UiText::get('fields.department', 'Department')),
            Select::make('decision_role')
                ->label(UiText::get('fields.decision_role', 'Decision role'))
                ->options(CrmOptions::decisionRoles())
                ->native(false),
            Toggle::make('is_primary')
                ->label(UiText::get('fields.primary_contact', 'Primary contact')),
            Toggle::make('is_active')
                ->label(UiText::get('fields.active_contact', 'Active contact'))
                ->default(true),
        ];
    }
}
