<?php

namespace App\Http\Controllers\FieldWork;

use App\Http\Controllers\Controller;
use App\Models\FieldWorkSession;
use App\Models\HireItem;
use App\Models\RaAssetAssignment;
use App\Models\ResearchAssistant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Services\AuditLogger;
use App\Services\RaAssetAssigner;

class RaAssetAssignmentController extends Controller
{
    public function __construct(private RaAssetAssigner $assigner) {}

    /**
     * The linked hire's items, each flagged with whether it can be handed to an RA.
     * An item is available when it hasn't been returned on the hire and no RA in
     * any session of the same hire still holds it — teams on one hire share a pool.
     */
    public function hireAssets($sessionId)
    {
        if (!Gate::allows('view_fieldwork')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $session = FieldWorkSession::findOrFail($sessionId);
        if (!$session->fw_hire_id) {
            return response()->json(['data' => [], 'hire_id' => null]);
        }

        $items = HireItem::with('product:prod_id,prod_name,prod_tag_number,cat_id', 'product.category:cat_id,cat_name')
            ->where('hire_id', $session->fw_hire_id)
            ->get();

        $holders = $this->openAssignments($session->fw_hire_id)
            ->with('assistant:id,ra_name', 'session:id,fw_title')
            ->get()
            ->keyBy('raa_product_id');

        $data = $items->map(function (HireItem $item) use ($holders) {
            $holder = $holders->get($item->product_id);
            $status = $item->is_returned ? 'returned' : ($holder ? 'assigned' : 'available');

            return [
                'prod_id'    => $item->product_id,
                'prod_name'  => $item->product?->prod_name,
                'prod_tag'   => $item->product?->prod_tag_number,
                'cat_id'     => $item->product?->cat_id,
                'cat_name'   => $item->product?->category?->cat_name ?? 'Uncategorised',
                'status'     => $status,
                'held_by'    => $holder ? [
                    'ra_name'       => $holder->assistant?->ra_name,
                    'session_id'    => $holder->raa_session_id,
                    'session_title' => $holder->session?->fw_title,
                ] : null,
            ];
        })->sortBy('prod_tag', SORT_NATURAL)->values();

        return response()->json(['data' => $data, 'hire_id' => $session->fw_hire_id]);
    }

    public function store(Request $request, $sessionId)
    {
        if (!Gate::allows('create_fieldwork')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $session = FieldWorkSession::findOrFail($sessionId);

        $data = $request->validate([
            'raa_ra_id'           => 'required|exists:research_assistants,id',
            'raa_product_id'      => 'required|exists:products,prod_id',
            'raa_date_out'        => 'required|date',
            'raa_expected_return' => 'nullable|date|after_or_equal:raa_date_out',
            'raa_condition_out'   => 'required|in:good,fair,poor',
            'raa_notes'           => 'nullable|string',
        ]);

        if ($error = $this->assignmentError($session, [$data['raa_ra_id']], [$data['raa_product_id']])) {
            return response()->json(['message' => $error], 422);
        }

        $data['raa_session_id'] = $sessionId;
        $assignment = RaAssetAssignment::create(RaAssetAssigner::normaliseDates($data));
        $assignment->load('product:prod_id,prod_name,prod_tag_number');

        AuditLogger::log('fieldwork', 'asset_assigned', "Asset '{$assignment->product?->prod_name}' assigned to RA in session #{$sessionId}", $assignment->id, null, ['session_id' => $sessionId, 'product_id' => $assignment->raa_product_id, 'date_out' => $assignment->raa_date_out]);

        return response()->json(['data' => $assignment], 201);
    }

    /** Assign many hire assets to RAs in one go, sharing dates and condition. */
    public function bulkStore(Request $request, $sessionId)
    {
        if (!Gate::allows('create_fieldwork')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $session = FieldWorkSession::findOrFail($sessionId);

        $data = $request->validate([
            'raa_date_out'                  => 'required|date',
            'raa_expected_return'           => 'nullable|date|after_or_equal:raa_date_out',
            'raa_condition_out'             => 'required|in:good,fair,poor',
            'assignments'                   => 'required|array|min:1|max:500',
            'assignments.*.raa_ra_id'       => 'required|integer',
            'assignments.*.raa_product_id'  => 'required|integer|distinct',
        ]);

        $raIds      = collect($data['assignments'])->pluck('raa_ra_id')->unique()->all();
        $productIds = collect($data['assignments'])->pluck('raa_product_id')->all();

        if ($error = $this->assignmentError($session, $raIds, $productIds)) {
            return response()->json(['message' => $error], 422);
        }

        $created = DB::transaction(fn() => $this->assigner->assign((int) $sessionId, $data['assignments'], $data));

        AuditLogger::log('fieldwork', 'assets_bulk_assigned', "{$created->count()} assets assigned to RAs in session #{$sessionId}", (int) $sessionId, null, ['session_id' => $sessionId, 'product_ids' => $productIds, 'date_out' => $data['raa_date_out']]);

        return response()->json(['data' => $created, 'count' => $created->count()], 201);
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

        $hireItem = DB::transaction(function () use ($assignment, $data) {
            $assignment->update(RaAssetAssigner::normaliseDates($data));
            return $this->syncHireItemReturn($assignment);
        });

        $assignment->load('product:prod_id,prod_name,prod_tag_number');

        AuditLogger::log('fieldwork', 'asset_returned', "Asset '{$assignment->product?->prod_name}' returned in session #{$sessionId}", $assignment->id, null, ['session_id' => $sessionId, 'date_returned' => $assignment->raa_date_returned, 'condition_in' => $assignment->raa_condition_in, 'hire_item_id' => $hireItem?->id]);

        return response()->json(['data' => $assignment, 'hire_item_returned' => (bool) $hireItem]);
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

    /** Assignments not yet returned, across every session linked to the hire. */
    private function openAssignments(int $hireId)
    {
        return RaAssetAssignment::whereNull('raa_date_returned')
            ->whereHas('session', fn($q) => $q->where('fw_hire_id', $hireId));
    }

    /** Why these RAs can't be given these products, or null if they can. */
    private function assignmentError(FieldWorkSession $session, array $raIds, array $productIds): ?string
    {
        $foreignRas = count($raIds) - ResearchAssistant::where('ra_fw_session_id', $session->id)
            ->whereIn('id', $raIds)->count();
        if ($foreignRas > 0) {
            return 'One or more research assistants do not belong to this session.';
        }

        return $this->assigner->productError($session, $productIds);
    }

    /**
     * An RA handing an asset back ends its hire too, so the invoice stops counting
     * days from the RA's return date. Returns the hire item it closed, if any.
     */
    private function syncHireItemReturn(RaAssetAssignment $assignment): ?HireItem
    {
        $hireId = $assignment->session?->fw_hire_id;
        if (!$hireId) {
            return null;
        }

        $item = HireItem::where('hire_id', $hireId)
            ->where('product_id', $assignment->raa_product_id)
            ->where('is_returned', false)
            ->first();
        if (!$item) {
            return null;
        }

        $item->update([
            'is_returned' => true,
            'returned_at' => Carbon::parse($assignment->raa_date_returned)->startOfDay(),
        ]);

        return $item;
    }
}
