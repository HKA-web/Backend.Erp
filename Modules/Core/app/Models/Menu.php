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
use Modules\Core\Database\Factories\MenuFactory;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Traits\HasRoles;
use Modules\Authentication\Models\User;

#[Table(name: 'core.menu', key: 'menu_id', keyType: 'string', incrementing: false)]
#[ScopedBy([ActiveOnlyScope::class])]
class Menu extends Model
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable, Searchable, SerializableDate, SoftDelete;

    protected $guard_name = 'api';

    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray(): array
    {
        return [
            'menu_id' => $this->menu_id,
            'menu_name' => $this->menu_name,
        ];
    }

    protected static function newFactory()
    {
        return MenuFactory::new();
    }

    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id', 'menu_id');
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class, 'permission_id', 'id');
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
