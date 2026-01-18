<?php

namespace App\Models;

use App\Observers\HistoryObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\SoftDelete;

class Product extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    // Uncoment with soft delete
    use SoftDelete;

    protected $connection = 'pgsql';
    protected $table = 'product';

    protected $primaryKey = 'product_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = ['is_removed',];
    protected $casts = ['is_removed' => 'boolean',];

    protected $fillable = [
        'product_id',
        'status',
        'is_removed',
    ];

    protected static function booted()
    {
        static::observe(HistoryObserver::class);
    }
}
