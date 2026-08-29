<?php

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        $notifications = auth()->user()
            ->notifications()
            ->latest()
            ->paginate(15);

        return view('notifications.index', compact('notifications'));
    }

    public function readAll(): RedirectResponse
    {
        auth()->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca');
    }

    public function show(DatabaseNotification $notification): RedirectResponse
    {
        if ($notification->notifiable_id !== auth()->id() || $notification->notifiable_type !== auth()->user()->getMorphClass()) {
            abort(403);
        }

        if ($notification->unread()) {
            $notification->markAsRead();
        }

        $url = data_get($notification->data, 'url');

        return redirect($url ?? route('dashboard'));
    }

    public function destroy(DatabaseNotification $notification): RedirectResponse
    {
        if ($notification->notifiable_id !== auth()->id() || $notification->notifiable_type !== auth()->user()->getMorphClass()) {
            abort(403);
        }

        $notification->delete();

        return back()->with('success', 'Notifikasi dihapus');
    }

    public function destroyAll(): RedirectResponse
    {
        auth()->user()->notifications()->delete();

        return back()->with('success', 'Semua notifikasi dihapus');
    }

    public function destroyRead(): RedirectResponse
    {
        auth()->user()->notifications()->whereNotNull('read_at')->delete();

        return back()->with('success', 'Notifikasi yang sudah dibaca dihapus');
    }
}
