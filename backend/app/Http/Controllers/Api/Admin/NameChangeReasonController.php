<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\NameChangeReason;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NameChangeReasonController extends Controller
{
    public function active(): JsonResponse
    {
        $reasons = NameChangeReason::query()
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
        $reasons = NameChangeReason::orderBy('order')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $reasons,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:name_change_reasons,name',
            'code' => 'required|string|max:50|unique:name_change_reasons,code',
            'description' => 'nullable|string',
            'order' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $reason = NameChangeReason::create($validated);

        return response()->json([
            'success' => true,
            'data' => $reason,
            'message' => 'Name change reason created successfully',
        ], 201);
    }

    public function show($id): JsonResponse
    {
        $reason = NameChangeReason::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $reason,
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $reason = NameChangeReason::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('name_change_reasons')->ignore($reason->id)],
            'code' => ['required', 'string', 'max:50', Rule::unique('name_change_reasons')->ignore($reason->id)],
            'description' => 'nullable|string',
            'order' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $reason->update($validated);

        return response()->json([
            'success' => true,
            'data' => $reason,
            'message' => 'Name change reason updated successfully',
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $reason = NameChangeReason::findOrFail($id);
        $reason->delete();

        return response()->json([
            'success' => true,
            'message' => 'Name change reason deleted successfully',
        ]);
    }
}
