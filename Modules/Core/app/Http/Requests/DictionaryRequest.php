<?php

namespace Modules\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DictionaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $allowed = in_array($this->input('status'), ['EDIT','DELETE']);

        return [
            'dictionary_id'   => $allowed ? 'nullable|string' : 'required|string',
            'dictionary_name' => $allowed ? 'nullable|string' : 'required|string|max:255',
            'key'             => $allowed ? 'nullable|string' : 'required|string|max:255',
            'status'    => 'required|in:DRAFT,POSTED,EDIT,DELETE',
        ];
    }

    public function messages(): array
    {
        return [
            'dictionary_id.required'   => 'Id field is required.',
            'dictionary_name.required' => 'Name field is required.',
            'key.required'             => 'Key field is required.',
            'status.in' => 'Status choice DRAFT,POSTED,EDIT,DELETE',
        ];
    }
}
