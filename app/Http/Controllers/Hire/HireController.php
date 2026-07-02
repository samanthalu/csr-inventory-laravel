<?php

namespace App\Http\Controllers\Hire;

use App\Http\Controllers\Controller;
use App\Models\Hire;
use App\Models\HireItem;
use App\Models\HireRate;
use App\Models\Product;
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
                'returned_at'       => $item->returned_at,
                'is_invoiced'       => $item->relationLoaded('invoiceItem') ? (bool) $item->invoiceItem : $item->invoiceItem()->exists(),
                'product'           => $item->product ? [
                    'id'        => $item->product->prod_id,
                    'prod_name' => $item->product->prod_name,
                    'prod_tag'  => $item->product->prod_tag_number,
                    'prod_cost' => $item->product->prod_cost,
                ] : null,
            ])->values(),
        ];
    }

    public function index(Request $request)
    {
        if (!Gate::allows('view_hires')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $perPage = max(1, min((int) $request->input('per_page', 10), 100));
        $search  = trim((string) $request->input('search', ''));
        $status  = $request->input('status', 'all');
        $today   = Carbon::today()->toDateString();

        // Base query with the search term applied (shared by counts and the page).
        $base = Hire::query()->when($search !== '', function ($q) use ($search) {
            $q->where(function ($q) use ($search) {
                $q->whereHas('staff', function ($s) use ($search) {
                    $s->where('staff_first_name', 'like', "%{$search}%")
                      ->orWhere('staff_last_name', 'like', "%{$search}%")
                      ->orWhereRaw("CONCAT(staff_first_name, ' ', staff_last_name) LIKE ?", ["%{$search}%"]);
                })
                ->orWhere('hire_purpose', 'like', "%{$search}%");

                $idSearch = ltrim($search, '#');
                if (is_numeric($idSearch)) {
                    $q->orWhere('id', (int) $idSearch);
                }
            });
        });

        // Summary counts (respect search, ignore the selected status). Overdue is derived.
        $counts = [
            'all'      => (clone $base)->count(),
            'returned' => (clone $base)->where('hire_status', 'returned')->count(),
            'overdue'  => (clone $base)->where('hire_status', '!=', 'returned')
                                       ->whereDate('hire_return_date', '<', $today)->count(),
            'active'   => (clone $base)->where('hire_status', '!=', 'returned')
                                       ->whereDate('hire_return_date', '>=', $today)->count(),
        ];

        // Page query: apply the status filter, eager-load and order.
        $query = (clone $base)->with(['staff', 'items.product', 'items.invoiceItem'])->orderBy('created_at', 'desc');
        if ($status === 'returned') {
            $query->where('hire_status', 'returned');
        } elseif ($status === 'overdue') {
            $query->where('hire_status', '!=', 'returned')->whereDate('hire_return_date', '<', $today);
        } elseif ($status === 'active') {
            $query->where('hire_status', '!=', 'returned')->whereDate('hire_return_date', '>=', $today);
        }

        $page = $query->paginate($perPage);

        return response()->json([
            'data'   => collect($page->items())->map(fn($h) => $this->formatHire($h))->values(),
            'meta'   => [
                'current_page' => $page->currentPage(),
                'last_page'    => $page->lastPage(),
                'per_page'     => $page->perPage(),
                'total'        => $page->total(),
                'from'         => $page->firstItem(),
                'to'           => $page->lastItem(),
            ],
            'counts' => $counts,
        ]);
    }

    public function show($id)
    {
        if (!Gate::allows('view_hires')) {
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

        $item->update(['is_returned' => true, 'returned_at' => now()]);

        return response()->json(['message' => 'Item marked as returned']);
    }

    public function unreturnItem($hireId, $itemId)
    {
        if (!Gate::allows('manage-hire')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $item = HireItem::where('hire_id', $hireId)->where('id', $itemId)->first();
        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        if ($item->invoiceItem()->exists()) {
            return response()->json(['message' => 'Cannot unreturn an item that has already been invoiced.'], 422);
        }

        $item->update(['is_returned' => false, 'returned_at' => null]);

        // If the hire was fully returned, an item coming back out reopens it.
        $hire = Hire::find($hireId);
        if ($hire && $hire->hire_status === 'returned') {
            $hire->update(['hire_status' => 'active']);
        }

        AuditLogger::log('hire', 'item_unreturned', "Item #{$itemId} on hire #{$hireId} marked as not returned", (int) $hireId);

        return response()->json([
            'message'     => 'Item marked as not returned',
            'hire_status' => $hire?->hire_status,
        ]);
    }

    public function addItems(Request $request, $id)
    {
        if (!Gate::allows('manage-hire')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $hire = Hire::find($id);
        if (!$hire) {
            return response()->json(['message' => 'Hire not found'], 404);
        }

        $validated = $request->validate([
            'product_ids'   => 'required|array|min:1',
            'product_ids.*' => 'required|exists:products,prod_id',
        ]);

        // Skip products already on this hire to avoid duplicate rows.
        $existing = $hire->items()->pluck('product_id')->all();
        $toAdd    = array_values(array_diff(array_unique($validated['product_ids']), $existing));

        if (empty($toAdd)) {
            return response()->json(['message' => 'Selected assets are already on this hire'], 422);
        }

        DB::beginTransaction();
        try {
            foreach ($toAdd as $productId) {
                $product = Product::find($productId);
                $rate    = HireRate::where('hr_item_category', $product->cat_id)->value('hr_rate');

                HireItem::create([
                    'hire_id'           => $hire->id,
                    'product_id'        => $productId,
                    'quantity'          => 1,
                    'hire_rate_per_day' => $rate,
                    'is_returned'       => false,
                ]);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to add assets', 'error' => $e->getMessage()], 500);
        }

        $hire->load(['staff', 'items.product']);
        AuditLogger::log('hire', 'items_added', count($toAdd) . " asset(s) added to hire #{$id}", (int) $id, null, $this->formatHire($hire));

        return response()->json([
            'message' => count($toAdd) . ' asset(s) added',
            'data'    => $this->formatHire($hire),
        ]);
    }

    public function removeItem($hireId, $itemId)
    {
        if (!Gate::allows('manage-hire')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $hire = Hire::with('items')->find($hireId);
        if (!$hire) {
            return response()->json(['message' => 'Hire not found'], 404);
        }
        $item = $hire->items->firstWhere('id', (int) $itemId);
        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }
        if ($hire->items->count() <= 1) {
            return response()->json(['message' => 'A hire must have at least one asset. Add another before removing this one.'], 422);
        }

        $item->delete();

        $hire->load(['staff', 'items.product']);
        AuditLogger::log('hire', 'item_removed', "Asset removed from hire #{$hireId}", (int) $hireId, null, $this->formatHire($hire));

        return response()->json([
            'message' => 'Asset removed',
            'data'    => $this->formatHire($hire),
        ]);
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
        $hire->items()->whereNull('returned_at')->update(['returned_at' => now()]);
        $hire->items()->update(['is_returned' => true]);

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
