<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\KeputusanPendaftaranRequest;
use App\Models\Applicant;
use App\Models\Scholarship;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KeputusanPendaftaranController extends Controller
{
    use RespondsToAjax;

    /**
     * Keputusan ini diambil per pendaftaran, bukan per profil.
     *
     * `verif_kesra` hanya menyatakan bahwa identitas mahasiswa sudah benar. Apakah
     * dia layak mendapat beasiswa tertentu tetap pertanyaan per baris `pendaftar`,
     * karena syarat, kuota, dan kampus tiap beasiswa berbeda-beda. Menurunkan
     * penerimaan dari `verif_kesra` akan otomatis menerima pendaftar kedua
     * setelah yang pertama ditolak — tanpa pernah ditinjau Kesra.
     *
     * Tapi tahap Kesra hanya punya satu keputusan. Aksi ini menulis status
     * pendaftaran sekaligus menutup tahap Kesra pada profil, jadi tidak ada lagi
     * langkah "verifikasi Kesra dulu, baru putuskan pendaftaran" yang bisa
     * menghasilkan dua verdict berbeda untuk satu pendaftaran. Status profil
     * ditulis di sini -- bukan diturunkan dari `pendaftar.status` -- supaya
     * profil tetap punya datanya sendiri, dan `catatan_kesra` lama tidak
     * ditimpa.
     */
    public function update(KeputusanPendaftaranRequest $request, User $user, Applicant $applicant): RedirectResponse|JsonResponse
    {
        abort_unless(auth()->user()->hasMenuAccess('admin.kesra.index'), 403);

        if ($applicant->user_id !== $user->id) {
            abort(404);
        }

        if ($applicant->isCancelled()) {
            $this->gagal('Pendaftaran ini sudah dibatalkan mahasiswa, jadi tidak bisa diputuskan.');
        }

        $data = $request->validated();
        $attributes = [
            'status' => $data['pendaftaran_status'],
            'catatan' => $data['pendaftaran_catatan'] ?? null,
            // Momen keputusan dicatat terpisah dari `updated_at`, yang ikut berubah
            // saat baris lain disentuh. Revisi keputusan lewat form yang sama akan
            // menimpa nilai ini, jadi yang tampil selalu keputusan terakhir.
            'diputuskan_at' => now(),
            'diputuskan_oleh' => auth()->id(),
        ];

        $applicant = DB::transaction(function () use ($applicant, $attributes, $user) {
            $scholarship = Scholarship::query()
                ->lockForUpdate()
                ->findOrFail($applicant->beasiswa_id);

            if ($attributes['status'] === 'diterima') {
                // Kuota dicek di dalam transaksi dengan baris beasiswa dikunci,
                // kalau tidak dua admin bisa sama-sama mengambil slot terakhir.
                // `lockForUpdate` pada hitungan juga, supaya yang dihitung adalah
                // data terbaru, bukan snapshot transaksi ini.
                $terpakai = $scholarship->pendaftar()
                    ->where('status', 'diterima')
                    // Pendaftaran ini sendiri tidak boleh dihitung: saat admin
                    // merevisi keputusan `diterima` yang sudah jadi, baris ini
                    // sudah termasuk dihitungan dan akan membuat kuota terlihat
                    // penuh padahal slot-nya memang miliknya.
                    ->whereKeyNot($applicant->getKey())
                    ->lockForUpdate()
                    ->count();

                if (max((int) $scholarship->kuota - $terpakai, 0) <= 0) {
                    $this->gagal("Kuota Beasiswa {$scholarship->nama} sudah penuh.");
                }
            }

            $applicant->update($attributes);

            // Tahap Kesra selesai bersama keputusan ini, termasuk saat hasilnya
            // ditolak: identitas sudah ditinjau, jadi tidak perlu tahap terpisah.
            $user->profile?->forceFill(['verif_kesra' => 'setuju'])->save();

            return $applicant->refresh();
        });

        return $this->ajaxOk(
            $request,
            "Pendaftaran Beasiswa {$applicant->beasiswa?->nama} berhasil di{$this->successVerb($attributes['status'])}.",
            route('admin.kesra.lihat', $user)
        );
    }

    /**
     * Error dikunci ke `pendaftaran_status` supaya tidak ikut menandai form lain
     * yang menumpang di halaman yang sama.
     */
    private function gagal(string $message): never
    {
        throw ValidationException::withMessages([
            'pendaftaran_status' => [$message],
        ]);
    }

    private function successVerb(string $status): string
    {
        return match ($status) {
            'diterima' => 'terima',
            default => 'tolak',
        };
    }
}
