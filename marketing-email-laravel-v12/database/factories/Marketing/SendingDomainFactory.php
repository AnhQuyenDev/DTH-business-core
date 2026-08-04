<?php

namespace Database\Factories\Marketing;

use App\Models\Marketing\SendingDomain;
use Illuminate\Database\Eloquent\Factories\Factory;

class SendingDomainFactory extends Factory
{
    protected $model = SendingDomain::class;

    public function definition(): array
    {
        return [
            'domain'       => $this->faker->unique()->domainName(),
            'status'       => 'pending',
            'spf_status'   => 'pending',
            'dkim_status'  => 'pending',
            'dmarc_status' => 'pending',
            'notes'        => null,
            'verified_at'  => null,
        ];
    }
}
