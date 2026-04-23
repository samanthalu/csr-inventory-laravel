<?php

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class AlertController extends Controller
{
    public function index()
    {
        $today    = now()->toDateString();
        $in7days  = now()->addDays(7)->toDateString();
        $in30days = now()->addDays(30)->toDateString();

        // Overdue hires: active but past return date
        $overdueHires = DB::table('hires')
            ->join('staff', 'hires.staff_id', '=', 'staff.staff_id')
            ->where('hires.hire_status', 'active')
            ->where('hires.hire_return_date', '<', $today)
            ->whereNotNull('hires.hire_return_date')
            ->select(
                'hires.id',
                DB::raw("CONCAT(staff.staff_first_name, ' ', staff.staff_last_name) as staff_name"),
                'hires.hire_return_date as due_date'
            )
            ->get();

        // Hires due within 7 days
        $hiresDueSoon = DB::table('hires')
            ->join('staff', 'hires.staff_id', '=', 'staff.staff_id')
            ->where('hires.hire_status', 'active')
            ->whereBetween('hires.hire_return_date', [$today, $in7days])
            ->select(
                'hires.id',
                DB::raw("CONCAT(staff.staff_first_name, ' ', staff.staff_last_name) as staff_name"),
                'hires.hire_return_date as due_date'
            )
            ->get();

        // Overdue maintenance
        $overdueMainenance = DB::table('maintenance_logs')
            ->join('products', 'maintenance_logs.product_id', '=', 'products.prod_id')
            ->whereIn('maintenance_logs.ml_status', ['pending', 'in_progress'])
            ->whereNotNull('maintenance_logs.ml_expected_return_date')
            ->where('maintenance_logs.ml_expected_return_date', '<', $today)
            ->select(
                'maintenance_logs.id',
                'products.prod_name',
                'products.prod_tag_number',
                'maintenance_logs.ml_expected_return_date as due_date',
                'maintenance_logs.ml_status as status'
            )
            ->get();

        // Pending disposal approvals
        $pendingDisposals = DB::table('disposal_records')
            ->join('products', 'disposal_records.product_id', '=', 'products.prod_id')
            ->where('disposal_records.dr_status', 'pending_approval')
            ->select(
                'disposal_records.id',
                'products.prod_name',
                'products.prod_tag_number',
                'disposal_records.dr_disposal_date as date',
                'disposal_records.dr_method as method'
            )
            ->get();

        // Warranty expiring within 30 days
        $warrantyExpiring = DB::table('products')
            ->whereNotNull('prod_warranty_expire')
            ->whereBetween('prod_warranty_expire', [$today, $in30days])
            ->whereNotIn('prod_current_status', ['disposed', 'Stolen'])
            ->select('prod_id as id', 'prod_name', 'prod_tag_number', 'prod_warranty_expire as expiry_date')
            ->orderBy('prod_warranty_expire')
            ->get();

        // Stolen assets summary
        $stolenCount = DB::table('products')
            ->where('prod_current_status', 'Stolen')
            ->count();

        $alerts = [];

        foreach ($overdueHires as $h) {
            $days = (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($h->due_date)->startOfDay());
            $alerts[] = [
                'type'     => 'overdue_hire',
                'severity' => 'critical',
                'title'    => 'Overdue Hire',
                'message'  => "{$h->staff_name}'s hire is {$days} day(s) overdue",
                'link'     => "/hire/{$h->id}/details",
                'date'     => $h->due_date,
            ];
        }

        foreach ($hiresDueSoon as $h) {
            $days = (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($h->due_date)->startOfDay());
            $label = $days === 0 ? 'today' : "in {$days} day(s)";
            $alerts[] = [
                'type'     => 'hire_due_soon',
                'severity' => 'warning',
                'title'    => 'Hire Due Soon',
                'message'  => "{$h->staff_name}'s hire is due back {$label}",
                'link'     => "/hire/{$h->id}/details",
                'date'     => $h->due_date,
            ];
        }

        foreach ($overdueMainenance as $m) {
            $days = (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($m->due_date)->startOfDay());
            $alerts[] = [
                'type'     => 'overdue_maintenance',
                'severity' => 'warning',
                'title'    => 'Overdue Maintenance',
                'message'  => "{$m->prod_name} ({$m->prod_tag_number}) is {$days} day(s) overdue from service",
                'link'     => "/maintenance/{$m->id}/details",
                'date'     => $m->due_date,
            ];
        }

        foreach ($pendingDisposals as $d) {
            $alerts[] = [
                'type'     => 'pending_disposal',
                'severity' => 'info',
                'title'    => 'Pending Disposal Approval',
                'message'  => "{$d->prod_name} ({$d->prod_tag_number}) is awaiting disposal approval",
                'link'     => "/disposal/{$d->id}/details",
                'date'     => $d->date,
            ];
        }

        foreach ($warrantyExpiring as $w) {
            $days = (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($w->expiry_date)->startOfDay());
            $label = $days === 0 ? 'today' : "in {$days} day(s)";
            $alerts[] = [
                'type'     => 'warranty_expiring',
                'severity' => 'warning',
                'title'    => 'Warranty Expiring',
                'message'  => "Warranty for {$w->prod_name} expires {$label} ({$w->expiry_date})",
                'link'     => "/products/manage/{$w->id}/details",
                'date'     => $w->expiry_date,
            ];
        }

        if ($stolenCount > 0) {
            $alerts[] = [
                'type'     => 'stolen_assets',
                'severity' => 'critical',
                'title'    => 'Stolen Assets',
                'message'  => "{$stolenCount} asset(s) are currently marked as stolen",
                'link'     => '/products',
                'date'     => null,
            ];
        }

        $counts = [
            'critical' => count(array_filter($alerts, fn($a) => $a['severity'] === 'critical')),
            'warning'  => count(array_filter($alerts, fn($a) => $a['severity'] === 'warning')),
            'info'     => count(array_filter($alerts, fn($a) => $a['severity'] === 'info')),
            'total'    => count($alerts),
        ];

        return response()->json(['data' => $alerts, 'counts' => $counts]);
    }
}
