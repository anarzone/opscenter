<?php

namespace Database\Factories;

use App\Models\Alert;
use App\Models\NotificationLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class NotificationLogFactory extends Factory
{
    protected $model = NotificationLog::class;

    public function definition()
    {
        return [
            'channel' => $this->faker->word(),
            'recipient' => $this->faker->word(),
            'status' => $this->faker->word(),
            'error_message' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'alert_id' => Alert::factory(),
        ];
    }
}
