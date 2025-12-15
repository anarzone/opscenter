<?php

namespace App\Models;

use App\Enums\WebhookEventStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WebhookEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'webhook_source_id',
        'event_type',
        'payload',
        'headers',
        'signature',
        'ip_address',
        'status',
        'processed_at',
    ];

    public function webhookSource()
    {
        return $this->belongsTo(WebhookSource::class);
    }

    protected function casts()
    {
        return [
            'payload' => 'array',
            'headers' => 'array',
            'status' => WebhookEventStatus::class,
            'processed_at' => 'timestamp',
        ];
    }
}
