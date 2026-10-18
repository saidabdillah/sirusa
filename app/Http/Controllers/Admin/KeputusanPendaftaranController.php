<?php

namespace App\Http\Controllers\Admin;

use App\Exports\KeputusanExport;
use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\KeputusanPendaftaranRequest;
use App\Models\Applicant;
use App\Models\Scholarship;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\ExcelDownload;
use App\Support\VerifikasiAntrean;
use Illuminate\Database\Eloquent\Collection;
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
     * Keputusan yang disimpan di sini BELUM terlihat mahasiswa: hasil seleksi
     * baru dipublikasikan lewat `umumkan()` (per beasiswa, serentak), dan pada
     * momen itulah tahap Kesra pada profil ditutup. Memisahkan keduanya
     * mencegah pendaftar A melihat hasilnya lebih dulu daripada pendaftar B
     * pada beasiswa yang sama.
     */
    public function update(KeputusanPendaftaranRequest $request, User $user, Applicant $applicant): RedirectResponse|JsonResponse
    {
        abort_unless(auth()->user()->hasMenuAccess('admin.kesra.index'), 403);

        // Gerbang Kesra: permohonan hanya boleh diputuskan setelah Catpil DAN
        // Kampus sama-sama menyetujui. Antrean di layar sudah menyaring ini,
        // tapi form/endpoint ini harus menolaknya sendiri juga -- kalau tidak,
        // siapa pun yang menyusun request manual bisa melewati tahapan.
        abort_unless($user->profile?->lolosGerbangKesra(), 403);

        if ($applicant->user_id !== $user->id) {
            abort(404);
        }

        if ($applicant->isCancelled()) {
            $this->gagal('Pendaftaran ini sudah dibatalkan mahasiswa, jadi tidak bisa diputuskan.');
        }

        // Setelah hasil diumumkan, keputusan dikunci: mahasiswa sudah membaca
        // hasilnya, jadi mengubahnya diam-diam akan membatalkan arti pengumuman
        // serentak. Perubahan hanya mungkin lewat pembatalan pengumuman.
        if ($applicant->isLockedByAnnouncement()) {
            $this->gagal('Hasil seleksi beasiswa ini sudah diumumkan, jadi keputusan tidak bisa diubah lagi.');
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

        $applicant = DB::transaction(function () use ($applicant, $attributes) {
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

            // `verif_kesra` TIDAK ditulis di sini. Tahap Kesra pada profil baru
            // dinyatakan selesai saat hasil seleksi DIUMUMKAN (`umumkan()`),
            // supaya mahasiswa tidak melihat "Verifikasi Kesra: Setuju"
            // sementara pendaftar lain pada beasiswa yang sama masih menunggu.
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
     * Halaman kerja pengumuman hasil seleksi, satu baris per beasiswa.
     *
     * Dipisah dari arsip "Keputusan" supaya mengumumkan punya menu dan tempat
     * sendiri: arsip dipakai untuk membaca/membatalkan keputusan, halaman ini
     * untuk menerbitkan hasil ke mahasiswa.
     */
    public function pengumuman(): View
    {
        abort_unless(auth()->user()->hasMenuAccess('admin.kesra.pengumuman'), 403);

        return view('admin.verifikasi.pengumuman', [
            'beasiswaRingkasan' => $this->ringkasanPengumuman(),
        ]);
    }

    /**
     * Ringkasan per beasiswa untuk tabel pengumuman.
     *
     * Dihitung dari baris `pendaftar` yang sama dengan antrean dan arsip, jadi
     * angka "belum diputuskan" di sini tidak bisa berbeda dari jumlah baris
     * berstatus Menunggu di halaman lain.
     *
     * @return Collection<int, Scholarship>
     */
    private function ringkasanPengumuman()
    {
        return Scholarship::query()
            ->whereHas('pendaftar')
            ->withCount([
                'pendaftar as total_pendaftar',
                'pendaftar as total_diputuskan' => fn ($query) => $query->whereIn('status', ['diterima', 'ditolak']),
                'pendaftar as total_menunggu' => fn ($query) => $query->where('status', Applicant::PENDING_STATUS),
            ])
            ->orderBy('nama')
            ->get();
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

        // Keputusan yang sudah diumumkan tidak bisa dibatalkan diam-diam:
        // mahasiswa sudah membaca "Diterima", jadi menariknya kembali harus
        // lewat jalur yang jelas, bukan tombol hapus di arsip.
        if ($applicant->isLockedByAnnouncement()) {
            return $this->ajaxFail(
                $request,
                'Hasil seleksi beasiswa ini sudah diumumkan, jadi keputusan tidak bisa dihapus dari daftar.',
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
     * Mengumumkan hasil seleksi satu beasiswa sekaligus.
     *
     * Inilah yang membuat hasil terasa adil: keputusan Kesra sudah tersimpan
     * sejak tiap pendaftar diputuskan, tetapi mahasiswa baru melihatnya setelah
     * aksi ini dijalankan. Satu penulisan `decision_at` untuk SEMUA pendaftar
     * beasiswa ini, jadi pendaftar A dan B yang membuka Sirusa berbarengan
     * melihat jawaban pada momen yang sama.
     *
     * Ditolak bila masih ada pendaftar `verifikasi`: mengumumkan sebagian akan
     * menciptakan lagi keadaan "A sudah tahu, B masih menggantung".
     */
    public function umumkan(Request $request, Scholarship $scholarship): RedirectResponse|JsonResponse
    {
        abort_unless(auth()->user()->hasMenuAccess('admin.kesra.index'), 403);

        if ($scholarship->result_announced_at !== null) {
            return $this->ajaxFail(
                $request,
                'Hasil seleksi Beasiswa '.$scholarship->nama.' sudah diumumkan sebelumnya.',
                route('admin.kesra.disetujui'),
            );
        }

        $belumDiputuskan = $scholarship->jumlahBelumDiputuskan();

        if ($belumDiputuskan > 0) {
            return $this->ajaxFail(
                $request,
                "Masih ada {$belumDiputuskan} pendaftar yang belum diputuskan pada Beasiswa {$scholarship->nama}. Putuskan semuanya dulu sebelum mengumumkan.",
                route('admin.kesra.disetujui'),
            );
        }

        if (! $scholarship->pendaftar()->whereIn('status', ['diterima', 'ditolak'])->exists()) {
            return $this->ajaxFail(
                $request,
                'Belum ada keputusan yang bisa diumumkan pada beasiswa ini.',
                route('admin.kesra.disetujui'),
            );
        }

        $jumlah = DB::transaction(function () use ($scholarship) {
            $sekarang = now();

            $jumlah = $scholarship->pendaftar()
                ->whereIn('status', ['diterima', 'ditolak'])
                ->whereNull('decision_at')
                ->update(['decision_at' => $sekarang]);

            $scholarship->forceFill(['result_announced_at' => $sekarang])->save();

            // Tahap Kesra pada profil baru dinyatakan selesai pada momen ini --
            // bukan saat keputusan per pendaftar diambil -- supaya kartu status
            // di dasbor berubah untuk semua pendaftar beasiswa ini sekaligus.
            UserProfile::query()
                ->whereIn('user_id', $scholarship->pendaftar()
                    ->whereIn('status', ['diterima', 'ditolak'])
                    ->select('user_id'))
                ->update(['verif_kesra' => 'setuju']);

            return $jumlah;
        });

        return $this->ajaxOk(
            $request,
            "Hasil seleksi Beasiswa {$scholarship->nama} diumumkan serentak untuk {$jumlah} pendaftar.",
            route('admin.kesra.pengumuman'),
        );
    }

    /**
     * Menarik kembali pengumuman satu beasiswa.
     *
     * Keputusan per pendaftar TIDAK dihapus -- yang dibersihkan hanya
     * `decision_at`, sehingga hasilnya kembali tersembunyi dari mahasiswa dan
     * Kesra bisa menilai ulang. `verif_kesra` pada profil juga dibuka kembali
     * untuk mahasiswa yang tidak punya keputusan terumum lain.
     *
     * Berguna saat pengumuman terlanjur ditekan padahal ada keputusan yang
     * masih keliru, atau saat kuota berubah dan hasilnya perlu disusun ulang.
     */
    public function batalkanPengumuman(Request $request, Scholarship $scholarship): RedirectResponse|JsonResponse
    {
        abort_unless(auth()->user()->hasMenuAccess('admin.kesra.pengumuman'), 403);

        if ($scholarship->result_announced_at === null) {
            return $this->ajaxFail(
                $request,
                'Hasil seleksi Beasiswa '.$scholarship->nama.' belum diumumkan.',
                route('admin.kesra.pengumuman'),
            );
        }

        $userIds = $scholarship->pendaftar()->pluck('user_id');

        DB::transaction(function () use ($scholarship, $userIds) {
            $scholarship->pendaftar()->update(['decision_at' => null]);
            $scholarship->forceFill(['result_announced_at' => null])->save();

            // Profil yang masih punya keputusan TERUMUM pada beasiswa lain tetap
            // `setuju`; hanya yang tidak punya sisa pengumuman yang dibuka lagi.
            $masihTerumum = Applicant::query()
                ->whereIn('user_id', $userIds)
                ->whereNotNull('decision_at')
                ->pluck('user_id')
                ->unique();

            UserProfile::query()
                ->whereIn('user_id', $userIds->diff($masihTerumum))
                ->update(['verif_kesra' => 'menunggu']);
        });

        return $this->ajaxOk(
            $request,
            "Pengumuman hasil seleksi Beasiswa {$scholarship->nama} dibatalkan. Hasilnya kembali tersembunyi dari mahasiswa.",
            route('admin.kesra.pengumuman'),
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
