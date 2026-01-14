<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderTemporary extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';
    protected $table = 'order_temporary';
    protected $primaryKey = 'temporary_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'order_id',
        'status',
        'session_id',
    ];

    /*public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }*/
}