<?php

namespace App\Http\Requests\Scholarship;

use Illuminate\Foundation\Http\FormRequest;

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
            'kampus' => 'required|string|max:255',
            'kuota' => 'required|integer|min:0',
            'tingkat_gelar' => 'required|in:S1,S2,S3',
            'cakupan' => 'required|in:penuh,sebagian',
            'batas_waktu' => 'required|date|after:today',
            'deskripsi' => 'required|string',
            'persyaratan' => 'nullable|string',
            'status' => 'required|in:aktif,non-aktif',
            'fakultas' => 'required|array|min:1',
            'fakultas.*.nama' => 'required|string|max:255',
            'fakultas.*.prodi' => 'required|array|min:1',
            'fakultas.*.prodi.*.nama' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama beasiswa harus diisi',
            'kampus.required' => 'Kampus tujuan harus diisi',
            'kuota.required' => 'Kuota harus diisi',
            'kuota.integer' => 'Kuota harus berupa angka',
            'kuota.min' => 'Kuota minimal 0',
            'tingkat_gelar.required' => 'Tingkat gelar harus dipilih',
            'tingkat_gelar.in' => 'Tingkat gelar tidak valid',
            'cakupan.required' => 'Cakupan harus dipilih',
            'cakupan.in' => 'Cakupan tidak valid',
            'batas_waktu.required' => 'Batas waktu harus diisi',
            'batas_waktu.after' => 'Batas waktu harus setelah hari ini',
            'deskripsi.required' => 'Deskripsi harus diisi',
            'status.required' => 'Status harus dipilih',
            'fakultas.required' => 'Minimal harus ada 1 fakultas',
            'fakultas.*.nama.required' => 'Nama fakultas harus diisi',
            'fakultas.*.prodi.required' => 'Minimal harus ada 1 program studi per fakultas',
            'fakultas.*.prodi.*.nama.required' => 'Nama program studi harus diisi',
        ];
    }
}
