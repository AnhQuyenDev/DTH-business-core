<?php

namespace Database\Factories\Crm;

use App\Models\Crm\Department;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        return [
            'code' => Str::slug(fake()->unique()->words(2, true), '-'),
            'name' => fake()->words(2, true),
            'function_key' => 'other',
            'color' => 'gray',
            'description' => null,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
