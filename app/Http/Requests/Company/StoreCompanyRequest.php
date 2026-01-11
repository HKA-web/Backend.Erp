<?php

namespace App\Http\Requests\Company;

use App\Http\Requests\BaseFormRequest;

class StoreCompanyRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'company_id'   => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
        ];
    }
}