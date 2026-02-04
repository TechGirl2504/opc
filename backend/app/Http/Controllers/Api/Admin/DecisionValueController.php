<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DecisionValue;
use Illuminate\Http\JsonResponse;

class DecisionValueController extends Controller
{
    /**
     * Display a listing of decision values
     * Accessible to all authenticated users (needed for vetting recommendations)
     */
    public function index(): JsonResponse
    {
        try {
            $values = DecisionValue::where('is_active', true)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $values
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SERVER_ERROR',
                    'message' => 'Failed to retrieve decision values',
                ],
            ], 500);
        }
    }
}

