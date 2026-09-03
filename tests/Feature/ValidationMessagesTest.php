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
