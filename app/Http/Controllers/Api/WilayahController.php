<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WilayahService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WilayahController extends Controller
{
    public function __construct(
        private WilayahService $wilayah
    ) {}

    public function provinsi(): JsonResponse
    {
        return response()->json($this->wilayah->getProvinces());
    }

    public function kabupaten(string $provinsi, Request $request): JsonResponse
    {
        $data = $this->wilayah->getRegencies($provinsi);

        if ($search = $request->input('term')) {
            $data = array_values(array_filter($data, fn ($item) => stripos($item['regency'], $search) !== false));
        }

        return response()->json($data);
    }

    public function kecamatan(string $kabupaten, Request $request): JsonResponse
    {
        $data = $this->wilayah->getDistricts($kabupaten);

        if ($search = $request->input('term')) {
            $data = array_values(array_filter($data, fn ($item) => stripos($item['district'], $search) !== false));
        }

        return response()->json($data);
    }

    public function desa(string $kecamatan, Request $request): JsonResponse
    {
        $data = $this->wilayah->getVillages($kecamatan);

        if ($search = $request->input('term')) {
            $data = array_values(array_filter($data, fn ($item) => stripos($item['village'], $search) !== false));
        }

        return response()->json($data);
    }
}
