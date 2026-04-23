<?php

namespace App\Http\Controllers\Hire;

use App\Http\Controllers\Controller;
use App\Models\HireRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class HireRateController extends Controller
{
    private function format(HireRate $r): array
    {
        return [
            'id'             => $r->hr_id,
            'cat_id'         => $r->hr_item_category,
            'rate_per_day'   => (float) $r->hr_rate,
            'rate_per_week'  => $r->hr_rate_per_week  ? (float) $r->hr_rate_per_week  : null,
            'rate_per_month' => $r->hr_rate_per_month ? (float) $r->hr_rate_per_month : null,
            'category'       => $r->category ? ['cat_name' => $r->category->cat_name] : null,
        ];
    }

    public function index()
    {
        if (!Gate::allows('read')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $rates = HireRate::with('category')->get()->map(fn($r) => $this->format($r));
        return response()->json(['data' => $rates]);
    }

    public function store(Request $request)
    {
        if (!Gate::allows('admin-only')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $validated = $request->validate([
            'cat_id'         => 'required|integer|exists:categories,cat_id|unique:hire_rates,hr_item_category',
            'rate_per_day'   => 'required|numeric|min:0',
            'rate_per_week'  => 'nullable|numeric|min:0',
            'rate_per_month' => 'nullable|numeric|min:0',
        ]);

        $rate = HireRate::create([
            'hr_item_category' => $validated['cat_id'],
            'hr_rate'          => $validated['rate_per_day'],
            'hr_rate_per_week' => $validated['rate_per_week'] ?? null,
            'hr_rate_per_month'=> $validated['rate_per_month'] ?? null,
        ]);

        $rate->load('category');

        return response()->json([
            'message' => 'Hire rate created successfully',
            'data'    => $this->format($rate),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        if (!Gate::allows('admin-only')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $rate = HireRate::find($id);
        if (!$rate) {
            return response()->json(['message' => 'Hire rate not found'], 404);
        }

        $validated = $request->validate([
            'rate_per_day'   => 'required|numeric|min:0',
            'rate_per_week'  => 'nullable|numeric|min:0',
            'rate_per_month' => 'nullable|numeric|min:0',
        ]);

        $rate->update([
            'hr_rate'          => $validated['rate_per_day'],
            'hr_rate_per_week' => $validated['rate_per_week'] ?? null,
            'hr_rate_per_month'=> $validated['rate_per_month'] ?? null,
        ]);

        $rate->load('category');

        return response()->json([
            'message' => 'Hire rate updated successfully',
            'data'    => $this->format($rate),
        ]);
    }

    public function destroy($id)
    {
        if (!Gate::allows('admin-only')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $rate = HireRate::find($id);
        if (!$rate) {
            return response()->json(['message' => 'Hire rate not found'], 404);
        }

        $rate->delete();
        return response()->json(['message' => 'Hire rate deleted successfully']);
    }
}
