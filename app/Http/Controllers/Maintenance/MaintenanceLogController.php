<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceLog;
use App\Models\Product;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaintenanceLogController extends Controller
{
    private function format(MaintenanceLog $log): array
    {
        return [
            'id'                      => $log->id,
            'product_id'              => $log->product_id,
            'ml_sent_date'            => $log->ml_sent_date?->format('Y-m-d'),
            'ml_expected_return_date' => $log->ml_expected_return_date?->format('Y-m-d'),
            'ml_actual_return_date'   => $log->ml_actual_return_date?->format('Y-m-d'),
            'ml_technician'           => $log->ml_technician,
            'ml_cost'                 => $log->ml_cost ? (float) $log->ml_cost : null,
            'ml_reason'               => $log->ml_reason,
            'ml_notes'                => $log->ml_notes,
            'ml_status'               => $log->ml_status,
            'created_at'              => $log->created_at?->format('d M Y'),
            'product' => $log->product ? [
                'prod_id'             => $log->product->prod_id,
                'prod_name'           => $log->product->prod_name,
                'prod_tag_number'     => $log->product->prod_tag_number,
                'prod_serial_num'     => $log->product->prod_serial_num,
                'prod_current_status' => $log->product->prod_current_status,
            ] : null,
        ];
    }

    public function index(Request $request)
    {
        if (!Gate::allows('read')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $query = MaintenanceLog::with('product')->orderBy('created_at', 'desc');

        if ($request->query('status')) {
            $query->where('ml_status', $request->query('status'));
        }

        return response()->json(['data' => $query->get()->map(fn($l) => $this->format($l))]);
    }

    public function show($id)
    {
        if (!Gate::allows('read')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $log = MaintenanceLog::with('product')->find($id);
        if (!$log) return response()->json(['message' => 'Record not found'], 404);
        return response()->json(['data' => $this->format($log)]);
    }

    public function store(Request $request)
    {
        if (!Gate::allows('manage-maintenance')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $validated = $request->validate([
            'product_id'              => 'required|exists:products,prod_id',
            'ml_sent_date'            => 'required|date',
            'ml_expected_return_date' => 'nullable|date|after_or_equal:ml_sent_date',
            'ml_technician'           => 'nullable|string|max:255',
            'ml_cost'                 => 'nullable|numeric|min:0',
            'ml_reason'               => 'required|string',
            'ml_notes'                => 'nullable|string',
            'ml_status'               => 'sometimes|in:pending,in_progress,completed,cancelled',
        ]);

        DB::beginTransaction();
        try {
            $log = MaintenanceLog::create([
                ...$validated,
                'ml_sent_date'  => Carbon::parse($validated['ml_sent_date'])->format('Y-m-d'),
                'ml_expected_return_date' => isset($validated['ml_expected_return_date'])
                    ? Carbon::parse($validated['ml_expected_return_date'])->format('Y-m-d')
                    : null,
                'ml_status' => $validated['ml_status'] ?? 'pending',
            ]);

            // Flag product as under maintenance
            Product::where('prod_id', $validated['product_id'])
                ->update(['prod_current_status' => 'maintenance']);

            DB::commit();
            $log->load('product');
            $prodName = optional($log->product)->prod_name ?? "product #{$log->product_id}";
            AuditLogger::log('maintenance', 'created', "Maintenance log created for {$prodName}", $log->id, null, $this->format($log));
            return response()->json(['message' => 'Maintenance log created', 'data' => $this->format($log)], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create log', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        if (!Gate::allows('manage-maintenance')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $log = MaintenanceLog::find($id);
        if (!$log) return response()->json(['message' => 'Record not found'], 404);

        $validated = $request->validate([
            'ml_expected_return_date' => 'nullable|date',
            'ml_actual_return_date'   => 'nullable|date',
            'ml_technician'           => 'nullable|string|max:255',
            'ml_cost'                 => 'nullable|numeric|min:0',
            'ml_reason'               => 'sometimes|string',
            'ml_notes'                => 'nullable|string',
            'ml_status'               => 'sometimes|in:pending,in_progress,completed,cancelled',
        ]);

        DB::beginTransaction();
        try {
            $log->update($validated);

            // Restore product status when maintenance completes or is cancelled
            if (in_array($validated['ml_status'] ?? '', ['completed', 'cancelled'])) {
                Product::where('prod_id', $log->product_id)
                    ->update(['prod_current_status' => 'available']);
            }

            DB::commit();
            $log->load('product');
            AuditLogger::log('maintenance', 'updated', "Maintenance log #{$id} updated", (int) $id, null, $this->format($log));
            return response()->json(['message' => 'Maintenance log updated', 'data' => $this->format($log)]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update log', 'error' => $e->getMessage()], 500);
        }
    }

    public function complete(Request $request, $id)
    {
        if (!Gate::allows('manage-maintenance')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $log = MaintenanceLog::find($id);
        if (!$log) return response()->json(['message' => 'Record not found'], 404);

        $validated = $request->validate([
            'ml_actual_return_date' => 'required|date',
            'ml_cost'               => 'nullable|numeric|min:0',
            'ml_notes'              => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $log->update([
                'ml_status'             => 'completed',
                'ml_actual_return_date' => Carbon::parse($validated['ml_actual_return_date'])->format('Y-m-d'),
                'ml_cost'               => $validated['ml_cost'] ?? $log->ml_cost,
                'ml_notes'              => $validated['ml_notes'] ?? $log->ml_notes,
            ]);

            Product::where('prod_id', $log->product_id)
                ->update(['prod_current_status' => 'available']);

            DB::commit();
            $log->load('product');
            $prodName = optional($log->product)->prod_name ?? "product #{$log->product_id}";
            AuditLogger::log('maintenance', 'completed', "Maintenance for {$prodName} completed", $log->id, null, $this->format($log));
            return response()->json(['message' => 'Maintenance completed — product marked available', 'data' => $this->format($log)]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to complete maintenance', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        if (!Gate::allows('manage-maintenance')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $log = MaintenanceLog::find($id);
        if (!$log) return response()->json(['message' => 'Record not found'], 404);

        // Only restore status if still in maintenance
        if (!in_array($log->ml_status, ['completed', 'cancelled'])) {
            Product::where('prod_id', $log->product_id)
                ->update(['prod_current_status' => 'available']);
        }

        AuditLogger::log('maintenance', 'deleted', "Maintenance log #{$id} deleted", (int) $id);
        $log->delete();
        return response()->json(['message' => 'Maintenance log deleted']);
    }
}
