<?php

namespace Modules\Authentication\Models;

use App\Traits\BaseModel;
use App\Traits\SerializableDate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Authentication\Database\Factories\UserFactory;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles, SerializableDate, BaseModel;

    protected $connection = 'pgsql';
    protected $table = 'authentication.user';
    protected $primaryKey = 'user_id';
    protected $guard_name = 'api';
    public $incrementing = false;
    protected $keyType = 'string';
    protected static function newFactory()
    {
        return UserFactory::new();
    }
    protected $fillable = [
        'user_id',
        'user_name',
        'email',
        'password',
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
