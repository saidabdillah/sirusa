<?php

use App\Http\Controllers\Api\KampusController;
use App\Http\Controllers\Api\WilayahController;
use Illuminate\Support\Facades\Route;

Route::prefix('wilayah')->group(function () {
    Route::get('/provinsi', [WilayahController::class, 'provinsi'])->name('api.wilayah.provinsi');
    Route::get('/kabupaten/{provinsi}', [WilayahController::class, 'kabupaten'])->name('api.wilayah.kabupaten');
    Route::get('/kecamatan/{kabupaten}', [WilayahController::class, 'kecamatan'])->name('api.wilayah.kecamatan');
    Route::get('/desa/{kecamatan}', [WilayahController::class, 'desa'])->name('api.wilayah.desa');
});

Route::get('/kampus/search', [KampusController::class, 'search'])->name('api.kampus.search');
