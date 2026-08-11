<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Crm\Staff;
use App\Models\User;
use App\Services\Organization\RoleDepartmentService;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    public function getTitle(): string
    {
        return __('configuration.account.create');
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): User {
            $staffId = $data['staff_id'] ?? null;
            unset($data['staff_id']);

            $staff = $staffId
                ? Staff::query()
                    ->with('department')
                    ->lockForUpdate()
                    ->findOrFail((int) $staffId)
                : null;

            if ($staff?->user_id !== null) {
                throw ValidationException::withMessages([
                    'staff_id' => __('validation.staff_already_has_user'),
                ]);
            }

            app(RoleDepartmentService::class)->assertCompatible(
                $data['role'] ?? null,
                $staff,
            );

            if ($staff) {
                $data['name'] = $staff->full_name;
            }

            $user = User::query()->create($data);

            if ($staff) {
                $staff->update(['user_id' => $user->id]);
            }

            return $user;
        });
    }

    public function getSubheading(): ?string
    {
        return __('configuration.account.create_subheading');
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->icon('heroicon-o-key');
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()->icon('heroicon-o-plus');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->icon('heroicon-o-arrow-left');
    }

}
