<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Neo4jGraphService;
use Illuminate\Http\Request;
use Throwable;

class Neo4jController extends Controller
{
    public function motorcycleRecommendations(Request $request, Neo4jGraphService $graph)
    {
        $limit = (int) $request->integer('limit', 5);
        $limit = max(1, min($limit, 10));

        try {
            $recommendations = $graph->motorcycleRecommendations($request->user()->id, $limit);

            return response()->json([
                'status' => 'success',
                'user_id' => $request->user()->id,
                'recommendation_count' => count($recommendations),
                'data' => $recommendations,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'error',
                'message' => 'Rekomendasi Neo4j belum dapat diambil. Pastikan konfigurasi Neo4j dan proses sinkronisasi sudah dijalankan.',
            ], 503);
        }
    }
}
