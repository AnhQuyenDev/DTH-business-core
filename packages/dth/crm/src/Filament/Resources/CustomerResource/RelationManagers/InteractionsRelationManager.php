<?php

namespace Dth\Crm\Filament\Resources\CustomerResource\RelationManagers;

use Dth\Crm\Models\CrmAgentProfile;
use Dth\Crm\Support\CrmOptions;
use Dth\Crm\Support\UiText;
use Filament\Actions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InteractionsRelationManager extends RelationManager
{
    protected static string $relationship = 'interactions';

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return UiText::get('relations.customer_interactions', 'Customer care history');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('interaction_type')
                ->label(UiText::get('fields.interaction_type', 'Interaction type'))
                ->options(CrmOptions::interactionTypes())
                ->required()
                ->native(false),
            TextInput::make('subject')
                ->label(UiText::get('fields.subject', 'Subject')),
            Textarea::make('content')
                ->label(UiText::get('fields.content', 'Content'))
                ->required()
                ->rows(4),
            TextInput::make('outcome')
                ->label(UiText::get('fields.outcome', 'Outcome')),
            DateTimePicker::make('interaction_at')
                ->label(UiText::get('fields.interaction_at', 'Interaction time'))
                ->default(now())
                ->required(),
            DateTimePicker::make('next_follow_up_at')
                ->label(UiText::get('fields.next_follow_up', 'Next follow up')),
            Toggle::make('is_support_action')
                ->label(UiText::get('fields.support_action', 'Customer care action')),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('interaction_type')
                    ->label(UiText::get('fields.interaction_type', 'Interaction type'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => CrmOptions::label('interaction_type', $state))
                    ->color('info'),
                TextColumn::make('subject')
                    ->label(UiText::get('fields.subject', 'Subject')),
                TextColumn::make('outcome')
                    ->label(UiText::get('fields.outcome', 'Outcome')),
                TextColumn::make('interaction_at')
                    ->label(UiText::get('fields.interaction_at', 'Interaction time'))
                    ->dateTime('d/m/Y H:i'),
                TextColumn::make('next_follow_up_at')
                    ->label(UiText::get('fields.next_follow_up', 'Next follow up'))
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
            ])
            ->headerActions([
                Actions\CreateAction::make()
                    ->label(UiText::get('actions.add_interaction', 'Add interaction'))
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->mutateDataUsing(fn (array $data): array => $data + [
                        'agent_profile_id' => CrmAgentProfile::query()->forUser(auth()->id())->value('id'),
                    ]),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\EditAction::make()
                        ->label(UiText::get('common.actions.edit', 'Edit')),
                    Actions\DeleteAction::make()
                        ->label(UiText::get('common.actions.delete', 'Delete')),
                ])->icon('heroicon-o-ellipsis-vertical')->iconButton(),
            ]);
    }
}
