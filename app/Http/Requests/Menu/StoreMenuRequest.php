<?php

namespace App\Http\Requests\Menu;

use App\Models\Menu;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Fluent;
use Illuminate\Validation\Rule;

class StoreMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * `parent_id` menerima DUA bentuk sekaligus karena select-nya memakai
     * Select2 `tags`:
     *
     * - angka yang menunjuk menu yang sudah ada -> langsung dipakai sebagai induk;
     * - teks apa pun (termasuk angka yang tidak menunjuk menu mana pun)
     *   -> nama menu induk BARU yang dibuat `MenuController::resolveParentId()`.
     *
     * Konsekuensinya mengetik "2024" membuat grup berlabel "2024", bukan error
     * "id tidak ditemukan". Disengaja: jalur UI tidak pernah bisa mengirim id
     * yang tidak ada (hanya opsi hasil query yang ditawarkan select), jadi angka
     * yang tidak dikenal berarti memang yang diketik admin.
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
                // Catatan `min` Laravel: nilai numerik dibandingkan sebagai
                // angka, bukan panjang karakter -- "2024" lolos sebagai angka.
                Rule::when(
                    fn (Fluent $data) => ! Menu::isExistingMenuId($data->parent_id ?? null),
                    ['min:2'],
                ),
            ],
            'label' => ['required', 'string', 'max:100'],
            'icon' => ['nullable', 'string', 'max:100'],
            // `route`/`scope` BUKAN field form: superadmin hanya membuat menu
            // untuk tampil di sidebar, sedangkan arah link-nya diisi developer
            // lewat koding (`MenuSeeder`/`tinker`). Karena tidak divalidasi,
            // `validated()` tidak memuat keduanya -- request yang tetap
            // mengirimnya akan diabaikan dan kolomnya lahir NULL sampai
            // diisi di koding.
            // `Rule::in(Menu::sections())` DIHAPUS: admin boleh membuat section
            // baru langsung dari form (Select2 `tags`). `Menu::sections()` kini
            // turunan default + isi database, jadi section baru langsung tampil
            // di sidebar tanpa edit kode.
            'section' => ['required', 'string', 'max:100'],
            'urutan' => ['required', 'integer', 'min:0'],
            // `aktif`/`wajib` sengaja TIDAK divalidasi: form-nya sudah dihapus,
            // jadi nilai itu tidak pernah dikirim. `store()` menulis defaultnya
            // sendiri (`aktif=true`, `wajib=false`) supaya atribut massal dari
            // request tidak bisa menyetel keduanya lewat endpoint ini.
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
