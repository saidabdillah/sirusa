<?php

use Illuminate\Support\Facades\File;

/*
|--------------------------------------------------------------------------
| Standar loading tunggal (spesifikasi batch 2, poin 2/3/21)
|
| Semua loading di SIRUSA harus mengikuti satu jalur: `submitWithSpinner()` untuk
| spinner di tombol, `showSubmitLoading()` untuk dialog SweetAlert, dan
| `closeSubmitLoading()` untuk melepas keduanya. Yang diuji di sini adalah
| JAMINAN KODE-nya, karena bug "tombol stuck disabled + spinner" adalah bug
| JavaScript -- tidak bisa ditangkap oleh test HTTP.
|
| Test ini sengaja membaca sumber JS sebagai teks, bukan menjalankan jQuery.
| Yang dijaga adalah bentuk kode yang wajib ada, supaya refactor berikutnya
| tidak diam-diam menghapus salah satu cabangnya.
|--------------------------------------------------------------------------
*/

beforeEach(function () {
    $this->js = File::get(base_path('public/assets/js/custom.js'));

    // Ratakan spasi/indenter supaya assertion boleh melintasi baris.
    $this->jsRata = preg_replace('/\s+/', ' ', $this->js);

    // Versi tanpa komentar. Aturan "jalur error tidak boleh menavigasi" harus
    // dinilai dari kode yang DIJALANKAN; kalau komentar yang menjelaskan aturan
    // itu sendiri memuat nama fungsinya, assertion jadi salah positives.
    //
    // Hanya komentar satu baris penuh yang dibuang. Menghapus `//` di tengah baris
    // berbahaya: URL di dalam string (`https://...`) dan regex literal
    // (`/^\d+$/`) sama-sama mengandung karakter itu.
    $this->jsKode = implode(
        "\n",
        array_filter(
            explode("\n", $this->js),
            fn ($baris) => ! str_starts_with(trim($baris), '//')
        )
    );
});

// ─── Spinner tombol: satu sumber kebenaran ──────────────────────────────

test('semua spinner tombol dibuat lewat satu helper', function () {
    // Helper tunggal + closure pelepas yang dikembalikan.
    expect($this->js)->toContain('function submitWithSpinner($btn, teks)')
        ->toContain('return function releaseSubmit()');

    // Tidak boleh ada lagi pathogenesis spinner inline di luar helper.
    // `fa-spinner` hanya boleh muncul di dalam `submitWithSpinner`.
    $occurrences = substr_count($this->js, '<i class="fas fa-spinner fa-spin mr-1"></i>');

    expect($occurrences)->toBe(1);
});

test('helper spinner menyimpan html asli supaya bisa dipulihkan', function () {
    // Simpanannya harus DIJAGA. Form `data-ajax-form` melewati `submitWithSpinner()`
    // dua kali dalam satu request: handler umum `on("submit", "form")` lalu
    // handler delegasi `on("submit", "form[data-ajax-form]")` yang memanggil
    // `submitAjax()`. Keduanya terikat di `document` secara berurutan, dan
    // `preventDefault()` tidak menghentikan handler yang lebih dulu.
    //
    // Tanpa penjaga, pemanggilan kedua menimpa `data-loading-html` dengan HTML
    // SPINNER; `releaseSubmit()` lalu memulihkan spinner sebagai "tombol asli"
    // dan tombolnya jadi `[⟳ Menyimpan...]` selamanya. Gejala persis seperti
    // laporan "tombol Simpan masih spinner setelah request selesai".
    expect($this->jsRata)->toContain(
        'if (!$btn.data("loading-html")) { $btn.data("loading-html", $btn.html()); }'
    );
});

test('pelepasan tombol membersihkan simpanan htmlnya', function () {
    // Kalau `data-loading-html` tidak dibuang, siklus request berikutnya akan
    // memakai HTML yang di-stash pada request sebelumnya -- salah begitu
    // markup tombol berubah di tengah (mis. tabel dimuat ulang).
    expect($this->jsRata)->toContain('var html = $btn.data("loading-html");')
        ->toContain('.prop("disabled", false) .html(html) .removeData("loading-html");');
});

test('pelepasan tombol hanya lewat releaseSubmit', function () {
    // `prop("disabled", false)` hanya boleh muncul di dalam releaseSubmit,
    // tidak di dalam .done()/.fail() yang bisa terlewat pada kondisi tertentu.
    $diFail = preg_match('/\.fail\(function \(xhr\) \{.*?\.prop\("disabled", false\)/s', $this->js);
    $diDone = preg_match('/\.done\(function \(payload\) \{.*?\.prop\("disabled", false\)/s', $this->js);

    expect($diFail)->toBe(0)
        ->and($diDone)->toBe(0);
});

// ─── Tombol non type="submit" tetap dapat loading state ─────────────────

test('tombol type button punya jalur loading sendiri', function () {
    // "Simpan Keputusan" di halaman verifikasi memakai `type="button"` karena
    // ada konfirmasi SweetAlert sebelum submit. `submitAjax()` mencari
    // `button[type="submit"]`, jadi tanpa fallback tombol itu berjalan tanpa
    // spinner dan tanpa `disabled` sama sekali.
    expect($this->js)->toContain('function tombolSubmit($form)')
        ->toContain('return $submit.length ? $submit : $form.find("[data-loading-button]").first();');

    // Kedua pemanggil (handler umum dan `submitAjax`) memakai helper itu,
    // bukan mencari sendiri-sendiri.
    expect(substr_count($this->js, 'tombolSubmit($form)'))->toBe(3); // 1 definisi + 2 pemakai
    expect(substr_count($this->js, "find('button[type=\"submit\"]')"))->toBe(1); // hanya di dalam helper
});

test('jaring bfcache juga rescuing tombol non type submit', function () {
    expect($this->jsRata)->toContain(
        "\$('form button[type=\"submit\"][disabled], form [data-loading-button][disabled]').each(function () {"
    );
});

test('tombol verify yang dikunci konfirmasi ditandai sebagai tombol submit', function () {
    $view = File::get(resource_path('views/admin/verifikasi/lihat.blade.php'));

    // Tanpa penanda ini, `tombolSubmit()` tidak menemukan apa-apa dan tombolnya
    // tetap idle selagi request berjalan.
    expect($view)->toContain('id="btn-verifikasi"')
        ->toContain('data-loading-button')
        ->toContain('data-loading-text="Menyimpan keputusan..."');
});

test('tombol non-simpan punya teks loading yang sesuai aksinya', function () {
    // Default helper adalah "Menyimpan...". Tombol lain yang memakai default
    // itu akan menampilkan teks yang salah -- tombol "Cari" yang sedang
    // memfilter daftar akan menampilkan "Menyimpan...".
    $harapan = [
        'views/admin/pendaftar/index.blade.php' => 'data-loading-text="Mencari..."',
        'views/admin/verifikasi/index.blade.php' => 'data-loading-text="Menerapkan filter..."',
        'views/auth/masuk.blade.php' => 'data-loading-text="Sedang masuk..."',
    ];

    foreach ($harapan as $relatif => $needle) {
        expect(File::get(resource_path($relatif)))->toContain($needle);
    }
});

// ─── Tidak ada request di view yang tanpa cabang gagal ──────────────────

test('setiap request di view punya cabang gagal', function () {
    $views = File::allFiles(resource_path('views'));
    $tanpaPenanganan = [];

    foreach ($views as $view) {
        $isi = $view->getContents();

        // Bentuk `$.getJSON(url, callback)` / `$.post(url, callback)` /
        // `$.get(url, callback)` incapable melaporkan kegagalan sama sekali --
        // hanya `successCallback` yang dipanggil. Bentuk `.then().then()`
        // untuk `fetch()` tanpa `.catch()` menelan error begitu saja.
        if (preg_match('/\$\.(getJSON|post|get|ajax)\(\s*[^,()]+,\s*function/', $isi)) {
            $tanpaPenanganan[] = $view->getRelativePathname().' (callback gaya getJSON)';
        }

        $adaFetch = preg_match('/\bfetch\(/', $isi);

        if ($adaFetch && ! preg_match('/\.catch\(/', $isi)) {
            $tanpaPenanganan[] = $view->getRelativePathname().' (fetch tanpa catch)';
        }
    }

    expect($tanpaPenanganan)->toBe([]);
});

test('daftar desa dibersihkan saat request gagal', function () {
    $view = File::get(resource_path('views/profil/index.blade.php'));

    // Tanpa `.fail()`, select desa menganggur di "Memuat data..." selamanya
    // begitu request gagal -- user tidak melihat daftar dan tidak ada pesan.
    //
    // Teks diratakan lebih dulu supaya assertion tidak bergantung pada line
    // ending (sumber repo memakai LF, working tree bisa CRLF).
    $rata = preg_replace('/\s+/', ' ', $view);

    // Bentuk `$.getJSON(url, successCallback)` hanya punya cabang sukses.
    // Bentuk berantai `.done().fail()` baru bisa melaporkan kegagalan.
    expect($rata)->toContain("$.getJSON(desaUrlBase + '/' + code) .done(function(data) {")
        ->toContain('.fail(function(xhr) {')
        ->toContain('Gagal memuat desa. Pilih kecamatan lagi.')
        ->toContain("title: xhr.status === 0 ? 'Koneksi bermasalah' : 'Gagal memuat desa'");
});

test('konfirmasi logout memberi pesan saat request gagal', function () {
    $view = File::get(resource_path('views/layouts/partials/navbar.blade.php'));

    expect($view)->toContain('if (!res.ok) {')
        ->toContain('.catch(function () {')
        ->toContain('Konfirmasi logout tidak bisa dimuat');
});

// ─── Error validasi: SELALU di bawah input, tidak pernah SweetAlert saja ──

test('validasi per-index digsambar di baris repeater yang salah', function () {
    // Aturan `nama_kampus.*` melapor sebagai `nama_kampus.0`, tapi Blade
    // merender repeater-nya `nama_kampus[]`. Tanpa pencocokan indeks,
    // `findField()` tidak menemukan apa pun, `paintFieldError()` mengembalikan
    // false, dan errornya hanya muncul sebagai SweetAlert umum -- persis
    // kegagalan yang dilarang spesifikasi.
    expect($this->jsRata)->toContain('var indeks = name.match(/\.(\d+)$/);')
        ->toContain('$form .find(\'[name="\' + name.replace(/\.\d+$/, "") + \'[]"]\') .eq(parseInt(indeks[1], 10));');
});

test('repeater kampus merender input sebagai nama_kampus[]', function () {
    // Kalau view berubah ke `nama_kampus[0]`, tahap keempat di atas jadi
    // tidak terpakai dan test sebelumnya tidak akan menangkapnya -- jadi
    // bentuk inputnya ikut dipin.
    $view = File::get(resource_path('views/admin/kampus/buat.blade.php'));

    expect($view)->toContain('name="nama_kampus[]"');
});

test('sweetalert hanya dipakai kalau tidak ada field yang bisa digambar', function () {
    // Urutannya harus: coba gambar dulu, baru SweetAlert sebagai cadangan.
    expect($this->jsRata)->toContain(
        'if (paintFieldError($form, name, messages[0])) { painted += 1; } }); if (painted === 0) {'
    );
});

// ─── Dialog SweetAlert: wajib ditutup di setiap kondisi akhir ───────────

test('dialog loading punya pasangan tutup', function () {
    expect($this->js)->toContain('window.showSubmitLoading = function (message)')
        ->toContain('window.closeSubmitLoading = function ()')
        ->toContain('submitLoadingOpen = false;');
});

test('dialog loading ditutup lewat always sehingga tidak pernah nyangkut', function () {
    // `.always()` berjalan untuk 2xx, 403, 404, 419, 422, 500, dan status 0
    // (koneksi putus) -- itulah jawaban atas "loading harus berhenti di semua
    // kondisi", termasuk jalur 422 yang `return` lebih dulu.
    expect($this->jsRata)->toContain(
        '.always(function () { releaseSubmit(); closeSubmitLoading(); });'
    );
});

test('koneksi putus punya pesan sendiri, bukan error generik', function () {
    expect($this->jsRata)->toContain('if (xhr.status === 0) {')
        ->toContain('Permintaan tidak terkirim');
});

// ─── Success: alert dulu, refresh belakangan ────────────────────────────

test('alert success menutup dirinya sendiri tanpa tombol OK', function () {
    // Tanpa `timer`, SweetAlert menunggu klik OK dan user harus menekan dua
    // kali untuk menyelesaikan satu aksi. `showConfirmButton: false` tanpa
    // `timer` tidak menutup apa pun -- keduanya harus berpasangan.
    expect($this->jsRata)->toMatch(
        '/icon: "success".*?text: \(payload && payload\.message\).*?timer: 1500.*?showConfirmButton: false.*?timerProgressBar: true/s'
    );
});

test('success alert baru mewariskan teks pesan dari server', function () {
    // Judul tetap "Berhasil", detailnya dari `response.message` supaya pesan
    // backend ("Pendaftaran dibatalkan. Anda bisa mendaftar beasiswa lain.")
    // yang sampai ke user, bukan kalimat generik yang menimpa.
    expect($this->jsRata)->toMatch(
        '/icon: "success", title: "Berhasil", text: \(payload && payload\.message\) \|\| "Data berhasil disimpan\."/s'
    );
});

test('refresh jalan di dalam then success', function () {
    // Refresh dipindah ke luar `.then()` berarti navigasi berjalan bersamaan
    // dengan animasi alert dan user tidak pernah melihat success-nya. Test ini
    // menahan urutan itu: options timer -> `.then()` -> refresh.
    expect($this->js)->toMatch(
        '/icon: "success".*?timerProgressBar: true,\s*\n\s*\}\)\.then\(function \(\) \{.*?refreshAfterSave\(\$form, payload\);/s'
    );
});

test('tidak ada navigasi di jalur error', function () {
    // Semua error harus meninggalkan user di form yang sama supaya isinya
    // tidak hilang. 422 validasi, 419 sesi kedaluwarsa, 403 akses, 500, dan
    // koneksi putus (status 0) semuanya masuk lewat `.fail()` ini.
    $mulai = strpos($this->jsKode, '.fail(function (xhr) {');
    $selesai = strpos($this->jsKode, '.always(function ()');

    expect($mulai)->toBeInt()->and($selesai)->toBeInt();

    $blokFail = substr($this->jsKode, $mulai, $selesai - $mulai);

    expect($blokFail)->not->toContain('refreshAfterSave')
        ->not->toContain('location.assign')
        ->not->toContain('location.reload');
});

test('hanya ada satu titik navigasi dan hanya bisa dicapai dari sukses', function () {
    // `window.location.assign` hidup di dalam `refreshAfterSave()`, dan
    // `refreshAfterSave()` hanya dipanggil dari `.then()` success. Dua
    // penghitungan ini mengunci invariant itu: menambah pemanggilan kedua di
    // cabang mana pun akan menggagalkan salah satunya.
    expect(substr_count($this->js, 'window.location.assign'))->toBe(1)
        ->and(substr_count($this->js, 'function refreshAfterSave'))->toBe(1)
        ->and(substr_count($this->js, 'refreshAfterSave($form, payload);'))->toBe(1);
});

test('jalur ajaxOk yang gagal tidak ikut mewariskan timer', function () {
    // Backend bisa membalas HTTP 200 dengan `success: false` (jalur
    // `ajaxFail` pada request biasa). Alert itu harus tetap interaktif --
    // pesan error butuh tombol OK, karena user harus membacanya dan
    // memperbaikinya, bukan melihatnya lewat dalam 1500 ms.
    expect($this->jsRata)->toMatch(
        '/if \(payload && payload\.success === false\) \{\s*Swal\.fire\(\{\s*icon: "error", title: "Gagal",.*?\}\);\s*return;/s'
    );
});

// ─── Validasi native tidak boleh membuka dialog yang tak akan tertutup ──

test('dialog loading dilewati kalau browser akan menahan submit', function () {
    expect($this->js)->toContain('function FormTahanLoading($form)')
        ->toContain('return !form.checkValidity();');

    // Ketiga jalur yang membuka dialog harus lewat gerbang ini.
    expect(substr_count($this->js, 'FormTahanLoading($form)'))->toBe(4); // 1 definisi + 3 pemakai
});

test('form ajax tidak dicegat validasi native browser', function () {
    // Validasi form CRUD murni di server; `checkValidity()` tidak boleh
    // memblokir request AJAX.
    expect($this->jsRata)->toContain('if ($form.is("[data-ajax-form]") || !form || typeof form.checkValidity !== "function")');
});

// ─── Jaring pengaman bfcache ────────────────────────────────────────────

test('halaman dari bfcache melepas tombol yang masih spinner', function () {
    expect($this->js)->toContain('window.addEventListener("pageshow"')
        ->toContain('if (!event.persisted) {')
        ->toContain('if (!this.hasAttribute("data-loading-html")) {');
});

// ─── Tidak ada implementasi spinner liar di view ───────────────────────

test('tidak ada view yang memasang spinner sendiri di tombol submit', function () {
    $views = File::allFiles(resource_path('views'));
    $pelanggar = [];

    foreach ($views as $view) {
        $isi = $view->getContents();

        // Yang dicari: JS view yang menulis HTML spinner ke tombol. Semua
        // spinner harus lewat `custom.js`.
        if (preg_match('/html\(\s*[\'"]<i class="fas fa-spinner/', $isi)) {
            $pelanggar[] = $view->getRelativePathname();
        }
    }

    expect($pelanggar)->toBe([]);
});

test('seluruh form ajax memakai handler tunggal dari custom.js', function () {
    $views = File::allFiles(resource_path('views'));
    $formAjax = 0;
    $handlerLokal = [];

    foreach ($views as $view) {
        $isi = $view->getContents();
        $formAjax += preg_match_all('/data-ajax-form/', $isi);

        // Handler submit yang di-bind lokal per halaman akan blew bypass
        // handler delegasi global -- lihat aturan di .ai/rules/views.md.
        if (preg_match('/\$\(document\)\.on\("submit"|\$\(["\'][^"\']+["\']\)\.on\("submit"/', $isi)) {
            $handlerLokal[] = $view->getRelativePathname();
        }
    }

    expect($formAjax)->toBeGreaterThan(30)
        ->and($handlerLokal)->toBe([]);
});
