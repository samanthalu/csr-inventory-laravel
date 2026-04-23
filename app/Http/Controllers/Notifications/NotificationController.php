<?php

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    private function format(Notification $n): array
    {
        return [
            'id'         => $n->notif_id,
            'message'    => $n->notif,
            'sent_by'    => $n->notif_by,
            'sent_to'    => $n->notif_to,
            'date'       => $n->notif_date?->format('d M Y'),
            'status'     => $n->notif_status ?? 'unread',
            'sender'     => $n->sender ? ['id' => $n->sender->id, 'name' => $n->sender->name] : null,
        ];
    }

    public function index()
    {
        $userId = Auth::id();
        $notifs = Notification::with('sender')
            ->where('notif_to', $userId)
            ->orderByDesc('notif_id')
            ->get()
            ->map(fn($n) => $this->format($n));

        $unreadCount = Notification::where('notif_to', $userId)
            ->where('notif_status', 'unread')
            ->count();

        return response()->json(['data' => $notifs, 'unread_count' => $unreadCount]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'notif_to' => 'required|integer|exists:users,id',
        ]);

        $notif = Notification::create([
            'notif'        => $validated['message'],
            'notif_by'     => Auth::id(),
            'notif_to'     => $validated['notif_to'],
            'notif_date'   => now()->toDateString(),
            'notif_status' => 'unread',
        ]);

        $notif->load('sender');
        return response()->json(['message' => 'Notification sent', 'data' => $this->format($notif)], 201);
    }

    public function markRead($id)
    {
        $notif = Notification::where('notif_id', $id)
            ->where('notif_to', Auth::id())
            ->first();

        if (!$notif) return response()->json(['message' => 'Not found'], 404);

        $notif->update(['notif_status' => 'read']);
        return response()->json(['message' => 'Marked as read', 'data' => $this->format($notif)]);
    }

    public function markAllRead()
    {
        Notification::where('notif_to', Auth::id())
            ->where('notif_status', 'unread')
            ->update(['notif_status' => 'read']);

        return response()->json(['message' => 'All notifications marked as read']);
    }

    public function destroy($id)
    {
        $notif = Notification::where('notif_id', $id)
            ->where('notif_to', Auth::id())
            ->first();

        if (!$notif) return response()->json(['message' => 'Not found'], 404);

        $notif->delete();
        return response()->json(['message' => 'Notification deleted']);
    }

    public function unreadCount()
    {
        $count = Notification::where('notif_to', Auth::id())
            ->where('notif_status', 'unread')
            ->count();

        return response()->json(['count' => $count]);
    }
}
