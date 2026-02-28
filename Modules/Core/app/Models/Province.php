<?php

namespace Modules\Core\Models;

use App\Traits\BaseModel;
use App\Traits\SerializableDate;
use App\Traits\SoftDelete;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Core\Database\Factories\ProvinceFactory;
use Spatie\Permission\Traits\HasRoles;

class Province extends Model
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles, SerializableDate, BaseModel, SoftDelete;

    protected $connection = 'pgsql';
    protected $table = 'core.province';

    protected $primaryKey = 'province_id';
    protected $guard_name = 'api';

    public $incrementing = false;
    protected $keyType = 'string';
    protected static function newFactory()
    {
        return ProvinceFactory::new();
    }

    public function city()
    {
        return $this->hasMany(City::class, 'province_id', 'province_id');
    }
}
