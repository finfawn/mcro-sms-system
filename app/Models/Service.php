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
        'registration_number',
        'citizen_name',
        'mobile_number',
        'service_type',
        'status',
        'notes',
        'payment_date',
        'posting_start_date',
        'release_date',
        'sms_posting_sent',
        'sms_ready_sent',
    ];
    protected $casts = [
        'payment_date' => 'date',
        'posting_start_date' => 'date',
        'release_date' => 'date',
        'sms_posting_sent' => 'boolean',
        'sms_ready_sent' => 'boolean',
    ];

    public function statusLogs()
    {
        return $this->hasMany(ServiceStatusLog::class)->orderBy('created_at', 'asc');
    }
}
