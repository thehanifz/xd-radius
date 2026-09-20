<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Radusergroup extends Model
{
    protected $connection = 'radius';
    protected $table = 'radusergroup';
    public $timestamps = false;

    protected $fillable = [
        'username',
        'groupname',
        'priority',
    ];
}
