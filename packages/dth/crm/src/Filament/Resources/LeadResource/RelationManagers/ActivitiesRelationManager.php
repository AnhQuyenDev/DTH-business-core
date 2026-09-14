<?php

namespace Dth\Crm\Filament\Resources\LeadResource\RelationManagers;

use Dth\Crm\Enums\LeadActivityType;
use Dth\Crm\Models\Staff;
use Dth\Crm\Support\CrmOptions;
use Dth\Crm\Support\UiText;
use Filament\Actions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return UiText::get('relations.lead_activities', 'Lead activity history');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('type')
                ->label(UiText::get('fields.activity_type', 'Activity type'))
                ->options(LeadActivityType::options())
                ->required()
                ->native(false),
            TextInput::make('subject')
                ->label(UiText::get('fields.subject', 'Subject')),
            Textarea::make('content')
                ->label(UiText::get('fields.content', 'Content'))
                ->rows(3),
            TextInput::make('outcome')
                ->label(UiText::get('fields.outcome', 'Outcome')),
            DateTimePicker::make('activity_at')
                ->label(UiText::get('fields.activity_at', 'Activity time'))
                ->default(now())
                ->required(),
            DateTimePicker::make('next_follow_up_at')
                ->label(UiText::get('fields.next_follow_up', 'Next follow up')),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label(UiText::get('fields.activity_type', 'Activity type'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => CrmOptions::label('activity_type', $state))
                    ->color('info'),
                TextColumn::make('subject')
                    ->label(UiText::get('fields.subject', 'Subject')),
                TextColumn::make('outcome')
                    ->label(UiText::get('fields.outcome', 'Outcome')),
                TextColumn::make('activity_at')
                    ->label(UiText::get('fields.activity_at', 'Activity time'))
                    ->dateTime('d/m/Y H:i'),
                TextColumn::make('next_follow_up_at')
                    ->label(UiText::get('fields.next_follow_up', 'Next follow up'))
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
            ])
            ->headerActions([
                Actions\CreateAction::make()
                    ->label(UiText::get('actions.add_activity', 'Add activity'))
                    ->icon('heroicon-o-plus')
                    ->mutateDataUsing(fn (array $data): array => $data + [
                        'staff_id' => Staff::query()->where('user_id', auth()->id())->value('id'),
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
