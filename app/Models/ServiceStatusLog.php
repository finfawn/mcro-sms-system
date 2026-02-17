<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceStatusLog extends Model
{
    protected $table = 'service_status_logs';
    protected $fillable = [
        'service_id',
        'status',
        'note',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
