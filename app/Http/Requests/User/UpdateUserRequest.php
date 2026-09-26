<?php

namespace App\Http\Requests\User;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->canManageUsers()) {
            return false;
        }

        // Hanya super_admin yang boleh menyunting akun super_admin. Dicek di sini, bukan
        // di controller, supaya responsnya 403 (bukan error validasi peran) dan konsisten
        // dengan Form->Ubah yang juga menolak halaman tersebut.
        $target = $this->route('user');

        return ! ($target instanceof User
            && $target->hasRole('super_admin')
            && ! $this->user()->hasRole('super_admin'));
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:aktif,non-aktif'],
            // `required`, bukan `nullable`: placeholder "-- Pilih Peran --" mengirim string
            // kosong yang memang harus ditolak, sama seperti halaman verifikasi.
            'peran' => ['required', Rule::in($this->user()->assignableRoles())],
            'kampus_id' => [
                'nullable',
                Rule::requiredIf($this->input('peran') === 'kampus'),
                'integer',
                Rule::exists('kampus', 'id'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status harus dipilih',
            'status.in' => 'Status tidak valid',
            'peran.required' => 'Peran harus dipilih',
            'peran.in' => $this->user()->hasRole('super_admin')
                ? 'Peran tidak valid'
                : 'Anda tidak dapat memberikan peran Super Admin',
            'kampus_id.required' => 'Kampus harus dipilih untuk admin kampus',
        ];
    }
}
