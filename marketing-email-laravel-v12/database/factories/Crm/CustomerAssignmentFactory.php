<?php

namespace Database\Factories\Crm;

use App\Enums\Crm\CustomerAssignmentReason;
use App\Enums\Crm\CustomerAssignmentStatus;
use App\Enums\Crm\CustomerAssignmentType;
use App\Models\Crm\Customer;
use App\Models\Crm\CustomerAssignment;
use App\Models\Crm\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerAssignmentFactory extends Factory
{
    protected $model = CustomerAssignment::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'staff_id' => Staff::factory(),
            'assignment_type' => CustomerAssignmentType::Owner,
            'status' => CustomerAssignmentStatus::Active,
            'reason' => CustomerAssignmentReason::Manual,
            'starts_at' => now(),
            'assigned_by_user_id' => 1,
        ];
    }

    public function owner(): static
    {
        return $this->state(fn (array $a) => [
            'assignment_type' => CustomerAssignmentType::Owner,
        ]);
    }

    public function support(): static
    {
        return $this->state(fn (array $a) => [
            'assignment_type' => CustomerAssignmentType::Support,
        ]);
    }
}
