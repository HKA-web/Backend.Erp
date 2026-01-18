<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\BaseFormRequest;

class UpdateProductRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'status'    => 'sometimes|required|string|max:50',
            'is_removed'=> 'nullable|boolean',
        ];
    }
}