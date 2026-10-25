/* ==========================================================================
 * Elsar5a Admin PWA Service Worker
 * Scope: /admin
 * Security Policy:
 *   - NEVER caches /admin/* HTML pages, booking details, poll-status,
 *     customer info, payment proofs, WhatsApp delivery states, or API responses.
 *   - Only caches static PWA shell icons/logos under CACHE_VERSION.
 * ========================================================================== */

const CACHE_VERSION = 'elsar5a-admin-pwa-v3';
const SAFE_STATIC_ASSETS = [
    '/images/sarkha-logo.png',
    '/images/pwa/admin-icon-192.png',
    '/images/pwa/admin-icon-512.png',
    '/images/pwa/admin-icon-maskable-512.png',
    '/images/pwa/admin-badge-96.png',
    '/favicon.ico'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_VERSION)
            .then((cache) => cache.addAll(SAFE_STATIC_ASSETS).catch(() => {}))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys
                    .filter((key) => key !== CACHE_VERSION)
                    .map((oldKey) => caches.delete(oldKey))
            ))
            .then(() => self.clients.claim())
    );
});

/**
 * Determine whether a request is strictly a safe static icon/logo asset.
 * Everything else (all /admin/* HTML, /admin/bookings/*, /admin/bookings/poll-status,
 * authenticated APIs, payment screenshots) bypasses cache completely.
 */
function isSafeStaticShellAsset(requestUrl) {
    try {
        const url = new URL(requestUrl);
        if (url.origin !== self.location.origin) {
            return false;
        }
        return SAFE_STATIC_ASSETS.includes(url.pathname);
    } catch (e) {
        return false;
    }
}

self.addEventListener('fetch', (event) => {
    const request = event.request;

    // Never intercept non-GET requests
    if (request.method !== 'GET') {
        return;
    }

    // Never cache navigations, /admin pages, poll-status, or dynamic endpoints
    if (request.mode === 'navigate' || !isSafeStaticShellAsset(request.url)) {
        return;
    }

    event.respondWith(
        caches.match(request).then((cached) => {
            const networkFetch = fetch(request)
                .then((response) => {
                    if (response && response.status === 200 && response.type === 'basic') {
                        const clone = response.clone();
                        caches.open(CACHE_VERSION).then((cache) => cache.put(request, clone));
                    }
                    return response;
                })
                .catch(() => cached);

            return cached || networkFetch;
        })
    );
});

/**
 * Sanitize click target URL so notifications only open /admin paths.
 */
function sanitizeAdminUrl(rawUrl, bookingId) {
    const fallback = bookingId ? ('/admin/bookings/' + bookingId) : '/admin/bookings';
    if (!rawUrl || typeof rawUrl !== 'string') {
        return fallback;
    }
    try {
        const parsed = new URL(rawUrl, self.location.origin);
        if (parsed.origin !== self.location.origin) {
            return fallback;
        }
        if (!parsed.pathname.startsWith('/admin') || parsed.pathname.startsWith('//')) {
            return fallback;
        }
        return parsed.pathname + parsed.search;
    } catch (e) {
        return fallback;
    }
}

self.addEventListener('push', (event) => {
    let payload = {
        title: '🔔 إشعار إدارة الصرخة',
        body: 'يوجد تحديث جديد في لوحة تحكم الحجوزات.',
        icon: '/images/pwa/admin-icon-192.png',
        badge: '/images/pwa/admin-badge-96.png',
        url: '/admin/bookings',
        tag: 'elsar5a-admin-notification'
    };

    if (event.data) {
        try {
            const parsed = event.data.json();
            payload = Object.assign({}, payload, parsed);
        } catch (e) {
            const text = event.data.text();
            if (text) {
                payload.body = text;
            }
        }
    }

    const safeTargetUrl = sanitizeAdminUrl(payload.url, payload.booking_id);

    const options = {
        body: payload.body || '',
        icon: payload.icon || '/images/pwa/admin-icon-192.png',
        badge: payload.badge || '/images/pwa/admin-badge-96.png',
        dir: 'rtl',
        lang: 'ar',
        tag: payload.tag || ('elsar5a-notif-' + (payload.notification_id || Date.now())),
        renotify: true,
        data: {
            url: safeTargetUrl,
            booking_id: payload.booking_id || null,
            ticket_id: payload.ticket_id || null,
            notification_id: payload.notification_id || null
        }
    };

    const promises = [
        self.registration.showNotification(payload.title || '🔔 إدارة الصرخة', options)
    ];

    // App Badging API: Display unread count badge on the external PWA app icon (iOS 16.4+ & Android/Desktop)
    const nav = (typeof self !== 'undefined' && self.navigator) ? self.navigator : (typeof navigator !== 'undefined' ? navigator : null);
    if (nav && 'setAppBadge' in nav) {
        const rawCount = parseInt(payload.unread_count, 10);
        const badgeCount = (!isNaN(rawCount) && rawCount > 0) ? rawCount : 1;
        promises.push(nav.setAppBadge(badgeCount).catch(() => {}));
    }

    event.waitUntil(Promise.all(promises));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    // Clear app icon badge when notification is opened / tapped
    const nav = (typeof self !== 'undefined' && self.navigator) ? self.navigator : (typeof navigator !== 'undefined' ? navigator : null);
    if (nav) {
        if ('clearAppBadge' in nav) {
            nav.clearAppBadge().catch(() => {});
        } else if ('setAppBadge' in nav) {
            nav.setAppBadge(0).catch(() => {});
        }
    }

    const data = event.notification.data || {};
    const targetPath = sanitizeAdminUrl(data.url, data.booking_id);
    const targetHref = new URL(targetPath, self.location.origin).href;

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            for (let i = 0; i < windowClients.length; i++) {
                const client = windowClients[i];
                try {
                    const clientUrl = new URL(client.url);
                    if (clientUrl.origin === self.location.origin && clientUrl.pathname.startsWith('/admin')) {
                        if ('navigate' in client) {
                            return client.navigate(targetHref).then((c) => (c && 'focus' in c ? c.focus() : null));
                        }
                        if ('focus' in client) {
                            return client.focus();
                        }
                    }
                } catch (e) {
                    // ignore invalid client URL
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(targetHref);
            }
        })
    );
});
