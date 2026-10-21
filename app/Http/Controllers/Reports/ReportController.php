<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function summary()
    {
        $totalProducts  = DB::table('products')->whereNull('deleted_at')->count();
        $totalValue     = (float) DB::table('products')->whereNull('deleted_at')->sum('prod_cost');
        $availableCount = DB::table('products')->whereNull('deleted_at')->where('prod_current_status', 'Available')->count();
        $hiredOutCount  = DB::table('products')->whereNull('deleted_at')->whereIn('prod_current_status', ['Hired out', 'hired out'])->count();
        $maintenanceCount = DB::table('products')->whereNull('deleted_at')->where('prod_current_status', 'maintenance')->count();
        $disposedCount  = DB::table('products')->whereNull('deleted_at')->where('prod_current_status', 'disposed')->count();

        $activeHires    = DB::table('hires')->where('hire_status', 'active')->count();
        $totalHires     = DB::table('hires')->count();

        $maintenanceLogs    = DB::table('maintenance_logs')->count();
        $maintenanceCost    = (float) DB::table('maintenance_logs')->sum('ml_cost');

        $disposalRecords    = DB::table('disposal_records')->count();
        $valueWrittenOff    = (float) DB::table('disposal_records')->sum('dr_value_at_disposal');

        $fieldworkTotal      = DB::table('field_work_sessions')->count();
        $fieldworkActive     = DB::table('field_work_sessions')->where('fw_status', 'active')->count();
        $fieldworkUnreturned = DB::table('ra_asset_assignments')->whereNull('raa_date_returned')->count();

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
                'fieldwork' => [
                    'total'      => $fieldworkTotal,
                    'active'     => $fieldworkActive,
                    'unreturned' => $fieldworkUnreturned,
                ],
            ]
        ]);
    }

    /**
     * Main dashboard: 12-month trends, availability by category, what's due back,
     * live field sessions and (for admins) recent activity. Any authenticated user.
     */
    public function dashboard(Request $request)
    {
        $start  = now()->startOfMonth()->subMonths(11);
        $months = collect(range(0, 11))->map(fn($i) => $start->copy()->addMonths($i)->format('Y-m'));

        $monthly = function ($table, $dateCol, $agg = 'count(*)') use ($start) {
            return DB::table($table)
                ->where($dateCol, '>=', $start->toDateString())
                ->selectRaw("DATE_FORMAT($dateCol, '%Y-%m') as ym, $agg as v")
                ->groupBy('ym')
                ->pluck('v', 'ym');
        };
        $series = fn($rows) => $months->map(fn($m) => (float) ($rows[$m] ?? 0))->values();

        $hires       = $monthly('hires', 'hire_date');
        $added       = DB::table('products')->whereNull('deleted_at')
            ->where('created_at', '>=', $start)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, count(*) as v")
            ->groupBy('ym')->pluck('v', 'ym');
        $maintCost   = $monthly('maintenance_logs', 'ml_sent_date', 'sum(ml_cost)');
        $invoiced    = $monthly('invoices', 'created_at', 'sum(total_amount)');

        // Availability per category (top 8 by size). Status values vary in case in the data.
        $byCategory = DB::table('products')
            ->whereNull('products.deleted_at')
            ->join('category', 'products.cat_id', '=', 'category.cat_id')
            ->selectRaw("category.cat_name as category,
                sum(lower(prod_current_status) = 'available') as available,
                sum(lower(prod_current_status) = 'hired out') as hired_out,
                sum(lower(prod_current_status) = 'maintenance') as maintenance,
                count(*) as total")
            ->groupBy('category.cat_name')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn($r) => [
                'category'    => $r->category,
                'available'   => (int) $r->available,
                'hired_out'   => (int) $r->hired_out,
                'maintenance' => (int) $r->maintenance,
                'other'       => (int) $r->total - (int) $r->available - (int) $r->hired_out - (int) $r->maintenance,
            ]);

        $today = now()->toDateString();
        $dueBack = DB::table('hires')
            ->join('staff', 'hires.staff_id', '=', 'staff.staff_id')
            ->where('hires.hire_status', 'active')
            ->whereNotNull('hires.hire_return_date')
            ->where('hires.hire_return_date', '<=', now()->addDays(14)->toDateString())
            ->selectRaw("hires.id, hires.hire_purpose as purpose, hires.hire_return_date as due_date,
                CONCAT(staff.staff_first_name, ' ', staff.staff_last_name) as staff_name,
                (select count(*) from hire_items hi where hi.hire_id = hires.id and hi.is_returned = 0) as items_out")
            ->orderBy('hires.hire_return_date')
            ->limit(8)
            ->get()
            ->map(fn($h) => [
                'id'         => $h->id,
                'staff_name' => $h->staff_name,
                'purpose'    => $h->purpose,
                'due_date'   => $h->due_date,
                'items_out'  => (int) $h->items_out,
                'overdue'    => $h->due_date < $today,
            ]);

        $fieldwork = DB::table('field_work_sessions')
            ->where('fw_status', 'active')
            ->selectRaw('id, fw_title as title, fw_location as location, fw_start_date as start_date, fw_end_date as end_date,
                (select count(*) from research_assistants ra where ra.ra_fw_session_id = field_work_sessions.id) as ras,
                (select count(*) from ra_asset_assignments a
                   join research_assistants ra on ra.id = a.raa_ra_id
                  where ra.ra_fw_session_id = field_work_sessions.id and a.raa_date_returned is null) as unreturned')
            ->orderByRaw('fw_end_date is null, fw_end_date')
            ->limit(5)
            ->get();

        $user    = $request->user();
        $isAdmin = $user && ($user->hasRole('super-admin') || $user->user_type === 'admin');
        $activity = $isAdmin
            ? DB::table('audit_logs')->orderByDesc('created_at')->limit(8)
                ->get(['id', 'user_name', 'module', 'action', 'description', 'created_at'])
            : null;

        return response()->json([
            'data' => [
                'months' => $months,
                'trend'  => [
                    'hires'            => $series($hires),
                    'assets_added'     => $series($added),
                    'maintenance_cost' => $series($maintCost),
                    'invoiced'         => $series($invoiced),
                ],
                'by_category'      => $byCategory,
                'due_back'         => $dueBack,
                'fieldwork'        => $fieldwork,
                'pending_requests' => DB::table('hire_requests')->where('status', 'pending')->count(),
                'activity'         => $activity,
            ],
        ]);
    }

    public function assets()
    {
        $byStatus = DB::table('products')
            ->whereNull('deleted_at')
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
            ->whereNull('deleted_at')
            ->select('prod_condition as condition', DB::raw('count(*) as count'))
            ->groupBy('prod_condition')
            ->orderByDesc('count')
            ->get()
            ->map(fn($r) => ['condition' => $r->condition, 'count' => (int) $r->count]);

        $byCategory = DB::table('products')
            ->whereNull('deleted_at')
            ->join('category', 'products.cat_id', '=', 'category.cat_id')
            ->select('category.cat_id', 'category.cat_name as category', DB::raw('count(*) as count'), DB::raw('sum(prod_cost) as total_value'))
            ->groupBy('category.cat_id', 'category.cat_name')
            ->orderByDesc('count')
            ->get()
            ->map(fn($r) => [
                'cat_id'      => (int) $r->cat_id,
                'category'    => $r->category,
                'count'       => (int) $r->count,
                'total_value' => (float) $r->total_value,
            ]);

        $recentlyAdded = DB::table('products')
            ->whereNull('deleted_at')
            ->select('prod_id', 'prod_name', 'prod_tag_number', 'prod_cost', 'prod_current_status', 'created_at')
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

        // Active hires past their return date (overdue is derived, not stored).
        $overdue = DB::table('hires')
            ->where('hire_status', 'active')
            ->whereNotNull('hire_return_date')
            ->where('hire_return_date', '<', now()->toDateString())
            ->count();

        return response()->json([
            'data' => [
                'by_status'     => $byStatus,
                'overdue'       => $overdue,
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
                'maintenance_logs.id',
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

    public function products(Request $request)
    {
        $categoryId = $request->query('category_id');
        $status     = $request->query('status');
        $condition  = $request->query('condition');
        $hireMonth  = $request->query('hire_month');
        $hireYear   = $request->query('hire_year');

        $hirePeriodActive = $hireMonth || $hireYear;

        $query = DB::table('products')
            ->whereNull('products.deleted_at')
            ->leftJoin('category', 'products.cat_id', '=', 'category.cat_id')
            ->select(
                'products.prod_id',
                'products.prod_name',
                'products.prod_tag_number',
                'products.prod_serial_num',
                'products.prod_model_number',
                'products.prod_cost',
                'products.prod_quantity',
                'products.prod_condition',
                'products.prod_current_status',
                'category.cat_name as category'
            );

        if ($hirePeriodActive) {
            $hireSubquery = DB::table('hire_items')
                ->join('hires', 'hire_items.hire_id', '=', 'hires.id')
                ->select('hire_items.product_id', DB::raw('COUNT(*) as hire_count'));

            if ($hireMonth) {
                $hireSubquery->whereMonth('hires.hire_date', (int) $hireMonth);
            }
            if ($hireYear) {
                $hireSubquery->whereYear('hires.hire_date', (int) $hireYear);
            }

            $hireSubquery->groupBy('hire_items.product_id');

            $query->leftJoinSub($hireSubquery, 'hc', 'hc.product_id', '=', 'products.prod_id')
                  ->addSelect(DB::raw('COALESCE(hc.hire_count, 0) as hire_count'));
        }

        if ($categoryId) {
            $query->where('products.cat_id', (int) $categoryId);
        }

        if ($status) {
            $query->whereRaw('LOWER(products.prod_current_status) = ?', [strtolower($status)]);
        }

        if ($condition) {
            $query->whereRaw('LOWER(products.prod_condition) = ?', [strtolower($condition)]);
        }

        $query->orderBy('products.prod_name');

        $rows = $query->get()->map(function ($r) use ($hirePeriodActive) {
            $row = [
                'prod_id'             => $r->prod_id,
                'prod_name'           => $r->prod_name,
                'prod_tag_number'     => $r->prod_tag_number,
                'prod_serial_num'     => $r->prod_serial_num,
                'prod_model_number'   => $r->prod_model_number,
                'prod_cost'           => (float) $r->prod_cost,
                'prod_quantity'       => (int) $r->prod_quantity,
                'prod_condition'      => $r->prod_condition,
                'prod_current_status' => $r->prod_current_status,
                'category'            => $r->category,
            ];
            if ($hirePeriodActive) {
                $row['hire_count'] = (int) $r->hire_count;
            }
            return $row;
        });

        $totalValue = $rows->sum('prod_cost');

        return response()->json([
            'data'        => $rows->values(),
            'total'       => $rows->count(),
            'total_value' => (float) $totalValue,
            'filters'     => [
                'category_id' => $categoryId ? (int) $categoryId : null,
                'status'      => $status ?: null,
                'condition'   => $condition ?: null,
                'hire_month'  => $hireMonth ? (int) $hireMonth : null,
                'hire_year'   => $hireYear  ? (int) $hireYear  : null,
            ],
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
