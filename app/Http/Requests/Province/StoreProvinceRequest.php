<?php

namespace App\Http\Requests\Province;

use App\Http\Requests\BaseFormRequest;

class StoreProvinceRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'province_id'   => 'required|string|max:255',
            'province_name' => 'required|string|max:255',
        ];
    }
}