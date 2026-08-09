<?php

namespace App\Filament\Resources\CompanyResource\RelationManagers;

use App\Enums\Crm\CompanyContactDecisionRole;
use App\Models\Marketing\Contact;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\AttachAction;
use Filament\Tables\Actions\DetachAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'contacts';

    public static function getTitle(
        Model $ownerRecord,
        string $pageClass
    ): string {
        return __('relation.title.company_contacts');
    }

    public function table(Table $table): Table
    {
        $decisionRoleOptions = collect(
            CompanyContactDecisionRole::cases()
        )->mapWithKeys(
            fn (CompanyContactDecisionRole $role): array => [
                $role->value => $role->label(),
            ]
        )->all();

        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label(__('field.contact')),
                TextColumn::make('pivot.job_title')
                    ->label(__('field.job_title')),
                TextColumn::make('pivot.department')
                    ->label(__('field.department')),
                TextColumn::make('pivot.decision_role')
                    ->label(__('field.decision_role'))
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string => CompanyContactDecisionRole::tryFrom(
                            (string) $state
                        )?->label() ?? __('common.other')
                    ),
                IconColumn::make('pivot.is_primary')
                    ->label(__('field.is_primary'))
                    ->boolean(),
                IconColumn::make('pivot.is_active')
                    ->label(__('field.is_active'))
                    ->boolean(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label(__('action.add_contact_to_company'))
                    ->preloadRecordSelect()
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label(__('field.contact'))
                            ->getOptionLabelFromRecordUsing(
                                fn (Contact $record): string => $record->full_name
                                    ?: 'Contact #'.$record->id
                            )
                            ->searchable(),
                        TextInput::make('job_title')->label(__('field.job_title'))
                            ->label(__('field.job_title')),
                        TextInput::make('department')->label(__('field.department'))
                            ->label(__('field.department')),
                        Select::make('decision_role')->label(__('field.decision_role'))
                            ->label(__('field.decision_role'))
                            ->options($decisionRoleOptions)
                            ->default(
                                CompanyContactDecisionRole::Other->value
                            )
                            ->required(),
                        Toggle::make('is_primary')->label(__('field.is_primary'))
                            ->label(__('field.is_primary'))
                            ->default(false),
                        Toggle::make('is_active')->label(__('field.is_active'))
                            ->label(__('field.is_active'))
                            ->default(true),
                    ])
                    ->visible(
                        fn (): bool => auth()->user()?->isAdmin() ?? false
                    ),
            ])
            ->actions([
                Action::make('edit_membership')
                    ->label(__('action.edit'))
                    ->icon('heroicon-o-pencil-square')
                    ->fillForm(fn (Contact $record): array => [
                        'job_title' => $record->pivot?->job_title,
                        'department' => $record->pivot?->department,
                        'decision_role' => $record->pivot?->decision_role,
                        'is_primary' => (bool) $record->pivot?->is_primary,
                        'is_active' => (bool) $record->pivot?->is_active,
                    ])
                    ->form([
                        TextInput::make('job_title')->label(__('field.job_title')),
                        TextInput::make('department')->label(__('field.department')),
                        Select::make('decision_role')->label(__('field.decision_role'))
                            ->options($decisionRoleOptions)
                            ->required(),
                        Toggle::make('is_primary')->label(__('field.is_primary')),
                        Toggle::make('is_active')->label(__('field.is_active')),
                    ])
                    ->action(function (
                        Contact $record,
                        array $data
                    ): void {
                        if (($data['is_primary'] ?? false) === true) {
                            $this->getOwnerRecord()
                                ->contacts()
                                ->newPivotStatement()
                                ->where('company_id', $this->getOwnerRecord()->id)
                                ->update(['is_primary' => false]);
                        }

                        $this->getOwnerRecord()
                            ->contacts()
                            ->updateExistingPivot($record->id, $data);
                    })
                    ->visible(
                        fn (): bool => auth()->user()?->isAdmin() ?? false
                    ),
                DetachAction::make()
                    ->visible(
                        fn (): bool => auth()->user()?->isAdmin() ?? false
                    ),
            ]);
    }
}
