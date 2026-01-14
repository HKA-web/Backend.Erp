<?php

namespace App\Http\Resources\Order;

use App\Http\Resources\ExpandableResource;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    use ExpandableResource;

    public function toArray($request)
    {
        return [
            'order_id' => $this->order_id,
            'status'      => $this->status,
        ];
    }
}