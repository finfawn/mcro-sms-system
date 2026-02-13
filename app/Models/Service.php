<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use SoftDeletes;
    protected $table = 'services';

    protected $fillable = [
        'reference_no',
        'citizen_name',
        'mobile_number',
        'category',
        'service_type',
        'status',
        'remarks',
        'payment_date',
        'posting_start_date',
        'release_date',
        'sms_posting_sent',
        'sms_release_sent',
    ];
    protected $casts = [
        'payment_date' => 'date',
        'posting_start_date' => 'date',
        'release_date' => 'date',
        'sms_posting_sent' => 'boolean',
        'sms_release_sent' => 'boolean',
    ];
}
