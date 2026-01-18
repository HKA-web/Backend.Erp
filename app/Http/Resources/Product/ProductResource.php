<?php

namespace App\Http\Resources\Product;

use App\Http\Resources\ExpandableResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    use ExpandableResource;

    public function toArray($request)
    {
        return [
            'product_id' => $this->product_id,
            'status'      => $this->status,
            'is_removed'  => $this->is_removed,
        ];
    }
}