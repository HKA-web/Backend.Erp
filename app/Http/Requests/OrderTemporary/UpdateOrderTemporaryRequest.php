<?php

namespace App\Http\Requests\OrderTemporary;

use App\Http\Requests\BaseFormRequest;

class UpdateOrderTemporaryRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'order_id' => 'nullable',
            'status' => 'nullable',
        ];
    }
}