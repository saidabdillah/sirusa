<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\KeputusanPendaftaranRequest;
use App\Models\Applicant;
use App\Models\Scholarship;
use App\Models\User;
use App\Notifications\ApplicationDecision;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KeputusanPendaftaranController extends Controller
{
    /**
     * Keputusan ini diambil per pendaftaran, bukan per profil.
     *
     * `verif_kesra` hanya menyatakan bahwa identitas mahasiswa sudah benar. Apakah
     * dia layak mendapat beasiswa tertentu tetap pertanyaan per baris `pendaftar`,
     * karena syarat, kuota, dan kampus tiap beasiswa berbeda-beda. Menurunkan
     * penerimaan dari `verif_kesra` akan otomatis menerima pendaftar kedua
     * setelah yang pertama ditolak — tanpa pernah ditinjau Kesra.
     */
    public function update(KeputusanPendaftaranRequest $request, User $user, Applicant $applicant): RedirectResponse
    {
        abort_unless(auth()->user()->hasMenuAccess('admin.kesra.index'), 403);

        if ($applicant->user_id !== $user->id) {
            abort(404);
        }

        if ($user->profile?->verif_kesra !== 'setuju') {
            $this->gagal('Profil mahasiswa harus disetujui pada tahap Kesra sebelum pendaftaran ini bisa diputuskan.');
        }

        if ($applicant->isCancelled()) {
            $this->gagal('Pendaftaran ini sudah dibatalkan mahasiswa, jadi tidak bisa diputuskan.');
        }

        $data = $request->validated();
        $attributes = [
            'status' => $data['pendaftaran_status'],
            'catatan' => $data['pendaftaran_catatan'] ?? null,
        ];

        if ($attributes['status'] === 'diterima') {
            // Kuota dicek di dalam transaksi dengan baris beasiswa dikunci, kalau
            // tidak dua admin bisa sama-sama mengambil slot terakhir.
            $applicant = DB::transaction(function () use ($applicant, $attributes) {
                $scholarship = Scholarship::query()
                    ->lockForUpdate()
                    ->findOrFail($applicant->beasiswa_id);

                // `lockForUpdate` pada hitungan juga, supaya yang dihitung adalah
                // data terbaru, bukan snapshot transaksi ini.
                $terpakai = $scholarship->pendaftar()
                    ->where('status', 'diterima')
                    ->lockForUpdate()
                    ->count();

                if (max((int) $scholarship->kuota - $terpakai, 0) <= 0) {
                    $this->gagal("Kuota Beasiswa {$scholarship->nama} sudah penuh.");
                }

                $applicant->update($attributes);

                return $applicant->refresh();
            });
        } else {
            $applicant->update($attributes);
        }

        $applicant->user->notify(new ApplicationDecision($applicant, $attributes['status'], $attributes['catatan']));

        return redirect()->route('admin.kesra.lihat', $user)
            ->with('success', "Pendaftaran Beasiswa {$applicant->beasiswa?->nama} berhasil di{$this->successVerb($attributes['status'])}.");
    }

    /**
     * Error dikunci ke `pendaftaran_status` supaya tidak ikut menandai form
     * verifikasi profil yang menumpang di halaman yang sama.
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
            'ditolak' => 'tolak',
            default => 'tarik kembali',
        };
    }
}
