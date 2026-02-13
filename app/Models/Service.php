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
    ];
}
