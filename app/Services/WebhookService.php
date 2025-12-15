<?php

namespace App\Services;

use App\Enums\WebhookEventStatus;
use App\Models\WebhookEvent;
use App\Models\WebhookSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    private array $signatureKeysMap = [
        "github" => "X-Hub-Signature-256",
        "gitlab" => "X-Gitlab-Token",
        "stripe" => "Stripe-Signature",
        "custom" => "X-Signature",
    ];

    public function process(WebhookSource $source, Request $request): array
    {
        $rawPayload = $request->getContent();
        $headers = $request->headers->all();
        $signature = $request->header($this->getSignatureKey($source->slug));
        $ipAddress = $request->ip();

        if (!WebhookSignatureValidator::validate($rawPayload, $signature, $source->secret_key, $source->slug)) {
            Log::warning("Webhook signature validation failed", [
                "source" => $source->slug,
                "ip" => $ipAddress,
            ]);

            return [
                'success' => false,
                'message' => 'Invalid signature',
                'webhook_event_id' => null,
                'errors' => ['signature' => 'Signature validation failed'] // Todo: make it dynamic
            ];
        }

        $webhookEvent = WebhookEvent::create([
            'webhook_source_id' => $source->id,
            'event_type' => $this->extractEventTyope($request, $source->slug),
            'payload' => json_decode($rawPayload, true),
            'headers' => $headers,
            'signature' => $signature,
            'ip_address' => $ipAddress,
            'status' => WebhookEventStatus::PENDING,
            'processed_at' => null
        ]);

        Log::info('Webhook received successfully', [
            'source' => $source->slug,
            'event_id' => $webhookEvent->id,
            'event_type' => $webhookEvent->event_type
        ]);

        return [
            'success' => true,
            'message' => 'Webhook received and queued for processing',
            'webhook_event_id' => $webhookEvent->id,
            'errors' => []
        ];
    }

    private function getSignatureKey(string $key): string
    {
        if (array_key_exists($key, $this->signatureKeysMap)) {
            return $this->signatureKeysMap[$key];
        }

        throw new \InvalidArgumentException("Provided Signature key is not valid");
    }

    private function extractEventTyope(Request $request, string $provider)
    {
        return match ($provider) {
            'github' => $request->header('X-GitHub-Event', 'unknown'),
            'gitlab' => $request->header('X-Gitlab-Event', 'unknown'),
            'stripe' => $request->json('type', 'unknown'),
            'custom' => $request->json('event', 'unknown'),
            default => 'unknown'
        };
    }
}
