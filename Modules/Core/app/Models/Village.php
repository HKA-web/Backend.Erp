<?php

namespace Modules\Core\Models;

use App\Models\Scopes\ActiveOnlyScope;
use App\Traits\SerializableDate;
use App\Traits\SoftDelete;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Core\Database\Factories\VillageFactory;
use Spatie\Permission\Traits\HasRoles;

#[Table(name: 'core.village', key: 'village_id', keyType: 'string', incrementing: false)]
#[ScopedBy([ActiveOnlyScope::class])]
class Village extends Model
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SerializableDate, SoftDelete;

    protected $connection = 'pgsql';

    protected $guard_name = 'api';

    protected static function newFactory()
    {
        return VillageFactory::new();
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id', 'district_id');
    }
}
