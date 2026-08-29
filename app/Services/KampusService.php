<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class KampusService
{
    private string $apiUrl = 'https://use.apiindonesia.id/api/v1/kampus/search';

    public function search(string $keyword): array
    {
        $response = Http::withHeaders([
            'x-api-key' => config('services.kampus.api_key'),
        ])->timeout(10)->get($this->apiUrl, [
            'q' => $keyword,
        ]);

        if ($response->failed()) {
            return [];
        }

        $data = $response->json('data', []);

        return array_map(fn ($item) => [
            'id' => $item['name'],
            'text' => $item['name'],
            'jenis' => $item['jenis'] ?? '',
            'kelompok' => $item['kelompok'] ?? '',
        ], $data);
    }
}
