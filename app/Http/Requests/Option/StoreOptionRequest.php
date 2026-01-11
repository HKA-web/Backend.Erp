<?php

namespace App\Http\Requests\Option;

use App\Http\Requests\BaseFormRequest;

class StoreOptionRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'option_id'   => 'required|string|max:255',
            'option_name' => 'required|string|max:255',
        ];
    }
}