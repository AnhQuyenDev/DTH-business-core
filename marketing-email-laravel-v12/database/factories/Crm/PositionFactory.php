<?php

namespace Database\Factories\Crm;

use App\Enums\Crm\PositionAuthority;
use App\Enums\Crm\PositionGroup;
use App\Models\Crm\Position;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Position> */
class PositionFactory extends Factory
{
    protected $model = Position::class;

    public function definition(): array
    {
        $title = 'Test Job Title '.$this->faker->unique()->numberBetween(1000, 999999);

        return [
            'code' => Str::of($title)->slug('_')->lower()->toString(),
            'title' => $title,
            'group_key' => PositionGroup::Professional->value,
            'authority_level' => PositionAuthority::Member->value,
            'function_key' => null,
            'department_id' => null,
            'description' => null,
            'sort_order' => 100,
            'is_active' => true,
        ];
    }
}
