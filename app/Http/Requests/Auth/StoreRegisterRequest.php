<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class StoreRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email:rfc,dns|unique:users,email|max:255',
            'password' => 'required|min:8|max:255',
            'password_confirmation' => 'required|same:password|min:8|max:255',
            'agree' => 'accepted',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email harus diisi',
            'email.email' => 'Email tidak valid',
            'email.unique' => 'Email sudah terdaftar',
            'password.required' => 'Kata sandi harus diisi',
            'password.min' => 'Kata sandi minimal 8 karakter',
            'password.max' => 'Kata sandi maksimal 255 karakter',
            'password_confirmation.required' => 'Konfirmasi kata sandi harus diisi',
            'password_confirmation.same' => 'Konfirmasi kata sandi tidak cocok',
            'agree.accepted' => 'Anda harus menyetujui syarat dan ketentuan',
        ];
    }
}
