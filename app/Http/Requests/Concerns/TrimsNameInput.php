<?php

namespace App\Http\Requests\Concerns;

/**
 * Memangkas spasi di ujung field nama sebelum validasi.
 *
 * Dua celah yang ditutup:
 *  1. `required` hanya menolak `null`, string kosong, dan array kosong. String
 *     yang isinya cuma spasi (`"   "`) lolos sebagai "terisi", jadi tanpa ini
 *     pengguna bisa membuat fakultas bernama spasi yang tidak terlihat di
 *     sidebar tapi memblokir nama aslinya selamanya lewat unique index.
 *  2. Pengecekan duplikat di `after()` memakai nilai yang belum dipangkas,
 *     sehingga `"  Teknik  "` tidak dianggap bentrok dengan `"Teknik"` yang
 *     sudah ada.
 *
 * Dipangkas sebelum validasi, bukan di dalam `after()`, supaya aturan
 * `required` dan `unique` ikut melihat nilai yang bersih.
 */
trait TrimsNameInput
{
    /**
     * Pangkas setiap entri string, pertahankan kunci array (dipakai untuk
     *untuk `nama.0`, `nama.1`, ... pada input banyak-baris) dan nilai
     * non-string apa adanya supaya aturan `string` tetap menolak mereka.
     *
     * @param  string|array<int, mixed>|null  $value
     * @return string|array<int, mixed>|null
     */
    protected function trimSederhana(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(
                static fn (mixed $item) => is_string($item) ? trim($item) : $item,
                $value
            );
        }

        return is_string($value) ? trim($value) : $value;
    }
}
