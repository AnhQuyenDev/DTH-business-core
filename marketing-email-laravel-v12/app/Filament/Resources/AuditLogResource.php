<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\Marketing\AuditLog;
use App\Support\Audit\AuditActivityPresenter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('uiux.audit.title');
    }

    public static function getModelLabel(): string
    {
        return __('uiux.audit.title');
    }

    public static function getPluralModelLabel(): string
    {
        return __('uiux.audit.title');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('system.view-audit') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'auditable']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('uiux.audit.time'))
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->width('145px'),
                TextColumn::make('user.name')
                    ->label(__('uiux.audit.actor'))
                    ->placeholder(__('uiux.audit.system'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('module')
                    ->label(__('uiux.audit.module_label'))
                    ->getStateUsing(fn (AuditLog $record): string => AuditActivityPresenter::module($record))
                    ->badge()
                    ->color(fn (AuditLog $record): string => match (AuditActivityPresenter::moduleKey($record)) {
                        'auth' => 'gray',
                        'email' => 'primary',
                        'marketing' => 'warning',
                        'crm' => 'info',
                        'sales' => 'warning',
                        'finance' => 'success',
                        'customer_care' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('activity_label')
                    ->label(__('uiux.audit.activity'))
                    ->getStateUsing(fn (AuditLog $record): string => AuditActivityPresenter::activity($record))
                    ->weight('medium')
                    ->wrap(),
                TextColumn::make('activity_description')
                    ->label(__('uiux.audit.description'))
                    ->getStateUsing(fn (AuditLog $record): string => AuditActivityPresenter::description($record))
                    ->wrap()
                    ->limit(120)
                    ->tooltip(fn (AuditLog $record): string => AuditActivityPresenter::description($record)),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('user_id')
                    ->label(__('uiux.audit.actor'))
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('module')
                    ->label(__('uiux.audit.module_label'))
                    ->form([
                        Select::make('module')
                            ->label(__('uiux.audit.module_label'))
                            ->options([
                                'auth' => __('uiux.audit.module.auth'),
                                'email' => __('uiux.audit.module.email'),
                                'marketing' => __('uiux.audit.module.marketing'),
                                'crm' => __('uiux.audit.module.crm'),
                                'sales' => __('uiux.audit.module.sales'),
                                'finance' => __('uiux.audit.module.finance'),
                                'customer_care' => __('uiux.audit.module.customer_care'),
                                'system' => __('uiux.audit.module.system'),
                            ])
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $module = $data['module'] ?? null;

                        return match ($module) {
                            'auth' => $query->where('action', 'login'),
                            'email' => $query->where(function (Builder $q): void {
                                foreach (['campaign.', 'template.', 'sending_account.', 'suppression.'] as $prefix) {
                                    $q->orWhere('action', 'like', $prefix.'%');
                                }
                            }),
                            'marketing' => $query->where(function (Builder $q): void {
                                foreach (['landing_page.', 'form.', 'segment.', 'tag.', 'contact.'] as $prefix) {
                                    $q->orWhere('action', 'like', $prefix.'%');
                                }
                            }),
                            'crm' => $query->where(function (Builder $q): void {
                                foreach (['lead.', 'distribution.'] as $prefix) {
                                    $q->orWhere('action', 'like', $prefix.'%');
                                }
                            }),
                            'sales' => $query->where(function (Builder $q): void {
                                foreach (['opportunity.', 'quotation.'] as $prefix) {
                                    $q->orWhere('action', 'like', $prefix.'%');
                                }
                            }),
                            'finance' => $query->where('action', 'like', 'payment.%'),
                            'customer_care' => $query->where('action', 'like', 'customer.%'),
                            'system' => $query->whereNot(function (Builder $q): void {
                                $q->where('action', 'login');
                                foreach (['campaign.', 'template.', 'sending_account.', 'suppression.', 'landing_page.', 'form.', 'segment.', 'tag.', 'contact.', 'lead.', 'distribution.', 'opportunity.', 'quotation.', 'payment.', 'customer.'] as $prefix) {
                                    $q->orWhere('action', 'like', $prefix.'%');
                                }
                            }),
                            default => $query,
                        };
                    }),
                Filter::make('created_at')
                    ->label(__('uiux.audit.time'))
                    ->form([
                        DatePicker::make('from')->label(__('uiux.audit.from_date')),
                        DatePicker::make('until')->label(__('uiux.audit.to_date')),
                    ])
                    ->columns(['default' => 1, 'md' => 2])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '<=', $date))),
            ])
            ->actions([
                Action::make('view_activity')
                    ->label(__('action.view'))
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(__('uiux.audit.detail'))
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('action.close'))
                    ->modalContent(fn (AuditLog $record) => view('filament.audit-log-detail', [
                        'log' => $record,
                        'presenter' => AuditActivityPresenter::class,
                    ])),
            ])
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
        ];
    }
}
