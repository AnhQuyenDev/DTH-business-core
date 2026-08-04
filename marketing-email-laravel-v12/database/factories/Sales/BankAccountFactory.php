<?php

namespace Database\Factories\Sales;

use App\Models\Sales\BankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankAccountFactory extends Factory
{
    protected $model = BankAccount::class;

    public function definition(): array
    {
        return [
            'bank_code' => fake()->randomElement(['VCB', 'BIDV', 'CTG', 'TCB']),
            'bank_name' => fake()->company(),
            'account_number' => fake()->numerify('###########'),
            'account_name' => fake()->company(),
            'status' => 'active',
            'is_default' => false,
            'created_by' => 1,
        ];
    }
}
