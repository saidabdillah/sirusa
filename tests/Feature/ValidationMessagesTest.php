<?php

use Illuminate\Support\Facades\Validator;

it('renders validation messages in indonesian', function () {
    $validator = Validator::make(
        ['kecamatan' => '', 'desa_kelurahan' => '', 'email' => 'bukan-email'],
        ['kecamatan' => 'required', 'desa_kelurahan' => 'required', 'email' => 'email'],
    );

    expect($validator->fails())->toBeTrue();

    expect($validator->errors()->first('kecamatan'))->toBe('Kecamatan harus diisi.');
    expect($validator->errors()->first('desa_kelurahan'))->toBe('Desa/Kelurahan harus diisi.');
    expect($validator->errors()->first('email'))->toBe('Email harus berupa alamat email yang valid.');
});

it('renders file upload validation messages in indonesian', function () {
    $validator = Validator::make(
        ['dokumen_ktp' => 'bukan-file'],
        ['dokumen_ktp' => 'file|mimes:pdf,jpg'],
    );

    expect($validator->errors()->first('dokumen_ktp'))->toBe('Dokumen KTP harus berupa file.');
});

it('nama field profil punya terjemahan yang rapi di pesan required', function (string $field, string $pesan) {
    $validator = Validator::make([$field => ''], [$field => 'required']);

    expect($validator->errors()->first($field))->toBe($pesan);
})->with([
    ['no_kk', 'No. Kartu Keluarga harus diisi.'],
    ['desil', 'Desil harus diisi.'],
    ['ukt', 'UKT/SPP harus diisi.'],
    ['ikut_kk', 'Ikut KK harus diisi.'],
    ['dokumen_kk', 'Dokumen KK harus diisi.'],
    ['dokumen_desil', 'Dokumen Desil harus diisi.'],
    ['ktp_ayah', 'KTP Ayah harus diisi.'],
    ['ktp_ibu', 'KTP Ibu harus diisi.'],
    ['ktp_wali', 'KTP Wali harus diisi.'],
    ['kk_wali', 'KK Wali harus diisi.'],
    ['fakultas', 'Fakultas harus diisi.'],
    ['nama_kampus', 'Nama Kampus harus diisi.'],
]);

it('pesan file pada dokumen prestasi tidak menampilkan indeks array', function () {
    $validator = Validator::make(
        ['dokumen_prestasi' => ['bukan-file']],
        ['dokumen_prestasi' => 'array', 'dokumen_prestasi.*' => 'file'],
    );

    expect($validator->errors()->first('dokumen_prestasi.0'))
        ->toBe('Sertifikat Prestasi harus berupa file.');
});
