<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsTemplate extends Model
{
    protected $fillable = [
        'service_type',
        'event_key',
        'template_body',
        'is_active',
    ];
    protected $casts = [
        'is_active' => 'boolean',
    ];
}
