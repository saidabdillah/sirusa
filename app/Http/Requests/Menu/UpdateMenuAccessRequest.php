<?php

namespace App\Http\Requests\Menu;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMenuAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'menus' => ['nullable', 'array'],
            'menus.*' => ['integer', 'exists:menus,id'],
        ];
    }
}
