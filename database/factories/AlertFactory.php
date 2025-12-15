<?php

namespace Database\Factories;

use App\Models\Alert;
use App\Models\WebhookEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class AlertFactory extends Factory
{
    protected $model = Alert::class;

    public function definition()
    {
        return [
            'title' => $this->faker->word(),
            'message' => $this->faker->word(),
            'severity' => $this->faker->word(),
            'metadata' => $this->faker->words(),
            'is_read' => $this->faker->boolean(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'webhook_event_id' => WebhookEvent::factory(),
        ];
    }
}
