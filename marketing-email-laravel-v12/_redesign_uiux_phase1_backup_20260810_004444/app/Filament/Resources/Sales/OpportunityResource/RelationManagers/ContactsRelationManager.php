<?php

namespace App\Filament\Resources\Sales\OpportunityResource\RelationManagers;

use App\Models\Marketing\Contact;
use App\Models\Sales\Opportunity;
use App\Services\Sales\OpportunityContactService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\Action;
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
                Action::make('add_contact')
                    ->label(__('action.add_contact'))
                    ->icon('heroicon-o-user-plus')
                    ->form([
                        Select::make('contact_id')
                            ->label(__('field.contact'))
                            ->options(
                                Contact::query()
                                    ->orderBy('id')
                                    ->get()
                                    ->mapWithKeys(
                                        fn (Contact $contact): array => [
                                            $contact->id => $contact->full_name
                                                ?: 'Contact #'.$contact->id,
                                        ]
                                    )
                            )
                            ->searchable()
                            ->required(),

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
                    ->action(function (array $data): void {
                        /** @var Opportunity $opportunity */
                        $opportunity = $this->getOwnerRecord();

                        app(OpportunityContactService::class)->upsert(
                            opportunity: $opportunity,
                            contactId: (int) $data['contact_id'],
                            role: (string) ($data['role'] ?? 'other'),
                            isPrimary: (bool) (
                                $data['is_primary'] ?? false
                            ),
                            actorUserId: auth()->id(),
                        );
                    })
                    ->visible(
                        fn (): bool => auth()->user()?->isAdmin()
                            || auth()->user()?->isCustomerServiceManager()
                    ),
            ])
            ->actions([
                Action::make('edit_membership')
                    ->label(__('action.edit'))
                    ->icon('heroicon-o-pencil-square')
                    ->fillForm(
                        fn (Model $record): array => [
                            'role' => $record->pivot?->role,
                            'is_primary' => (bool) (
                                $record->pivot?->is_primary
                            ),
                        ]
                    )
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

                        Toggle::make('is_primary')
                            ->label(__('field.is_primary')),
                    ])
                    ->action(
                        function (
                            Model $record,
                            array $data
                        ): void {
                            /** @var Opportunity $opportunity */
                            $opportunity = $this->getOwnerRecord();

                            app(
                                OpportunityContactService::class
                            )->upsert(
                                opportunity: $opportunity,
                                contactId: (int) $record->id,
                                role: (string) (
                                    $data['role'] ?? 'other'
                                ),
                                isPrimary: (bool) (
                                    $data['is_primary'] ?? false
                                ),
                                actorUserId: auth()->id(),
                            );
                        }
                    )
                    ->visible(
                        fn (): bool => auth()->user()?->isAdmin()
                            || auth()->user()?->isCustomerServiceManager()
                    ),

                Action::make('remove_contact')
                    ->label(__('action.delete'))
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Model $record): void {
                        /** @var Opportunity $opportunity */
                        $opportunity = $this->getOwnerRecord();

                        app(
                            OpportunityContactService::class
                        )->detach(
                            opportunity: $opportunity,
                            contactId: (int) $record->id,
                            actorUserId: auth()->id(),
                        );
                    })
                    ->visible(
                        fn (): bool => auth()->user()?->isAdmin()
                            || auth()->user()?->isCustomerServiceManager()
                    ),
            ]);
    }
}
