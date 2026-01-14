<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\BaseFormRequest;

class UpdateOrderRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'status' => 'sometimes|required|string|max:50',
        ];
    }
}