<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Crm\Department;
use App\Models\Crm\Staff;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $departmentId = $data['staff_department'] ?? null;

        if ($departmentId === null) {
            $departmentId = Department::query()->where('code', 'marketing')->value('id');
        }

        $this->staffData = [
            'department_id' => $departmentId,
            'position_id' => $data['staff_position_id'] ?? null,
            'phone' => $data['staff_phone'] ?? null,
            'employment_status' => $data['staff_employment_status'] ?? 'active',
            'can_receive_customers' => $data['staff_can_receive_customers'] ?? true,
            'customer_capacity' => $data['staff_customer_capacity'] ?? null,
            'distribution_weight' => $data['staff_distribution_weight'] ?? 1.00,
            'started_at' => $data['staff_started_at'] ?? null,
            'ended_at' => $data['staff_ended_at'] ?? null,
        ];

        unset(
            $data['staff_department'],
            $data['staff_position_id'],
            $data['staff_phone'],
            $data['staff_employment_status'],
            $data['staff_can_receive_customers'],
            $data['staff_customer_capacity'],
            $data['staff_distribution_weight'],
            $data['staff_started_at'],
            $data['staff_ended_at'],
        );

        return $data;
    }

    protected function afterCreate(): void
    {
        $nextId = (Staff::withTrashed()->max('id') ?? 0) + 1;
        $employeeCode = 'EMP-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        $staff = Staff::create([
            'user_id' => $this->record->id,
            'employee_code' => $employeeCode,
            'full_name' => $this->record->name,
            'department_id' => $this->staffData['department_id'],
            'position_id' => $this->staffData['position_id'],
            'phone' => $this->staffData['phone'],
            'employment_status' => $this->staffData['employment_status'],
            'can_receive_customers' => $this->staffData['can_receive_customers'],
            'customer_capacity' => $this->staffData['customer_capacity'],
            'distribution_weight' => $this->staffData['distribution_weight'],
            'started_at' => $this->staffData['started_at'],
            'ended_at' => $this->staffData['ended_at'],
        ]);
    }
}
