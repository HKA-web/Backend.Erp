<?php

namespace Modules\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id'   => 'required|string|max:10',
            'company_name' => 'required|string|max:255',
            'status'                => 'required|in:DRAFT,POSTED',
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required'   => 'ID wajib diisi.',
            'company_name.required' => 'Nama tidak boleh kosong.',
            'status.in'                      => 'Status harus DRAFT atau POSTED.',
        ];
    }
}
