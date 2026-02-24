<?php

namespace Modules\Core\Models;

use App\Traits\BaseModel;
use App\Traits\SerializableDate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Core\Database\Factories\DictionaryFactory;
use Spatie\Permission\Traits\HasRoles;

class Dictionary extends Model
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles, SerializableDate, BaseModel;

    protected $connection = 'pgsql';
    protected $table = 'core.dictionary';

    protected $primaryKey = 'dictionary_id';
    protected $guard_name = 'api';

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function newFactory()
    {
        return DictionaryFactory::new();
    }
}
