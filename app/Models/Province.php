<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Province extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $connection = 'erpro';
    protected $table = 'core.province';
    protected $primaryKey = 'province_id';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'province_id',
        'province_name',
    ];

    public function companys()
    {
        return $this->hasMany(Company::class, 'province_id', 'province_id');
    }
}
