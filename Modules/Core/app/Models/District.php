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
use Laravel\Scout\Searchable;
use Modules\Core\Database\Factories\DistrictFactory;
use Spatie\Permission\Traits\HasRoles;
use Modules\Authentication\Models\User;

#[Table(name: 'core.district', key: 'district_id', keyType: 'string', incrementing: false)]
#[ScopedBy([ActiveOnlyScope::class])]
class District extends Model
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable, Searchable, SerializableDate, SoftDelete;

    protected $guard_name = 'api';

    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray(): array
    {
        return [
            'district_id' => $this->district_id,
            'district_name' => $this->district_name,
        ];
    }

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

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'user_id');
    }
}
