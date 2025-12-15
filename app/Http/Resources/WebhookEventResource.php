<?php

namespace App\Http\Resources;

use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WebhookEvent */
class WebhookEventResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'event_type' => $this->event_type,
            'payload' => $this->payload,
            'headers' => $this->headers,
            'signature' => $this->signature,
            'ip_address' => $this->ip_address,
            'status' => $this->status,
            'processed_at' => $this->processed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'webhook_source_id' => $this->webhook_source_id,

            'webhookSource' => new WebhookSourceResource($this->whenLoaded('webhookSource')),
        ];
    }
}
