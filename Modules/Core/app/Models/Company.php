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
use Modules\Core\Database\Factories\CompanyFactory;
use Spatie\Permission\Traits\HasRoles;
use Modules\Authentication\Models\User;

#[Table(name: 'core.company', key: 'company_id', keyType: 'string', incrementing: false)]
#[ScopedBy([ActiveOnlyScope::class])]
class Company extends Model
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SerializableDate, SoftDelete;

    /**
     * Guard name untuk authorization
     */
    protected $guard_name = 'api';

    /**
     * Kolom yang boleh diisi secara mass assignment
     */
    protected $fillable = [
        'company_id',
        'tenant_id',
        'company_name',
        'email',
        'phone',
        'address',
        'website',
        'province_id',
        'city_id',
        'district_id',
        'village_id',
        'properties',
        'enable',
        'readonly',
        'is_removed',
        'status',
        'created_by',
        'updated_by',
    ];

    protected static function newFactory()
    {
        return CompanyFactory::new();
    }

    public function tenant()
    {
        return $this->belongsTo(\Stancl\Tenancy\Database\Models\Tenant::class, 'tenant_id', 'id');
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

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'user_id');
    }
}
