<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InstitutionController extends Controller
{
    public function index()
    {
        $institutions = Institution::orderBy('name')->get();
        
        return response()->json([
            'success' => true,
            'data' => $institutions
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:institutions,name',
            'code' => 'required|string|max:50|unique:institutions,code',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $institution = Institution::create($validated);

        return response()->json([
            'success' => true,
            'data' => $institution,
            'message' => 'Institution created successfully'
        ], 201);
    }

    public function show($id)
    {
        $institution = Institution::findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $institution
        ]);
    }

    public function update(Request $request, $id)
    {
        $institution = Institution::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('institutions')->ignore($institution->id)],
            'code' => ['required', 'string', 'max:50', Rule::unique('institutions')->ignore($institution->id)],
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $institution->update($validated);

        return response()->json([
            'success' => true,
            'data' => $institution,
            'message' => 'Institution updated successfully'
        ]);
    }

    public function destroy($id)
    {
        $institution = Institution::findOrFail($id);
        $institution->delete();

        return response()->json([
            'success' => true,
            'message' => 'Institution deleted successfully'
        ]);
    }
}
