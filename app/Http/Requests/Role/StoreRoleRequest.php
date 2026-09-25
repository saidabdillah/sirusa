<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')],
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
