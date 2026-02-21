<?php

namespace Modules\Core\Models;

use App\Traits\BaseModel;
use App\Traits\SerializableDate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Core\Database\Factories\CityFactory;
use Spatie\Permission\Traits\HasRoles;

class City extends Model
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles, SerializableDate, BaseModel;

    protected $connection = 'pgsql';
    protected $table = 'core.city';

    protected $primaryKey = 'city_id';
    protected $guard_name = 'api';

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function newFactory()
    {
        return CityFactory::new();
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id', 'province_id');
    }

    public function district()
    {
        return $this->hasMany(District::class, 'city_id', 'city_id');
    }
}
