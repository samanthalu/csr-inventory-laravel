<?php

namespace App\Http\Controllers\Hire;

use App\Http\Controllers\Controller;
use App\Mail\HireRequestMail;
use App\Models\HireRequest;
use App\Models\HireRequestItem;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class HireRequestController extends Controller
{
    private function format(HireRequest $r): array
    {
        return [
            'id'                   => $r->id,
            'purpose'              => $r->purpose,
            'notes'                => $r->notes,
            'requested_start_date' => $r->requested_start_date?->format('Y-m-d'),
            'requested_end_date'   => $r->requested_end_date?->format('Y-m-d'),
            'status'               => $r->status,
            'review_note'          => $r->review_note,
            'created_at'           => $r->created_at?->format('Y-m-d H:i'),
            'staff'                => $r->staff ? [
                'id'       => $r->staff->staff_id,
                'name'     => $r->staff->staff_first_name . ' ' . $r->staff->staff_last_name,
                'position' => $r->staff->staff_position,
            ] : null,
            'requested_by' => $r->requestedBy ? [
                'id'   => $r->requestedBy->id,
                'name' => $r->requestedBy->name,
            ] : null,
            'reviewed_by' => $r->reviewedBy ? [
                'id'   => $r->reviewedBy->id,
                'name' => $r->reviewedBy->name,
            ] : null,
            'items' => $r->items->map(fn($i) => [
                'id'          => $i->id,
                'category_id' => $i->category_id,
                'quantity'    => $i->quantity,
                'category'    => $i->category ? [
                    'id'   => $i->category->cat_id,
                    'name' => $i->category->cat_name,
                ] : null,
            ])->values(),
        ];
    }

    public function index()
    {
        $user = Auth::user();

        // Admins / ICT see all; others see only their own
        if (in_array($user->user_type, ['admin', 'ict'])) {
            $requests = HireRequest::with(['staff', 'requestedBy', 'reviewedBy', 'items.category'])
                ->orderByDesc('created_at')
                ->get();
        } else {
            $requests = HireRequest::with(['staff', 'requestedBy', 'reviewedBy', 'items.category'])
                ->where('requested_by', $user->id)
                ->orderByDesc('created_at')
                ->get();
        }

        return response()->json(['data' => $requests->map(fn($r) => $this->format($r))]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'staff_id'             => 'nullable|exists:staff,staff_id',
            'purpose'              => 'required|string|max:255',
            'notes'                => 'nullable|string',
            'requested_start_date' => 'required|date',
            'requested_end_date'   => 'required|date|after_or_equal:requested_start_date',
            'items'                => 'required|array|min:1',
            'items.*.category_id'  => 'required|exists:category,cat_id',
            'items.*.quantity'     => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $hireRequest = HireRequest::create([
                'staff_id'             => $validated['staff_id'] ?? null,
                'requested_by'         => Auth::id(),
                'purpose'              => $validated['purpose'],
                'notes'                => $validated['notes'] ?? null,
                'requested_start_date' => $validated['requested_start_date'],
                'requested_end_date'   => $validated['requested_end_date'],
                'status'               => 'pending',
            ]);

            foreach ($validated['items'] as $item) {
                HireRequestItem::create([
                    'hire_request_id' => $hireRequest->id,
                    'category_id'     => $item['category_id'],
                    'quantity'        => $item['quantity'],
                ]);
            }

            $hireRequest->load(['staff', 'requestedBy', 'items.category']);

            // Notify all admins (in-app + email)
            $admins = User::where('user_type', 'admin')->get();
            $requesterName = Auth::user()->name;
            $message = "New hire request from {$requesterName}: \"{$hireRequest->purpose}\"";

            foreach ($admins as $admin) {
                Notification::create([
                    'notif'        => $message,
                    'notif_by'     => Auth::id(),
                    'notif_to'     => $admin->id,
                    'notif_date'   => now()->toDateString(),
                    'notif_status' => 0,
                ]);

                try {
                    Mail::to($admin->email)->send(new HireRequestMail($hireRequest));
                } catch (\Throwable $e) {
                    // Email failure should not block the request
                    \Log::warning("Failed to send hire request email to {$admin->email}: " . $e->getMessage());
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Hire request submitted successfully. Admins have been notified.',
                'data'    => $this->format($hireRequest),
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('HireRequest store failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to submit request. Please try again.'], 500);
        }
    }

    public function show($id)
    {
        $user = Auth::user();
        $r = HireRequest::with(['staff', 'requestedBy', 'reviewedBy', 'items.category'])->find($id);

        if (!$r) return response()->json(['message' => 'Request not found'], 404);

        // Only admins/ict or the requester can view
        if (!in_array($user->user_type, ['admin', 'ict']) && $r->requested_by !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(['data' => $this->format($r)]);
    }

    public function approve(Request $request, $id)
    {
        if (!in_array(Auth::user()->user_type, ['admin', 'ict'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $r = HireRequest::find($id);
        if (!$r) return response()->json(['message' => 'Request not found'], 404);
        if ($r->status !== 'pending') return response()->json(['message' => 'Request is no longer pending'], 422);

        $r->update([
            'status'      => 'approved',
            'reviewed_by' => Auth::id(),
            'review_note' => $request->input('review_note'),
        ]);

        // Notify the requester
        $reviewerName = Auth::user()->name;
        Notification::create([
            'notif'        => "Your hire request \"{$r->purpose}\" was approved by {$reviewerName}. A hire order will be created.",
            'notif_by'     => Auth::id(),
            'notif_to'     => $r->requested_by,
            'notif_date'   => now()->toDateString(),
            'notif_status' => 0,
        ]);

        $r->load(['staff', 'requestedBy', 'reviewedBy', 'items.category']);
        return response()->json(['message' => 'Request approved', 'data' => $this->format($r)]);
    }

    public function reject(Request $request, $id)
    {
        if (!in_array(Auth::user()->user_type, ['admin', 'ict'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'review_note' => 'required|string|max:500',
        ]);

        $r = HireRequest::find($id);
        if (!$r) return response()->json(['message' => 'Request not found'], 404);
        if ($r->status !== 'pending') return response()->json(['message' => 'Request is no longer pending'], 422);

        $r->update([
            'status'      => 'rejected',
            'reviewed_by' => Auth::id(),
            'review_note' => $validated['review_note'],
        ]);

        // Notify the requester
        $reviewerName = Auth::user()->name;
        Notification::create([
            'notif'        => "Your hire request \"{$r->purpose}\" was not approved. Reason: {$validated['review_note']}",
            'notif_by'     => Auth::id(),
            'notif_to'     => $r->requested_by,
            'notif_date'   => now()->toDateString(),
            'notif_status' => 0,
        ]);

        $r->load(['staff', 'requestedBy', 'reviewedBy', 'items.category']);
        return response()->json(['message' => 'Request rejected', 'data' => $this->format($r)]);
    }
}
