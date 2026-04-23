<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function summary()
    {
        $totalProducts  = DB::table('products')->count();
        $totalValue     = (float) DB::table('products')->sum('prod_cost');
        $availableCount = DB::table('products')->where('prod_current_status', 'Available')->count();
        $hiredOutCount  = DB::table('products')->whereIn('prod_current_status', ['Hired out', 'hired out'])->count();
        $maintenanceCount = DB::table('products')->where('prod_current_status', 'maintenance')->count();
        $disposedCount  = DB::table('products')->where('prod_current_status', 'disposed')->count();

        $activeHires    = DB::table('hires')->where('hire_status', 'active')->count();
        $totalHires     = DB::table('hires')->count();

        $maintenanceLogs    = DB::table('maintenance_logs')->count();
        $maintenanceCost    = (float) DB::table('maintenance_logs')->sum('ml_cost');

        $disposalRecords    = DB::table('disposal_records')->count();
        $valueWrittenOff    = (float) DB::table('disposal_records')->sum('dr_value_at_disposal');

        $staffCount = DB::table('staff')->count();

        return response()->json([
            'data' => [
                'assets' => [
                    'total'       => $totalProducts,
                    'total_value' => $totalValue,
                    'available'   => $availableCount,
                    'hired_out'   => $hiredOutCount,
                    'maintenance' => $maintenanceCount,
                    'disposed'    => $disposedCount,
                ],
                'hire' => [
                    'total'  => $totalHires,
                    'active' => $activeHires,
                ],
                'maintenance' => [
                    'total' => $maintenanceLogs,
                    'cost'  => $maintenanceCost,
                ],
                'disposal' => [
                    'total'            => $disposalRecords,
                    'value_written_off' => $valueWrittenOff,
                ],
                'staff' => [
                    'total' => $staffCount,
                ],
            ]
        ]);
    }

    public function assets()
    {
        $byStatus = DB::table('products')
            ->select('prod_current_status as status', DB::raw('count(*) as count'), DB::raw('sum(prod_cost) as total_value'))
            ->groupBy('prod_current_status')
            ->orderByDesc('count')
            ->get()
            ->map(fn($r) => [
                'status'      => $r->status,
                'count'       => (int) $r->count,
                'total_value' => (float) $r->total_value,
            ]);

        $byCondition = DB::table('products')
            ->select('prod_condition as condition', DB::raw('count(*) as count'))
            ->groupBy('prod_condition')
            ->orderByDesc('count')
            ->get()
            ->map(fn($r) => ['condition' => $r->condition, 'count' => (int) $r->count]);

        $byCategory = DB::table('products')
            ->join('category', 'products.cat_id', '=', 'category.cat_id')
            ->select('category.cat_name as category', DB::raw('count(*) as count'), DB::raw('sum(prod_cost) as total_value'))
            ->groupBy('category.cat_name')
            ->orderByDesc('count')
            ->get()
            ->map(fn($r) => [
                'category'    => $r->category,
                'count'       => (int) $r->count,
                'total_value' => (float) $r->total_value,
            ]);

        $recentlyAdded = DB::table('products')
            ->select('prod_name', 'prod_tag_number', 'prod_cost', 'prod_current_status', 'created_at')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return response()->json([
            'data' => [
                'by_status'     => $byStatus,
                'by_condition'  => $byCondition,
                'by_category'   => $byCategory,
                'recently_added' => $recentlyAdded,
            ]
        ]);
    }

    public function hire()
    {
        $byStatus = DB::table('hires')
            ->select('hire_status', DB::raw('count(*) as count'))
            ->groupBy('hire_status')
            ->get()
            ->map(fn($r) => ['status' => $r->hire_status, 'count' => (int) $r->count]);

        $topProducts = DB::table('hire_items')
            ->join('products', 'hire_items.product_id', '=', 'products.prod_id')
            ->select('products.prod_name', 'products.prod_tag_number', DB::raw('count(*) as hire_count'), DB::raw('sum(hire_items.quantity) as total_qty'))
            ->groupBy('products.prod_id', 'products.prod_name', 'products.prod_tag_number')
            ->orderByDesc('hire_count')
            ->limit(10)
            ->get();

        $recentHires = DB::table('hires')
            ->join('staff', 'hires.staff_id', '=', 'staff.staff_id')
            ->select(
                'hires.id',
                DB::raw("CONCAT(staff.staff_first_name, ' ', staff.staff_last_name) as staff_name"),
                'hires.hire_date',
                'hires.hire_return_date',
                'hires.hire_status'
            )
            ->orderByDesc('hires.hire_date')
            ->limit(10)
            ->get();

        $totalHireItems = (int) DB::table('hire_items')->sum('quantity');

        return response()->json([
            'data' => [
                'by_status'     => $byStatus,
                'top_products'  => $topProducts,
                'recent_hires'  => $recentHires,
                'total_hire_items' => $totalHireItems,
            ]
        ]);
    }

    public function maintenance()
    {
        $byStatus = DB::table('maintenance_logs')
            ->select('ml_status', DB::raw('count(*) as count'), DB::raw('sum(ml_cost) as total_cost'))
            ->groupBy('ml_status')
            ->get()
            ->map(fn($r) => [
                'status'     => $r->ml_status,
                'count'      => (int) $r->count,
                'total_cost' => (float) $r->total_cost,
            ]);

        $topCostItems = DB::table('maintenance_logs')
            ->join('products', 'maintenance_logs.product_id', '=', 'products.prod_id')
            ->select(
                'products.prod_name',
                'products.prod_tag_number',
                'maintenance_logs.ml_reason',
                'maintenance_logs.ml_cost',
                'maintenance_logs.ml_status',
                'maintenance_logs.ml_sent_date'
            )
            ->orderByDesc('maintenance_logs.ml_cost')
            ->limit(10)
            ->get();

        $totalCost = (float) DB::table('maintenance_logs')->sum('ml_cost');
        $totalLogs = (int) DB::table('maintenance_logs')->count();
        $completedLogs = (int) DB::table('maintenance_logs')->where('ml_status', 'completed')->count();

        return response()->json([
            'data' => [
                'by_status'       => $byStatus,
                'top_cost_items'  => $topCostItems,
                'total_cost'      => $totalCost,
                'total_logs'      => $totalLogs,
                'completed_logs'  => $completedLogs,
            ]
        ]);
    }

    public function disposal()
    {
        $byMethod = DB::table('disposal_records')
            ->select('dr_method', DB::raw('count(*) as count'), DB::raw('sum(dr_value_at_disposal) as total_value'))
            ->groupBy('dr_method')
            ->get()
            ->map(fn($r) => [
                'method'      => $r->dr_method,
                'count'       => (int) $r->count,
                'total_value' => (float) $r->total_value,
            ]);

        $byStatus = DB::table('disposal_records')
            ->select('dr_status', DB::raw('count(*) as count'))
            ->groupBy('dr_status')
            ->get()
            ->map(fn($r) => ['status' => $r->dr_status, 'count' => (int) $r->count]);

        $recentRecords = DB::table('disposal_records')
            ->join('products', 'disposal_records.product_id', '=', 'products.prod_id')
            ->select(
                'disposal_records.id',
                'products.prod_name',
                'products.prod_tag_number',
                'disposal_records.dr_method',
                'disposal_records.dr_disposal_date',
                'disposal_records.dr_value_at_disposal',
                'disposal_records.dr_status'
            )
            ->orderByDesc('disposal_records.created_at')
            ->limit(10)
            ->get();

        $totalValue  = (float) DB::table('disposal_records')->sum('dr_value_at_disposal');
        $totalCount  = (int) DB::table('disposal_records')->count();

        return response()->json([
            'data' => [
                'by_method'      => $byMethod,
                'by_status'      => $byStatus,
                'recent_records' => $recentRecords,
                'total_value'    => $totalValue,
                'total_count'    => $totalCount,
            ]
        ]);
    }
}
