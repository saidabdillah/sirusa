<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreRegisterRequest;
use App\Models\Kampus;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Registrasi mandiri mahasiswa.
 *
 * Sebelum ini akun mahasiswa hanya bisa dibuat petugas Kampus. Sekarang
 * mahasiswa mendaftar sendiri dengan NIK + kata sandi + kampus, lalu
 * melengkapi sisanya di halaman Profil setelah akunnya diaktifkan.
 */
class RegisterController extends Controller
{
    public function daftar(): View
    {
        return view('auth.daftar', [
            'kampusList' => Kampus::orderBy('nama_kampus')->get(['id', 'nama_kampus']),
        ]);
    }

    public function simpan(StoreRegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            DB::transaction(function () use ($data): void {
                // `username` NOT NULL dan unik, sedangkan NIK sudah unik dan
                // tidak pernah berubah -- jadi NIK dipakai sebagai username.
                // Kolom `nik` sendiri tetap ada di profil, tempat login
                // membacanya.
                $user = User::create([
                    'username' => $data['nik'],
                    'password' => $data['password'],
                    // Kampus yang dipilih dicatat di akun supaya petugas tahu
                    // mahasiswa ini mendaftar lewat kampus mana. Prodi tetap
                    // dipilih mahasiswa sendiri di halaman Profil.
                    'kampus_id' => $data['kampus_id'],
                    // Mekanisme aktivasi akun yang sudah berlaku DIPERTAHANKAN:
                    // akun baru belum bisa masuk sampai Superadmin/Kesra
                    // mengaktifkannya. Tanpa ini, siapa pun bisa langsung
                    // mengajukan beasiswa.
                    'status' => 'non-aktif',
                ]);

                // Peran ditentukan server, tidak pernah dari input pengguna.
                $user->assignRole('user');

                // Baris profil dibuat dengan NIK saja: sisanya kosong sampai
                // mahasiswa melengkapinya sendiri, dan itulah yang membuat
                // login dengan NIK langsung bekerja.
                $user->profile()->create(['nik' => $data['nik']]);
            });
        } catch (\Throwable $e) {
            // Sama seperti penyimpanan profil: detail teknis ke log, layar
            // hanya diberi keterangan umum. Pesan SQL memuat NIK.
            Log::error('Gagal registrasi mahasiswa: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return back()->withInput($request->only('nik', 'kampus_id'))
                ->withErrors(['nik' => 'Pendaftaran gagal karena kesalahan pada sistem. Silakan coba lagi.']);
        }

        return redirect()->route('login')->with(
            'success',
            'Pendaftaran berhasil. Akun Anda menunggu aktivasi admin sebelum bisa digunakan.'
        );
    }
}
