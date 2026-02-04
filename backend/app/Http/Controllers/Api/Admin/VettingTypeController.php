<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\VettingType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VettingTypeController extends Controller
{
    public function index()
    {
        $types = VettingType::orderBy('name')->get();
        
        return response()->json([
            'success' => true,
            'data' => $types
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:vetting_types,name',
            'code' => 'required|string|max:50|unique:vetting_types,code',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $type = VettingType::create($validated);

        return response()->json([
            'success' => true,
            'data' => $type,
            'message' => 'Vetting type created successfully'
        ], 201);
    }

    public function show($id)
    {
        $type = VettingType::findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $type
        ]);
    }

    public function update(Request $request, $id)
    {
        $type = VettingType::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('vetting_types')->ignore($type->id)],
            'code' => ['required', 'string', 'max:50', Rule::unique('vetting_types')->ignore($type->id)],
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $type->update($validated);

        return response()->json([
            'success' => true,
            'data' => $type,
            'message' => 'Vetting type updated successfully'
        ]);
    }

    public function destroy($id)
    {
        $type = VettingType::findOrFail($id);
        $type->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vetting type deleted successfully'
        ]);
    }
}
