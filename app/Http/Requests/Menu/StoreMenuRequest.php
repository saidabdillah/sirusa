<?php

namespace App\Http\Requests\Menu;

use App\Models\Menu;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', 'exists:menus,id'],
            'label' => ['required', 'string', 'max:100'],
            'icon' => ['nullable', 'string', 'max:100'],
            'route' => ['nullable', 'string', 'max:150', function ($attribute, $value, $fail) {
                if ($value && ! Route::has($value)) {
                    $fail('Nama route tidak terdaftar.');
                }
            }],
            'scope' => ['nullable', 'string', 'max:100', 'regex:/^[a-z][a-z0-9._-]*$/'],
            'section' => ['required', Rule::in(Menu::sections())],
            'urutan' => ['required', 'integer', 'min:0'],
            'aktif' => ['boolean'],
            'wajib' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->input('route') && ! $this->input('scope')) {
                $validator->errors()->add('route', 'Isi nama route atau scope.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'label.required' => 'Label menu harus diisi.',
            'section.required' => 'Section menu harus dipilih.',
            'section.in' => 'Section menu tidak valid.',
            'urutan.required' => 'Urutan menu harus diisi.',
            'urutan.integer' => 'Urutan menu harus berupa angka.',
            'scope.regex' => 'Scope harus huruf kecil, angka, titik, atau garis bawah.',
        ];
    }
}
