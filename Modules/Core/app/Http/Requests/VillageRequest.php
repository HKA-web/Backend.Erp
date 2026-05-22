<?php

namespace Modules\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VillageRequest extends FormRequest
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
            'village_id' => $isPost ? 'required|string' : 'nullable|string',
            'village_name' => $isPost ? 'required|string|max:255' : 'nullable|string|max:255',
            // Kita tetap jaga is_removed untuk soft-delete logic di level draft
            'is_removed' => 'nullable|boolean',
        ];
    }

    /**
     * Custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'village_id.required' => 'The Village ID is required to identify the resource.',
            'village_name.required' => 'The Village name cannot be empty.',
            'village_name.max' => 'The name is too long (max 255 characters).',
        ];
    }
}
