<?php

namespace App\Filament\Resources\StaffResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ActivityLogRelationManager extends RelationManager
{
    protected static string $relationship = 'activityLogs';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('relation.title.activity_log');
    }

    public function getLabel(): string
    {
        return __('relation.title.activity_log');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('action')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('field.time'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('action')
                    ->label(__('field.action'))
                    ->formatStateUsing(fn ($state): string => __($this->mapActionToTransKey($state)))
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        str_contains($state, 'created') || str_contains($state, 'completed') => 'success',
                        str_contains($state, 'updated') || str_contains($state, 'assigned') => 'info',
                        str_contains($state, 'deleted') || str_contains($state, 'failed') => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('auditable_type')
                    ->label(__('field.object_type'))
                    ->formatStateUsing(fn ($state): string => class_basename($state ?? '')),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label(__('field.ip_address')),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([]);

    }

    private function mapActionToTransKey(string $action): string
    {
        $map = [
            'login' => 'activity.login',
            'customer.created' => 'activity.customer_created',
            'customer.updated' => 'activity.customer_updated',
            'customer.deleted' => 'activity.customer_deleted',
            'contact.created' => 'activity.contact_created',
            'contact.updated' => 'activity.contact_updated',
            'contact.deleted' => 'activity.contact_deleted',
            'contact.converted_to_customer' => 'activity.contact_converted',
            'campaign.created' => 'activity.campaign_created',
            'campaign.updated' => 'activity.campaign_updated',
            'campaign.deleted' => 'activity.campaign_deleted',
            'template.created' => 'activity.template_created',
            'template.updated' => 'activity.template_updated',
            'template.deleted' => 'activity.template_deleted',
            'sending_account.created' => 'activity.sending_account_created',
            'sending_account.updated' => 'activity.sending_account_updated',
            'sending_account.deleted' => 'activity.sending_account_deleted',
            'suppression.created' => 'activity.suppression_created',
            'distribution.completed' => 'activity.distribution_completed',
        ];

        return $map[$action] ?? 'activity.unknown';
    }
}
