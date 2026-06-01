<?php

namespace Modules\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DictionaryRequest extends FormRequest
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
        /**
         * Logic:
         * 1. Jika method POST (Store Draft baru), id mungkin optional jika auto-gen di DB,
         * tapi biasanya required jika UUID ditentukan dari frontend.
         * 2. Jika method PUT/PATCH (Update Draft), kita hanya validasi field yang dikirim.
         * 3. Field 'status' tidak lagi wajib dikirim dari Frontend karena SP
         * sudah tahu mana yang DRAFT dan mana yang COMMIT berdasarkan Route.
         */
        $isPost = $this->isMethod('post');

        return [
            'dictionary_id' => $isPost ? 'required|string' : 'nullable|string',
            'dictionary_name' => $isPost ? 'required|string|max:255' : 'nullable|string|max:255',
            // Kita tetap jaga is_removed untuk soft-delete logic di level draft
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
            'dictionary_id.required' => 'The Dictionary ID is required to identify the resource.',
            'dictionary_name.required' => 'The Dictionary name cannot be empty.',
            'dictionary_name.max' => 'The name is too long (max 255 characters).',
        ];
    }
}
