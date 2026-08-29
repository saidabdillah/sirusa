<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\KampusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KampusController extends Controller
{
    public function __construct(
        private KampusService $kampus
    ) {}

    public function search(Request $request): JsonResponse
    {
        $keyword = $request->input('q', '');

        // if (strlen($keyword) < 2) {
        //     return response()->json([]);
        // }

        $results = $this->kampus->search($keyword);

        return response()->json($results);
    }
}
