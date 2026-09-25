<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('super_admin');
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:aktif,non-aktif'],
            'peran' => ['nullable', 'in:super_admin,capil,kampus,kesra,user'],
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
            'peran.in' => 'Peran tidak valid',
            'kampus_id.required' => 'Kampus harus dipilih untuk admin kampus',
        ];
    }
}
