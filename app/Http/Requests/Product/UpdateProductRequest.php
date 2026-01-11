<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\BaseFormRequest;

class UpdateProductRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'product_name' => 'sometimes|required|string|max:255',
        ];
    }
}