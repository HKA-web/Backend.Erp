<?php

namespace Modules\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $allowed = in_array($this->input('status'), ['EDIT','DELETE']);

        return [
            'city_id'   => $allowed ? 'nullable|string' : 'required|string',
            'city_name' => $allowed ? 'nullable|string' : 'required|string|max:255',
            'status'    => 'required|in:DRAFT,POSTED,EDIT,DELETE',
        ];
    }

    public function messages(): array
    {
        return [
            'city_id.required'   => 'Id field is required.',
            'city_name.required' => 'Name field is required.',
            'status.in' => 'Status choice DRAFT,POSTED,EDIT,DELETE',
        ];
    }
}
