<?php

namespace App\Http\Controllers\Software;
use App\Models\Software;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SoftwareController extends Controller
{
    public function index(): JsonResponse
    {
        $softwares = Software::all();
        return response()->json(['data' => $softwares], 200);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'soft_name' => 'required|string|max:255',
            'soft_version' => 'required|string|max:50',
            'soft_category' => 'required|string|max:100',
            'soft_desc' => 'sometimes|string',
            'sup_id' => 'required|exists:suppliers,sup_id',
            'soft_date_purchased' => 'required|date',
            'soft_license_type' => 'required|string|max:100',
            'soft_license_from' => 'sometimes|date',
            'soft_license_to' => 'sometimes|date|after:soft_license_from',
            'soft_license' => 'nullable'
        ]);

        $software = Software::create($validated);
        return response()->json(['data' => $software, 'message' => 'Software created successfully'], 201);
    }

    public function show(Software $software): JsonResponse
    {
        $software->load('supplier');
        return response()->json(['data' => $software], 200);
    }

    public function update(Request $request, Software $software): JsonResponse
    {
        $validated = $request->validate([
            'soft_name' => 'required|string|max:255',
            'soft_version' => 'required|string|max:50',
            'soft_category' => 'required|string|max:100',
            'soft_desc' => 'sometimes|string',
            'sup_id' => 'required|exists:suppliers,sup_id',
            'soft_date_purchased' => 'required|date',
            'soft_license_type' => 'required|string|max:100',
            'soft_license_from' => 'sometimes|date',
            'soft_license_to' => 'sometimes|date|after:soft_license_from',
            'soft_license' => 'nullable'
        ]);

        $software->update($validated);
        return response()->json(['data' => $software, 'message' => 'Software updated successfully'], 200);
    }

    public function destroy(Software $software): JsonResponse
    {
        $software->delete();
        return response()->json(['message' => 'Software deleted successfully'], 204);
    }
}
