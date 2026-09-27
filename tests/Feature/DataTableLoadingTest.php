<?php

/*
|--------------------------------------------------------------------------
| Indikator loading DataTable seragam (spesifikasi batch 2, poin 14)
|
| Tabel yang punya `processing: true` menampilkan "Processing..." bahasa
| Inggris; tabel yang tidak punya `processing` sama sekali diam. Dua-duanya
| hasil yang tidak konsisten, jadi:
|   1. teksnya disetel SEKALI di `custom.js` lewat
|      `$.fn.dataTable.defaults.oLanguage.sProcessing`
|   2. setiap tabel punya `processing: true`
|
| Test ini membaca sumber view sebagai teks. `processing` adalah opsi
| JavaScript, jadi tidak bisa diuji lewat HTTP.
|--------------------------------------------------------------------------
*/

/**
 * Setiap view Blade yang menginisialisasi DataTable.
 *
 * Fungsi biasa dengan API filesystem bawaan PHP, bukan closure dan bukan
 * facade: dataset `->with()` dievaluasi SEBELUM `beforeEach`, saat container
 * aplikasi belum booted -- `$this`, `base_path()`, `resource_path()`, dan
 * facade `File` belum ada satu pun.
 *
 * @return array<string, string> path relatif => isi berkas
 */
function tabelDataTable(): array
{
    $hasil = [];
    $root = dirname(__DIR__, 2).'/resources/views';

    $berkas = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

    foreach ($berkas as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $isi = file_get_contents($file->getPathname());

        if (str_contains($isi, '.DataTable({')) {
            $hasil[substr($file->getPathname(), strlen($root) + 1)] = $isi;
        }
    }

    ksort($hasil);

    return $hasil;
}

/**
 * Hanya tabel yang memakai pemuatan sisi server.
 *
 * @return array<string, string>
 */
function tabelServerSide(): array
{
    return array_filter(
        tabelDataTable(),
        fn (string $isi) => str_contains($isi, 'serverSide: true')
    );
}

beforeEach(function () {
    // Container sudah hidup di sini, jadi `base_path()` aman.
    $this->customJs = file_get_contents(base_path('public/assets/js/custom.js'));
    $this->tabel = tabelDataTable();
});

test('teks loading disetel sekali secara global di custom js', function () {
    expect($this->customJs)
        ->toContain('$.fn.dataTable.defaults.oLanguage.sProcessing = "Memproses..."');
});

test('ada tabel yang memakai DataTable untuk diuji', function () {
    // Penjaga: kalau filter di atas salah, semua test lain di file ini lulus
    // tanpa memeriksa apa pun.
    expect($this->tabel)->toHaveCount(9);
});

test('setiap tabel punya flag processing', function (string $path) {
    // `toContain()` menerima banyak needle, jadi pesan tidak boleh ikut
    // jadi argumen kedua -- ia akan ikut diperiksa sebagai needle dan
    // selalu gagal.
    expect(str_contains(tabelDataTable()[$path], 'processing: true'))
        ->toBeTrue("Tabel di {$path} tidak punya `processing: true`");
})->with(fn () => array_keys(tabelDataTable()));

test('tabel tidak mengaktifkan processing secara eksplisit menjadi false', function (string $path) {
    expect(tabelDataTable()[$path])->not->toContain('processing: false');
})->with(fn () => array_keys(tabelDataTable()));

test('tabel server side memakai ajax dan processing', function (string $path) {
    $isi = tabelServerSide()[$path];

    // `serverSide: true` tanpa `processing` = tabel yang diam saat memuat,
    // persis keluhan yang harus hilang.
    expect(str_contains($isi, 'processing: true'))->toBeTrue("Tabel server side {$path} tidak punya `processing: true`")
        ->and(str_contains($isi, 'ajax:'))->toBeTrue("Tabel server side {$path} tidak menentukan `ajax`");
})->with(fn () => array_keys(tabelServerSide()));

test('tabel sisi klien tidak memakai pemuatan server', function (string $path) {
    expect(tabelDataTable()[$path])
        ->not->toContain('serverSide');
})->with(fn () => array_keys(array_diff_key(tabelDataTable(), tabelServerSide())));

test('tabel tidak mendefinisikan ulang bahasa loading secara lokal', function (string $path) {
    $isi = tabelDataTable()[$path];

    // Kalau sebuah view menulis `processing:` sendiri, teksnya bisa berbeda
    // dari tabel lain. Setelan global harus tetap satu-satunya sumber.
    expect($isi)
        ->not->toContain('sProcessing')
        ->not->toContain('processing: "');
})->with(fn () => array_keys(tabelDataTable()));
