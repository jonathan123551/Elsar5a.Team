@extends('layouts.admin')

@section('title', 'مركز الإشعارات وإعدادات التنبيهات')

@section('content')
<section class="max-w-5xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold flex items-center gap-2">
                <span>🔔 مركز الإشعارات والتنبيهات الفورية</span>
                @if($unreadCount > 0)
                    <span id="page-notif-unread-badge" class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/40 tabular-nums">
                        {{ $unreadCount }} غير مقروء
                    </span>
                @endif
            </h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">
                تتبّع تنبيهات الحجوزات الجديدة، إثباتات التحويل، وحالات تسليم تذاكر واتساب لحظة بلحظة.
            </p>
        </div>

        <div class="flex items-center gap-2">
            @if($unreadCount > 0)
                <form action="{{ route('admin.notifications.read-all') }}" method="POST">
                    @csrf
                    <button type="submit" class="admin-btn admin-btn--secondary admin-btn--sm">
                        <span>✓ تحديد الكل كمقروء</span>
                    </button>
                </form>
            @endif
            @include('admin.partials.back-button', ['url' => route('admin.dashboard'), 'label' => 'العودة للرئيسية'])
        </div>
    </div>

    {{-- Top Grid: Push Activation + Notification Preferences --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

        {{-- Web Push Activation Card (Phase 8) --}}
        <div class="lg:col-span-5 bg-[#0e131f] border border-white/10 rounded-2xl p-5 flex flex-col justify-between gap-4 shadow-xl">
            <div class="space-y-2">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-amber-500/15 text-amber-300 border border-amber-500/30">
                        تطبيق الهاتف (PWA + Web Push)
                    </span>
                    <span class="text-[11px] text-slate-400 tabular-nums">
                        الأجهزة المفعّلة: <strong class="text-white">{{ $subscriptionsCount }}</strong>
                    </span>
                </div>
                <h2 class="text-base font-bold text-white pt-1">إشعارات الهاتف الفورية</h2>
                <p class="text-xs text-slate-300 leading-relaxed">
                    فعّل الإشعارات الفورية على هاتفك أو متصفحك ليصلك تنبيه فوري عند وصول حجز جديد أو رفع إثبات تحويل أو تغيّر حالة تسليم الواتساب حتى لو كانت الصفحة مغلقة.
                </p>
            </div>

            <div class="space-y-2.5 pt-2 border-t border-white/10">
                <div id="push-status-feedback" class="hidden text-xs font-semibold px-3 py-2 rounded-xl bg-emerald-500/15 border border-emerald-500/40 text-emerald-300">
                    تم تفعيل الإشعارات بنجاح ✓
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button type="button"
                            data-enable-admin-push
                            class="admin-btn admin-btn--primary w-full sm:w-auto flex-1">
                        <span>🔔 تفعيل إشعارات الحجوزات</span>
                    </button>

                    <button type="button"
                            data-disable-admin-push
                            class="admin-btn admin-btn--danger admin-btn--sm hidden">
                        <span>إيقاف على هذا الجهاز</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Notification Preferences Card (Phase 6) --}}
        <div class="lg:col-span-7 bg-[#0e131f] border border-white/10 rounded-2xl p-5 shadow-xl">
            <div class="mb-3">
                <h2 class="text-base font-bold text-white">⚙️ إعدادات أنواع الإشعارات</h2>
                <p class="text-xs text-slate-400 mt-0.5">
                    اختر الفئات التي ترغب في استلام إشعارات بها داخل اللوحة وعلى الهاتف.
                </p>
            </div>

            <form action="{{ route('admin.notifications.preferences.update') }}" method="POST" class="space-y-3">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs">
                    <label class="flex items-center gap-2.5 p-3 rounded-xl bg-black/40 border border-white/10 cursor-pointer hover:border-amber-400/40 transition">
                        <input type="checkbox" name="new_bookings" value="1" {{ $preferences->new_bookings ? 'checked' : '' }}
                               class="w-4 h-4 rounded accent-amber-500">
                        <div>
                            <div class="font-bold text-slate-100">🔔 الحجوزات الجديدة</div>
                            <div class="text-[11px] text-slate-400">عند إنشاء حجز جديد أو اعتماده</div>
                        </div>
                    </label>

                    <label class="flex items-center gap-2.5 p-3 rounded-xl bg-black/40 border border-white/10 cursor-pointer hover:border-amber-400/40 transition">
                        <input type="checkbox" name="payment_proofs" value="1" {{ $preferences->payment_proofs ? 'checked' : '' }}
                               class="w-4 h-4 rounded accent-amber-500">
                        <div>
                            <div class="font-bold text-slate-100">💳 إثباتات التحويل</div>
                            <div class="text-[11px] text-slate-400">عند رفع صورة تحويل جديدة للمراجعة</div>
                        </div>
                    </label>

                    <label class="flex items-center gap-2.5 p-3 rounded-xl bg-black/40 border border-white/10 cursor-pointer hover:border-amber-400/40 transition">
                        <input type="checkbox" name="whatsapp_failures" value="1" {{ $preferences->whatsapp_failures ? 'checked' : '' }}
                               class="w-4 h-4 rounded accent-amber-500">
                        <div>
                            <div class="font-bold text-slate-100">⚠️ أعطال وفشل واتساب</div>
                            <div class="text-[11px] text-slate-400">عند فشل إرسال القالب أو التذكرة</div>
                        </div>
                    </label>

                    <label class="flex items-center gap-2.5 p-3 rounded-xl bg-black/40 border border-white/10 cursor-pointer hover:border-amber-400/40 transition">
                        <input type="checkbox" name="ticket_delivery" value="1" {{ $preferences->ticket_delivery ? 'checked' : '' }}
                               class="w-4 h-4 rounded accent-amber-500">
                        <div>
                            <div class="font-bold text-slate-100">🎟️ تسليم التذاكر</div>
                            <div class="text-[11px] text-slate-400">نقر العميل وتأكيد استلام التذاكر</div>
                        </div>
                    </label>

                    <label class="flex items-center gap-2.5 p-3 rounded-xl bg-black/40 border border-white/10 cursor-pointer hover:border-amber-400/40 transition sm:col-span-2">
                        <input type="checkbox" name="completed_bookings" value="1" {{ $preferences->completed_bookings ? 'checked' : '' }}
                               class="w-4 h-4 rounded accent-amber-500">
                        <div>
                            <div class="font-bold text-slate-100">✅ اكتمال الحجوزات</div>
                            <div class="text-[11px] text-slate-400">إشعار عند اكتمال استلام جميع تذاكر الحجز بالكامل</div>
                        </div>
                    </label>
                </div>

                <div class="pt-1 flex justify-end">
                    <button type="submit" class="admin-btn admin-btn--secondary admin-btn--sm">
                        <span>حفظ تفضيلات الإشعارات</span>
                    </button>
                </div>
            </form>
        </div>

    </div>

    {{-- Notification List (Phase 7) --}}
    <div class="bg-[#0e131f] border border-white/10 rounded-2xl overflow-hidden shadow-xl">
        <div class="px-5 py-4 border-b border-white/10 flex items-center justify-between">
            <h2 class="text-sm sm:text-base font-bold text-white">سجل الإشعارات الحديثة</h2>
            <span class="text-xs text-slate-400">{{ $notifications->count() }} إشعار</span>
        </div>

        @if($notifications->isEmpty())
            <div class="p-10 text-center text-slate-400 text-sm">
                لا توجد إشعارات مسجلة حتى الآن.
            </div>
        @else
            <div class="divide-y divide-white/5">
                @foreach($notifications as $item)
                    <a href="{{ route('admin.notifications.open', $item) }}"
                       class="block px-4 sm:px-5 py-3.5 transition hover:bg-white/[0.04] {{ $item->isUnread() ? 'bg-amber-500/[0.06]' : '' }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="space-y-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    @if($item->isUnread())
                                        <span class="w-2 h-2 rounded-full bg-amber-400 shrink-0" title="غير مقروء"></span>
                                    @endif
                                    <span class="text-sm font-bold {{ $item->isUnread() ? 'text-amber-200' : 'text-slate-200' }}">
                                        {{ $item->title }}
                                    </span>
                                    @if($item->booking_id)
                                        <span class="text-[11px] px-2 py-0.5 rounded-full bg-white/5 border border-white/10 text-slate-300 tabular-nums">
                                            حجز #{{ $item->booking_id }}
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-slate-300 leading-relaxed">
                                    {{ $item->body }}
                                </p>
                            </div>

                            <div class="text-left shrink-0 flex flex-col items-end gap-1">
                                <span class="text-[11px] text-slate-400 whitespace-nowrap" dir="ltr">
                                    {{ $item->created_at?->diffForHumans() }}
                                </span>
                                <span class="text-[11px] text-amber-400/90 font-medium">
                                    فتح الحجز &larr;
                                </span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

</section>
@endsection
