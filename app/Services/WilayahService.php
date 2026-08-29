<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WilayahService
{
    private string $baseUrl = 'https://konoland-api.vercel.app';

    public function getProvinces(): array
    {
        return Cache::remember('wilayah_provinces', 3600, function () {
            $response = Http::get("{$this->baseUrl}/province", [
                'limit' => 100,
                'sortBy' => 'province',
            ]);

            return $response->json('data', []);
        });
    }

    public function getRegencies(string $provinceCode): array
    {
        return Cache::remember("wilayah_regencies_{$provinceCode}", 3600, function () use ($provinceCode) {
            $response = Http::get("{$this->baseUrl}/regency", [
                'provinceCode' => $provinceCode,
                'limit' => 100,
                'sortBy' => 'regency',
            ]);

            return $response->json('data', []);
        });
    }

    public function getDistricts(string $regencyCode): array
    {
        return Cache::remember("wilayah_districts_{$regencyCode}", 3600, function () use ($regencyCode) {
            $response = Http::get("{$this->baseUrl}/district", [
                'regencyCode' => $regencyCode,
                'limit' => 100,
                'sortBy' => 'district',
            ]);

            return $response->json('data', []);
        });
    }

    public function getVillages(string $districtCode): array
    {
        return Cache::remember("wilayah_villages_{$districtCode}", 3600, function () use ($districtCode) {
            $response = Http::get("{$this->baseUrl}/village", [
                'districtCode' => $districtCode,
                'limit' => 100,
                'sortBy' => 'village',
            ]);

            return $response->json('data', []);
        });
    }
}
