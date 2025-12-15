<?php

namespace Database\Seeders;

use App\Models\WebhookSource;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WebhookSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sources = [
            [
                'name' => 'GitHub',
                'slug' => 'github',
                'secret_key' => Str::random(32),
                'is_active' => true,
            ],
            [
                'name' => 'Stripe',
                'slug' => 'stripe',
                'secret_key' => Str::random(32),
                'is_active' => true,
            ],
            [
                'name' => 'Custom Webhook',
                'slug' => 'custom',
                'secret_key' => Str::random(32),
                'is_active' => true,
            ],
            [
                'name' => 'GitLab',
                'slug' => 'gitlab',
                'secret_key' => Str::random(32),
                'is_active' => true,
            ],
            [
                'name' => 'Shopify',
                'slug' => 'shopify',
                'secret_key' => Str::random(32),
                'is_active' => false, // Disabled by default
            ],
        ];

        foreach ($sources as $source) {
            WebhookSource::updateOrCreate(
                ['slug' => $source['slug']],
                $source
            );
        }

        $this->command->info('Webhook sources seeded successfully!');
        $this->command->newLine();
        $this->command->info('Available webhook endpoints:');

        foreach (WebhookSource::where('is_active', true)->get() as $source) {
            $this->command->line("  - POST /api/webhooks/{$source->slug} (Secret: {$source->secret_key})");
        }
    }
}
