<?php

namespace App\Http\Requests\Menu;

use App\Models\Menu;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Fluent;
use Illuminate\Validation\Rule;

class UpdateMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * `parent_id` menerima id menu yang sudah ada ATAU nama menu induk BARU
     * (Select2 `tags` di modal Ubah) -- lihat catatan di `StoreMenuRequest`.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'parent_id' => [
                'nullable',
                'string',
                'max:100',
                // `min:2` hanya untuk nama BARU: id menu yang sudah ada boleh
                // berupa satu digit, jadi harus dikecualikan lewat kondisi.
                Rule::when(
                    fn (Fluent $data) => ! Menu::isExistingMenuId($data->parent_id ?? null),
                    ['min:2'],
                ),
                function ($attribute, $value, $fail) {
                    $menu = $this->route('menu');

                    if (Menu::isExistingMenuId($value) && $menu && (int) $value === (int) $menu->id) {
                        $fail('Menu tidak dapat dijadikan induk bagi dirinya sendiri.');
                    }
                },
            ],
            'label' => ['required', 'string', 'max:100'],
            'icon' => ['nullable', 'string', 'max:100'],
            // `route`/`scope` BUKAN field form: superadmin hanya membuat menu
            // tampilan, arah link-nya diisi developer lewat koding
            // (`MenuSeeder`/`tinker`). Karena tidak divalidasi, `validated()`
            // tidak pernah memuat keduanya sehingga `update()` selalu
            // mempertahankan nilai tersimpan -- request yang tetap mengirimnya
            // akan diabaikan.
            // `Rule::in(Menu::sections())` DIHAPUS: section baru boleh diketik
            // langsung (Select2 `tags`) dan `Menu::sections()` kini menurunkan
            // daftarnya dari isi tabel `menus`.
            'section' => ['required', 'string', 'max:100'],
            'urutan' => ['required', 'integer', 'min:0'],
            // `aktif`/`wajib` TIDAK punya rule dan tidak pernah masuk `validated()`
            // -- `update()` sengaja tidak menyentuh keduanya supaya status lama
            // (mis. menu `wajib` yang memang harus tetap `aktif`) tidak tertimpa
            // oleh request yang tidak lagi membawa kedua kolom itu.
        ];
    }

    protected function prepareForValidation(): void
    {
        $bersih = [];

        foreach (['parent_id', 'label', 'section'] as $kunci) {
            if (is_scalar($this->input($kunci))) {
                $bersih[$kunci] = trim((string) $this->input($kunci));
            }
        }

        if ($bersih !== []) {
            $this->merge($bersih);
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'label.required' => 'Label menu harus diisi.',
            'label.max' => 'Label menu maksimal 100 karakter.',
            'section.required' => 'Section menu harus diisi.',
            'section.max' => 'Section menu maksimal 100 karakter.',
            'urutan.required' => 'Urutan menu harus diisi.',
            'urutan.integer' => 'Urutan menu harus berupa angka.',
            'parent_id.max' => 'Nama menu induk maksimal 100 karakter.',
            'parent_id.min' => 'Nama menu induk baru minimal 2 karakter.',
        ];
    }
}
