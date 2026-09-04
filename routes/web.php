<?php

use App\Http\Controllers\Admin\BeasiswaController;
use App\Http\Controllers\Admin\KampusController;
use App\Http\Controllers\Admin\PendaftarController;
use App\Http\Controllers\Admin\PenggunaController;
use App\Http\Controllers\Admin\PengumumanJadwalController;
use App\Http\Controllers\Admin\TemplateController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Notifications\NotificationController;
use App\Http\Controllers\Profile\ProfileController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\User\BeasiswaController as UserBeasiswaController;
use App\Http\Controllers\User\PendaftaranController;
use App\Http\Controllers\User\PengumumanController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', fn () => view('landing'))->name('landing');

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'masuk'])->name('login');
    Route::post('/masuk', [AuthController::class, 'simpanMasuk'])->name('login.store');
    Route::get('/daftar', [AuthController::class, 'daftar'])->name('register');
    Route::post('/daftar', [AuthController::class, 'simpanDaftar'])->name('register.store');

    // Forgot password with OTP
    Route::get('/lupa-kata-sandi', [AuthController::class, 'lupaKataSandi'])->name('password.request');
    Route::post('/lupa-kata-sandi', [AuthController::class, 'kirimOtp'])->name('password.otp.send');
    Route::get('/lupa-kata-sandi/verifikasi', [AuthController::class, 'verifikasiOtp'])->name('password.otp.verify');
    Route::post('/lupa-kata-sandi/verifikasi', [AuthController::class, 'cekOtp'])->name('password.otp.check');
    Route::get('/lupa-kata-sandi/reset', [AuthController::class, 'resetKataSandi'])->name('password.reset.form');
    Route::post('/lupa-kata-sandi/reset', [AuthController::class, 'simpanKataSandiBaru'])->name('password.reset.store');
});

Route::middleware(['auth', 'status.aktif'])->group(function () {
    Route::post('/keluar', [AuthController::class, 'keluar'])->name('logout');
    Route::get('/keluar/info', function () {
        return response()->json([
            'title' => 'Keluar?',
            'text' => 'Anda akan keluar dari akun SIRUSA.',
            'icon' => 'question',
            'confirmButtonText' => 'Ya, Keluar',
            'confirmButtonColor' => '#d33',
        ]);
    })->name('logout.info');
    Route::get('/dasbor', DashboardController::class)->name('dashboard');

    // Profil
    Route::get('/profil', [ProfileController::class, 'index'])->name('profile');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profil/foto', [ProfileController::class, 'destroyPhoto'])->name('profile.photo.delete');
    Route::delete('/profil/ktp/{type}', [ProfileController::class, 'destroyKtp'])->name('profile.ktp.delete');
    Route::get('/profil/foto/info', function () {
        $profile = auth()->user()->profile;
        if (! $profile?->foto_profil) {
            return response()->json([
                'title' => 'Tidak Ada Foto',
                'text' => 'Anda belum mengunggah foto profil.',
                'icon' => 'info',
                'confirmButtonText' => 'OK',
                'confirmButtonColor' => '#3085d6',
            ]);
        }

        return response()->json([
            'title' => 'Hapus Foto Profil?',
            'text' => 'Foto profil Anda akan dihapus permanen.',
            'icon' => 'warning',
            'confirmButtonText' => 'Ya, Hapus',
            'confirmButtonColor' => '#d33',
        ]);
    })->name('profile.photo.info');
    Route::get('/profil/foto/{path}', function ($path) {
        $disk = Storage::disk('local');
        if ($disk->exists($path)) {
            $file = $disk->path($path);
            $mimeType = mime_content_type($file);

            return response()->file($file, [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }
        abort(404);
    })->name('profile.photo')->where('path', '.*');

    // Pengaturan akun
    Route::get('/pengaturan', [SettingsController::class, 'index'])->name('settings');
    Route::put('/pengaturan', [SettingsController::class, 'updateAccount'])->name('settings.update');
    Route::post('/pengaturan/email/otp', [SettingsController::class, 'sendEmailOtp'])->name('settings.email.otp.send');
    Route::get('/pengaturan/email/verifikasi', [SettingsController::class, 'showEmailOtpVerify'])->name('settings.email.verify');
    Route::post('/pengaturan/email/verifikasi', [SettingsController::class, 'verifyEmailChange'])->name('settings.email.verify.store');

    // Notifikasi
    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifikasi/baca-semua', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::delete('/notifikasi', [NotificationController::class, 'destroyAll'])->name('notifications.destroy-all');
    Route::delete('/notifikasi/sudah-dibaca', [NotificationController::class, 'destroyRead'])->name('notifications.destroy-read');
    Route::delete('/notifikasi/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    // Pengumuman publik (akses semua role yang login)
    Route::get('/pengumuman/{scholarship}', [PengumumanController::class, 'show'])->name('pengumuman.show');
    Route::get('/notifikasi/{notification}', [NotificationController::class, 'show'])->name('notifications.show');

    // Admin + Super Admin routes
    Route::middleware('role:super_admin|admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/kampus', [KampusController::class, 'index'])->name('kampus.index');
        Route::get('/kampus/{kampus}/fakultas', [KampusController::class, 'fakultasIndex'])->name('kampus.fakultas.index');
        Route::get('/kampus/{kampus}/fakultas/{fakultas}/prodi', [KampusController::class, 'prodiIndex'])->name('kampus.prodi.index');
        Route::get('/beasiswa', [BeasiswaController::class, 'index'])->name('beasiswa.index');
        Route::get('/beasiswa/{scholarship}/lihat', [BeasiswaController::class, 'show'])->name('beasiswa.lihat');

        Route::get('/pendaftar/export', [PendaftarController::class, 'export'])->name('pendaftar.export');
        Route::get('/pendaftar', [PendaftarController::class, 'index'])->name('pendaftar.index');
        Route::get('/pendaftar/{applicant}/lihat', [PendaftarController::class, 'show'])->name('pendaftar.lihat');

        Route::get('/pengumuman/{scholarship}/export-pdf', [PengumumanController::class, 'exportPdf'])->name('pengumuman.export-pdf');

        Route::get('/pengumuman', [PengumumanJadwalController::class, 'index'])->name('pengumuman.index');
    });

    // Admin routes (only admin manages scholarships)
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/kampus/buat', [KampusController::class, 'create'])->name('kampus.buat');
        Route::post('/kampus', [KampusController::class, 'store'])->name('kampus.simpan');
        Route::get('/kampus/{kampus}/ubah', [KampusController::class, 'edit'])->name('kampus.ubah');
        Route::put('/kampus/{kampus}', [KampusController::class, 'update'])->name('kampus.perbarui');
        Route::delete('/kampus/{kampus}', [KampusController::class, 'destroy'])->name('kampus.hapus');

        Route::get('/kampus/{kampus}/fakultas/buat', [KampusController::class, 'fakultasCreate'])->name('kampus.fakultas.buat');
        Route::post('/kampus/{kampus}/fakultas', [KampusController::class, 'fakultasStore'])->name('kampus.fakultas.simpan');
        Route::get('/kampus/{kampus}/fakultas/{fakultas}/ubah', [KampusController::class, 'fakultasEdit'])->name('kampus.fakultas.ubah');
        Route::put('/kampus/{kampus}/fakultas/{fakultas}', [KampusController::class, 'fakultasUpdate'])->name('kampus.fakultas.perbarui');
        Route::delete('/kampus/{kampus}/fakultas/{fakultas}', [KampusController::class, 'fakultasDestroy'])->name('kampus.fakultas.hapus');

        Route::get('/kampus/{kampus}/fakultas/{fakultas}/prodi/buat', [KampusController::class, 'prodiCreate'])->name('kampus.prodi.buat');
        Route::post('/kampus/{kampus}/fakultas/{fakultas}/prodi', [KampusController::class, 'prodiStore'])->name('kampus.prodi.simpan');
        Route::get('/kampus/{kampus}/fakultas/{fakultas}/prodi/{prodi}/ubah', [KampusController::class, 'prodiEdit'])->name('kampus.prodi.ubah');
        Route::put('/kampus/{kampus}/fakultas/{fakultas}/prodi/{prodi}', [KampusController::class, 'prodiUpdate'])->name('kampus.prodi.perbarui');
        Route::delete('/kampus/{kampus}/fakultas/{fakultas}/prodi/{prodi}', [KampusController::class, 'prodiDestroy'])->name('kampus.prodi.hapus');

        Route::get('/beasiswa/buat', [BeasiswaController::class, 'create'])->name('beasiswa.buat');
        Route::post('/beasiswa', [BeasiswaController::class, 'store'])->name('beasiswa.simpan');
        Route::get('/beasiswa/{scholarship}/ubah', [BeasiswaController::class, 'edit'])->name('beasiswa.ubah');
        Route::put('/beasiswa/{scholarship}', [BeasiswaController::class, 'update'])->name('beasiswa.perbarui');
        Route::delete('/beasiswa/{scholarship}', [BeasiswaController::class, 'destroy'])->name('beasiswa.hapus');

        Route::put('/pendaftar/{applicant}', [PendaftarController::class, 'update'])->name('pendaftar.perbarui');
        Route::get('/pendaftar/{applicant}/info', [PendaftarController::class, 'deleteInfo'])->name('pendaftar.info');
        Route::delete('/pendaftar/{applicant}', [PendaftarController::class, 'destroy'])->name('pendaftar.hapus');

        Route::get('/pengumuman/{scholarship}/ubah', [PengumumanJadwalController::class, 'edit'])->name('pengumuman.ubah');
        Route::put('/pengumuman/{scholarship}', [PengumumanJadwalController::class, 'update'])->name('pengumuman.perbarui');
        Route::delete('/pengumuman/{scholarship}', [PengumumanJadwalController::class, 'destroy'])->name('pengumuman.hapus');

        Route::get('/template', [TemplateController::class, 'index'])->name('template.index');
        Route::put('/template', [TemplateController::class, 'update'])->name('template.perbarui');
        Route::delete('/template', [TemplateController::class, 'destroy'])->name('template.hapus');
        Route::get('/template/info', [TemplateController::class, 'deleteInfo'])->name('template.info');
    });

    // Admin + Super Admin routes
    Route::middleware('role:super_admin|admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/pengguna', [PenggunaController::class, 'index'])->name('pengguna.index');
        Route::patch('/pengguna/{user}/status', [PenggunaController::class, 'toggleStatus'])->name('pengguna.toggle-status');
    });

    // Super Admin routes
    Route::middleware('role:super_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/pengguna/buat', [PenggunaController::class, 'create'])->name('pengguna.buat');
        Route::post('/pengguna', [PenggunaController::class, 'store'])->name('pengguna.simpan');
        Route::get('/pengguna/{user}/ubah', [PenggunaController::class, 'edit'])->name('pengguna.ubah');
        Route::put('/pengguna/{user}', [PenggunaController::class, 'update'])->name('pengguna.perbarui');
        Route::delete('/pengguna/{user}', [PenggunaController::class, 'destroy'])->name('pengguna.hapus');
    });

    // Preview surat permohonan template (inline view)
    Route::get('/preview/surat-permohonan', function () {
        $dir = Storage::disk('local')->path('templates');
        $files = ['surat_permohonan.docx', 'surat_permohonan.doc', 'surat_permohonan.pdf'];

        foreach ($files as $file) {
            $path = $dir.'/'.$file;
            if (file_exists($path)) {
                $mimeType = mime_content_type($path);
                $extension = pathinfo($path, PATHINFO_EXTENSION);

                return response()->file($path, [
                    'Content-Type' => $mimeType,
                    'Content-Disposition' => 'inline; filename="Surat_Permohonan_Beasiswa.'.$extension.'"',
                ]);
            }
        }

        abort(404, 'Template surat permohonan tidak ditemukan');
    })->name('preview.application-letter');

    // Download surat permohonan template
    Route::get('/download/surat-permohonan', function () {
        $dir = Storage::disk('local')->path('templates');
        $files = ['surat_permohonan.docx', 'surat_permohonan.doc', 'surat_permohonan.pdf'];

        foreach ($files as $file) {
            $path = $dir.'/'.$file;
            if (file_exists($path)) {
                $mimeType = mime_content_type($path);
                $extension = pathinfo($path, PATHINFO_EXTENSION);

                return response()->download($path, 'Surat_Permohonan_Beasiswa.'.$extension);
            }
        }

        abort(404, 'Template surat permohonan tidak ditemukan');
    })->name('download.application-letter');

    // User routes
    Route::middleware('role:user')->prefix('pengguna')->name('user.')->group(function () {
        Route::get('/beasiswa', [UserBeasiswaController::class, 'index'])->name('beasiswa.index');
        Route::get('/beasiswa/{scholarship}', [UserBeasiswaController::class, 'show'])->name('beasiswa.lihat');
        Route::get('/pendaftaran', [PendaftaranController::class, 'index'])->name('pendaftaran.index');
        Route::get('/pendaftaran/{applicant}', [PendaftaranController::class, 'show'])->name('pendaftaran.lihat');
        Route::get('/pendaftaran/{applicant}/lengkapi', [PendaftaranController::class, 'edit'])->name('pendaftaran.lengkapi');
        Route::put('/pendaftaran/{applicant}', [PendaftaranController::class, 'update'])->name('pendaftaran.perbarui');
        Route::get('/daftar-beasiswa', [PendaftaranController::class, 'create'])->name('pendaftaran.buat');
        Route::post('/pendaftaran', [PendaftaranController::class, 'store'])->name('pendaftaran.simpan');
    });
});
