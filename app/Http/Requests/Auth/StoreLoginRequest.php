<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login' => 'required|string|max:255',
            'password' => 'required|min:8|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'login.required' => 'Email atau username harus diisi',
            'login.max' => 'Email atau username terlalu panjang',
            'password.required' => 'Kata sandi harus diisi',
            'password.min' => 'Kata sandi minimal 8 karakter',
        ];
    }
}
