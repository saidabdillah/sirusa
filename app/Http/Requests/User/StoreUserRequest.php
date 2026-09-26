<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->canManageUsers();
    }

    /**
     * Buang key yang bukan milik peran yang dipilih, supaya aturannya tidak pernah
     * ikut dijalankan pada nilai yang form sembunyikan.
     *
     * `toggleAkun()` di `admin/pengguna/{buat,ubah}` menyembunyikan blok per peran dengan
     * `d-none` DAN men-`disabled` isi blok itu, jadi browser memang tidak mengirim key-nya.
     * Baris di bawah menutup jalur sisanya: sisa input yang tidak terhapus, auto-fill
     * browser, atau POST yang dirakit manual.
     */
    protected function prepareForValidation(): void
    {
        $peran = $this->input('peran');

        // #akunStaf + [data-field="username"] disembunyikan untuk peran `user`; akun
        // mahasiswa selalu memakai username `usr`+acak dan kata sandi `12345678`.
        $this->removeInputUnless($peran !== 'user', ['username', 'password', 'password_confirmation']);
        // #akunMahasiswa (NIK) hanya dirender untuk peran `user`.
        $this->removeInputUnless($peran === 'user', ['nik']);
        // #akunKampus (kampus_id) hanya dirender untuk peran `kampus`.
        $this->removeInputUnless($peran === 'kampus', ['kampus_id']);
    }

    public function rules(): array
    {
        return [
            // Hanya peran kanonik yang boleh diberikan, dan hanya super_admin yang boleh
            // memberikan peran super_admin (lihat assignableRoles()).
            'peran' => ['required', Rule::in($this->user()->assignableRoles())],
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
            // NIM sengaja tidak ada di sini — mahasiswa mengisinya sendiri lewat halaman Profil.
            'nik' => ['required_if:peran,user', 'nullable', 'string', 'max:16', Rule::unique('profil_pengguna', 'nik')],
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
            'peran.in' => $this->peranInMessage(),
            'status.required' => 'Status harus dipilih',
            'status.in' => 'Status tidak valid',
            'kampus_id.required' => 'Kampus harus dipilih untuk admin kampus',
            'nik.required_if' => 'NIK wajib untuk akun mahasiswa',
            'nik.unique' => 'NIK sudah terdaftar',
            'nik.max' => 'NIK maksimal 16 karakter',
        ];
    }

    private function peranInMessage(): string
    {
        return $this->user()->hasRole('super_admin')
            ? 'Peran tidak valid'
            : 'Anda tidak dapat memberikan peran Super Admin';
    }

    /**
     * Hapus key dari input bila `$condition` tidak terpenuhi.
     *
     * `remove()` (bukan `merge([... => null])) karena `Validator::validatePresent()` hanya
     * memanggil `Arr::has()`: key bernilai null tetap dianggap "ada", sehingga aturan
     * non-implicit (`min:8`, `same:password`, `unique:...`) ikut dijalankan atas null dan
     * menggagalkan validasi. Menghapus key membuatnya persis sama dengan input `disabled`.
     */
    private function removeInputUnless(bool $condition, array $fields): void
    {
        if ($condition) {
            return;
        }

        foreach ($fields as $field) {
            $this->getInputSource()->remove($field);
        }
    }
}
