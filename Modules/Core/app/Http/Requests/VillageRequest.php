<?php

namespace Modules\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VillageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'village_id'   => 'required|string|max:10',
            'village_name' => 'required|string|max:255',
            'status'       => 'required|in:DRAFT,POSTED',
        ];
    }

    public function messages(): array
    {
        return [
            'village_id.required'   => 'ID Desa wajib diisi.',
            'village_name.required' => 'Nama Desa tidak boleh kosong.',
            'status.in'             => 'Status harus DRAFT atau POSTED.',
        ];
    }
}
