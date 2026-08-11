<?php

namespace Database\Factories\Crm;

use App\Enums\Crm\PositionAuthority;
use App\Models\Crm\Department;
use App\Models\Crm\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

class PositionFactory extends Factory
{
    protected $model = Position::class;

    public function definition(): array
    {
        return [
            'title' => fake()->jobTitle(),
            'authority_level' => PositionAuthority::Member->value,
            'department_id' => Department::factory(),
            'description' => null,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
