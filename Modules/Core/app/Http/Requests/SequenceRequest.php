<?php

namespace Modules\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SequenceRequest extends FormRequest
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
            'sequence_id'    => $isPost ? 'required|string' : 'nullable|string',
            'sequence_name'  => $isPost ? 'required|string|max:255' : 'nullable|string|max:255',
            'prefix'         => 'nullable|string|max:255',
            'suffix'         => 'nullable|string|max:255',
            'padding'        => 'nullable|integer|min:1|max:10',
            'current_number' => 'nullable|integer|min:0',
            'reset_type'     => 'nullable|string|in:NONE,YEARLY,MONTHLY,DAILY',
            // Kita tetap jaga is_removed untuk soft-delete logic di level draft
            'is_removed'     => 'nullable|boolean',
        ];
    }

    /**
     * Custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'sequence_id.required'   => 'The Sequence ID is required to identify the resource.',
            'sequence_name.required' => 'The Sequence name cannot be empty.',
            'sequence_name.max'      => 'The name is too long (max 255 characters).',
        ];
    }
}
