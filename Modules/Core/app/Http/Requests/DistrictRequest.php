<?php

namespace Modules\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DistrictRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $allowed = in_array($this->input('status'), ['EDIT','DELETE']);

        return [
            'district_id'   => $allowed ? 'nullable|string' : 'required|string',
            'district_name' => $allowed ? 'nullable|string' : 'required|string|max:255',
            'status'    => 'required|in:DRAFT,POSTED,EDIT,DELETE',
        ];
    }

    public function messages(): array
    {
        return [
            'district_id.required'   => 'Id field is required.',
            'district_name.required' => 'Name field is required.',
            'status.in' => 'Status choice DRAFT,POSTED,EDIT,DELETE',
        ];
    }
}
