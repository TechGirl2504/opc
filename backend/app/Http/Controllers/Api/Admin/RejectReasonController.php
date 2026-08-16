<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\RejectReason;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RejectReasonController extends Controller
{
    public function active(): JsonResponse
    {
        $reasons = RejectReason::query()
            ->where('is_active', true)
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $reasons,
        ]);
    }

    public function index(): JsonResponse
    {
        $reasons = RejectReason::orderBy('order')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $reasons,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:reject_reasons,name',
            'code' => 'required|string|max:50|unique:reject_reasons,code',
            'description' => 'nullable|string',
            'order' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $reason = RejectReason::create($validated);

        return response()->json([
            'success' => true,
            'data' => $reason,
            'message' => 'Reject reason created successfully',
        ], 201);
    }

    public function show($id): JsonResponse
    {
        $reason = RejectReason::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $reason,
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $reason = RejectReason::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('reject_reasons')->ignore($reason->id)],
            'code' => ['required', 'string', 'max:50', Rule::unique('reject_reasons')->ignore($reason->id)],
            'description' => 'nullable|string',
            'order' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $reason->update($validated);

        return response()->json([
            'success' => true,
            'data' => $reason,
            'message' => 'Reject reason updated successfully',
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $reason = RejectReason::findOrFail($id);
        $reason->delete();

        return response()->json([
            'success' => true,
            'message' => 'Reject reason deleted successfully',
        ]);
    }
}
