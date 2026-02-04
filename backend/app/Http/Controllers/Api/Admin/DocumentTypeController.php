<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DocumentTypeController extends Controller
{
    public function index()
    {
        // Get active document types, ordered by name
        $types = DocumentType::where('is_active', true)
            ->orWhereNull('is_active') // Include types where is_active is null (backward compatibility)
            ->orderBy('name')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $types
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:document_types,name',
            'code' => 'required|string|max:50|unique:document_types,code',
            'description' => 'nullable|string',
            'max_file_size' => 'required|integer|min:1',
            'allowed_mime_types' => 'required|array',
            'allowed_mime_types.*' => 'string',
            'is_active' => 'boolean',
        ]);

        $type = DocumentType::create($validated);

        return response()->json([
            'success' => true,
            'data' => $type,
            'message' => 'Document type created successfully'
        ], 201);
    }

    public function show($id)
    {
        $type = DocumentType::findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $type
        ]);
    }

    public function update(Request $request, $id)
    {
        $type = DocumentType::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('document_types')->ignore($type->id)],
            'code' => ['required', 'string', 'max:50', Rule::unique('document_types')->ignore($type->id)],
            'description' => 'nullable|string',
            'max_file_size' => 'required|integer|min:1',
            'allowed_mime_types' => 'required|array',
            'allowed_mime_types.*' => 'string',
            'is_active' => 'boolean',
        ]);

        $type->update($validated);

        return response()->json([
            'success' => true,
            'data' => $type,
            'message' => 'Document type updated successfully'
        ]);
    }

    public function destroy($id)
    {
        $type = DocumentType::findOrFail($id);
        $type->delete();

        return response()->json([
            'success' => true,
            'message' => 'Document type deleted successfully'
        ]);
    }
}
