<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('super_admin');
    }

    public function rules(): array
    {
        return [
            'peran' => ['required', Rule::exists('roles', 'name')],
            'username' => ['required_unless:peran,user', 'nullable', 'string', 'max:255', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required_unless:peran,user', 'min:8', 'max:255'],
            'password_confirmation' => ['required_unless:peran,user', 'same:password'],
            'status' => ['required', 'in:aktif,non-aktif'],
            'kampus_id' => [
                'nullable',
                Rule::requiredIf($this->input('peran') === 'kampus'),
                'integer',
                Rule::exists('kampus', 'id'),
            ],
            // NIK hanya wajib untuk peran user: field hanya dirender pada blok akun mahasiswa.
            'nik' => ['required_if:peran,user', 'nullable', 'string', 'max:16', Rule::unique('profil_pengguna', 'nik')],
            'nim' => ['nullable', 'string', 'min:8', 'max:255', Rule::unique('profil_pengguna', 'nim')],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required_unless' => 'Username wajib diisi untuk akun non-mahasiswa',
            'username.unique' => 'Username sudah digunakan',
            'email.email' => 'Email tidak valid',
            'email.unique' => 'Email sudah terdaftar',
            'password.required_unless' => 'Kata sandi harus diisi',
            'password.min' => 'Kata sandi minimal 8 karakter',
            'password_confirmation.required_unless' => 'Konfirmasi kata sandi harus diisi',
            'password_confirmation.same' => 'Konfirmasi kata sandi tidak cocok',
            'peran.required' => 'Peran harus dipilih',
            'peran.exists' => 'Peran tidak valid',
            'status.required' => 'Status harus dipilih',
            'status.in' => 'Status tidak valid',
            'kampus_id.required' => 'Kampus harus dipilih untuk admin kampus',
            'nik.required_if' => 'NIK wajib untuk akun mahasiswa',
            'nik.unique' => 'NIK sudah terdaftar',
            'nik.max' => 'NIK maksimal 16 karakter',
            'nim.unique' => 'NIM sudah terdaftar',
            'nim.min' => 'NIM minimal 8 karakter',
        ];
    }
}
