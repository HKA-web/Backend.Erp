<?php

namespace App\Http\Requests\Option;

use App\Http\Requests\BaseFormRequest;

class UpdateOptionRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'option_name' => 'sometimes|required|string|max:255',
        ];
    }
}