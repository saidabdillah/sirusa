<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('super_admin');
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:aktif,non-aktif',
            'peran' => 'nullable|in:super_admin,admin,user',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status harus dipilih',
            'status.in' => 'Status tidak valid',
            'peran.in' => 'Peran tidak valid',
        ];
    }
}
