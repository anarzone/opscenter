<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'alert_id',
        'channel',
        'recipient',
        'status',
        'error_message',
    ];

    public function alert()
    {
        return $this->belongsTo(Alert::class);
    }
}
