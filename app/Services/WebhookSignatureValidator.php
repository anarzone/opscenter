<?php

namespace App\Services;

class WebhookSignatureValidator
{
    public static function validate(string $payload, string $signature, string $secret, string $provider): bool
    {
        return match ($provider) {
            "github" => self::validateGithub($payload, $signature, $secret),
            "stripe" => self::validateStripe($payload, $signature, $secret),
            default => throw new \InvalidArgumentException("Unsupported provider: $provider")
        };
    }

    private static function validateGithub(string $payload, string $signature, string $secret): bool
    {
        $hash = hash_hmac('sha256', $payload, $secret);
        $expectedSignature = 'sha256=' . $hash;

        return hash_equals($expectedSignature, $signature);
    }

    private static function validateStripe(string $payload, string $signature, string $secret): bool
    {
        $parts = explode(',', $signature);
        $timestamp = null;
        $signatures = [];

        foreach ($parts as $part) {
            [$key, $value] = explode('=', $part, 2);
            if ($key === 't') {
                $timestamp = $value;
            } elseif ($key === 'v1') {
                $signatures[] = $value;
            }
        }

        $signedPayload = $timestamp . '.' . $payload;

        $expectedSignature = hash_hmac('sha256', $signedPayload, $secret);

        return array_any($signatures, fn($sig) => hash_equals($expectedSignature, $sig));
    }
}
