<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Alert extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'webhook_event_id',
        'title',
        'message',
        'severity',
        'metadata',
        'is_read',
    ];

    public function webhookEvent()
    {
        return $this->belongsTo(WebhookEvent::class);
    }

    protected function casts()
    {
        return [
            'metadata' => 'array',
            'is_read' => 'boolean',
        ];
    }
}
