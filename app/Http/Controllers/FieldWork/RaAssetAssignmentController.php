<?php

namespace App\Http\Controllers\FieldWork;

use App\Http\Controllers\Controller;
use App\Models\RaAssetAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Services\AuditLogger;

class RaAssetAssignmentController extends Controller
{
    public function store(Request $request, $sessionId)
    {
        if (!Gate::allows('create_fieldwork')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'raa_ra_id'           => 'required|exists:research_assistants,id',
            'raa_product_id'      => 'required|exists:products,prod_id',
            'raa_date_out'        => 'required|date',
            'raa_expected_return' => 'nullable|date|after_or_equal:raa_date_out',
            'raa_condition_out'   => 'required|in:good,fair,poor',
            'raa_notes'           => 'nullable|string',
        ]);

        $data['raa_session_id'] = $sessionId;
        $assignment = RaAssetAssignment::create($data);
        $assignment->load('product:prod_id,prod_name,prod_tag_number');

        AuditLogger::log('fieldwork', 'asset_assigned', "Asset '{$assignment->product?->prod_name}' assigned to RA in session #{$sessionId}", $assignment->id, null, ['session_id' => $sessionId, 'product_id' => $assignment->raa_product_id, 'date_out' => $assignment->raa_date_out]);

        return response()->json(['data' => $assignment], 201);
    }

    public function return(Request $request, $sessionId, $id)
    {
        if (!Gate::allows('update_fieldwork')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $assignment = RaAssetAssignment::where('raa_session_id', $sessionId)->findOrFail($id);

        $data = $request->validate([
            'raa_date_returned' => 'required|date',
            'raa_condition_in'  => 'required|in:good,fair,poor,damaged',
            'raa_notes'         => 'nullable|string',
        ]);

        $assignment->update($data);
        $assignment->load('product:prod_id,prod_name,prod_tag_number');

        AuditLogger::log('fieldwork', 'asset_returned', "Asset '{$assignment->product?->prod_name}' returned in session #{$sessionId}", $assignment->id, null, ['session_id' => $sessionId, 'date_returned' => $assignment->raa_date_returned, 'condition_in' => $assignment->raa_condition_in]);

        return response()->json(['data' => $assignment]);
    }

    public function destroy($sessionId, $id)
    {
        if (!Gate::allows('delete_fieldwork')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $assignment = RaAssetAssignment::where('raa_session_id', $sessionId)->findOrFail($id);
        AuditLogger::log('fieldwork', 'assignment_deleted', "Asset assignment #{$id} deleted from session #{$sessionId}", $id);
        $assignment->delete();

        return response()->noContent();
    }
}
