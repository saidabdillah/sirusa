<?php

use App\Http\Controllers\Admin\BeasiswaController;
use App\Http\Controllers\Admin\KampusController;
use App\Http\Controllers\Admin\KeputusanPendaftaranController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\PendaftarController;
use App\Http\Controllers\Admin\PenggunaController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\VerifikasiController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Notifications\NotificationController;
use App\Http\Controllers\Profile\ProfileController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\User\BeasiswaController as UserBeasiswaController;
use App\Http\Controllers\User\PendaftaranController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', fn () => view('landing'))->name('landing');

Route::middleware(['guest'])->group(function () {
    Route::get('/masuk', [AuthController::class, 'masuk'])->name('login');
    Route::post('/masuk', [AuthController::class, 'simpanMasuk'])->name('login.store');
});

Route::middleware(['auth', 'status.aktif', 'akses.menu'])->group(function () {
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

    // Dokumen upload (pas foto, KTP, KK, dll)
    Route::get('/dokumen/{path}', function ($path) {
        $disk = Storage::disk('public');
        if ($disk->exists($path)) {
            $file = $disk->path($path);
            $mimeType = mime_content_type($file);

            return response()->file($file, [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }
        abort(404);
    })->name('dokumen.show')->where('path', '.*');

    // Pengaturan akun
    Route::get('/pengaturan', [SettingsController::class, 'index'])->name('settings');
    Route::put('/pengaturan', [SettingsController::class, 'updateAccount'])->name('settings.update');

    // Notifikasi
    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifikasi/baca-semua', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::delete('/notifikasi', [NotificationController::class, 'destroyAll'])->name('notifications.destroy-all');
    Route::delete('/notifikasi/sudah-dibaca', [NotificationController::class, 'destroyRead'])->name('notifications.destroy-read');
    Route::delete('/notifikasi/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::get('/notifikasi/{notification}', [NotificationController::class, 'show'])->name('notifications.show');

    // Admin routes (akses via menu, bukan role)
    Route::prefix('admin')->name('admin.')->group(function () {
        // Kampus (termasuk fakultas & prodi)
        Route::get('/kampus', [KampusController::class, 'index'])->name('kampus.index');
        Route::get('/kampus/data', [KampusController::class, 'data'])->name('kampus.data');
        Route::get('/kampus/{kampus}/fakultas', [KampusController::class, 'fakultasIndex'])->name('kampus.fakultas.index');
        Route::get('/kampus/{kampus}/fakultas/data', [KampusController::class, 'fakultasData'])->name('kampus.fakultas.data');
        Route::get('/kampus/{kampus}/fakultas/{fakultas}/prodi', [KampusController::class, 'prodiIndex'])->name('kampus.prodi.index');
        Route::get('/kampus/{kampus}/fakultas/{fakultas}/prodi/data', [KampusController::class, 'prodiData'])->name('kampus.prodi.data');
        Route::get('/kampus/buat', [KampusController::class, 'create'])->name('kampus.buat');
        Route::post('/kampus', [KampusController::class, 'store'])->name('kampus.simpan');
        Route::get('/kampus/{kampus}/ubah', [KampusController::class, 'edit'])->name('kampus.ubah');
        Route::put('/kampus/{kampus}', [KampusController::class, 'update'])->name('kampus.perbarui');
        Route::delete('/kampus/hapus-massal', [KampusController::class, 'massDestroy'])->name('kampus.massDestroy');
        Route::delete('/kampus/{kampus}', [KampusController::class, 'destroy'])->name('kampus.hapus');

        Route::get('/kampus/{kampus}/fakultas/buat', [KampusController::class, 'fakultasCreate'])->name('kampus.fakultas.buat');
        Route::post('/kampus/{kampus}/fakultas', [KampusController::class, 'fakultasStore'])->name('kampus.fakultas.simpan');
        Route::get('/kampus/{kampus}/fakultas/{fakultas}/ubah', [KampusController::class, 'fakultasEdit'])->name('kampus.fakultas.ubah');
        Route::put('/kampus/{kampus}/fakultas/{fakultas}', [KampusController::class, 'fakultasUpdate'])->name('kampus.fakultas.perbarui');
        Route::delete('/kampus/{kampus}/fakultas/hapus-massal', [KampusController::class, 'fakultasMassDestroy'])->name('kampus.fakultas.massDestroy');
        Route::delete('/kampus/{kampus}/fakultas/{fakultas}', [KampusController::class, 'fakultasDestroy'])->name('kampus.fakultas.hapus');

        Route::get('/kampus/{kampus}/fakultas/{fakultas}/prodi/buat', [KampusController::class, 'prodiCreate'])->name('kampus.prodi.buat');
        Route::post('/kampus/{kampus}/fakultas/{fakultas}/prodi', [KampusController::class, 'prodiStore'])->name('kampus.prodi.simpan');
        Route::get('/kampus/{kampus}/fakultas/{fakultas}/prodi/{prodi}/ubah', [KampusController::class, 'prodiEdit'])->name('kampus.prodi.ubah');
        Route::put('/kampus/{kampus}/fakultas/{fakultas}/prodi/{prodi}', [KampusController::class, 'prodiUpdate'])->name('kampus.prodi.perbarui');
        Route::delete('/kampus/{kampus}/fakultas/{fakultas}/prodi/hapus-massal', [KampusController::class, 'prodiMassDestroy'])->name('kampus.prodi.massDestroy');
        Route::delete('/kampus/{kampus}/fakultas/{fakultas}/prodi/{prodi}', [KampusController::class, 'prodiDestroy'])->name('kampus.prodi.hapus');

        // Beasiswa
        Route::get('/beasiswa', [BeasiswaController::class, 'index'])->name('beasiswa.index');
        Route::get('/beasiswa/data', [BeasiswaController::class, 'data'])->name('beasiswa.data');
        Route::get('/beasiswa/buat', [BeasiswaController::class, 'create'])->name('beasiswa.buat');
        Route::post('/beasiswa', [BeasiswaController::class, 'store'])->name('beasiswa.simpan');
        Route::get('/beasiswa/{scholarship}/lihat', [BeasiswaController::class, 'show'])->name('beasiswa.lihat');
        Route::get('/beasiswa/{scholarship}/ubah', [BeasiswaController::class, 'edit'])->name('beasiswa.ubah');
        Route::put('/beasiswa/{scholarship}', [BeasiswaController::class, 'update'])->name('beasiswa.perbarui');
        Route::delete('/beasiswa/{scholarship}', [BeasiswaController::class, 'destroy'])->name('beasiswa.hapus');

        // Pendaftar
        Route::get('/pendaftar', [PendaftarController::class, 'index'])->name('pendaftar.index');
        Route::get('/pendaftar/data', [PendaftarController::class, 'data'])->name('pendaftar.data');

        // Verifikasi data profil
        Route::get('/verifikasi/capil', [VerifikasiController::class, 'index'])->defaults('stage', 'capil')->name('capil.index');
        Route::get('/verifikasi/capil/{user}', [VerifikasiController::class, 'show'])->defaults('stage', 'capil')->name('capil.lihat');
        Route::put('/verifikasi/capil/{user}', [VerifikasiController::class, 'verifikasi'])->defaults('stage', 'capil')->name('capil.verifikasi');
        Route::get('/verifikasi/kampus', [VerifikasiController::class, 'index'])->defaults('stage', 'kampus')->name('kampusverif.index');
        Route::get('/verifikasi/kampus/{user}', [VerifikasiController::class, 'show'])->defaults('stage', 'kampus')->name('kampusverif.lihat');
        Route::put('/verifikasi/kampus/{user}', [VerifikasiController::class, 'verifikasi'])->defaults('stage', 'kampus')->name('kampusverif.verifikasi');
        Route::get('/verifikasi/kesra', [VerifikasiController::class, 'index'])->defaults('stage', 'kesra')->name('kesra.index');
        Route::get('/verifikasi/kesra/{user}', [VerifikasiController::class, 'show'])->defaults('stage', 'kesra')->name('kesra.lihat');
        Route::put('/verifikasi/kesra/{user}', [VerifikasiController::class, 'verifikasi'])->defaults('stage', 'kesra')->name('kesra.verifikasi');
        Route::put('/verifikasi/kesra/{user}/pendaftaran/{applicant}', [KeputusanPendaftaranController::class, 'update'])->defaults('stage', 'kesra')->name('kesra.pendaftaran.keputusan');

        // Pengguna
        Route::get('/pengguna', [PenggunaController::class, 'index'])->name('pengguna.index');
        Route::patch('/pengguna/{user}/status', [PenggunaController::class, 'toggleStatus'])->name('pengguna.toggle-status');
        Route::get('/pengguna/buat', [PenggunaController::class, 'create'])->name('pengguna.buat');
        Route::post('/pengguna', [PenggunaController::class, 'store'])->name('pengguna.simpan');
        // Setelah `pengguna.buat` — kalau didaftarkan lebih dulu, `/{user}` akan
        // menelan `/pengguna/buat`.
        Route::get('/pengguna/{user}', [PenggunaController::class, 'show'])->name('pengguna.lihat');
        Route::get('/pengguna/{user}/ubah', [PenggunaController::class, 'edit'])->name('pengguna.ubah');
        Route::put('/pengguna/{user}', [PenggunaController::class, 'update'])->name('pengguna.perbarui');
        Route::post('/pengguna/{user}/reset-password', [PenggunaController::class, 'resetPassword'])->name('pengguna.reset-password');
        Route::delete('/pengguna/{user}', [PenggunaController::class, 'destroy'])->name('pengguna.hapus');

        // Role & Akses Menu
        Route::get('/role', [RoleController::class, 'index'])->name('role.index');
        Route::post('/role', [RoleController::class, 'store'])->name('role.simpan');
        Route::put('/role/{role}', [RoleController::class, 'update'])->name('role.perbarui');
        Route::delete('/role/{role}', [RoleController::class, 'destroy'])->name('role.hapus');

        // Akses Menu (matriks role × menu)
        Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');
        Route::put('/menu', [MenuController::class, 'perbarui'])->name('menu.grants');

        // Kelola Menu (CRUD menu sidebar)
        Route::get('/menu/kelola', [MenuController::class, 'kelola'])->name('menuKelola.index');
        Route::post('/menu/kelola', [MenuController::class, 'store'])->name('menuKelola.simpan');
        Route::put('/menu/kelola/{menu}', [MenuController::class, 'update'])->name('menuKelola.perbarui');
        Route::delete('/menu/kelola/{menu}', [MenuController::class, 'destroy'])->name('menuKelola.hapus');
    });

    // User routes (akses via menu, bukan role)
    Route::prefix('pengguna')->name('user.')->group(function () {
        Route::get('/beasiswa', [UserBeasiswaController::class, 'index'])->name('beasiswa.index');
        Route::get('/beasiswa/{scholarship}', [UserBeasiswaController::class, 'show'])->name('beasiswa.lihat');
        Route::get('/pendaftaran', [PendaftaranController::class, 'index'])->name('pendaftaran.index');
        Route::get('/pendaftaran/{applicant}', [PendaftaranController::class, 'show'])->name('pendaftaran.lihat');
        Route::get('/daftar-beasiswa', [PendaftaranController::class, 'create'])->name('pendaftaran.buat');
        Route::post('/pendaftaran', [PendaftaranController::class, 'store'])->name('pendaftaran.simpan');
        Route::delete('/pendaftaran/{applicant}', [PendaftaranController::class, 'destroy'])->name('pendaftaran.batal');
    });
});
