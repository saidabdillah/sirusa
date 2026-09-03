<?php

namespace App\Http\Requests\Scholarship;

use App\Models\Prodi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreScholarshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'nama' => 'required|string|max:255',
            'kampus_id' => 'required|integer|exists:kampus,id',
            'kuota' => 'required|integer|min:0',
            'tingkat_gelar' => 'required|in:S1,S2,S3',
            'cakupan' => 'required|in:penuh,sebagian',
            'batas_waktu' => 'required|date|after:today',
            'ipk_minimal' => 'required|numeric|between:0,4',
            'semester_minimal' => 'required|integer|between:1,14',
            'deskripsi' => 'required|string',
            'persyaratan' => 'nullable|string',
            'status' => 'required|in:aktif,non-aktif',
            'prodi_ids' => 'required|array|min:1',
            'prodi_ids.*' => 'required|integer|distinct|exists:prodi,id',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $kampusId = (int) $this->input('kampus_id');

                foreach ($this->input('prodi_ids', []) as $prodiId) {
                    $fakultas = Prodi::query()->whereKey($prodiId)->first()?->fakultas;

                    if ($fakultas && $fakultas->kampus_id !== $kampusId) {
                        $validator->errors()->add('prodi_ids', 'Semua program studi harus berada di kampus tujuan beasiswa.');
                    }
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama beasiswa harus diisi',
            'kampus_id.required' => 'Kampus tujuan harus dipilih',
            'kampus_id.exists' => 'Kampus tujuan tidak valid',
            'kuota.required' => 'Kuota harus diisi',
            'kuota.integer' => 'Kuota harus berupa angka',
            'kuota.min' => 'Kuota minimal 0',
            'tingkat_gelar.required' => 'Tingkat gelar harus dipilih',
            'tingkat_gelar.in' => 'Tingkat gelar tidak valid',
            'cakupan.required' => 'Cakupan harus dipilih',
            'cakupan.in' => 'Cakupan tidak valid',
            'batas_waktu.required' => 'Batas waktu harus diisi',
            'batas_waktu.after' => 'Batas waktu harus setelah hari ini',
            'ipk_minimal.required' => 'IPK minimal harus diisi',
            'ipk_minimal.numeric' => 'IPK minimal harus berupa angka',
            'ipk_minimal.between' => 'IPK minimal harus antara 0 hingga 4',
            'semester_minimal.required' => 'Semester minimal harus diisi',
            'semester_minimal.integer' => 'Semester minimal harus berupa angka',
            'semester_minimal.between' => 'Semester minimal harus antara 1 hingga 14',
            'deskripsi.required' => 'Deskripsi harus diisi',
            'status.required' => 'Status harus dipilih',
            'prodi_ids.required' => 'Minimal harus memilih 1 program studi',
            'prodi_ids.min' => 'Minimal harus memilih 1 program studi',
            'prodi_ids.*.exists' => 'Program studi yang dipilih tidak valid',
            'prodi_ids.*.distinct' => 'Program studi tidak boleh dipilih lebih dari sekali',
        ];
    }
}
