<?php

namespace App\Http\Requests\Scholarship;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePengumumanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'tanggal_pengumuman' => 'nullable|date',
            'tanggal_pengumuman_selesai' => 'nullable|date',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $mulai = $this->input('tanggal_pengumuman');
                $selesai = $this->input('tanggal_pengumuman_selesai');

                if ((bool) $mulai !== (bool) $selesai) {
                    $validator->errors()->add('tanggal_pengumuman_selesai', 'Tanggal mulai dan selesai pengumuman harus diisi bersama.');

                    return;
                }

                if ($mulai && $selesai && strtotime($selesai) < strtotime($mulai)) {
                    $validator->errors()->add('tanggal_pengumuman_selesai', 'Tanggal selesai pengumuman tidak boleh sebelum tanggal mulai.');

                    return;
                }

                if ($mulai && $this->route('scholarship')?->batas_waktu) {
                    $batasWaktu = $this->route('scholarship')->batas_waktu->startOfDay();

                    if (strtotime($mulai) <= $batasWaktu->getTimestamp()) {
                        $validator->errors()->add('tanggal_pengumuman', 'Tanggal mulai pengumuman harus setelah batas waktu pendaftaran.');
                    }
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'tanggal_pengumuman.date' => 'Tanggal mulai pengumuman tidak valid',
            'tanggal_pengumuman_selesai.date' => 'Tanggal selesai pengumuman tidak valid',
        ];
    }
}
