<?php

namespace App\Http\Controllers\Disposal;

use App\Http\Controllers\Controller;
use App\Models\DisposalRecord;
use App\Models\Product;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DisposalController extends Controller
{
    private function format(DisposalRecord $rec): array
    {
        return [
            'id'                   => $rec->id,
            'product_id'           => $rec->product_id,
            'dr_disposal_date'     => $rec->dr_disposal_date?->format('Y-m-d'),
            'dr_method'            => $rec->dr_method,
            'dr_reason'            => $rec->dr_reason,
            'dr_authorised_by'     => $rec->dr_authorised_by,
            'dr_value_at_disposal' => $rec->dr_value_at_disposal ? (float) $rec->dr_value_at_disposal : null,
            'dr_recipient'         => $rec->dr_recipient,
            'dr_notes'             => $rec->dr_notes,
            'dr_status'            => $rec->dr_status,
            'created_at'           => $rec->created_at?->format('d M Y'),
            'product' => $rec->product ? [
                'prod_id'             => $rec->product->prod_id,
                'prod_name'           => $rec->product->prod_name,
                'prod_tag_number'     => $rec->product->prod_tag_number,
                'prod_serial_num'     => $rec->product->prod_serial_num,
                'prod_cost'           => $rec->product->prod_cost,
                'prod_current_status' => $rec->product->prod_current_status,
            ] : null,
        ];
    }

    public function index(Request $request)
    {
        if (!Gate::allows('read')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $query = DisposalRecord::with('product')->orderBy('created_at', 'desc');

        if ($request->query('status')) {
            $query->where('dr_status', $request->query('status'));
        }
        if ($request->query('method')) {
            $query->where('dr_method', $request->query('method'));
        }

        return response()->json(['data' => $query->get()->map(fn($r) => $this->format($r))]);
    }

    public function show($id)
    {
        if (!Gate::allows('read')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $rec = DisposalRecord::with('product')->find($id);
        if (!$rec) return response()->json(['message' => 'Record not found'], 404);
        return response()->json(['data' => $this->format($rec)]);
    }

    public function store(Request $request)
    {
        if (!Gate::allows('manage-disposal')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $validated = $request->validate([
            'product_id'           => 'required|exists:products,prod_id',
            'dr_disposal_date'     => 'required|date',
            'dr_method'            => 'required|in:auction,scrap,donation,written_off,stolen,other',
            'dr_reason'            => 'required|string',
            'dr_authorised_by'     => 'nullable|string|max:255',
            'dr_value_at_disposal' => 'nullable|numeric|min:0',
            'dr_recipient'         => 'nullable|string|max:255',
            'dr_notes'             => 'nullable|string',
            'dr_status'            => 'sometimes|in:pending_approval,approved,completed',
        ]);

        DB::beginTransaction();
        try {
            $rec = DisposalRecord::create([
                ...$validated,
                'dr_disposal_date' => Carbon::parse($validated['dr_disposal_date'])->format('Y-m-d'),
                'dr_status'        => $validated['dr_status'] ?? 'pending_approval',
            ]);

            // Immediately mark product as disposed if status is approved or completed
            if (in_array($rec->dr_status, ['approved', 'completed'])) {
                Product::where('prod_id', $validated['product_id'])
                    ->update(['prod_current_status' => 'disposed']);
            }

            DB::commit();
            $rec->load('product');
            $prodName = optional($rec->product)->prod_name ?? "product #{$rec->product_id}";
            AuditLogger::log('disposal', 'created', "Disposal record created for {$prodName}", $rec->id, null, $this->format($rec));
            return response()->json(['message' => 'Disposal record created', 'data' => $this->format($rec)], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create record', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        if (!Gate::allows('manage-disposal')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $rec = DisposalRecord::find($id);
        if (!$rec) return response()->json(['message' => 'Record not found'], 404);

        $validated = $request->validate([
            'dr_disposal_date'     => 'sometimes|date',
            'dr_method'            => 'sometimes|in:auction,scrap,donation,written_off,stolen,other',
            'dr_reason'            => 'sometimes|string',
            'dr_authorised_by'     => 'nullable|string|max:255',
            'dr_value_at_disposal' => 'nullable|numeric|min:0',
            'dr_recipient'         => 'nullable|string|max:255',
            'dr_notes'             => 'nullable|string',
            'dr_status'            => 'sometimes|in:pending_approval,approved,completed',
        ]);

        DB::beginTransaction();
        try {
            $rec->update($validated);

            if (in_array($validated['dr_status'] ?? '', ['approved', 'completed'])) {
                Product::where('prod_id', $rec->product_id)
                    ->update(['prod_current_status' => 'disposed']);
            }

            DB::commit();
            $rec->load('product');
            AuditLogger::log('disposal', 'updated', "Disposal record #{$id} updated", (int) $id, null, $this->format($rec));
            return response()->json(['message' => 'Disposal record updated', 'data' => $this->format($rec)]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update record', 'error' => $e->getMessage()], 500);
        }
    }

    public function approve($id)
    {
        if (!Gate::allows('manage-disposal')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $rec = DisposalRecord::find($id);
        if (!$rec) return response()->json(['message' => 'Record not found'], 404);

        DB::beginTransaction();
        try {
            $rec->update(['dr_status' => 'approved']);
            Product::where('prod_id', $rec->product_id)
                ->update(['prod_current_status' => 'disposed']);
            DB::commit();
            $rec->load('product');
            $prodName = optional($rec->product)->prod_name ?? "product #{$rec->product_id}";
            AuditLogger::log('disposal', 'approved', "Disposal for {$prodName} approved", $rec->id, null, $this->format($rec));
            return response()->json(['message' => 'Disposal approved — product marked as disposed', 'data' => $this->format($rec)]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to approve', 'error' => $e->getMessage()], 500);
        }
    }

    public function complete($id)
    {
        if (!Gate::allows('manage-disposal')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $rec = DisposalRecord::find($id);
        if (!$rec) return response()->json(['message' => 'Record not found'], 404);

        DB::beginTransaction();
        try {
            $rec->update(['dr_status' => 'completed']);
            Product::where('prod_id', $rec->product_id)
                ->update(['prod_current_status' => 'disposed']);
            DB::commit();
            $rec->load('product');
            $prodName = optional($rec->product)->prod_name ?? "product #{$rec->product_id}";
            AuditLogger::log('disposal', 'completed', "Disposal for {$prodName} completed", $rec->id, null, $this->format($rec));
            return response()->json(['message' => 'Disposal completed', 'data' => $this->format($rec)]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to complete', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        if (!Gate::allows('manage-disposal')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $rec = DisposalRecord::find($id);
        if (!$rec) return response()->json(['message' => 'Record not found'], 404);

        // Only reverse status if disposal wasn't finalised
        if ($rec->dr_status === 'pending_approval') {
            Product::where('prod_id', $rec->product_id)
                ->update(['prod_current_status' => 'available']);
        }

        AuditLogger::log('disposal', 'deleted', "Disposal record #{$id} deleted", (int) $id);
        $rec->delete();
        return response()->json(['message' => 'Disposal record deleted']);
    }
}
