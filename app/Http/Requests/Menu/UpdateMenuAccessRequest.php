<?php

namespace App\Http\Requests\Menu;

use App\Support\MenuAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            // Aksi granular: `aksi[<menu_id>][]` berisi nama aksi. Hanya aksi
            // yang dikenal `MenuAction::ACTIONS` yang diterima, dan menu-nya
            // harus benar-benar ada.
            'aksi' => ['nullable', 'array'],
            'aksi.*' => ['array'],
            'aksi.*.*' => ['string', Rule::in(MenuAction::ACTIONS)],
        ];
    }

    public function messages(): array
    {
        return [
            'aksi.*.array' => 'Format aksi tidak valid.',
            'aksi.*.*.in' => 'Aksi tidak dikenal.',
        ];
    }
}
