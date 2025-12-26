<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApplicationStatus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApplicationStatusController extends Controller
{
    public function index()
    {
        $statuses = ApplicationStatus::orderBy('order')->get();
        
        return response()->json([
            'success' => true,
            'data' => $statuses
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:application_statuses,name',
            'code' => 'required|string|max:50|unique:application_statuses,code',
            'description' => 'nullable|string',
            'order' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $status = ApplicationStatus::create($validated);

        return response()->json([
            'success' => true,
            'data' => $status,
            'message' => 'Application status created successfully'
        ], 201);
    }

    public function show($id)
    {
        $status = ApplicationStatus::findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $status
        ]);
    }

    public function update(Request $request, $id)
    {
        $status = ApplicationStatus::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('application_statuses')->ignore($status->id)],
            'code' => ['required', 'string', 'max:50', Rule::unique('application_statuses')->ignore($status->id)],
            'description' => 'nullable|string',
            'order' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $status->update($validated);

        return response()->json([
            'success' => true,
            'data' => $status,
            'message' => 'Application status updated successfully'
        ]);
    }

    public function destroy($id)
    {
        $status = ApplicationStatus::findOrFail($id);
        $status->delete();

        return response()->json([
            'success' => true,
            'message' => 'Application status deleted successfully'
        ]);
    }
}
