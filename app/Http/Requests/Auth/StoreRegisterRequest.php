<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Registrasi mandiri mahasiswa.
 *
 * Sengaja HANYA menerima NIK, kata sandi, dan kampus. Peran tidak pernah
 * datang dari pengguna (role selalu diisi server), dan data lain dilengkapi
 * mahasiswa sendiri setelah masuk lewat halaman Profil.
 */
class StoreRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nik' => [
                'required',
                'digits:16',
                // NIK adalah identitas login mahasiswa, jadi harus unik. Pesannya
                // sengaja tidak menyebut akun mana yang memakainya.
                Rule::unique('profil_pengguna', 'nik'),
            ],
            // Aturan keamanan mengikuti `StoreUserRequest` yang sudah dipakai
            // form admin: minimal 8 karakter. `confirmed` mencocokkan
            // `password_confirmation`.
            'password' => ['required', 'confirmed', Password::min(8)],
            'kampus_id' => ['required', 'integer', Rule::exists('kampus', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'nik.required' => 'NIK harus diisi.',
            'nik.digits' => 'NIK harus terdiri dari 16 digit angka.',
            'nik.unique' => 'NIK ini sudah terdaftar. Silakan masuk atau hubungi admin.',
            'password.required' => 'Kata sandi harus diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'kampus_id.required' => 'Kampus harus dipilih.',
            'kampus_id.exists' => 'Kampus tidak valid.',
        ];
    }
}
