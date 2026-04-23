<?php

namespace App\Http\Controllers\AuditLog;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        if (!Gate::allows('admin-only')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $query = AuditLog::orderBy('created_at', 'desc');

        if ($request->query('module')) {
            $query->where('module', $request->query('module'));
        }
        if ($request->query('action')) {
            $query->where('action', $request->query('action'));
        }
        if ($request->query('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }
        if ($request->query('search')) {
            $term = $request->query('search');
            $query->where(function ($q) use ($term) {
                $q->where('description', 'like', "%{$term}%")
                  ->orWhere('user_name', 'like', "%{$term}%");
            });
        }
        if ($request->query('from')) {
            $query->whereDate('created_at', '>=', $request->query('from'));
        }
        if ($request->query('to')) {
            $query->whereDate('created_at', '<=', $request->query('to'));
        }

        $perPage = min((int) ($request->query('per_page', 50)), 200);
        $results = $query->paginate($perPage);

        return response()->json([
            'data'         => $results->items(),
            'total'        => $results->total(),
            'per_page'     => $results->perPage(),
            'current_page' => $results->currentPage(),
            'last_page'    => $results->lastPage(),
        ]);
    }

    public function show($id)
    {
        if (!Gate::allows('admin-only')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $log = AuditLog::find($id);
        if (!$log) return response()->json(['message' => 'Log entry not found'], 404);
        return response()->json(['data' => $log]);
    }
}
