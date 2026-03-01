<?php

namespace Modules\Authentication\Models;

use App\Traits\BaseModel;
use App\Traits\SerializableDate;
use App\Traits\SoftDelete;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Authentication\Database\Factories\UserFactory;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles, SerializableDate, BaseModel, SoftDelete;

    protected $connection = 'pgsql';
    protected $table = 'authentication.user';

    protected $primaryKey = 'user_id';

    // Guard name untuk Spatie & Sanctum
    protected $guard_name = 'api';

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Karena kita override primaryKey jadi 'user_id',
     * Sanctum butuh kepastian ID mana yang dipakai.
     */
    public function getAuthIdentifier()
    {
        return $this->user_id;
    }

    protected static function newFactory()
    {
        return UserFactory::new();
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $fillable = [
        'user_id',
        'user_name',
        'email',
        'password',
    ];

}
