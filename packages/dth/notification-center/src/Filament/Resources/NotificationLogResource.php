<?php

namespace Dth\NotificationCenter\Filament\Resources;

use Dth\NotificationCenter\Filament\Navigation\NotificationNavigationGroup;

use Dth\NotificationCenter\Filament\Resources\NotificationLogResource\Pages;
use Dth\NotificationCenter\Models\Notification;
use Dth\NotificationCenter\Services\NotificationAuthorization;
use Dth\NotificationCenter\Support\NotificationPresenter;
use Dth\NotificationCenter\Support\UiText;
use Filament\Actions;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class NotificationLogResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $model = Notification::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';
    protected static string|\UnitEnum|null $navigationGroup = NotificationNavigationGroup::Notifications;
    protected static ?int $navigationSort = 30;
    protected static ?string $slug = 'notification-delivery-log';

    public static function getNavigationLabel(): string { return app()->getLocale() === 'en' ? 'Delivery log' : 'Nhật ký gửi'; }
    public static function getModelLabel(): string { return app()->getLocale() === 'en' ? 'Notification' : 'Thông báo'; }
    public static function getPluralModelLabel(): string { return static::getNavigationLabel(); }
    public static function form(Schema $schema): Schema { return $schema; }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sent_at')->label(UiText::get('fields.sent_at', 'Thời gian'))->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('title')->label(UiText::get('fields.title', 'Tiêu đề'))->searchable()->wrap()->limit(70),
                TextColumn::make('type')->label(UiText::get('fields.type', 'Loại'))->badge()->formatStateUsing(fn ($state): string => NotificationPresenter::typeLabel((string) $state)),
                TextColumn::make('source_module')->label(UiText::get('fields.module', 'Phân hệ'))->formatStateUsing(fn ($state): string => NotificationPresenter::moduleLabel((string) $state)),
                TextColumn::make('sender_name')->label(UiText::get('fields.sender', 'Người gửi'))->placeholder(app()->getLocale() === 'en' ? 'System' : 'Hệ thống'),
                TextColumn::make('recipients_count')->counts('recipients')->label(UiText::get('fields.recipients', 'Người nhận')),
                TextColumn::make('email_sent_count')->label(app()->getLocale() === 'en' ? 'Email sent' : 'Email đã gửi')->state(fn (Notification $record): int => $record->recipients()->where('email_status', 'sent')->count()),
                TextColumn::make('email_failed_count')->label(app()->getLocale() === 'en' ? 'Email failed' : 'Email lỗi')->state(fn (Notification $record): int => $record->recipients()->where('email_status', 'failed')->count()),
            ])
            ->filters([
                SelectFilter::make('type')->label(UiText::get('fields.type', 'Loại'))->options(NotificationPresenter::typeOptions()),
                SelectFilter::make('source_module')->label(UiText::get('fields.module', 'Phân hệ'))->options(fn (): array => Notification::query()->whereNotNull('source_module')->distinct()->pluck('source_module', 'source_module')->all()),
            ])
            ->recordActions([
                Actions\Action::make('delivery')
                    ->label(app()->getLocale() === 'en' ? 'Delivery details' : 'Chi tiết phân phối')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn (Notification $record): string => $record->title)
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(app()->getLocale() === 'en' ? 'Close' : 'Đóng')
                    ->modalContent(fn (Notification $record) => view('dth-notification-center::filament.modals.delivery-log', [
                        'record' => $record->load(['recipients.user']),
                    ])),
            ])
            ->defaultSort('sent_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListNotificationLogs::route('/')];
    }

    public static function canViewAny(): bool { return app(NotificationAuthorization::class)->canViewAudit(); }
    public static function canCreate(): bool { return false; }
    public static function canEdit(Model $record): bool { return false; }
    public static function canDelete(Model $record): bool { return false; }
}
