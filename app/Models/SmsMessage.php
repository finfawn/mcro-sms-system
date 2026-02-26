<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsMessage extends Model
{
    protected $table = 'sms_messages';
    protected $fillable = [
        'service_id',
        'to',
        'body',
        'provider',
        'event_key',
        'status',
        'error',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
