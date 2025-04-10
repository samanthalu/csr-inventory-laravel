<?php

namespace App\Http\Controllers\Staff;

use App\Models\Staff;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StaffController extends Controller
{
   /**
     * Display a listing of the staff.
     */
    public function index(): JsonResponse
    {
        $staff = Staff::all();
        return response()->json([
            'status' => 'success',
            'data' => $staff
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created staff in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'staff_first_name' => 'required|string|max:255',
            'staff_last_name' => 'required|string|max:255',
            'staff_email' => 'required|email|unique:staff,staff_email',
            'staff_phone' => 'nullable|string|max:20',
            'staff_position' => 'required|string|max:100',
            'staff_status' => 'required|in:active,inactive,on_leave,left'
        ]);

        $staff = Staff::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Staff created successfully',
            'data' => $staff
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified staff.
     */
    public function show(Staff $staff): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $staff
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified staff in storage.
     */
    public function update(Request $request, Staff $staff): JsonResponse
    {
        $validated = $request->validate([
            'staff_first_name' => 'sometimes|required|string|max:255',
            'staff_last_name' => 'sometimes|required|string|max:255',
            'staff_email' => 'sometimes|required|email|unique:staff,staff_email,' . $staff->staff_id . ',staff_id',
            'staff_phone' => 'nullable|string|max:20',
            'staff_position' => 'sometimes|required|string|max:100',
            'staff_status' => 'sometimes|required|in:active,inactive,on_leave,left'
        ]);

        $staff->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Staff updated successfully',
            'data' => $staff
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified staff from storage.
     */
    public function destroy(Staff $staff): JsonResponse
    {
        $staff->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Staff deleted successfully'
        ], Response::HTTP_OK);
    }
}
