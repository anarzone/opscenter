<?php

namespace App\Http\Controllers;

use App\Models\WebhookSource;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WebhookController
{
    public function handle(WebhookSource $source, Request $request): JsonResponse
    {
        if (!$source->is_active){
            return response()->json([
                'error' => 'Webhook source is disabled',
                'source' => $source->slug
            ], Response::HTTP_FORBIDDEN);
        }

        $result = app(WebhookService::class)->process($source, $request);

        if ($result['success']) {
            return response()->json([
                'status' => 'accepted',
                'id' => $result['webhook_event_id'],
                'message' => $result['message']
            ]);
        } else {
            return response()->json([
                'error' => $result['message'],
                'details' => $result['errors']
            ], Response::HTTP_UNAUTHORIZED);
        }
    }
}
