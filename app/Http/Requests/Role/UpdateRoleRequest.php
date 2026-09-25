<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('role')->name !== 'super_admin';
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($this->route('role')->id)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama role harus diisi',
            'name.unique' => 'Role dengan nama tersebut sudah ada',
        ];
    }
}
