<?php

namespace Modules\Core\Models;

use App\Traits\BaseModel;
use App\Traits\SerializableDate;
use App\Traits\SoftDelete;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Core\Database\Factories\DistrictFactory;
use Spatie\Permission\Traits\HasRoles;

class District extends Model
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles, SerializableDate, BaseModel, SoftDelete;

    protected $connection = 'pgsql';
    protected $table = 'core.district';

    protected $primaryKey = 'district_id';
    protected $guard_name = 'api';

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function newFactory()
    {
        return DistrictFactory::new();
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id', 'city_id');
    }

    public function village()
    {
        return $this->hasMany(Village::class, 'district_id', 'district_id');
    }
}
