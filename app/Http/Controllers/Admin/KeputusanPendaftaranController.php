<?php

namespace App\Http\Controllers\Admin;

use App\Exports\KeputusanExport;
use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\KeputusanPendaftaranRequest;
use App\Models\Applicant;
use App\Models\Scholarship;
use App\Models\User;
use App\Support\ExcelDownload;
use App\Support\VerifikasiAntrean;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            // Catatan tidak lagi dikumpulkan dari tahap Kesra (permintaan
            // pengguna), jadi keputusan baru tidak menulis `pendaftar.catatan`.
            'catatan' => null,
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
     * Daftar pendaftaran yang sudah diputuskan Kesra -- disetujui dan ditolak.
     *
     * Antrean di `admin.kesra.index` defaultnya hanya menampilkan yang menunggu
     * putusan, jadi keputusan yang sudah diambil tenggelam di balik filter. Halaman
     * ini menaruhnya di layar terpisah supaya arsip "sudah selesai" bisa dibaca
     * tanpa mengubah antrean kerja, dan supaya keputusan yang keliru punya satu
     * tempat jelas untuk dibatalkan lewat aksi Hapus. Kolom Status membedakan
     * hasil tiap baris; Hapus hanya tersedia untuk yang `diterima`, karena
     * `batalkan()` menolak baris `ditolak`.
     */
    public function disetujui(): View
    {
        abort_unless(auth()->user()->hasMenuAccess('admin.kesra.index'), 403);

        return view('admin.verifikasi.kesra-disetujui', [
            'pendaftaran' => (new VerifikasiAntrean('kesra'))->pendaftaran(['diterima', 'ditolak']),
        ]);
    }

    /**
     * Unduhan Excel daftar keputusan Kesra (disetujui dan ditolak).
     *
     * Membaca koleksi yang sama dengan halaman Keputusan, jadi unduhan tidak
     * mungkin menyimpang dari daftar di layar. Route-nya bernama
     * `admin.kesra.disetujui.export` dan scope menu `admin.kesra.disetujui`
     * menutupinya lewat `menuScopeCovers()`, jadi tidak ada pengecualian
     * otorisasi baru.
     */
    public function disetujuiExport(): StreamedResponse
    {
        abort_unless(auth()->user()->hasMenuAccess('admin.kesra.disetujui.export'), 403);

        $export = new KeputusanExport((new VerifikasiAntrean('kesra'))->pendaftaran(['diterima', 'ditolak']));

        return ExcelDownload::response($export->toSpreadsheet(), $export->fileName());
    }

    /**
     * Membatalkan keputusan "disetujui" dan mengembalikan pendaftaran ke antrean.
     *
     * Hapus di sini bukan menghapus baris: pendaftaran mahasiswa tetap ada dan
     * kembali ke status `verifikasi`, sehingga slot kuota yang tadi terpakai ikut
     * dilepas dan keputusan bisa diambil ulang. Karena itu aksi ini hanya berlaku
     * untuk baris `diterima` -- baris `ditolak` masih memakai form keputusan yang
     * sama seperti biasa, dan membuang catatannya lewat sini hanya menambah
     * jalur tulis kedua untuk satu keputusan.
     */
    public function batalkan(Request $request, User $user, Applicant $applicant): RedirectResponse|JsonResponse
    {
        abort_unless(auth()->user()->hasMenuAccess('admin.kesra.index'), 403);

        if ($applicant->user_id !== $user->id) {
            abort(404);
        }

        if ($applicant->status !== 'diterima') {
            return $this->ajaxFail(
                $request,
                'Hanya pendaftaran yang disetujui yang bisa dihapus dari daftar ini.',
                route('admin.kesra.disetujui'),
            );
        }

        DB::transaction(function () use ($applicant, $user) {
            $applicant->update([
                'status' => Applicant::PENDING_STATUS,
                'catatan' => null,
                'diputuskan_at' => null,
                'diputuskan_oleh' => null,
            ]);

            // `verif_kesra` hanya menyatakan "mahasiswa ini pernah diputuskan",
            // jadi nilainya diturunkan ulang dari baris yang tersisa, bukan
            // di-reset buta: satu mahasiswa bisa punya beberapa pendaftaran, dan
            // menghapus satu tidak boleh membuka kembali tahap Kesra selama
            // pendaftaran lain masih menyimpan keputusannya.
            $masihAdaKeputusan = $user->applicants()
                ->whereKeyNot($applicant->getKey())
                ->whereIn('status', ['diterima', 'ditolak'])
                ->exists();

            $user->profile?->forceFill(['verif_kesra' => $masihAdaKeputusan ? 'setuju' : 'menunggu'])->save();
        });

        return $this->ajaxOk(
            $request,
            "Pendaftaran Beasiswa {$applicant->beasiswa?->nama} dihapus dari daftar disetujui dan dikembalikan ke antrean putusan.",
            route('admin.kesra.disetujui'),
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
