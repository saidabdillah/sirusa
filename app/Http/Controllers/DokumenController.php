<?php

namespace App\Http\Controllers;

use App\Models\DokumenAkademik;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Penyaji berkas dokumen.
 *
 * Sebelum ini penyajiannya ada di dalam `routes/web.php` dan melayani berkas
 * APA PUN di disk publik kepada siapa pun yang sudah login -- tanpa memeriksa
 * apakah berkas itu memang miliknya. Siapa pun yang tahu (atau menebak) nama
 * berkasnya bisa membaca KTP, KK, dan transkrip mahasiswa lain.
 *
 * Aturannya sekarang: mahasiswa hanya boleh membaca berkasnya sendiri,
 * sedangkan petugas (Catpil/Kampus/Kesra/Super Admin) boleh membaca berkas
 * yang memang jadi tugasnya memverifikasi. Hak akses peran tetap dijaga
 * middleware menu/aksi; controller ini menjaga KEPEMILIKAN berkas.
 */
class DokumenController extends Controller
{
    /**
     * Peran yang memang bertugas memeriksa dokumen mahasiswa.
     *
     * @var list<string>
     */
    private const PERAN_VERIFIKATOR = ['super_admin', 'catpil', 'kampus', 'kesra'];

    public function show(Request $request, string $path): BinaryFileResponse
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            abort(404);
        }

        if (! $this->bolehBaca($request, $path)) {
            // 403, bukan 404: berkasnya memang ada, tapi bukan hak pemanggil.
            abort(403);
        }

        return response()->file($disk->path($path), [
            'Content-Type' => mime_content_type($disk->path($path)),
            // Sengaja TIDAK lagi `public`: berkas ini pribadi, jadi jangan
            // sampai di-cache proxy/CDN lalu tersaji ke orang lain.
            'Cache-Control' => 'private, max-age=0, no-store',
        ]);
    }

    /**
     * Boleh tidak pemanggil membaca berkas ini.
     *
     * Berkas profil disimpan dengan awalan `profil/<user_id>/...`, jadi pemiliknya
     * bisa dibaca dari path. Pemeriksaannya sengaja berbasis path itu, bukan
     * sekadar "punya peran", supaya petugas pun tidak otomatis bisa membaca
     * berkas yang bukan urusannya.
     */
    private function bolehBaca(Request $request, string $path): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        $pemilikId = $this->pemilikDariPath($path);

        // Path di luar pola `profil/<id>/...` (mis. berkas yayasan/umum) TIDAK
        // dilayani di sini: tidak ada cara memastikan kepemilikannya, jadi
        // jawabannya tolak. Berkas seperti itu harus disajikan lewat jalur
        // sendiri yang memang publik.
        if ($pemilikId === null) {
            return false;
        }

        // Pemilik berkas selalu boleh membaca berkasnya sendiri.
        if ((int) $user->id === $pemilikId) {
            return true;
        }

        // Petugas verifikator boleh membaca berkas mahasiswa yang ada di
        // antreannya. Untuk `kampus`, cakupannya dibatasi kampusnya sendiri --
        // sama seperti `VerifikasiController::diLuarKampus()`.
        if ($user->hasRole('super_admin') || $user->hasRole('kesra') || $user->hasRole('catpil')) {
            return true;
        }

        if ($user->hasRole('kampus')) {
            $profil = UserProfile::query()->where('user_id', $pemilikId)->first();

            return $profil !== null
                && $profil->prodi?->fakultas?->kampus_id === $user->kampus_id;
        }

        return false;
    }

    /**
     * Ambil id pemilik dari path berkas, atau null kalau polanya tidak dikenal.
     *
     * Format yang dipakai aplikasi:
     *   `profil/<user_id>/<berkas>`
     *   `profil/<user_id>/akademik/<berkas>`  (KRS/KHS per semester)
     */
    private function pemilikDariPath(string $path): ?int
    {
        if (preg_match('#^profil/(\d+)/#', $path, $cocok) !== 1) {
            return null;
        }

        return (int) $cocok[1];
    }
}
