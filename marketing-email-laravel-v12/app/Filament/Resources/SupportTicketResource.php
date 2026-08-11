<?php

namespace App\Filament\Resources;

use App\Enums\Crm\DepartmentFunction;
use App\Enums\Support\TicketPriority;
use App\Enums\Support\TicketStatus;
use App\Filament\Resources\SupportTicketResource\Pages;
use App\Models\Crm\Customer;
use App\Models\Crm\Staff;
use App\Models\Support\SupportTicket;
use App\Services\Support\SupportTicketService;
use App\Services\Business\WorkflowPolicyService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;
    protected static ?string $navigationIcon = 'heroicon-o-lifebuoy';
    protected static ?int $navigationSort = 35;

    public static function getNavigationGroup(): string { return __('navigation.group.customer_care'); }
    public static function getNavigationLabel(): string { return __('v1.support.resource_plural'); }
    public static function getModelLabel(): string { return __('v1.support.resource_singular'); }
    public static function getPluralModelLabel(): string { return __('v1.support.resource_plural'); }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        if (! app(WorkflowPolicyService::class)->supportTicketsEnabled()) {
            return false;
        }

        return $user?->can('customer-care.view-tickets') ?? false;
    }

    public static function canViewAny(): bool { return static::shouldRegisterNavigation(); }
    public static function canCreate(): bool { return auth()->user()?->can('customer-care.manage-tickets') ?? false; }
    public static function canEdit(Model $record): bool { return auth()->user()?->can('customer-care.manage-tickets') ?? false; }
    public static function canDelete(Model $record): bool { return false; }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user || $user->can('customer-care.manage-assignments')) {
            return $query;
        }

        if ($user->can('customer-care.view-tickets') && $user->staff?->id) {
            return $query->where(function (Builder $query) use ($user): void {
                $query->where('assigned_staff_id', $user->staff->id)
                    ->orWhereHas('customer.assignments', fn (Builder $assignment): Builder =>
                        $assignment->where('staff_id', $user->staff->id)
                            ->where('status', 'active')
                    );
            });
        }

        return $query->whereRaw('0 = 1');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('customer_id')
                ->label(__('resource.customer.singular'))
                ->options(function (): array {
                    $query = Customer::query();
                    $user = auth()->user();

                    if ($user?->can('customer-care.view-tickets') && ! $user?->can('customer-care.manage-assignments')) {
                        $staffId = $user->staff?->id ?? 0;
                        $query->whereHas('assignments', fn (Builder $assignment): Builder =>
                            $assignment->where('staff_id', $staffId)
                                ->where('status', 'active')
                        );
                    }

                    return $query->orderBy('display_name')->pluck('display_name', 'id')->all();
                })
                ->searchable()->preload()->required(),
            TextInput::make('requester_name')->label(__('v1.support.requester_name'))->maxLength(255),
            TextInput::make('requester_email')->label(__('field.email'))->email()->required()->maxLength(255),
            TextInput::make('subject')->label(__('v1.support.subject'))->required()->maxLength(255)->columnSpanFull(),
            Textarea::make('description')->label(__('v1.support.description'))->rows(5)->columnSpanFull(),
            Select::make('priority')->label(__('v1.support.priority_label'))->options(TicketPriority::options())
                ->default(config('v1_workflow.support.default_priority', 'normal'))->required(),
            Select::make('status')->label(__('field.status'))->options(TicketStatus::options())
                ->default(TicketStatus::Open->value)->required(),
            Select::make('assigned_staff_id')
                ->label(__('field.assigned_staff'))
                ->options(fn (): array => Staff::query()
                    ->withBusinessFunction(DepartmentFunction::CustomerService)
                    ->where('employment_status', 'active')
                    ->orderBy('full_name')
                    ->pluck('full_name', 'id')->all())
                ->searchable()->preload(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ticket_code')->label(__('v1.support.ticket_code'))->searchable()->sortable(),
                TextColumn::make('customer.display_name')->label(__('resource.customer.singular'))->searchable(),
                TextColumn::make('subject')->label(__('v1.support.subject'))->limit(50)->searchable(),
                TextColumn::make('priority')->label(__('v1.support.priority_label'))->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof TicketPriority ? $state->label() : (TicketPriority::tryFrom((string) $state)?->label() ?? (string) $state))
                    ->color(fn ($state): string => $state instanceof TicketPriority ? $state->color() : (TicketPriority::tryFrom((string) $state)?->color() ?? 'gray')),
                TextColumn::make('status')->label(__('field.status'))->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof TicketStatus ? $state->label() : (TicketStatus::tryFrom((string) $state)?->label() ?? (string) $state))
                    ->color(fn ($state): string => $state instanceof TicketStatus ? $state->color() : (TicketStatus::tryFrom((string) $state)?->color() ?? 'gray')),
                TextColumn::make('assignedStaff.full_name')->label(__('field.assigned_staff'))->placeholder('—'),
                TextColumn::make('last_activity_at')->label(__('v1.support.last_activity'))->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(TicketStatus::options()),
                SelectFilter::make('priority')->options(TicketPriority::options()),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('reply_email')
                        ->label(__('v1.support.reply_email'))
                        ->icon('heroicon-o-envelope')
                        ->form([
                            Textarea::make('body')->label(__('v1.support.reply_body'))->rows(6)->required(),
                        ])
                        ->action(fn (SupportTicket $record, array $data) => app(SupportTicketService::class)
                            ->replyByEmail($record, auth()->user(), (string) $data['body']))
                        ->visible(fn (): bool => auth()->user()?->can('customer-care.manage-tickets') ?? false),
                    EditAction::make()->visible(fn (): bool => auth()->user()?->can('customer-care.manage-tickets') ?? false),
                ])->iconButton(),
            ])
            ->defaultSort('last_activity_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupportTickets::route('/'),
            'create' => Pages\CreateSupportTicket::route('/create'),
            'edit' => Pages\EditSupportTicket::route('/{record}/edit'),
        ];
    }
}
