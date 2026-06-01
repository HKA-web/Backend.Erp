<?php

namespace Modules\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $isPost = $this->isMethod('post');

        return [
            'option_id' => $isPost ? 'required|string' : 'nullable|string',
            'option_name' => $isPost ? 'required|string|max:255' : 'nullable|string|max:255',
            'key' => 'nullable|string|max:255',
            'value' => 'nullable|string',
            'is_removed' => 'nullable|boolean',
        ];
    }

    /**
     * Prepare the data for validation by casting is_removed to boolean.
     */
    protected function prepareForValidation(): void
    {
        // Convert is_removed to proper boolean format
        if ($this->has('is_removed')) {
            $value = $this->get('is_removed');
            
            if (is_string($value)) {
                // Convert string "true"/"false" to boolean
                $this->merge([
                    'is_removed' => $value === 'true' || $value === '1' ? true : false
                ]);
            } elseif (is_numeric($value)) {
                // Convert numeric 0/1 to boolean
                $this->merge([
                    'is_removed' => (bool)$value
                ]);
            }
        } else {
            // Default is_removed to false if not provided
            $this->merge([
                'is_removed' => false
            ]);
        }
    }

    /**
     * Custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'option_id.required' => 'The Option ID is required to identify the resource.',
            'option_name.required' => 'The Option name cannot be empty.',
            'option_name.max' => 'The name is too long (max 255 characters).',
        ];
    }
}
