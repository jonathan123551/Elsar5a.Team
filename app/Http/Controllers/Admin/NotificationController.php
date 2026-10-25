<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\AdminNotificationPreference;
use App\Models\PushSubscription;
use App\Services\WebPushService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request, WebPushService $webPush)
    {
        $userId = (int) $request->user()->id;

        $notifications = AdminNotification::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $unreadCount = AdminNotification::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();

        $preferences = AdminNotificationPreference::forUser($userId);

        if ($request->expectsJson() || $request->boolean('json')) {
            return response()->json([
                'ok' => true,
                'unread_count' => $unreadCount,
                'notifications' => $notifications->map(fn (AdminNotification $n) => [
                    'id' => $n->id,
                    'type' => $n->type,
                    'category' => $n->category,
                    'title' => $n->title,
                    'body' => $n->body,
                    'booking_id' => $n->booking_id,
                    'ticket_id' => $n->ticket_id,
                    'target_url' => $n->target_url,
                    'is_unread' => $n->isUnread(),
                    'read_at' => $n->read_at?->toIso8601String(),
                    'created_at_human' => $n->created_at?->diffForHumans(),
                    'created_at_formatted' => $n->created_at?->format('Y-m-d g:i A'),
                ]),
                'preferences' => [
                    'new_bookings' => (bool) $preferences->new_bookings,
                    'payment_proofs' => (bool) $preferences->payment_proofs,
                    'whatsapp_failures' => (bool) $preferences->whatsapp_failures,
                    'ticket_delivery' => (bool) $preferences->ticket_delivery,
                    'completed_bookings' => (bool) $preferences->completed_bookings,
                ],
            ])->header('Cache-Control', 'private, no-store, no-cache, must-revalidate');
        }

        $subscriptionsCount = PushSubscription::where('user_id', $userId)->count();
        $vapidPublicKey = $webPush->getPublicKey();

        return view('admin.notifications.index', compact(
            'notifications',
            'unreadCount',
            'preferences',
            'subscriptionsCount',
            'vapidPublicKey'
        ));
    }

    public function markAsRead(Request $request, AdminNotification $notification)
    {
        abort_unless((int) $notification->user_id === (int) $request->user()->id, 403);

        $notification->markAsRead();

        $unreadCount = AdminNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        $targetUrl = AdminNotification::sanitizeTargetUrl($notification->target_url, $notification->booking_id);

        if ($request->expectsJson() || $request->isJson()) {
            return response()->json([
                'ok' => true,
                'unread_count' => $unreadCount,
                'target_url' => $targetUrl,
            ]);
        }

        return redirect($targetUrl);
    }

    public function open(Request $request, AdminNotification $notification)
    {
        abort_unless((int) $notification->user_id === (int) $request->user()->id, 403);

        $notification->markAsRead();

        $targetUrl = AdminNotification::sanitizeTargetUrl($notification->target_url, $notification->booking_id);

        return redirect($targetUrl);
    }

    public function markAllAsRead(Request $request)
    {
        AdminNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($request->expectsJson() || $request->isJson()) {
            return response()->json([
                'ok' => true,
                'unread_count' => 0,
                'message' => 'تم تحديد جميع الإشعارات كمقروءة ✓',
            ]);
        }

        return back()->with('status', 'تم تحديد جميع الإشعارات كمقروءة ✓');
    }

    public function updatePreferences(Request $request)
    {
        $preferences = AdminNotificationPreference::forUser((int) $request->user()->id);

        $preferences->update([
            'new_bookings' => $request->boolean('new_bookings'),
            'payment_proofs' => $request->boolean('payment_proofs'),
            'whatsapp_failures' => $request->boolean('whatsapp_failures'),
            'ticket_delivery' => $request->boolean('ticket_delivery'),
            'completed_bookings' => $request->boolean('completed_bookings'),
        ]);

        if ($request->expectsJson() || $request->isJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'تم حفظ إعدادات الإشعارات بنجاح ✅',
                'preferences' => [
                    'new_bookings' => (bool) $preferences->new_bookings,
                    'payment_proofs' => (bool) $preferences->payment_proofs,
                    'whatsapp_failures' => (bool) $preferences->whatsapp_failures,
                    'ticket_delivery' => (bool) $preferences->ticket_delivery,
                    'completed_bookings' => (bool) $preferences->completed_bookings,
                ],
            ]);
        }

        return back()->with('status', 'تم حفظ إعدادات الإشعارات بنجاح ✅');
    }
}
