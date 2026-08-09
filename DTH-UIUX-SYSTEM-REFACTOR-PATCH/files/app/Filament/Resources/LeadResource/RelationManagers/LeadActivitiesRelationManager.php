<?php

namespace App\Filament\Resources\LeadResource\RelationManagers;

use App\Enums\Crm\LeadActivityStatus;
use App\Enums\Crm\LeadActivityType;
use App\Support\Ui\BadgePalette;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class LeadActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    public static function getTitle(
        Model $ownerRecord,
        string $pageClass,
    ): string {
        return __('relation.lead_activities');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('subject')
            ->defaultSort('activity_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('activity_type')
                    ->label(__('field.activity_type'))
                    ->badge()
                    ->formatStateUsing(
                        fn (LeadActivityType $state): string => $state->label()
                    ),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('field.activity_status'))
                    ->badge()
                    ->formatStateUsing(
                        fn (LeadActivityStatus $state): string => $state->label()
                    )
                    ->color(fn (LeadActivityStatus $state): string => BadgePalette::status($state)),
                Tables\Columns\TextColumn::make('subject')
                    ->label(__('field.subject'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('staff.full_name')
                    ->label(__('field.staff'))
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('outcome')
                    ->label(__('field.outcome'))
                    ->limit(50)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('activity_at')
                    ->label(__('field.activity_at'))
                    ->formatStateUsing(
                        fn ($state): string => $state
                            ? $state
                                ->copy()
                                ->timezone(config('business_flow.timezone'))
                                ->format('d/m/Y H:i')
                            : '—'
                    )
                    ->sortable(),
                Tables\Columns\TextColumn::make('next_follow_up_at')
                    ->label(__('field.next_follow_up'))
                    ->formatStateUsing(
                        fn ($state): string => $state
                            ? $state
                                ->copy()
                                ->timezone(config('business_flow.timezone'))
                                ->format('d/m/Y H:i')
                            : '—'
                    )
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->form([
                        Textarea::make('content')
                            ->label(__('field.content'))
                            ->disabled(),
                        Textarea::make('outcome')
                            ->label(__('field.outcome'))
                            ->disabled(),
                    ]),
            ]);
    }
}
