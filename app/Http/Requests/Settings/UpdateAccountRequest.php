<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Ganti kata sandi selalu harus lengkap: password lama untuk membuktikan
     * pemilik akun, password baru, dan konfirmasi yang sama. Sebelumnya
     * `password` berstatus `sometimes|nullable`, sehingga form kosong tetap
     * "berhasil" dan menampilkan pesan update padahal tidak ada yang berubah.
     *
     * `password_confirmation` divalidasi sendiri (bukan mengandalkan `confirmed`
     * saja) supaya pesan error-nya menempel di field konfirmasi, tempat
     * pengguna membandingkan kedua input.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'same:password'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'current_password.current_password' => 'Kata sandi saat ini salah.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Kata sandi baru minimal 8 karakter',
            'password.max' => 'Kata sandi baru maksimal 255 karakter',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password_confirmation.required' => 'Konfirmasi password wajib diisi.',
            'password_confirmation.same' => 'Konfirmasi password tidak cocok.',
        ];
    }

    /**
     * Dipakai untuk pesan validasi bawaan yang belum kita tulis manual.
     * Ketiga field sudah ada di `$dontFlash` bawaan Laravel, jadi tidak ada
     * password yang bocor ke HTML saat request non-AJAX gagal validasi.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'current_password' => 'kata sandi saat ini',
            'password' => 'kata sandi baru',
            'password_confirmation' => 'konfirmasi kata sandi baru',
        ];
    }
}
