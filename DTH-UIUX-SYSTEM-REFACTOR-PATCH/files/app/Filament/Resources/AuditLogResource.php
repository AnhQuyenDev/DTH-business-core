<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\Marketing\AuditLog;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.system');
    }

    public static function getModelLabel(): string
    {
        return __('resource.audit_log.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.audit_log.plural');
    }

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 10;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('marketing.view-audit') ?? false;
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('created_at')->label(__('field.created_at'))->dateTime('d/m/Y H:i')->sortable(),
            TextColumn::make('user.name')->label(__('field.user'))->toggleable(),
            TextColumn::make('action')->label(__('field.action'))->searchable()->sortable(),
            TextColumn::make('auditable_type')->label(__('field.auditable_type'))->toggleable(),
            TextColumn::make('auditable_id')->label(__('field.auditable_id'))->toggleable(),
            TextColumn::make('ip_address')->label(__('field.ip_address'))->toggleable(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
        ];
    }
}
