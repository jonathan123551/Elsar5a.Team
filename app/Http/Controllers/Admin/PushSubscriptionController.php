<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use App\Services\WebPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function vapidPublicKey(WebPushService $webPush): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'publicKey' => $webPush->getPublicKey(),
        ]);
    }

    public function store(Request $request)
    {
        $endpoint = (string) $request->input('endpoint', '');
        $publicKey = (string) ($request->input('keys.p256dh') ?? $request->input('public_key') ?? $request->input('p256dh') ?? '');
        $authToken = (string) ($request->input('keys.auth') ?? $request->input('auth_token') ?? $request->input('auth') ?? '');
        $contentEncoding = (string) ($request->input('contentEncoding') ?? $request->input('content_encoding') ?? 'aes128gcm');

        $request->merge([
            'endpoint' => trim($endpoint),
            'resolved_public_key' => trim($publicKey),
            'resolved_auth_token' => trim($authToken),
        ]);

        $request->validate([
            'endpoint' => ['required', 'string', 'url', 'max:2048'],
            'resolved_public_key' => ['required', 'string', 'max:255'],
            'resolved_auth_token' => ['required', 'string', 'max:255'],
        ]);

        $subscription = PushSubscription::registerForUser((int) $request->user()->id, [
            'endpoint' => $request->input('endpoint'),
            'public_key' => $request->input('resolved_public_key'),
            'auth_token' => $request->input('resolved_auth_token'),
            'content_encoding' => $contentEncoding ?: 'aes128gcm',
            'user_agent' => (string) $request->userAgent(),
            'device_label' => $request->input('device_label'),
        ]);

        if ($request->expectsJson() || $request->isJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'تم تفعيل الإشعارات بنجاح ✓',
                'subscription_id' => $subscription->id,
            ]);
        }

        return back()->with('status', 'تم تفعيل الإشعارات بنجاح ✓');
    }

    public function destroy(Request $request)
    {
        $endpoint = trim((string) $request->input('endpoint', ''));
        $query = PushSubscription::where('user_id', $request->user()->id);

        if ($endpoint !== '') {
            $query->where('endpoint_hash', hash('sha256', $endpoint));
        }

        $deleted = $query->delete();

        if ($request->expectsJson() || $request->isJson()) {
            return response()->json([
                'ok' => true,
                'deleted' => $deleted,
                'message' => 'تم إلغاء اشتراك الإشعارات لهذا الجهاز.',
            ]);
        }

        return back()->with('status', 'تم إلغاء اشتراك الإشعارات لهذا الجهاز.');
    }
}
