<?php

namespace App\Http\Requests\Company;

use App\Http\Requests\BaseFormRequest;

class UpdateCompanyRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'company_name' => 'sometimes|required|string|max:255',
        ];
    }
}