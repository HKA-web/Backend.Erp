<?php

namespace Modules\Core\Models;

use App\Traits\BaseModel;
use App\Traits\SerializableDate;
use App\Traits\SoftDelete;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Core\Database\Factories\VillageFactory;
use Spatie\Permission\Traits\HasRoles;

class Village extends Model
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles, SerializableDate, BaseModel, SoftDelete;

    protected $connection = 'pgsql';
    protected $table = 'core.village';

    protected $primaryKey = 'village_id';
    protected $guard_name = 'api';

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function newFactory()
    {
        return VillageFactory::new();
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id', 'district_id');
    }
}
