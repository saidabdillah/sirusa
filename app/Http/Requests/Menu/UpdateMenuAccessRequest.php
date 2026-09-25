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
            'grants' => ['nullable', 'array'],
            'grants.*' => ['array'],
            'grants.*.*' => ['integer', 'exists:menus,id'],
        ];
    }
}
