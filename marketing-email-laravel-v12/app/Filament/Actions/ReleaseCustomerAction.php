<?php

namespace App\Filament\Actions;

use App\Enums\Crm\CustomerAssignmentStatus;
use App\Models\Crm\Customer;
use App\Models\Crm\CustomerAssignment;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;

class ReleaseCustomerAction
{
    public static function make(): Action
    {
        return Action::make('release_customer')
            ->label(__('action.release_customer'))
            ->icon('heroicon-o-arrow-uturn-right')
            ->color('warning')
            ->modalHeading(__('action.release_customer_confirm'))
            ->modalDescription(__('action.release_customer_desc'))
            ->modalSubmitActionLabel(__('action.release_customer'))
            ->form([
                Select::make('reason')
                    ->label(__('field.release_reason'))
                    ->options([
                        'customer_refused' => __('enum.assignment_reason.customer_refused'),
                        'wrong_owner' => __('enum.assignment_reason.wrong_owner'),
                        'no_response' => __('enum.assignment_reason.no_response'),
                        'overload' => __('enum.assignment_reason.overload'),
                        'other' => __('enum.assignment_reason.other'),
                    ])
                    ->required(),
                Textarea::make('note')
                    ->label(__('field.note')),
            ])
            ->visible(function (Customer $record): bool {
                $user = auth()->user();
                if (! $user || $user->isAdmin() || $user->isMarketingManager() || $user->isCustomerServiceManager()) {
                    return false;
                }
                $staff = $user->staff;
                if (! $staff) {
                    return false;
                }

                return CustomerAssignment::query()
                    ->where('customer_id', $record->id)
                    ->where('staff_id', $staff->id)
                    ->where('status', CustomerAssignmentStatus::Active->value)
                    ->exists();
            })
            ->action(function (Customer $record, array $data): void {
                $user = auth()->user();
                $staff = $user?->staff;
                if (! $staff) {
                    return;
                }

                $now = now();

                CustomerAssignment::query()
                    ->where('customer_id', $record->id)
                    ->where('staff_id', $staff->id)
                    ->where('status', CustomerAssignmentStatus::Active->value)
                    ->update([
                        'status' => CustomerAssignmentStatus::Ended->value,
                        'ends_at' => $now,
                        'ended_at' => $now,
                        'ended_by_user_id' => $user->id,
                        'note' => ($data['reason'] ?? '').($data['note'] ? ': '.$data['note'] : ''),
                    ]);

                Notification::make()
                    ->title(__('notification.customer_released'))
                    ->success()
                    ->send();
            });
    }
}
