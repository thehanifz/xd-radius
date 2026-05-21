<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AppUser extends Model implements AuthenticatableContract
{
    use Authenticatable, HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'app_users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active'          => 'boolean',
        'email_verified_at'  => 'datetime',
    ];

    // -------------------------------------------------------
    // Roles
    // -------------------------------------------------------

    const ROLE_SUPERUSER = 'superuser';
    const ROLE_OPERATOR  = 'operator';

    public function isSuperUser(): bool
    {
        return $this->role === self::ROLE_SUPERUSER;
    }

    public function isOperator(): bool
    {
        return $this->role === self::ROLE_OPERATOR;
    }

    // -------------------------------------------------------
    // Password
    // -------------------------------------------------------

    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password'] = Hash::make($value);
    }

    // -------------------------------------------------------
    // Activity Log
    // -------------------------------------------------------

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'role', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // -------------------------------------------------------
    // Relationships
    // -------------------------------------------------------

    public function preferences()
    {
        return $this->hasMany(AppUserPreference::class, 'user_id');
    }
}
