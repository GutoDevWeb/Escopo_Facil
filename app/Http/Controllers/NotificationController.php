<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'project_updates' => app(\App\Services\ProjectActivity::class)->unreadProjects($user),
            'unread_count' => $user->unreadNotifications()->count(),
            'items' => $user->notifications()->latest()->limit(30)->get()->map(fn ($n) => [
                'id' => $n->id, 'title' => $n->data['title'] ?? 'Notificação', 'body' => $n->data['body'] ?? '',
                'created_at' => $n->created_at->toIso8601String(), 'read' => $n->read_at !== null,
            ]),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function read(Request $request)
    {
        $data = $request->validate(['ids' => 'required|array|min:1|max:30', 'ids.*' => 'required|uuid|distinct']);
        // Both reads and writes are scoped to the authenticated account.
        $ownIds = $request->user()->notifications()->whereIn('id', $data['ids'])->pluck('id');
        $request->user()->unreadNotifications()->whereIn('id', $ownIds)->update(['read_at' => now()]);
        return response()->json(['read_ids' => $ownIds, 'unread_count' => $request->user()->unreadNotifications()->count()])->header('Cache-Control', 'no-store, private');
    }
}
