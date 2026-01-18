<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\BaseFormRequest;

class StoreProductRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'product_id' => 'required|string|max:255',
            'status'      => 'nullable|string|max:50',
            'is_removed'  => 'nullable|boolean',
        ];
    }
}