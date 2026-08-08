<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Crm\Staff;
use App\Models\User;
use App\Services\Organization\RoleDepartmentService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['staff_id'] = $this->record->staff?->id;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data): Model {
            /** @var User $record */
            $staffId = $data['staff_id'] ?? null;
            unset($data['staff_id']);

            $currentStaff = Staff::query()
                ->where('user_id', $record->id)
                ->lockForUpdate()
                ->first();

            $newStaff = $staffId
                ? Staff::query()
                    ->with('department')
                    ->lockForUpdate()
                    ->findOrFail((int) $staffId)
                : null;

            if (
                $newStaff
                && $newStaff->user_id !== null
                && (int) $newStaff->user_id !== (int) $record->id
            ) {
                throw ValidationException::withMessages([
                    'staff_id' => __('validation.staff_already_has_user'),
                ]);
            }

            app(RoleDepartmentService::class)->assertCompatible(
                $data['role'] ?? $record->role,
                $newStaff,
            );

            if ($newStaff) {
                $data['name'] = $newStaff->full_name;
            }

            $record->update($data);

            if ($currentStaff && $currentStaff->id !== $newStaff?->id) {
                $currentStaff->update(['user_id' => null]);
            }

            if ($newStaff && (int) $newStaff->user_id !== (int) $record->id) {
                $newStaff->update(['user_id' => $record->id]);
            }

            return $record;
        });
    }

}
