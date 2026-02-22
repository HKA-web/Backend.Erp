<?php

namespace Modules\Authentication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $status = $this->input('status');
        $allowed = in_array($status, ['EDIT', 'DELETE']);

        return [
            'user_id'   => 'required|string',
            'user_name' => $allowed ? 'nullable|string' : 'required|string|max:255',
            'email'     => $allowed ? 'nullable|email' : 'required|email|max:255',
            'status'    => 'required|in:DRAFT,POSTED,EDIT,DELETE',
            'password'       => 'nullable|string|min:8',
            'remember_token' => 'nullable|string|max:100',
        ];
    }

    protected function prepareForValidation()
    {
        if (!$this->has('password') && $this->input('status') === 'DRAFT') {
            $this->merge([
                'password' => Hash::make('#user#'),
                'remember_token' => Str::random(10),
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'user_id.required'   => 'ID wajib diisi.',
            'user_name.required' => 'Nama pengguna wajib diisi.',
            'email.required'     => 'Alamat email wajib diisi.',
            'email.email'        => 'Format email tidak valid.',
            'status.required'    => 'Status wajib diisi.',
            'status.in'          => 'Pilihan status hanya: DRAFT, POSTED, EDIT, atau DELETE.',
        ];
    }
}
