<?php

namespace Database\Factories\Marketing;

use App\Models\Marketing\EmailTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmailTemplateFactory extends Factory
{
    protected $model = EmailTemplate::class;

    public function definition(): array
    {
        return [
            'name'       => $this->faker->unique()->sentence(3),
            'subject'    => $this->faker->sentence(),
            'preheader'  => $this->faker->sentence(),
            'html_body'  => '<p>Hello {{first_name}},</p><p>' . $this->faker->paragraph() . '</p><p><a href="{{unsubscribe_url}}">Unsubscribe</a></p>',
            'text_body'  => 'Hello {{first_name}}, ' . $this->faker->paragraph(),
            'status'     => 'active',
            'created_by' => null,
        ];
    }
}
