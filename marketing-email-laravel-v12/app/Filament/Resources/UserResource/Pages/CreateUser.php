<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Crm\Staff;
use App\Models\User;
use App\Services\Organization\RoleDepartmentService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

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
}
