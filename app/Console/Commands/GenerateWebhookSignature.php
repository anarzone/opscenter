<?php

namespace App\Console\Commands;

use App\Models\WebhookSource;
use Illuminate\Console\Command;

class GenerateWebhookSignature extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhook:signature
                            {provider : The webhook provider slug (github, stripe, etc.)}
                            {payload? : The JSON payload string}
                            {--file= : Path to JSON file containing the payload}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate HMAC signature for testing webhook requests';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $provider = $this->argument('provider');

        // Find the webhook source
        $source = WebhookSource::where('slug', $provider)->first();

        if (! $source) {
            $this->error("Webhook source '{$provider}' not found in database.");
            $this->line('Available sources:');
            WebhookSource::all()->each(fn ($s) => $this->line("  - {$s->slug}"));

            return Command::FAILURE;
        }

        // Get the payload
        $payload = $this->getPayload();

        if (! $payload) {
            $this->error('No payload provided. Use either {payload} argument or --file option.');

            return Command::FAILURE;
        }

        // Validate JSON
        if (! $this->isValidJson($payload)) {
            $this->error('Invalid JSON payload provided.');

            return Command::FAILURE;
        }

        // Generate signature based on provider
        $signature = $this->generateSignature($provider, $payload, $source->secret_key);

        // Output results
        $this->info('✓ Signature generated successfully!');
        $this->newLine();

        $this->line('<fg=cyan>Provider:</>     '.$source->name.' ('.$provider.')');
        $this->line('<fg=cyan>Secret Key:</>   '.$source->secret_key);
        $this->line('<fg=cyan>Header Name:</>  '.$this->getHeaderName($provider));
        $this->newLine();

        $this->line('<fg=green;options=bold>Signature:</>');
        $this->line($signature);
        $this->newLine();

        $this->line('<fg=yellow>Copy and paste the signature above into your HTTP request header.</>');

        return Command::SUCCESS;
    }

    /**
     * Get the payload from argument or file
     */
    private function getPayload(): ?string
    {
        // Check if file option is provided
        if ($file = $this->option('file')) {
            if (! file_exists($file)) {
                $this->error("File not found: {$file}");

                return null;
            }

            return file_get_contents($file);
        }

        // Check if payload argument is provided
        if ($payload = $this->argument('payload')) {
            return $payload;
        }

        // Try to read from stdin
        if (! posix_isatty(STDIN)) {
            return stream_get_contents(STDIN);
        }

        return null;
    }

    /**
     * Validate JSON payload
     */
    private function isValidJson(string $payload): bool
    {
        return json_validate($payload);
    }

    /**
     * Generate signature based on provider
     */
    private function generateSignature(string $provider, string $payload, string $secret): string
    {
        return match ($provider) {
            'gitlab' => hash_hmac('sha256', $payload, $secret),
            'stripe' => $this->generateStripeSignature($payload, $secret),
            default => 'sha256='.hash_hmac('sha256', $payload, $secret),
        };
    }

    /**
     * Generate Stripe-specific signature format
     */
    private function generateStripeSignature(string $payload, string $secret): string
    {
        $timestamp = time();
        $signedPayload = $timestamp.'.'.$payload;
        $signature = hash_hmac('sha256', $signedPayload, $secret);

        return "t=$timestamp,v1=$signature";
    }

    /**
     * Get the header name for the provider
     */
    private function getHeaderName(string $provider): string
    {
        return match ($provider) {
            'github' => 'X-Hub-Signature-256',
            'gitlab' => 'X-Gitlab-Token',
            'stripe' => 'Stripe-Signature',
            default => 'X-Signature',
        };
    }
}
