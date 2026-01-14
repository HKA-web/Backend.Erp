<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\BaseFormRequest;

class StoreOrderRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'order_id' => 'required|string|max:255',
            'status'      => 'nullable|string|max:50',
        ];
    }
}