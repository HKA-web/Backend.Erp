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
            'product_id'   => $this->product_id,
            'product_name' => $this->product_name,
        ];
    }
}