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
        $allowed = in_array($this->input('status'), ['EDIT','DELETE']);

        return [
            'company_id'   => $allowed ? 'nullable|string' : 'required|string',
            'company_name' => $allowed ? 'nullable|string' : 'required|string|max:255',
            'status'       => 'required|in:DRAFT,POSTED,EDIT,DELETE',
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required'   => 'ID wajib diisi.',
            'company_name.required' => 'Nama tidak boleh kosong.',
            'status.in'                      => 'Status harus DRAFT, POSTED, EDIT, DELETE.',
        ];
    }
}
