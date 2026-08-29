<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => 'required|min:8|max:255|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'Kata sandi baru harus diisi',
            'password.min' => 'Kata sandi minimal 8 karakter',
            'password.max' => 'Kata sandi maksimal 255 karakter',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok',
        ];
    }
}
