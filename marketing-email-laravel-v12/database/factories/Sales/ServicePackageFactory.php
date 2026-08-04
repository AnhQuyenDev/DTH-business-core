<?php

namespace Database\Factories\Sales;

use App\Enums\Sales\AudienceType;
use App\Enums\Sales\BillingPeriodUnit;
use App\Enums\Sales\PackageStatus;
use App\Models\Sales\ServicePackage;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServicePackageFactory extends Factory
{
    protected $model = ServicePackage::class;

    public function definition(): array
    {
        return [
            'service_id' => \App\Models\Sales\Service::factory(),
            'package_code' => 'PKG_' . strtoupper(fake()->bothify('??###')),
            'name' => fake()->words(3, true),
            'audience_type' => fake()->randomElement(AudienceType::cases()),
            'billing_period' => 1,
            'billing_period_unit' => BillingPeriodUnit::Month,
            'unit' => 'tháng',
            'default_quantity' => 1,
            'status' => PackageStatus::Active,
            'sort_order' => fake()->numberBetween(1, 100),
            'created_by' => 1,
        ];
    }
}
