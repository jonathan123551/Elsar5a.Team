@extends('layouts.admin')

@section('title', 'إدارة الحجوزات والتحويلات')

@section('content')
<style>
    /* Tap target baseline */
    .adm-tap {
        min-height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    /* Status-colored right accent border on mobile cards in RTL */
    .adm-card {
        position: relative;
        overflow: hidden;
    }
    .adm-card::before {
        content: "";
        position: absolute;
        inset-inline-start: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: var(--adm-accent, rgba(125,211,252,0.7));
        opacity: 0.85;
    }
    .adm-card[data-status="approved"] { --adm-accent: rgb(52,211,153); }
    .adm-card[data-status="rejected"] { --adm-accent: rgb(248,113,113); }
    .adm-card[data-status="pending"]  { --adm-accent: rgb(125,211,252); }

    /* Segmented status filter chips */
    .adm-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        min-height: 44px;
        padding: 0 14px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 500;
        color: rgb(209,213,219);
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08);
        transition: all .15s ease-in-out;
        white-space: nowrap;
        flex: 1 1 auto;
    }
    .adm-chip:active { transform: scale(.97); }
    .adm-chip.is-active {
        background: rgb(245, 158, 11);
        color: rgb(2, 6, 23);
        border-color: rgb(245, 158, 11);
        font-weight: 700;
    }
    .adm-chip[data-tone="pending"].is-active  { background: rgb(56, 189, 248); border-color: rgb(56, 189, 248); color: #020617; }
    .adm-chip[data-tone="approved"].is-active { background: rgb(52, 211, 153); border-color: rgb(52, 211, 153); color: #020617; }
    .adm-chip[data-tone="rejected"].is-active { background: rgb(248, 113, 113); border-color: rgb(248, 113, 113); color: #ffffff; }

    @media (max-width: 639px) {
        #statusFilter { display: none; }
    }

    /* Sticky filter bar */
    .adm-filter-bar {
        position: sticky;
        top: 68px;
        z-index: 30;
    }

    /* Thumbnail preview trigger */
    .proof-thumb-btn {
        position: relative;
        display: inline-block;
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.15);
        background: #020617;
        transition: all 0.2s ease;
    }
    .proof-thumb-btn:hover {
        border-color: rgba(245, 158, 11, 0.7);
        transform: scale(1.05);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.6);
    }

    /* Multi-stage delivery badge */
    .adm-delivery-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 9px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.02em;
        white-space: nowrap;
    }
</style>

<section class="space-y-4 sm:space-y-6">

    {{-- ------------------------------------------------------
         HEADER & PAGE CONTEXT
         ------------------------------------------------------ --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-black/40 border border-white/10 rounded-2xl p-4 backdrop-blur-md">
        <div class="min-w-0">
            <div class="flex items-center gap-2.5 mb-1">
                <svg class="w-6 h-6 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                </svg>
                <h1 class="text-lg sm:text-2xl font-bold font-heading text-white">إدارة الحجوزات والتحويلات</h1>
            </div>
            <p id="resultCount" class="text-xs text-slate-400 tabular-nums">
                متابعة طلبات الحجز، مطابقة التحويلات، وتتبع تسليم التذاكر عبر واتساب
            </p>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap self-start sm:self-auto">
            @if(isset($unreceivedTicketsCount) && $unreceivedTicketsCount > 0)
                <button type="button"
                        id="openGlobalResendModal"
                        class="admin-btn admin-btn--indigo"
                        title="إرسال التذاكر غير المستلمة لكافة الحجوزات المعتمدة">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    <span>إرسال التذاكر غير المستلمة ({{ $unreceivedTicketsCount }})</span>
                </button>
            @else
                <button type="button"
                        disabled
                        class="admin-btn admin-btn--indigo opacity-50 cursor-not-allowed"
                        title="جميع تذاكر الحجوزات مستلمة بالفعل">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>إرسال التذاكر غير المستلمة ({{ $unreceivedTicketsCount ?? 0 }})</span>
                </button>
            @endif

            @include('admin.partials.back-button', ['url' => route('admin.dashboard'), 'label' => 'العودة للوحة التحكم'])
        </div>
    </div>

    {{-- ------------------------------------------------------
         STATUS COUNT SUMMARY
         ------------------------------------------------------ --}}
    @php
        $counts = [
            'all'      => $bookings->count(),
            'pending'  => $bookings->where('status', 'pending')->count(),
            'approved' => $bookings->where('status', 'approved')->count(),
            'rejected' => $bookings->where('status', 'rejected')->count(),
        ];
    @endphp

    @if($bookings->isNotEmpty())
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3" role="tablist" aria-label="أقسام حالات الحجز">
            <button type="button" class="adm-chip is-active" data-status-jump="" data-tone="">
                <span class="opacity-80">الكل</span>
                <span class="font-bold tabular-nums">({{ $counts['all'] }})</span>
            </button>
            <button type="button" class="adm-chip" data-status-jump="pending" data-tone="pending">
                <span class="opacity-80">قيد المراجعة</span>
                <span class="font-bold tabular-nums">({{ $counts['pending'] }})</span>
            </button>
            <button type="button" class="adm-chip" data-status-jump="approved" data-tone="approved">
                <span class="opacity-80">مقبول</span>
                <span class="font-bold tabular-nums">({{ $counts['approved'] }})</span>
            </button>
            <button type="button" class="adm-chip" data-status-jump="rejected" data-tone="rejected">
                <span class="opacity-80">مرفوض</span>
                <span class="font-bold tabular-nums">({{ $counts['rejected'] }})</span>
            </button>
        </div>
    @endif

    {{-- ------------------------------------------------------
         STICKY FILTER & SEARCH BAR
         ------------------------------------------------------ --}}
    <div class="adm-filter-bar bg-[#0b101b]/90 backdrop-blur-md border border-white/10 rounded-2xl p-3 sm:p-4 shadow-xl">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5 text-sm">
            {{-- Search Input --}}
            <div class="relative sm:col-span-1 lg:col-span-2">
                <div class="absolute inset-y-0 start-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input id="searchInput" type="search"
                       autocomplete="off"
                       enterkeyhint="search"
                       inputmode="search"
                       placeholder="بحث بالاسم / رقم الهاتف / الكود المرجعي / رقم الحجز (#3)"
                       class="w-full rounded-xl bg-black/60 border border-white/15 ps-10 pe-10
                              py-2.5 min-h-[44px] text-sm text-slate-100 placeholder:text-slate-500
                              focus:outline-none focus:border-amber-400/70 transition">
                <button id="searchClear" type="button"
                        aria-label="مسح نص البحث"
                        class="hidden absolute inset-y-0 end-2 my-auto h-8 w-8 rounded-full
                               bg-white/10 hover:bg-white/20 text-xs text-slate-300 items-center justify-center">
                    <svg class="w-3.5 h-3.5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Show / Showtime Dropdown --}}
            <div>
                <select id="dateTimeFilter"
                        aria-label="تصفية حسب موعد العرض"
                        class="w-full rounded-xl bg-black/60 border border-white/15 px-3
                               py-2.5 min-h-[44px] text-sm text-slate-200
                               focus:outline-none focus:border-amber-400/70 transition">
                    <option value="">جميع المواعيد والعروض</option>
                    @foreach(
                        $bookings->map(fn($b) => $b->showTime
                            ? $b->showTime->date->format('Y-m-d').' '.$b->showTime->time
                            : null)->filter()->unique()->sort()
                        as $dt
                    )
                        <option value="{{ $dt }}">
                            {{ \Carbon\Carbon::parse($dt)->format('d/m/Y • g:i A') }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Hidden status select for JS filter consistency --}}
        <select id="statusFilter" class="hidden" aria-hidden="true">
            <option value="">كل الحالات</option>
            <option value="pending">قيد المراجعة</option>
            <option value="approved">مقبول</option>
            <option value="rejected">مرفوض</option>
        </select>
    </div>

    @if($bookings->isEmpty())
        {{-- Empty State --}}
        <div class="bg-black/40 border border-white/10 rounded-2xl p-10 text-center space-y-3">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center text-slate-400">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
            </div>
            <h2 class="text-base font-semibold text-gray-200">لا توجد حجوزات مسجلة بعد</h2>
            <p class="text-xs text-gray-400 max-w-md mx-auto leading-relaxed">
                بمجرد إتمام الحجز والدفع من قبل الجمهور، ستظهر طلبات الحجز هنا تلقائياً لمراجعة صورة التحويل واعتماد التذاكر.
            </p>
        </div>
    @else

    {{-- ------------------------------------------------------
         💻 DESKTOP TABLE
         ------------------------------------------------------ --}}
    <div class="hidden md:block">
        <div class="bg-black/40 border border-white/10 rounded-2xl overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right text-slate-200">
                    <thead class="bg-white/5 text-xs text-slate-400 uppercase tracking-wider border-b border-white/10">
                        <tr>
                            <th class="px-3 py-3 font-semibold text-center w-16">#</th>
                            <th class="px-4 py-3 font-semibold">بيانات العميل</th>
                            <th class="px-4 py-3 font-semibold">العرض والموعد</th>
                            <th class="px-4 py-3 font-semibold">المبلغ والتحويل</th>
                            <th class="px-4 py-3 font-semibold text-center">حالة الحجز</th>
                            <th class="px-4 py-3 font-semibold text-center">واتساب والتسليم</th>
                            <th class="px-4 py-3 font-semibold">الكود</th>
                            <th class="px-3 py-3 font-semibold text-center w-24">الإجراء</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                    @foreach($bookings as $booking)
                        @php
                            $dt = $booking->showTime
                                ? $booking->showTime->date->format('Y-m-d').' '.$booking->showTime->time
                                : '';
                            $totalTickets = $booking->tickets->count();
                            $sentTickets  = $booking->tickets->filter(fn($t) => $t->whatsapp_sent && $t->delivery_status !== 'failed')->count();
                            $hasFailed    = $booking->tickets->where('delivery_status', 'failed')->isNotEmpty() || $booking->template_status === 'failed';
                            $isClaimed    = $booking->tickets->filter(fn($t) => $t->claimed_at)->isNotEmpty();
                        @endphp
                        <tr class="booking-row hover:bg-white/[0.03] transition duration-150"
                            data-search="{{ strtolower($booking->full_name.' '.$booking->phone.' '.$booking->reference_code) }}"
                            data-pn="{{ $booking->public_number ?? '' }}"
                            data-status="{{ $booking->status }}"
                            data-datetime="{{ $dt }}">

                            {{-- Booking Number --}}
                            <td class="px-3 py-3 text-center align-middle">
                                <span class="inline-flex items-center justify-center min-w-[42px] h-8 px-2 rounded-lg font-bold tabular-nums text-xs bg-amber-400/10 text-amber-200 border border-amber-400/30">
                                    #{{ $booking->public_number ?? $booking->id }}
                                </span>
                            </td>

                            {{-- Customer details --}}
                            <td class="px-4 py-3 align-top">
                                <p class="font-bold text-white leading-snug">{{ $booking->full_name }}</p>
                                <p class="text-xs text-slate-400 font-mono mt-0.5" dir="ltr">{{ $booking->phone }}</p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="inline-flex items-center gap-1 text-[11px] text-amber-300 font-medium">
                                        <svg class="w-3.5 h-3.5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                                        </svg>
                                        <span>{{ $booking->tickets_count }} تذكرة</span>
                                    </span>
                                    @if($booking->tickets->count() > 1)
                                        <details class="group inline-block">
                                            <summary class="cursor-pointer text-[11px] text-slate-500 hover:text-slate-300 select-none">
                                                (عرض {{ $booking->tickets->count() }} أسماء)
                                            </summary>
                                            <div class="mt-1 space-y-0.5 p-2 rounded-lg bg-black/70 border border-white/10 text-xs">
                                                @foreach($booking->tickets as $ticket)
                                                    <div class="text-[11px] text-slate-300 flex items-center justify-between gap-2">
                                                        <span class="inline-flex items-center gap-1">
                                                            <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                            </svg>
                                                            <span>{{ $ticket->name }}</span>
                                                        </span>
                                                        <span class="text-slate-500 font-mono" dir="ltr">{{ $ticket->phone }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </details>
                                    @endif
                                </div>
                            </td>

                            {{-- Show and ShowTime --}}
                            <td class="px-4 py-3 align-top">
                                <p class="font-semibold text-slate-100 text-xs sm:text-sm leading-snug">
                                    {{ $booking->showTime?->show?->title ?? '-' }}
                                </p>
                                <span class="text-slate-400 text-xs mt-0.5 inline-flex items-center gap-1.5 flex-wrap">
                                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span>{{ $booking->showTime?->date?->format('d/m/Y') }}</span>
                                    <span>•</span>
                                    <span>{{ $booking->showTime?->time ? \Carbon\Carbon::parse($booking->showTime->time)->format('g:i A') : '' }}</span>
                                </span>
                            </td>

                            {{-- Price & Payment Transfer Proof --}}
                            <td class="px-4 py-3 align-top">
                                <div class="flex items-start gap-2.5">
                                    @if($booking->transfer_screenshot_path)
                                        <button type="button"
                                                class="proof-thumb-btn w-12 h-14 shrink-0 focus:outline-none focus:ring-2 focus:ring-amber-400"
                                                data-preview-img="{{ $booking->transfer_screenshot_path }}"
                                                data-preview-title="إثبات تحويل #{{ $booking->public_number ?? $booking->id }} — {{ $booking->full_name }}"
                                                title="انقر لتكبير صورة التحويل">
                                            <img src="{{ $booking->transfer_screenshot_path }}"
                                                 alt="صورة التحويل"
                                                 class="w-full h-full object-cover">
                                        </button>
                                    @else
                                        <div class="w-12 h-14 rounded-lg bg-white/5 border border-white/10 shrink-0 flex items-center justify-center text-[10px] text-slate-500 text-center p-1">
                                            لا يوجد إثبات
                                        </div>
                                    @endif

                                    <div class="min-w-0">
                                        <p class="font-extrabold text-amber-300 text-sm tabular-nums">
                                            {{ number_format((int) $booking->total_price) }} <span class="text-[11px] font-normal text-amber-200/80">ج.م</span>
                                        </p>
                                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-white/5 border border-white/10 text-slate-400 mt-1 inline-block">
                                            {{ ($booking->payment_method && $booking->payment_method !== 'manual_transfer') ? $booking->payment_method : 'محفظة / إنستاباي' }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            {{-- Booking Status Pill --}}
                            <td class="px-4 py-3 text-center align-middle">
                                @if($booking->status === 'approved')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/15 text-emerald-200 border border-emerald-500/40">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                        مقبول
                                    </span>
                                @elseif($booking->status === 'rejected')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-500/15 text-rose-200 border border-rose-500/40">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                                        مرفوض
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-sky-500/15 text-sky-200 border border-sky-500/40">
                                        <span class="w-1.5 h-1.5 rounded-full bg-sky-400 animate-pulse"></span>
                                        قيد المراجعة
                                    </span>
                                @endif
                            </td>

                            {{-- WhatsApp Multi-Stage Delivery Indicator --}}
                            <td class="px-4 py-3 text-center align-middle">
                                @if($booking->status === 'approved')
                                    @if($sentTickets === $totalTickets && $totalTickets > 0)
                                        <span class="adm-delivery-pill bg-emerald-500/15 text-emerald-300 border border-emerald-500/40" title="تم تسليم جميع التذاكر للعميل عبر واتساب">
                                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                                            <span>تم الاستلام ({{ $sentTickets }}/{{ $totalTickets }})</span>
                                        </span>
                                    @elseif($hasFailed)
                                        <span class="adm-delivery-pill bg-rose-500/15 text-rose-300 border border-rose-500/40" title="تعذر إرسال بعض التذاكر عبر واتساب">
                                            <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                                            <span>فشل التسليم ({{ $sentTickets }}/{{ $totalTickets }})</span>
                                        </span>
                                    @elseif($isClaimed)
                                        <span class="adm-delivery-pill bg-amber-500/15 text-amber-300 border border-amber-500/40" title="نقر العميل وجاري الإرسال">
                                            <span class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span>
                                            <span>جاري التسليم ({{ $sentTickets }}/{{ $totalTickets }})</span>
                                        </span>
                                    @elseif($booking->template_status === 'sent')
                                        <span class="adm-delivery-pill bg-sky-500/15 text-sky-300 border border-sky-500/40" title="أُرسل قالب الاستلام، بانتظار نقر العميل على واتساب">
                                            <span class="w-2 h-2 rounded-full bg-sky-400"></span>
                                            <span>بانتظار نقر العميل</span>
                                        </span>
                                    @elseif($booking->template_status === 'sending')
                                        <span class="adm-delivery-pill bg-amber-500/15 text-amber-200 border border-amber-500/40">
                                            <span class="btn-spinner"></span>
                                            <span>جاري إرسال القالب…</span>
                                        </span>
                                    @else
                                        <span class="adm-delivery-pill bg-white/5 text-slate-400 border border-white/10">
                                            <span class="w-2 h-2 rounded-full bg-slate-500"></span>
                                            <span>معتمد (لم يُرسل)</span>
                                        </span>
                                    @endif
                                @elseif($booking->status === 'rejected')
                                    <span class="text-xs text-slate-500">—</span>
                                @else
                                    <span class="text-xs text-slate-500">بانتظار الاعتماد</span>
                                @endif
                            </td>

                            {{-- Reference Code --}}
                            <td class="px-4 py-3 align-middle font-mono text-xs text-slate-300" dir="ltr">
                                {{ $booking->reference_code }}
                            </td>

                            {{-- Action --}}
                            <td class="px-3 py-3 align-middle text-center">
                                <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                    <a href="{{ route('admin.bookings.show', $booking) }}"
                                       class="admin-btn admin-btn--primary admin-btn--sm">
                                        <span>تفاصيل</span>
                                        <svg class="w-3.5 h-3.5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ------------------------------------------------------
         📱 MOBILE CARDS
         ------------------------------------------------------ --}}
    <div class="md:hidden space-y-3">
        @foreach($bookings as $booking)
            @php
                $dt = $booking->showTime
                    ? $booking->showTime->date->format('Y-m-d').' '.$booking->showTime->time
                    : '';
                $totalTickets = $booking->tickets->count();
                $sentTickets  = $booking->tickets->filter(fn($t) => $t->whatsapp_sent && $t->delivery_status !== 'failed')->count();
                $hasFailed    = $booking->tickets->where('delivery_status', 'failed')->isNotEmpty() || $booking->template_status === 'failed';
                $isClaimed    = $booking->tickets->filter(fn($t) => $t->claimed_at)->isNotEmpty();
            @endphp

            <div class="adm-card bg-[#0e131f]/90 border border-white/10 rounded-2xl p-4 space-y-3 shadow-md booking-card"
                 data-search="{{ strtolower($booking->full_name.' '.$booking->phone.' '.$booking->reference_code) }}"
                 data-pn="{{ $booking->public_number ?? '' }}"
                 data-status="{{ $booking->status }}"
                 data-datetime="{{ $dt }}">

                {{-- Row 1: #N + Status Pill + Price --}}
                <div class="flex items-center justify-between gap-2 border-b border-white/5 pb-2.5">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center justify-center min-w-[38px] h-7 px-2 rounded-lg font-bold tabular-nums text-xs bg-amber-400/15 text-amber-200 border border-amber-400/30">
                            #{{ $booking->public_number ?? $booking->id }}
                        </span>

                        @if($booking->status === 'approved')
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/15 text-emerald-200 border border-emerald-500/40">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                مقبول
                            </span>
                        @elseif($booking->status === 'rejected')
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-500/15 text-rose-200 border border-rose-500/40">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                                مرفوض
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-500/15 text-sky-200 border border-sky-500/40">
                                <span class="w-1.5 h-1.5 rounded-full bg-sky-400 animate-pulse"></span>
                                قيد المراجعة
                            </span>
                        @endif
                    </div>

                    <div class="font-extrabold text-amber-300 text-sm tabular-nums">
                        {{ number_format((int) $booking->total_price) }} <span class="text-[11px] font-normal text-amber-200/80">ج.م</span>
                    </div>
                </div>

                {{-- Row 2: Customer Name & Phone & Screenshot Preview --}}
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <h2 class="font-bold text-base text-white truncate">{{ $booking->full_name }}</h2>
                        <p class="text-xs text-slate-400 font-mono mt-0.5" dir="ltr">{{ $booking->phone }}</p>
                        <p class="text-xs text-slate-300 mt-1.5 flex items-center gap-1.5 flex-wrap">
                            <svg class="w-3.5 h-3.5 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>
                            </svg>
                            <span class="font-medium text-slate-200">{{ $booking->showTime?->show?->title ?? '—' }}</span>
                            <span class="text-slate-500 mx-0.5">·</span>
                            <span class="text-slate-400 tabular-nums">
                                {{ $booking->showTime?->date?->format('d/m') }}
                                • {{ $booking->showTime?->time ? \Carbon\Carbon::parse($booking->showTime->time)->format('g:i A') : '' }}
                            </span>
                        </p>
                    </div>

                    @if($booking->transfer_screenshot_path)
                        <button type="button"
                                class="proof-thumb-btn w-12 h-16 shrink-0"
                                data-preview-img="{{ $booking->transfer_screenshot_path }}"
                                data-preview-title="إثبات تحويل #{{ $booking->public_number ?? $booking->id }} — {{ $booking->full_name }}"
                                title="عرض لقطة شاشة التحويل">
                            <img src="{{ $booking->transfer_screenshot_path }}"
                                 alt="صورة التحويل"
                                 class="w-full h-full object-cover">
                        </button>
                    @endif
                </div>

                {{-- Row 3: Tickets & WhatsApp State --}}
                <div class="flex items-center justify-between gap-2 pt-2 border-t border-white/5 text-xs flex-wrap">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-300 border border-amber-500/25 font-semibold text-[11px]">
                            <svg class="w-3 h-3 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                            </svg>
                            <span>{{ $booking->tickets_count }} تذكرة</span>
                        </span>

                        @if($booking->status === 'approved')
                            @if($sentTickets === $totalTickets && $totalTickets > 0)
                                <span class="adm-delivery-pill bg-emerald-500/15 text-emerald-300 border border-emerald-500/40 text-[10.5px]">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                    <span>تم الاستلام ({{ $sentTickets }}/{{ $totalTickets }})</span>
                                </span>
                            @elseif($hasFailed)
                                <span class="adm-delivery-pill bg-rose-500/15 text-rose-300 border border-rose-500/40 text-[10.5px]">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                                    <span>فشل التسليم</span>
                                </span>
                            @elseif($booking->template_status === 'sent')
                                <span class="adm-delivery-pill bg-sky-500/15 text-sky-300 border border-sky-500/40 text-[10.5px]">
                                    <span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span>
                                    <span>بانتظار نقر العميل</span>
                                </span>
                            @endif
                        @endif
                    </div>

                    <div class="flex items-center gap-1.5 ms-auto">
                        <a href="{{ route('admin.bookings.show', $booking) }}"
                           class="admin-btn admin-btn--primary admin-btn--sm">
                            <span>عرض التفاصيل</span>
                            <svg class="w-3.5 h-3.5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- "No matches" empty state --}}
    <div id="noMatchesEmpty" class="hidden bg-black/40 border border-white/10 rounded-2xl p-8 text-center space-y-3">
        <div class="w-12 h-12 mx-auto rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-slate-400">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
        <p class="text-sm text-gray-200">لا توجد حجوزات تطابق معايير البحث الحالية.</p>
        <button type="button" id="clearFilters"
                class="admin-btn admin-btn--secondary admin-btn--sm mt-2">
            مسح جميع الفلاتر
        </button>
    </div>

    @endif

</section>

{{-- =========================================================
     INTERACTIVE SCREENSHOT PREVIEW MODAL
     ========================================================= --}}
<div id="adm-screenshot-modal"
     class="fixed inset-0 z-50 hidden items-center justify-center p-3 sm:p-6 bg-black/85 backdrop-blur-md transition-opacity duration-200"
     role="dialog"
     aria-modal="true"
     aria-labelledby="adm-modal-title">
    <div class="relative max-w-xl w-full bg-[#0b0f19] border border-white/15 rounded-2xl overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
        <div class="flex items-center justify-between p-3.5 border-b border-white/10 bg-white/5">
            <h3 id="adm-modal-title" class="text-sm sm:text-base font-bold text-white truncate pe-2">
                إثبات التحويل
            </h3>
            <button type="button"
                    id="adm-modal-close"
                    class="w-9 h-9 rounded-xl bg-white/10 hover:bg-white/20 text-slate-200 flex items-center justify-center transition focus:outline-none"
                    aria-label="إغلاق المعاينة">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="p-3 overflow-auto flex-1 flex items-center justify-center bg-black/50">
            <img id="adm-modal-img"
                 src=""
                 alt="لقطة شاشة التحويل بالحجم الكامل"
                 class="max-w-full max-h-[70vh] object-contain rounded-lg border border-white/10">
        </div>

        <div class="p-3 border-t border-white/10 bg-white/5 flex items-center justify-between">
            <span class="text-xs text-slate-400">انقر خارج النافذة أو زر الإغلاق للخروج</span>
            <a id="adm-modal-link"
               href=""
               target="_blank"
               rel="noopener"
               class="admin-btn admin-btn--primary admin-btn--sm">
                <span>فتح الرابط الأصلي</span>
                <svg class="w-3.5 h-3.5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
            </a>
        </div>
    </div>
</div>

{{-- =========================================================
     GLOBAL SEND UNRECEIVED TICKETS CONFIRMATION MODAL
     ========================================================= --}}
<div id="adm-global-resend-modal"
     class="fixed inset-0 z-50 hidden items-center justify-center p-3 sm:p-4 bg-black/85 backdrop-blur-md"
     role="dialog"
     aria-modal="true"
     aria-labelledby="adm-global-resend-title">
    <div class="relative w-full max-w-md bg-[#0f172a] border border-white/20 rounded-2xl shadow-2xl p-5 sm:p-6 text-right">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-white/10">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-indigo-500/20 text-indigo-400 flex items-center justify-center">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </div>
                <h3 id="adm-global-resend-title" class="text-base font-bold text-white">تأكيد الإرسال الشامل للتذاكر</h3>
            </div>
            <button type="button"
                    id="adm-global-resend-close"
                    class="w-8 h-8 rounded-lg bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white flex items-center justify-center transition"
                    aria-label="إغلاق">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="space-y-3 mb-5">
            <p class="text-sm text-slate-100 font-semibold leading-relaxed">
                سيتم إرسال التذاكر غير المستلمة إلى أصحابها — العدد: <span class="text-amber-400 font-bold tabular-nums text-base">{{ $unreceivedTicketsCount ?? 0 }}</span>
            </p>
            <p class="text-xs text-slate-400 leading-normal">
                سيتم إرسال التذاكر مباشرة عبر واتساب للعملاء المتاحين، أو إرسال قوالب الاستلام لتجديد نافذة الـ 24 ساعة، مع تخطي التذاكر المستلمة مسبقاً تلقائياً لمنع التكرار.
            </p>
        </div>

        <form action="{{ route('admin.bookings.send-all-unreceived') }}"
              method="POST"
              data-processing-message="جاري معالجة وإرسال التذاكر الشامل عبر واتساب…">
            @csrf
            <div class="flex items-center justify-end gap-2.5">
                <button type="button"
                        id="adm-global-resend-cancel"
                        class="admin-btn admin-btn--secondary admin-btn--sm">
                    إلغاء
                </button>
                <button type="submit"
                        class="admin-btn admin-btn--indigo admin-btn--sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    <span>تأكيد وإرسال الآن</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var search       = document.getElementById('searchInput');
    var status       = document.getElementById('statusFilter');
    var dt           = document.getElementById('dateTimeFilter');
    var clearBtn     = document.getElementById('searchClear');
    var clearAllBtn  = document.getElementById('clearFilters');
    var resultCount  = document.getElementById('resultCount');
    var noMatches    = document.getElementById('noMatchesEmpty');
    var statusChips  = document.querySelectorAll('[data-status-jump]');
    var rows         = document.querySelectorAll('.booking-row');
    var cards        = document.querySelectorAll('.booking-card');
    var totalItems   = rows.length;

    function filter() {
        if (!search) return;
        var s = (search.value || '').toLowerCase().trim();
        var st = status.value;
        var when = dt.value;

        var hashMatch = s.match(/^#?\s*(\d+)$/);
        var pnQuery = hashMatch ? hashMatch[1] : null;
        var searchQuery = pnQuery ? '' : s;

        function matches(el) {
            if (pnQuery !== null && el.dataset.pn !== pnQuery) return false;
            if (searchQuery && !el.dataset.search.includes(searchQuery)) return false;
            if (st && el.dataset.status !== st) return false;
            if (when && el.dataset.datetime !== when) return false;
            return true;
        }

        var visibleRows = 0;
        rows.forEach(function (el) {
            var ok = matches(el);
            el.style.display = ok ? '' : 'none';
            if (ok) visibleRows++;
        });
        cards.forEach(function (el) {
            el.style.display = matches(el) ? '' : 'none';
        });

        if (clearBtn) clearBtn.classList.toggle('hidden', !s);
        if (noMatches) {
            noMatches.classList.toggle('hidden', visibleRows > 0 || totalItems === 0);
        }

        if (resultCount) {
            resultCount.innerText = (s || st || when)
                ? 'نتائج البحث: ' + visibleRows + ' من أصل ' + totalItems + ' حجز'
                : 'إجمالي الحجوزات: ' + totalItems + ' حجز';
        }
    }

    function syncChips() {
        statusChips.forEach(function (c) {
            c.classList.toggle('is-active', (c.dataset.statusJump || '') === (status.value || ''));
        });
    }

    if (search && status && dt) {
        [search, status, dt].forEach(function (i) {
            i.addEventListener('input', function () { filter(); syncChips(); });
            i.addEventListener('change', function () { filter(); syncChips(); });
        });

        statusChips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                status.value = chip.dataset.statusJump || '';
                filter();
                syncChips();
            });
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                search.value = '';
                filter();
                search.focus();
            });
        }
        if (clearAllBtn) {
            clearAllBtn.addEventListener('click', function () {
                search.value = '';
                status.value = '';
                dt.value = '';
                filter();
                syncChips();
            });
        }

        filter();
        syncChips();
    }

    // Modal Image Preview
    var modal = document.getElementById('adm-screenshot-modal');
    var modalImg = document.getElementById('adm-modal-img');
    var modalTitle = document.getElementById('adm-modal-title');
    var modalLink = document.getElementById('adm-modal-link');
    var modalClose = document.getElementById('adm-modal-close');

    function openModal(src, title) {
        if (!modal || !modalImg) return;
        modalImg.src = src;
        modalTitle.textContent = title || 'إثبات التحويل';
        modalLink.href = src;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal() {
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        if (modalImg) modalImg.src = '';
    }

    document.querySelectorAll('[data-preview-img]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            openModal(this.getAttribute('data-preview-img'), this.getAttribute('data-preview-title'));
        });
    });

    if (modalClose) modalClose.addEventListener('click', closeModal);
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModal();
        });
    }

    // Global Send Unreceived Tickets Modal
    var globalResendModal = document.getElementById('adm-global-resend-modal');
    var openGlobalResendBtn = document.getElementById('openGlobalResendModal');
    var closeGlobalResendBtn = document.getElementById('adm-global-resend-close');
    var cancelGlobalResendBtn = document.getElementById('adm-global-resend-cancel');

    function openGlobalResend() {
        if (!globalResendModal) return;
        globalResendModal.classList.remove('hidden');
        globalResendModal.classList.add('flex');
    }

    function closeGlobalResend() {
        if (!globalResendModal) return;
        globalResendModal.classList.add('hidden');
        globalResendModal.classList.remove('flex');
    }

    if (openGlobalResendBtn) openGlobalResendBtn.addEventListener('click', openGlobalResend);
    if (closeGlobalResendBtn) closeGlobalResendBtn.addEventListener('click', closeGlobalResend);
    if (cancelGlobalResendBtn) cancelGlobalResendBtn.addEventListener('click', closeGlobalResend);
    if (globalResendModal) {
        globalResendModal.addEventListener('click', function (e) {
            if (e.target === globalResendModal) closeGlobalResend();
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            if (modal && !modal.classList.contains('hidden')) closeModal();
            if (globalResendModal && !globalResendModal.classList.contains('hidden')) closeGlobalResend();
        }
    });
});
</script>
@endsection
