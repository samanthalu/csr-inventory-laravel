<?php

namespace App\Http\Controllers\FieldWork;

use App\Http\Controllers\Controller;
use App\Models\ResearchAssistant;
use App\Models\FieldWorkSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Services\AuditLogger;
use App\Services\RaAssetAssigner;

class ResearchAssistantController extends Controller
{
    /** Add an RA, optionally handing them assets in the same step (all or nothing). */
    public function store(Request $request, $sessionId, RaAssetAssigner $assigner)
    {
        if (!Gate::allows('create_fieldwork')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $session = FieldWorkSession::findOrFail($sessionId);

        $data = $request->validate([
            'ra_name'      => 'required|string|max:255',
            'ra_phone'     => 'nullable|string|max:50',
            'ra_email'     => 'nullable|email|max:255',
            'ra_id_number' => 'nullable|string|max:100',
            'ra_district'  => 'nullable|string|max:100',
            'ra_notes'     => 'nullable|string',
        ]);

        $assets = $request->validate([
            'assets'                     => 'nullable|array',
            'assets.product_ids'         => 'required_with:assets|array|max:20',
            'assets.product_ids.*'       => 'integer|distinct',
            'assets.raa_date_out'        => 'required_with:assets|date',
            'assets.raa_expected_return' => 'nullable|date|after_or_equal:assets.raa_date_out',
            'assets.raa_condition_out'   => 'required_with:assets|in:good,fair,poor',
        ])['assets'] ?? null;
        $productIds = $assets['product_ids'] ?? [];

        if ($productIds && ($error = $assigner->productError($session, $productIds))) {
            return response()->json(['message' => $error], 422);
        }

        $data['ra_fw_session_id'] = $session->id;
        [$ra, $assignments] = DB::transaction(function () use ($data, $session, $assets, $productIds, $assigner) {
            $ra = ResearchAssistant::create($data);
            $pairs = array_map(fn($id) => ['raa_ra_id' => $ra->id, 'raa_product_id' => $id], $productIds);
            return [$ra, $pairs ? $assigner->assign($session->id, $pairs, $assets) : collect()];
        });

        AuditLogger::log('fieldwork', 'ra_added', "Research assistant '{$ra->ra_name}' added to session #{$session->id}", $ra->id, null, ['session_id' => $session->id, 'name' => $ra->ra_name, 'product_ids' => $productIds]);

        return response()->json(['data' => [...$ra->toArray(), 'assignments' => $assignments]], 201);
    }

    public function bulkStore(Request $request, $sessionId)
    {
        if (!Gate::allows('create_fieldwork')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $session = FieldWorkSession::findOrFail($sessionId);

        $request->validate([
            'assistants'               => 'required|array|min:1',
            'assistants.*.ra_name'     => 'required|string|max:255',
            'assistants.*.ra_phone'    => 'nullable|string|max:50',
            'assistants.*.ra_email'    => 'nullable|email|max:255',
            'assistants.*.ra_id_number'=> 'nullable|string|max:100',
            'assistants.*.ra_district' => 'nullable|string|max:100',
            'assistants.*.ra_notes'    => 'nullable|string',
        ]);

        $created = collect($request->assistants)->map(function ($row) use ($session) {
            return ResearchAssistant::create(array_merge($row, ['ra_fw_session_id' => $session->id]));
        });

        AuditLogger::log('fieldwork', 'ra_bulk_added', "{$created->count()} research assistant(s) added to session #{$session->id}", $session->id, null, ['session_id' => $session->id, 'count' => $created->count(), 'names' => $created->pluck('ra_name')->all()]);

        return response()->json(['data' => $created, 'count' => $created->count()], 201);
    }

    public function update(Request $request, $sessionId, $id)
    {
        if (!Gate::allows('update_fieldwork')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $ra = ResearchAssistant::where('ra_fw_session_id', $sessionId)->findOrFail($id);

        $data = $request->validate([
            'ra_name'      => 'sometimes|string|max:255',
            'ra_phone'     => 'nullable|string|max:50',
            'ra_email'     => 'nullable|email|max:255',
            'ra_id_number' => 'nullable|string|max:100',
            'ra_district'  => 'nullable|string|max:100',
            'ra_notes'     => 'nullable|string',
        ]);

        $ra->update($data);

        AuditLogger::log('fieldwork', 'ra_updated', "Research assistant '{$ra->ra_name}' updated in session #{$sessionId}", $ra->id, null, $data);

        return response()->json(['data' => $ra]);
    }

    public function bulkDestroy(Request $request, $sessionId)
    {
        if (!Gate::allows('delete_fieldwork')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $ras = ResearchAssistant::where('ra_fw_session_id', $sessionId)
            ->whereIn('id', $request->ids)
            ->get();

        $deleted = $ras->count();
        $names   = $ras->pluck('ra_name')->all();

        ResearchAssistant::where('ra_fw_session_id', $sessionId)
            ->whereIn('id', $request->ids)
            ->delete();

        AuditLogger::log('fieldwork', 'ra_bulk_deleted', "{$deleted} research assistant(s) removed from session #{$sessionId}", $sessionId, null, ['session_id' => $sessionId, 'count' => $deleted, 'names' => $names]);

        return response()->json(['deleted' => $deleted]);
    }

    public function destroy($sessionId, $id)
    {
        if (!Gate::allows('delete_fieldwork')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $ra = ResearchAssistant::where('ra_fw_session_id', $sessionId)->findOrFail($id);
        AuditLogger::log('fieldwork', 'ra_deleted', "Research assistant '{$ra->ra_name}' removed from session #{$sessionId}", $ra->id);
        $ra->delete();

        return response()->noContent();
    }
}
