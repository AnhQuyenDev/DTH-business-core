<?php

namespace Database\Factories\Crm;

use App\Models\Crm\Department;
use App\Models\Crm\Staff;
use App\Enums\Crm\StaffEmploymentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class StaffFactory extends Factory
{
    protected $model = Staff::class;

    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'employee_code' => 'EMP' . fake()->unique()->numerify('#####'),
            'full_name' => fake()->name(),
            'department_id' => Department::factory(),
            'employment_status' => StaffEmploymentStatus::Active,
            'can_receive_customers' => true,
            'customer_capacity' => 50,
            'distribution_weight' => 1.0,
            'started_at' => now()->subYear()->toDateString(),
        ];
    }
}
