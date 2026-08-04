<?php

namespace App\Filament\Widgets;

use App\Enums\Crm\InteractionStatus;
use App\Models\Crm\Customer;
use App\Models\Crm\CustomerInteraction;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Model;
use Saade\FilamentFullCalendar\Actions;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class StaffScheduleWidget extends FullCalendarWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 1;

    public Model | string | null $model = CustomerInteraction::class;

    public function config(): array
    {
        return [
            'initialView' => 'dayGridMonth',
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,timeGridWeek,timeGridDay',
            ],
            'height' => 'auto',
            'selectable' => true,
            'editable' => true,
        ];
    }

    public function fetchEvents(array $info): array
    {
        $staff = auth()->user()?->staff;

        if (!$staff) {
            return [];
        }

        $start = Carbon::parse($info['start']);
        $end = Carbon::parse($info['end']);

        return CustomerInteraction::query()
            ->where('staff_id', $staff->id)
            ->whereNotNull('next_follow_up_at')
            ->whereBetween('next_follow_up_at', [$start, $end])
            ->get()
            ->map(fn (CustomerInteraction $interaction) => [
                'id' => (string) $interaction->id,
                'title' => $interaction->subject . ' - ' . ($interaction->customer?->display_name ?: ''),
                'start' => $interaction->next_follow_up_at->toIso8601String(),
                'backgroundColor' => $interaction->next_follow_up_at->isPast() ? '#ef4444' : '#22c55e',
                'borderColor' => $interaction->next_follow_up_at->isPast() ? '#ef4444' : '#22c55e',
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'status' => $interaction->status->value,
                    'customer' => $interaction->customer?->display_name,
                    'type' => __('enum.interaction_type.' . $interaction->interaction_type),
                ],
            ])
            ->all();
    }

    public function getFormSchema(): array
    {
        $staffId = auth()->user()?->staff?->id;

        return [
            Forms\Components\Select::make('customer_id')
                ->label(__('field.customer'))
                ->options(
                    Customer::whereHas('assignments', fn ($q) => $q->where('staff_id', $staffId))
                        ->pluck('display_name', 'id')
                )
                ->searchable()
                ->required(),
            Forms\Components\Select::make('interaction_type')
                ->label(__('field.method'))
                ->options([
                    'call' => __('enum.interaction_type.call'),
                    'email' => __('enum.interaction_type.email'),
                    'message' => __('enum.interaction_type.message'),
                    'meeting' => __('enum.interaction_type.meeting'),
                    'support' => __('enum.interaction_type.support'),
                    'follow_up' => __('enum.interaction_type.follow_up'),
                ])
                ->required(),
            Forms\Components\TextInput::make('subject')
                ->label(__('field.subject'))
                ->required()
                ->maxLength(255),
            Forms\Components\Textarea::make('content')
                ->label(__('field.content')),
            Forms\Components\DateTimePicker::make('next_follow_up_at')
                ->label(__('field.appointment_at'))
                ->required(),
            Forms\Components\Select::make('status')
                ->label(__('field.status'))
                ->options(collect(InteractionStatus::cases())
                    ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
                )
                ->default(InteractionStatus::Scheduled->value)
                ->required(),
            Forms\Components\Hidden::make('interaction_at'),
        ];
    }

    public function onDateSelect(string $start, ?string $end, bool $allDay, ?array $view, ?array $resource): void
    {
        [$start, $end] = $this->calculateTimezoneOffset($start, $end, $allDay);

        $this->mountAction('create', [
            'next_follow_up_at' => $start->toDateTimeString(),
            'interaction_at' => now()->toDateTimeString(),
            'status' => InteractionStatus::Scheduled->value,
        ]);
    }

    public function onEventClick(array $event): void
    {
        if ($this->getModel()) {
            $this->record = $this->resolveRecord($event['id']);
        }

        $this->mountAction('view');
    }

    public function onEventDrop(array $event, array $oldEvent, array $relatedEvents, array $delta, ?array $oldResource, ?array $newResource): bool
    {
        $record = CustomerInteraction::find($event['id']);

        if ($record && $record->staff_id === auth()->user()?->staff?->id) {
            $record->update([
                'next_follow_up_at' => Carbon::parse($event['start']),
            ]);

            $this->refreshRecords();
        }

        return true;
    }

    protected function headerActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(function (array $data) {
                    $data['staff_id'] = auth()->user()?->staff?->id;
                    $data['interaction_at'] ??= now();
                    $data['status'] ??= InteractionStatus::Scheduled->value;
                    return $data;
                }),
        ];
    }

    protected function modalActions(): array
    {
        return [
            Actions\EditAction::make()
                ->mutateFormDataUsing(function (array $data) {
                    $data['staff_id'] = auth()->user()?->staff?->id;
                    return $data;
                }),
            Actions\DeleteAction::make(),
        ];
    }

    protected function viewAction(): \Filament\Actions\Action
    {
        return Actions\ViewAction::make();
    }

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user && !$user->isAdmin() && $user->staff !== null;
    }
}
