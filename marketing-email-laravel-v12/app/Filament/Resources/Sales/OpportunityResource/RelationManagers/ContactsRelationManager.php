<?php

namespace App\Filament\Resources\Sales\OpportunityResource\RelationManagers;

use Filament\Forms\Components\Select;
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
        return __('relation.title.opportunity_contacts');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('full_name')
            ->columns([
                TextColumn::make('full_name')
                    ->label(__('field.contact')),
                TextColumn::make('pivot.role')
                    ->label(__('field.role'))
                    ->badge(),
                IconColumn::make('pivot.is_primary')
                    ->label(__('field.is_primary'))
                    ->boolean(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label(__('action.add_contact'))
                    ->preloadRecordSelect()
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label(__('field.contact'))
                            ->getOptionLabelFromRecordUsing(
                                fn (Model $record): string => $record->full_name
                                    ?: 'Contact #'.$record->id
                            )
                            ->searchable(),
                        Select::make('role')
                            ->label(__('field.role'))
                            ->options([
                                'primary_contact' => __('field.role.primary_contact'),
                                'decision_maker' => __('field.role.decision_maker'),
                                'influencer' => __('field.role.influencer'),
                                'technical_contact' => __('field.role.technical_contact'),
                                'other' => __('field.role.other'),
                            ])
                            ->default('other')
                            ->required(),
                        Toggle::make('is_primary')
                            ->label(__('field.is_primary'))
                            ->default(false),
                    ])
                    ->visible(
                        fn (): bool => auth()->user()?->isAdmin()
                            || auth()->user()?->isCustomerServiceManager()
                    ),
            ])
            ->actions([
                Action::make('edit_membership')
                    ->label(__('action.edit'))
                    ->icon('heroicon-o-pencil-square')
                    ->fillForm(fn (Model $record): array => [
                        'role' => $record->pivot?->role,
                        'is_primary' => (bool) $record->pivot?->is_primary,
                    ])
                    ->form([
                        Select::make('role')
                            ->label(__('field.role'))
                            ->options([
                                'primary_contact' => __('field.role.primary_contact'),
                                'decision_maker' => __('field.role.decision_maker'),
                                'influencer' => __('field.role.influencer'),
                                'technical_contact' => __('field.role.technical_contact'),
                                'other' => __('field.role.other'),
                            ])
                            ->required(),
                        Toggle::make('is_primary'),
                    ])
                    ->action(function (Model $record, array $data): void {
                        if (($data['is_primary'] ?? false) === true) {
                            $this->getOwnerRecord()
                                ->contacts()
                                ->newPivotStatement()
                                ->where('opportunity_id', $this->getOwnerRecord()->id)
                                ->where('contact_id', '!=', $record->id)
                                ->update(['is_primary' => false]);
                        }

                        $this->getOwnerRecord()
                            ->contacts()
                            ->updateExistingPivot($record->id, $data);
                    })
                    ->visible(
                        fn (): bool => auth()->user()?->isAdmin()
                            || auth()->user()?->isCustomerServiceManager()
                    ),
                DetachAction::make()
                    ->visible(
                        fn (): bool => auth()->user()?->isAdmin()
                            || auth()->user()?->isCustomerServiceManager()
                    ),
            ]);
    }
}
