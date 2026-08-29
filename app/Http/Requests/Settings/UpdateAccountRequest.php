<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['sometimes', 'nullable', 'email:rfc,dns', 'max:255', Rule::unique('users', 'email')->ignore($this->user()->id)],
            'password' => 'sometimes|nullable|min:8|max:255|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'email.email' => 'Email tidak valid',
            'email.unique' => 'Email sudah terdaftar',
            'email.max' => 'Email maksimal 255 karakter',
            'password.min' => 'Kata sandi minimal 8 karakter',
            'password.max' => 'Kata sandi maksimal 255 karakter',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok',
        ];
    }
}
