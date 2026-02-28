<?php

namespace Modules\Core\Models;

use App\Traits\BaseModel;
use App\Traits\SerializableDate;
use App\Traits\SoftDelete;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Core\Database\Factories\CompanyFactory;
use Spatie\Permission\Traits\HasRoles;

class Company extends Model
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles, SerializableDate, BaseModel, SoftDelete;

    protected $connection = 'pgsql';
    protected $table = 'core.company';

    protected $primaryKey = 'company_id';
    protected $guard_name = 'api';

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function newFactory()
    {
        return CompanyFactory::new();
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id', 'province_id');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id', 'city_id');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id', 'district_id');
    }

    public function village()
    {
        return $this->belongsTo(Village::class, 'village_id', 'village_id');
    }
}
