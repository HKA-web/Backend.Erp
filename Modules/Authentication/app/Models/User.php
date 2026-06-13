<?php

namespace Modules\Authentication\Models;

use App\Traits\SerializableDate;
use App\Traits\SoftDelete;
use App\Models\Scopes\ActiveOnlyScope;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Authentication\Database\Factories\UserFactory;
use Spatie\Permission\Traits\HasRoles;

#[Table(name: 'authentication.user', key: 'user_id', keyType: 'string', incrementing: false)]
#[Fillable('user_id', 'user_name', 'email', 'password', 'properties')]
#[Hidden('password', 'remember_token')]
#[ScopedBy([ActiveOnlyScope::class])]
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SerializableDate, SoftDelete;

    protected $connection = 'pgsql';

    protected $guard_name = 'api';

    public function getAuthIdentifier()
    {
        return $this->user_id;
    }

    protected static function newFactory()
    {
        return UserFactory::new();
    }

    #[\Override]

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'email_verified_at' => 'datetime',
        ];
    }
}
