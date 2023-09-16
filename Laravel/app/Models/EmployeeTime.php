<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeTime extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'remote_time',
        'office_time',
        'attendence_status',
    ];

}
