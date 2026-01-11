<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Option extends Model
{
    // NOTE: unmanaged model (no migration generated, like Django managed = false)

    use HasApiTokens, HasFactory, Notifiable;

    protected $connection = 'erpro';
    protected $table = 'core.option';
    protected $primaryKey = 'option_id';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'option_id',
        'option_name',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'company_id');
    }
}
