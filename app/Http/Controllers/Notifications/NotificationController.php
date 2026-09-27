<?php

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    use RespondsToAjax;

    public function index(): View
    {
        $notifications = auth()->user()
            ->notifications()
            ->latest()
            ->get();

        return view('notifications.index', compact('notifications'));
    }

    public function readAll(Request $request): RedirectResponse|JsonResponse
    {
        auth()->user()->unreadNotifications->markAsRead();

        return $this->ajaxOk($request, 'Semua notifikasi ditandai sudah dibaca');
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

        if ($url) {
            $path = parse_url($url, PHP_URL_PATH) ?? '/';
            $query = parse_url($url, PHP_URL_QUERY);

            return redirect()->to($path.($query ? '?'.$query : ''));
        }

        return redirect()->route('dashboard');
    }

    public function destroy(Request $request, DatabaseNotification $notification): RedirectResponse|JsonResponse
    {
        if ($notification->notifiable_id !== auth()->id() || $notification->notifiable_type !== auth()->user()->getMorphClass()) {
            abort(403);
        }

        $notification->delete();

        return $this->ajaxOk($request, 'Notifikasi dihapus');
    }

    public function destroyAll(Request $request): RedirectResponse|JsonResponse
    {
        auth()->user()->notifications()->delete();

        return $this->ajaxOk($request, 'Semua notifikasi dihapus');
    }

    public function destroyRead(Request $request): RedirectResponse|JsonResponse
    {
        auth()->user()->notifications()->whereNotNull('read_at')->delete();

        return $this->ajaxOk($request, 'Notifikasi yang sudah dibaca dihapus');
    }
}
