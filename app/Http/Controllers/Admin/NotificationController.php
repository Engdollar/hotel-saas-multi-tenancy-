<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $readState = trim((string) $request->query('read_state', 'all'));
        $search = trim((string) $request->query('search', ''));

        if (! in_array($readState, ['all', 'unread', 'read'], true)) {
            $readState = 'all';
        }

        $query = auth()->user()->notifications();

        if ($readState === 'unread') {
            $query->whereNull('read_at');
        }

        if ($readState === 'read') {
            $query->whereNotNull('read_at');
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('type', 'like', "%{$search}%")
                    ->orWhereRaw("json_extract(data, '$.title') like ?", ["%{$search}%"])
                    ->orWhereRaw("json_extract(data, '$.message') like ?", ["%{$search}%"]);
            });
        }

        return view('admin.notifications.index', [
            'notifications' => $query->latest()->paginate(12)->withQueryString(),
            'filters' => [
                'read_state' => $readState,
                'search' => $search,
            ],
            'stats' => [
                'total' => auth()->user()->notifications()->count(),
                'unread' => auth()->user()->unreadNotifications()->count(),
                'read' => auth()->user()->notifications()->whereNotNull('read_at')->count(),
                'actionable' => auth()->user()->notifications()->whereNotNull('data->url')->count(),
            ],
        ]);
    }

    public function markAsRead(DatabaseNotification $notification): RedirectResponse
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_unless($notification->notifiable_id === auth()->id(), 403);

        $notification->markAsRead();

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllAsRead(): RedirectResponse
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        auth()->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }
}