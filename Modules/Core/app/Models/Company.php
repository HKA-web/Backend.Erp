<?php

namespace Modules\Core\Models;

use App\Models\Scopes\ActiveOnlyScope;
use App\Traits\SerializableDate;
use App\Traits\SoftDelete;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Core\Database\Factories\CompanyFactory;
use Spatie\Permission\Traits\HasRoles;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Models\Tenant;

/**
 * Model Company yang merepresentasikan Tenant dalam sistem SaaS Multi-Tenancy
 * 
 * Class ini di-extend dari Stancl Tenant dan mengimplementasikan HasDatabase & HasDomains.
 * Dengan cara ini, setiap Company merepresentasikan satu tenant yang memiliki:
 * - Database fisik terpisah di PostgreSQL (db_delta_tech_com, db_budi_co_id, dll)
 * - Multiple domain/website untuk akses tenant (delta-tech.saasmu.com, delta-tech.com, dll)
 * 
 * @property string $company_id Primary Key (string, bukan auto-increment)
 * @property string $website Unique identifier yang di-transform menjadi tenant_id (db_xxx_xxx)
 * @property string $company_name Nama perusahaan/tenant
 * @property string $email Email tenant
 * @property string $phone Nomor telepon
 * @property string $address Alamat
 * @property string $province_id Foreign key ke tabel core.province
 * @property string $city_id Foreign key ke tabel core.city
 * @property string $district_id Foreign key ke tabel core.district
 * @property string $village_id Foreign key ke tabel core.village
 */
#[Table(name: 'core.company', key: 'company_id', keyType: 'string', incrementing: false)]
#[ScopedBy([ActiveOnlyScope::class])]
class Company extends Tenant implements TenantWithDatabase
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SerializableDate, SoftDelete;

    // Implementasi Tenancy trait untuk handle database
    use HasDatabase;

    /**
     * Connection yang digunakan untuk model ini (database central)
     * Model ini hidup di database central, bukan di database tenant
     */
    protected $connection = 'pgsql';

    /**
     * Guard name untuk authorization
     */
    protected $guard_name = 'api';

    /**
     * Kolom yang boleh diisi secara mass assignment
     */
    protected $fillable = [
        'company_id',
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

    /**
     * Factory untuk generate test data
     */
    protected static function newFactory()
    {
        return CompanyFactory::new();
    }

    /**
     * OVERRIDE: Disable default Stancl Tenancy behavior yang menyimpan data di kolom 'data'
     *
     * Default behavior Stancl Tenancy adalah menyimpan semua property tenant di kolom JSON 'data'.
     * Tapi schema kita menggunakan kolom actual (company_id, company_name, dll), bukan JSON.
     *
     * Method ini override untuk return empty array agar tidak ada data yang disimpan di kolom 'data'.
     */
    public function getTenantData(): array
    {
        return [];
    }

    /**
     * OVERRIDE: Disable Stancl Tenancy dari mengisi kolom 'data' saat save
     *
     * Default behavior: Stancl Tenancy akan mengisi kolom 'data' dengan JSON dari semua fillable fields.
     * Kita disable ini karena schema kita menggunakan kolom actual, bukan kolom 'data'.
     */
    protected function setTenantData(array $data): void
    {
        // Do nothing - kita tidak menggunakan kolom 'data'
    }

    /**
     * OVERRIDE: Gunakan company_id sebagai tenant identifier
     *
     * Method ini override default behavior stancl/tenancy.
     * Tenant ID akan langsung menggunakan kolom 'company_id' (UUID).
     *
     * @return string Tenant ID (company_id)
     */
    public function getTenantKey()
    {
        return $this->company_id;
    }


    /**
     * OVERRIDE: Method untuk mendefinisikan kolom mana yang digunakan sebagai tenant identifier
     *
     * Stancl/tenancy default menggunakan kolom 'id' sebagai tenant identifier.
     * Kita menggunakan kolom 'company_id' sebagai tenant identifier.
     *
     * @return string Nama kolom yang digunakan sebagai unique identifier tenant
     */
    public function getTenantKeyName(): string
    {
        return 'company_id';
    }

    /**
     * RELATIONSHIPS
     */

    /**
     * Relasi ke tabel core.province
     * Setiap company memiliki satu province (provinsi/negara bagian tempat berdomisili)
     */
    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id', 'province_id');
    }

    /**
     * Relasi ke tabel core.city
     * Setiap company memiliki satu city (kota tempat berdomisili)
     */
    public function city()
    {
        return $this->belongsTo(City::class, 'city_id', 'city_id');
    }

    /**
     * Relasi ke tabel core.district
     * Setiap company memiliki satu district (kecamatan tempat berdomisili)
     */
    public function district()
    {
        return $this->belongsTo(District::class, 'district_id', 'district_id');
    }

    /**
     * Relasi ke tabel core.village
     * Setiap company memiliki satu village (desa/kelurahan tempat berdomisili)
     */
    public function village()
    {
        return $this->belongsTo(Village::class, 'village_id', 'village_id');
    }
}
