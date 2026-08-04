<?php

namespace Database\Factories\Marketing;

use App\Models\Marketing\SendingAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class SendingAccountFactory extends Factory
{
    protected $model = SendingAccount::class;

    public function definition(): array
    {
        return [
            'name'             => $this->faker->company() . ' Mail',
            'provider'         => 'log',
            'from_name'        => $this->faker->name(),
            'from_email'       => $this->faker->companyEmail(),
            'reply_to'         => null,
            'config_encrypted' => ['mailer' => 'log'],
            'daily_limit'      => 1000,
            'hourly_limit'     => 100,
            'status'           => 'active',
        ];
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }
}
