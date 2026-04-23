<?php

namespace App\Http\Controllers\Hire;

use App\Http\Controllers\Controller;
use App\Models\Hire;
use App\Models\HireItem;
use App\Models\HireRate;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;

class HireController extends Controller
{
    private function formatHire(Hire $hire): array
    {
        return [
            'id'               => $hire->id,
            'staff_id'         => $hire->staff_id,
            'hire_date'        => $hire->hire_date,
            'hire_return_date' => $hire->hire_return_date,
            'hire_status'      => $hire->hire_status,
            'hire_purpose'     => $hire->hire_purpose,
            'hire_notes'       => $hire->hire_notes,
            'staff'            => $hire->staff,
            'items'            => $hire->items->map(fn($item) => [
                'id'                => $item->id,
                'hire_id'           => $item->hire_id,
                'product_id'        => $item->product_id,
                'quantity'          => $item->quantity,
                'hire_rate_per_day' => $item->hire_rate_per_day,
                'is_returned'       => (bool) $item->is_returned,
                'product'           => $item->product ? [
                    'id'        => $item->product->prod_id,
                    'prod_name' => $item->product->prod_name,
                    'prod_tag'  => $item->product->prod_tag_number,
                    'prod_cost' => $item->product->prod_cost,
                ] : null,
            ])->values(),
        ];
    }

    public function index()
    {
        if (!Gate::allows('read')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $hires = Hire::with(['staff', 'items.product'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($h) => $this->formatHire($h));

        return response()->json(['data' => $hires]);
    }

    public function show($id)
    {
        if (!Gate::allows('read')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $hire = Hire::with(['staff', 'items.product'])->find($id);
        if (!$hire) {
            return response()->json(['message' => 'Hire not found'], 404);
        }
        return response()->json(['data' => $this->formatHire($hire)]);
    }

    public function store(Request $request)
    {
        if (!Gate::allows('manage-hire')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $validated = $request->validate([
            'staff_id'         => 'required|exists:staff,staff_id',
            'hire_date'        => 'required|date',
            'hire_return_date' => 'required|date|after_or_equal:hire_date',
            'hire_purpose'     => 'nullable|string|max:255',
            'hire_notes'       => 'nullable|string',
            'items'            => 'required|array|min:1',
            'items.*.cat_id'   => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.product_ids' => 'required|array|min:1',
            'items.*.product_ids.*' => 'required|exists:products,prod_id',
        ]);

        DB::beginTransaction();
        try {
            $hire = Hire::create([
                'staff_id'         => $validated['staff_id'],
                'hire_date'        => Carbon::parse($validated['hire_date'])->format('Y-m-d'),
                'hire_return_date' => Carbon::parse($validated['hire_return_date'])->format('Y-m-d'),
                'hire_status'      => 'active',
                'hire_purpose'     => $validated['hire_purpose'] ?? null,
                'hire_notes'       => $validated['hire_notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $rate = HireRate::where('hr_item_category', $item['cat_id'])->value('hr_rate');

                foreach ($item['product_ids'] as $productId) {
                    HireItem::create([
                        'hire_id'           => $hire->id,
                        'product_id'        => $productId,
                        'quantity'          => 1,
                        'hire_rate_per_day' => $rate,
                    ]);
                }
            }

            DB::commit();

            $hire->load(['staff', 'items.product']);
            $staffName = optional($hire->staff)->staff_first_name . ' ' . optional($hire->staff)->staff_last_name;
            AuditLogger::log('hire', 'created', "Hire created for {$staffName}", $hire->id, null, $this->formatHire($hire));

            return response()->json([
                'message' => 'Hire created successfully',
                'data'    => $this->formatHire($hire),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create hire', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        if (!Gate::allows('manage-hire')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $hire = Hire::find($id);
        if (!$hire) {
            return response()->json(['message' => 'Hire not found'], 404);
        }

        $validated = $request->validate([
            'hire_status'      => 'sometimes|in:active,returned,overdue',
            'hire_purpose'     => 'sometimes|nullable|string|max:255',
            'hire_notes'       => 'sometimes|nullable|string',
            'hire_return_date' => 'sometimes|date',
        ]);

        $hire->update($validated);
        $hire->load(['staff', 'items.product']);

        AuditLogger::log('hire', 'updated', "Hire #{$id} updated", (int) $id, null, $this->formatHire($hire));

        return response()->json([
            'message' => 'Hire updated successfully',
            'data'    => $this->formatHire($hire),
        ]);
    }

    public function returnItem($hireId, $itemId)
    {
        if (!Gate::allows('manage-hire')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $item = HireItem::where('hire_id', $hireId)->where('id', $itemId)->first();
        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        $item->update(['is_returned' => true]);

        return response()->json(['message' => 'Item marked as returned']);
    }

    public function return($id)
    {
        if (!Gate::allows('manage-hire')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $hire = Hire::find($id);
        if (!$hire) {
            return response()->json(['message' => 'Hire not found'], 404);
        }

        $hire->update(['hire_status' => 'returned']);

        AuditLogger::log('hire', 'returned', "Hire #{$id} marked as returned", (int) $id);

        return response()->json(['message' => 'Hire marked as returned']);
    }

    public function destroy($id)
    {
        if (!Gate::allows('manage-hire')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $hire = Hire::find($id);
        if (!$hire) {
            return response()->json(['message' => 'Hire not found'], 404);
        }

        AuditLogger::log('hire', 'deleted', "Hire #{$id} deleted", (int) $id);
        $hire->delete();
        return response()->json(['message' => 'Hire deleted successfully']);
    }
}
