<?php

namespace App\Http\Resources\OrderTemporary;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderTemporaryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'temporary_id' => $this->temporary_id,
            'order_id' => $this->order_id,
            'status' => $this->status,
            'session_id' => $this->session_id,
        ];
    }
}