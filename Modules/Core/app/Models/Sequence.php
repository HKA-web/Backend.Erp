<?php

namespace Modules\Core\Models;

use App\Traits\SerializableDate;
use App\Traits\SoftDelete;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Core\Database\Factories\Tenant\SequenceFactory;
use Spatie\Permission\Traits\HasRoles;

#[Table(name: 'core.sequence', key: 'sequence_id', keyType: 'string', incrementing: false)]
class Sequence extends Model
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles, SerializableDate, SoftDelete;

    public array $clearsCache = ['createdBy', 'updatedBy'];

    protected $guard_name = 'api';

    #[Override]
    protected function casts(): array
    {
        return [];
    }

    protected static function newFactory()
    {
        return SequenceFactory::new();
    }
}
