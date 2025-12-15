<?php

namespace Database\Factories;

use App\Models\WebhookEvent;
use App\Models\WebhookSource;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class WebhookEventFactory extends Factory
{
    protected $model = WebhookEvent::class;

    public function definition()
    {
        return [
            'event_type' => $this->faker->word(),
            'payload' => $this->faker->words(),
            'headers' => $this->faker->words(),
            'signature' => $this->faker->word(),
            'ip_address' => $this->faker->ipv4(),
            'status' => $this->faker->word(),
            'processed_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'webhook_source_id' => WebhookSource::factory(),
        ];
    }
}
