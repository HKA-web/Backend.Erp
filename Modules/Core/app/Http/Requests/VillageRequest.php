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
        $allowed = in_array($this->input('status'), ['EDIT','DELETE']);

        return [
            'village_id'   => $allowed ? 'nullable|string' : 'required|string',
            'village_name' => $allowed ? 'nullable|string' : 'required|string|max:255',
            'status'    => 'required|in:DRAFT,POSTED,EDIT,DELETE',
        ];
    }

    public function messages(): array
    {
        return [
            'village_id.required'   => 'Id field is required.',
            'village_name.required' => 'Name field is required.',
            'status.in' => 'Status choice DRAFT,POSTED,EDIT,DELETE',
        ];
    }
}
