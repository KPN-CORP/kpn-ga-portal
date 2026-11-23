<?php

namespace App\Http\Controllers;

use App\Support\NotificationPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Dipanggil via AJAX oleh bell notifikasi di dashboard (mirip status bar Android):
     * mengembalikan jumlah belum dibaca + daftar notifikasi terbaru milik user yang login.
     *
     * Catatan akses: karena notifikasi hanya pernah dikirim ke user yang berhak
     * (approver, admin, atasan, pemohon, dst — sesuai logic di masing-masing modul),
     * cukup ambil notifikasi milik user itu sendiri. Tidak perlu filter role tambahan.
     */
    public function fetch(Request $request)
    {
        $user = Auth::user();

        $notifications = $user->notifications()
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'success'      => true,
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => NotificationPresenter::presentMany($notifications),
        ]);
    }

    /**
     * Halaman daftar semua notifikasi milik user yang login.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = $user->notifications();

        if ($request->query('filter') === 'unread') {
            $query = $user->unreadNotifications();
        }

        $notifications = $query->latest()->paginate(20)->withQueryString();

        $presented = NotificationPresenter::presentMany($notifications->getCollection());

        return view('notifications.index', [
            'notifications' => $notifications,
            'presented'     => $presented,
            'unreadCount'   => $user->unreadNotifications()->count(),
            'activeFilter'  => $request->query('filter', 'all'),
        ]);
    }

    /**
     * Tandai satu notifikasi sudah dibaca lalu arahkan ke url tujuannya.
     */
    public function markRead(Request $request, string $id)
    {
        $user = Auth::user();
        $notification = $user->notifications()->where('id', $id)->first();

        $url = $notification?->data['url'] ?? route('notifications.index');

        if ($notification && is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'url' => $url]);
        }

        return redirect($url);
    }

    /**
     * Tandai semua notifikasi sudah dibaca.
     */
    public function markAllRead(Request $request)
    {
        Auth::user()->unreadNotifications->markAsRead();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true]);
        }

        return back();
    }
}
