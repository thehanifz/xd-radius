<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppUserPreference extends Model
{
    protected $table = 'app_user_preferences';

    protected $fillable = ['user_id', 'key', 'value'];

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }
}
