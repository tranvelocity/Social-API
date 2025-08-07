<?php

namespace Modules\PingPong\Http\Controllers\Api\v2;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class PingPongController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json(['data' => 'Welcome to version 2'], 200);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => 'show'], 200);
    }

    /**
     * Update a resource.
     * @return JsonResponse
     */
    public function update(): JsonResponse
    {
        return response()->json(['data' => 'update'], 200);
    }

    /**
     * Update a resource.
     * @return JsonResponse
     */
    public function destroy(): JsonResponse
    {
        return response()->json(['data' => 'destroy'], 200);
    }
}
