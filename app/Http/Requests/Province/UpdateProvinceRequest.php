<?php

namespace App\Http\Requests\Province;

use App\Http\Requests\BaseFormRequest;

class UpdateProvinceRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'province_name' => 'sometimes|required|string|max:255',
        ];
    }
}