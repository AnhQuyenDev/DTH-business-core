<?php

namespace Database\Factories\Marketing;

use App\Enums\Marketing\CampaignStatus;
use App\Models\Marketing\Campaign;
use App\Models\Marketing\EmailTemplate;
use App\Models\Marketing\SendingAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(4),
            'subject' => $this->faker->sentence(),
            'preheader' => $this->faker->sentence(),
            'email_template_id' => EmailTemplate::factory(),
            'sending_account_id' => SendingAccount::factory(),
            'audience_type' => 'all_subscribed',
            'audience_id' => null,
            'status' => CampaignStatus::Draft->value,
            'created_by' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => CampaignStatus::Draft->value]);
    }

    public function scheduled(): static
    {
        return $this->state([
            'status' => CampaignStatus::Scheduled->value,
            'scheduled_at' => now()->addHours(2),
        ]);
    }

    public function sent(): static
    {
        return $this->state([
            'status' => CampaignStatus::Sent->value,
            'sent_at' => now()->subHour(),
        ]);
    }
}
