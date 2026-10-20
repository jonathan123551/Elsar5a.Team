@extends('layouts.admin')

@section('title', 'تفاصيل الحجز #' . ($booking->public_number ?? $booking->id) . ' — ' . $booking->full_name)

@section('content')
<style>
/* =========================================================
   STAGE ARCHITECTURE & GLASS CONTAINERS
   ========================================================= */
.adm2-aurora {
    position: fixed;
    inset: 0;
    z-index: 0;
    pointer-events: none;
    overflow: hidden;
}
.adm2-aurora::before,
.adm2-aurora::after {
    content: "";
    position: absolute;
    width: 60vmax;
    height: 60vmax;
    border-radius: 50%;
    filter: blur(80px);
    opacity: .25;
}
.adm2-aurora::before {
    top: -20vmax;
    inset-inline-start: -10vmax;
    background: radial-gradient(circle, rgba(245,158,11,0.4), transparent 60%);
}
.adm2-aurora::after {
    bottom: -25vmax;
    inset-inline-end: -10vmax;
    background: radial-gradient(circle, rgba(220,38,38,0.35), transparent 60%);
}

.adm2-stage {
    position: relative;
    z-index: 1;
    padding-bottom: calc(140px + env(safe-area-inset-bottom));
}

.adm2-glass {
    position: relative;
    background:
        linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.01)),
        rgba(15,23,42,0.65);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 1.5rem;
    backdrop-filter: blur(20px) saturate(160%);
    -webkit-backdrop-filter: blur(20px) saturate(160%);
    box-shadow:
        0 24px 60px -30px rgba(0,0,0,0.7),
        inset 0 1px 0 rgba(255,255,255,0.05);
}

.adm2-glass-soft {
    background:
        linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0)),
        rgba(15,23,42,0.45);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 1.25rem;
    backdrop-filter: blur(14px) saturate(140%);
    -webkit-backdrop-filter: blur(14px) saturate(140%);
}

/* =========================================================
   COMPACT BOOKING SUMMARY CARD
   ========================================================= */
.adm-summary-card {
    position: relative;
    overflow: hidden;
    border-radius: 1.5rem;
    border: 1px solid rgba(255,255,255,0.1);
    padding: 20px 22px;
    background:
        linear-gradient(180deg, rgba(15,23,42,0.75) 0%, rgba(2,6,23,0.92) 100%),
        rgba(15,23,42,0.6);
    backdrop-filter: blur(20px) saturate(160%);
    -webkit-backdrop-filter: blur(20px) saturate(160%);
    box-shadow: 0 25px 50px -20px rgba(0,0,0,0.8);
}
.adm-summary-card::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image: var(--adm2-poster, none);
    background-size: cover;
    background-position: center;
    opacity: .2;
    filter: blur(24px) saturate(130%);
    transform: scale(1.15);
    z-index: 0;
}
.adm-summary-card > * { position: relative; z-index: 1; }

.adm-price-gold {
    font-feature-settings: "tnum", "ss01";
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, monospace;
    font-weight: 800;
    letter-spacing: -0.02em;
    background: linear-gradient(180deg, #fef08a 0%, #f59e0b 60%, #d97706 100%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}

.adm2-status {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 5px 14px;
    border-radius: 9999px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.02em;
    border: 1px solid transparent;
}
.adm2-status .dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: currentColor;
    box-shadow: 0 0 10px currentColor;
}
.adm2-status[data-tone="approved"] {
    color: rgb(110,231,183);
    background: rgba(16,185,129,0.12);
    border-color: rgba(16,185,129,0.4);
}
.adm2-status[data-tone="rejected"] {
    color: rgb(252,165,165);
    background: rgba(239,68,68,0.12);
    border-color: rgba(239,68,68,0.4);
}
.adm2-status[data-tone="pending"] {
    color: rgb(125,211,252);
    background: rgba(14,165,233,0.12);
    border-color: rgba(14,165,233,0.4);
}

/* =========================================================
   PAYMENT PROOF THUMBNAIL
   ========================================================= */
.adm-proof-thumb {
    position: relative;
    display: block;
    width: 80px;
    height: 104px;
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,0.18);
    background: #020617;
    box-shadow: 0 10px 25px -5px rgba(0,0,0,0.6);
    transition: all 0.2s ease;
    cursor: pointer;
    touch-action: manipulation;
}
.adm-proof-thumb:hover {
    border-color: rgba(245,158,11,0.6);
    transform: scale(1.03);
    box-shadow: 0 14px 30px -5px rgba(245,158,11,0.25);
}
.adm-proof-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* =========================================================
   TICKET OPERATIONAL BREAKDOWN & STUBS
   ========================================================= */
.adm2-stub {
    position: relative;
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 12px;
    padding: 14px 16px;
    border-radius: 14px;
    background:
        linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.01)),
        rgba(15,23,42,0.5);
    border: 1px solid rgba(255,255,255,0.08);
    transition: all .15s ease-in-out;
}
.adm2-stub:hover {
    border-color: rgba(245,158,11,0.25);
    background: rgba(15,23,42,0.65);
}
.adm2-stub .who .name {
    font-size: 14px;
    font-weight: 700;
    color: white;
}
.adm2-stub .who .phone {
    font-size: 12px;
    color: rgb(148,163,184);
    margin-top: 2px;
    font-variant-numeric: tabular-nums;
}
.adm2-stub .code {
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    font-size: 11px;
    letter-spacing: 0.04em;
    color: rgb(252,211,77);
    background: rgba(245,158,11,0.08);
    border: 1px solid rgba(245,158,11,0.25);
    border-radius: 6px;
    padding: 2px 7px;
    margin-top: 6px;
    display: inline-block;
}

.adm2-stub .led {
    text-align: center;
    align-self: center;
    padding-inline-start: 12px;
    border-inline-start: 1px dashed rgba(255,255,255,0.12);
    min-width: 80px;
}
.adm2-stub .led .bulb {
    width: 9px;
    height: 9px;
    border-radius: 50%;
    margin: 0 auto 5px;
}
.adm2-stub .led .bulb[data-tone="ok"] {
    background: rgb(74,222,128);
    box-shadow: 0 0 12px rgba(74,222,128,0.7);
}
.adm2-stub .led .bulb[data-tone="fail"] {
    background: rgb(248,113,113);
    box-shadow: 0 0 12px rgba(248,113,113,0.6);
}
.adm2-stub .led .bulb[data-tone="wait"] {
    background: rgb(56,189,248);
    box-shadow: 0 0 12px rgba(56,189,248,0.6);
}

.adm2-stub-acts {
    grid-column: 1 / -1;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px dashed rgba(255,255,255,0.08);
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

/* =========================================================
   FLOATING ACTION DOCK
   ========================================================= */
.adm2-dock {
    position: fixed;
    inset-inline: 0;
    bottom: 0;
    z-index: 60;
    padding: 0 14px max(14px, env(safe-area-inset-bottom));
    pointer-events: none;
}
.adm2-dock-inner {
    pointer-events: auto;
    max-width: 44rem;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 8px 12px;
    background: rgba(7, 10, 17, 0.92);
    border: 1px solid rgba(255,255,255,0.14);
    border-radius: 9999px;
    backdrop-filter: blur(20px) saturate(160%);
    -webkit-backdrop-filter: blur(20px) saturate(160%);
    box-shadow: 0 30px 60px -20px rgba(0,0,0,0.85);
}
@media (max-width: 639.98px) {
    .adm2-dock {
        padding: 0 8px max(10px, env(safe-area-inset-bottom));
    }
    .adm2-dock-inner {
        border-radius: 1.25rem;
        padding: 6px 8px;
        gap: 5px;
    }
    .adm2-dock-inner .admin-btn {
        min-height: 42px;
        padding: 0.375rem 0.625rem;
        font-size: 0.72rem;
        gap: 0.35rem;
        flex: 1 1 0%;
        white-space: nowrap;
    }
    .adm2-dock-inner .admin-btn svg {
        width: 0.875rem;
        height: 0.875rem;
    }
}

/* Two-step confirm states */
[data-confirm] .adm2-confirm-armed { display: none; }
[data-confirm][data-armed="true"] .adm2-confirm-trigger { display: none; }
[data-confirm][data-armed="true"] .adm2-confirm-armed {
    display: flex;
    gap: 8px;
    width: 100%;
}

/* Processing Overlay */
.adm2-processing {
    position: fixed;
    inset: 0;
    z-index: 80;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(2,6,23,0.85);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}
.adm2-processing[data-open="true"] { display: flex; }
.adm2-processing .panel {
    background: rgba(15,23,42,0.95);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 20px;
    padding: 28px 32px;
    text-align: center;
    max-width: 380px;
    box-shadow: 0 30px 60px -15px rgba(0,0,0,0.85);
}
.adm2-processing .ring {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    margin: 0 auto 16px;
    border: 3px solid rgba(245,158,11,0.2);
    border-top-color: rgb(252,211,77);
    animation: admSpin .8s linear infinite;
}
</style>

<div class="adm2-aurora" aria-hidden="true"></div>

@php
    $show        = $booking->showTime?->show;
    $showTime    = $booking->showTime;
    $posterCss   = $show?->poster_path ? "url('" . e($show->poster_path) . "')" : null;
    $totalCount  = $booking->tickets->count();
    $sentCount   = $booking->tickets->where('whatsapp_sent', true)->count();
    $failedCount = $booking->tickets->where('delivery_status', 'failed')->count();
    $pendingCount = $totalCount - $sentCount - $failedCount;
    $claimedCount = $booking->tickets->filter(fn($t) => $t->claimed_at)->count();
    $hasUnreceived = ($sentCount < $totalCount) && ($booking->status === 'approved');
@endphp

<section class="adm2-stage space-y-5 max-w-4xl mx-auto">

    {{-- Top Navigation Back Affordance & Reference --}}
    <div class="flex items-center justify-between gap-3">
        @include('admin.partials.back-button', ['url' => route('admin.bookings.index'), 'label' => 'العودة لقائمة الحجوزات'])

        <div class="flex items-center gap-2">
            <span class="text-xs text-slate-400 font-mono bg-white/5 border border-white/10 px-3 py-1.5 rounded-xl" dir="ltr">
                Ref: {{ $booking->reference_code }}
            </span>
        </div>
    </div>

    {{-- ------------------------------------------------------
         1. COMPACT BOOKING SUMMARY CARD (WITH INTEGRATED PROOF)
         ------------------------------------------------------ --}}
    <div class="adm-summary-card" @if($posterCss) style="--adm2-poster: {{ $posterCss }};" @endif>
        {{-- Header Strip: Booking Number + Status Pill --}}
        <div class="flex items-center justify-between gap-3 flex-wrap pb-3.5 border-b border-white/10">
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center justify-center min-w-[50px] h-8 px-2.5 rounded-lg font-bold tabular-nums text-sm bg-amber-400/15 text-amber-200 border border-amber-400/35">
                    #{{ $booking->public_number ?? $booking->id }}
                </span>
                <span class="text-xs text-slate-400 font-medium hidden sm:inline">حجز مؤكد بالنظام</span>
            </div>

            @if($booking->status === 'approved')
                <span class="adm2-status" data-tone="approved">
                    <span class="dot" aria-hidden="true"></span>
                    <span>معتمد ومقبول</span>
                </span>
            @elseif($booking->status === 'rejected')
                <span class="adm2-status" data-tone="rejected">
                    <span class="dot" aria-hidden="true"></span>
                    <span>حجز مرفوض</span>
                </span>
            @else
                <span class="adm2-status" data-tone="pending">
                    <span class="dot" aria-hidden="true"></span>
                    <span>قيد مراجعة التحويل</span>
                </span>
            @endif
        </div>

        {{-- Core Details Grid --}}
        <div class="mt-4 grid grid-cols-1 md:grid-cols-12 gap-4 items-center">

            {{-- Show & Booker Info --}}
            <div class="md:col-span-8 space-y-3">
                {{-- Show title and timing --}}
                <div>
                    <h1 class="text-lg sm:text-xl font-bold font-heading text-white flex items-center gap-2 flex-wrap">
                        <svg class="w-5 h-5 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>
                        </svg>
                        <span>{{ $show?->title ?? 'عرض مسرحي' }}</span>
                    </h1>
                    @if($showTime)
                        <p class="text-xs sm:text-sm text-slate-300 mt-1 flex items-center gap-2 flex-wrap">
                            <span class="inline-flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span>{{ $showTime->date?->format('d/m/Y') }}</span>
                            </span>
                            <span class="text-slate-500">·</span>
                            <span class="inline-flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>{{ $showTime->time ? \Carbon\Carbon::parse($showTime->time)->format('g:i A') : '' }}</span>
                            </span>
                            @if($showTime->hall_name)
                                <span class="text-slate-500">·</span>
                                <span class="text-slate-400">{{ $showTime->hall_name }}</span>
                            @endif
                        </p>
                    @endif
                </div>

                {{-- Booker details & Direct WhatsApp Action --}}
                <div class="pt-2 flex items-center justify-between gap-3 flex-wrap">
                    <div>
                        <div class="text-xs text-slate-400">صاحب الحجز:</div>
                        <p class="text-base font-bold text-white">{{ $booking->full_name }}</p>
                        <p class="text-xs text-slate-300 font-mono mt-0.5" dir="ltr">{{ $booking->phone }}</p>
                    </div>

                    @if($booking->phone)
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $booking->phone) }}"
                           target="_blank" rel="noopener"
                           class="admin-btn admin-btn--success admin-btn--sm">
                            <svg class="w-4 h-4 shrink-0 text-emerald-400" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                            </svg>
                            <span>مراسلة واتساب</span>
                        </a>
                    @endif
                </div>

                {{-- Metrics Strip: Quantity + Total EGP + Payment Method --}}
                <div class="pt-3 border-t border-white/10 flex items-center gap-4 sm:gap-6 flex-wrap">
                    <div>
                        <span class="text-[11px] text-slate-400 block">عدد التذاكر</span>
                        <span class="text-base font-bold text-white tabular-nums flex items-center gap-1.5 mt-0.5">
                            <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                            </svg>
                            <span>{{ $booking->tickets_count }} تذكرة</span>
                        </span>
                    </div>

                    <div>
                        <span class="text-[11px] text-slate-400 block">إجمالي المبلغ المطلوب</span>
                        <span class="text-lg sm:text-xl adm-price-gold block mt-0.5 tabular-nums">
                            {{ number_format((int) $booking->total_price) }} <span class="text-xs font-normal text-amber-200">ج.م</span>
                        </span>
                    </div>

                    <div>
                        <span class="text-[11px] text-slate-400 block">طريقة التحويل</span>
                        <span class="text-xs font-medium text-slate-300 px-2 py-0.5 rounded-lg bg-white/5 border border-white/10 inline-block mt-1">
                            {{ $booking->payment_method ?? 'محفظة إلكترونية / إنستاباي' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Integrated Payment Proof Thumbnail (Clean in-page Lightbox Trigger) --}}
            <div class="md:col-span-4 flex flex-col items-center sm:items-end justify-center pt-2 md:pt-0">
                @if($booking->transfer_screenshot_path)
                    <div class="text-center sm:text-end space-y-1.5">
                        <span class="text-[11px] font-semibold text-slate-400 flex items-center gap-1 justify-center sm:justify-end">
                            <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>صورة إثبات التحويل</span>
                        </span>

                        <button type="button"
                                id="openProofBtn"
                                class="adm-proof-thumb group focus:outline-none focus:ring-2 focus:ring-amber-400 mx-auto sm:ms-auto sm:me-0"
                                aria-label="عرض صورة إثبات التحويل بحجم كبير">
                            <img src="{{ $booking->transfer_screenshot_path }}"
                                 alt="صورة تحويل الحجز"
                                 loading="lazy">
                            <div class="absolute inset-0 bg-black/40 group-hover:bg-black/10 transition flex items-center justify-center text-white">
                                <svg class="w-5 h-5 drop-shadow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/>
                                </svg>
                            </div>
                        </button>
                        <span class="text-[10px] text-slate-500 block">انقر للتكبير الفوري</span>
                    </div>
                @else
                    <div class="w-24 h-28 rounded-xl bg-white/5 border border-dashed border-white/15 flex flex-col items-center justify-center p-2 text-center text-slate-500 text-[11px]">
                        <svg class="w-5 h-5 mb-1 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>لم يُرفق إثبات تحويل</span>
                    </div>
                @endif
            </div>

        </div>
    </div>

    {{-- ------------------------------------------------------
         2. TICKET DELIVERY STATUS (REFINED & SIMPLIFIED SECTION)
         ------------------------------------------------------ --}}
    <div class="adm2-glass p-5 sm:p-6 space-y-4">
        {{-- Section Title & Aggregate Status Pill --}}
        <div class="flex items-center justify-between gap-3 border-b border-white/10 pb-3.5 flex-wrap">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-bold font-heading text-white">حالة تسليم التذاكر عبر واتساب</h2>
                    <p class="text-xs text-slate-400">متابعة دقيقة لحالة الإرسال، قالب Meta، والأخطاء</p>
                </div>
            </div>

            {{-- Aggregate State Pill --}}
            <div>
                @if($totalCount > 0 && $sentCount === $totalCount)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-emerald-500/15 text-emerald-300 border border-emerald-500/40">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        <span>تم تسليم جميع التذاكر بالكامل ({{ $sentCount }}/{{ $totalCount }})</span>
                    </span>
                @elseif($failedCount > 0)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-rose-500/15 text-rose-300 border border-rose-500/40">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>يوجد {{ $failedCount }} تذكرة متعثرة التسليم ({{ $sentCount }}/{{ $totalCount }})</span>
                    </span>
                @elseif($booking->template_status === 'sent')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-sky-500/15 text-sky-300 border border-sky-500/40">
                        <span class="w-2 h-2 rounded-full bg-sky-400 animate-pulse"></span>
                        <span>أُرسل القالب · بانتظار نقر العميل على واتساب</span>
                    </span>
                @elseif($booking->status === 'approved')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-amber-500/15 text-amber-300 border border-amber-500/40">
                        <span class="btn-spinner"></span>
                        <span>معتمد · جاري المعالجة والإرسال</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-white/5 text-slate-400 border border-white/10">
                        <span>بانتظار اعتماد الحجز أولاً</span>
                    </span>
                @endif
            </div>
        </div>

        {{-- 3-Step Compact Progress Overview --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            {{-- Step 1: Meta Template --}}
            <div class="p-3.5 rounded-xl bg-black/40 border border-white/10 space-y-1">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-400 font-medium">1. قالب الاستلام (Meta)</span>
                    @if($booking->template_status === 'sent')
                        <span class="text-emerald-400 font-bold">✓ تم الإرسال</span>
                    @elseif($booking->template_status === 'failed')
                        <span class="text-rose-400 font-bold">⚠ تعذر الإرسال</span>
                    @elseif($booking->template_status === 'sending')
                        <span class="text-amber-400 font-bold">جاري الإرسال…</span>
                    @else
                        <span class="text-slate-500">معلق</span>
                    @endif
                </div>
                <p class="text-[11px] text-slate-400 leading-tight">
                    @if($booking->template_status === 'sent')
                        أُرسل للعميل بنجاح @if($booking->template_sent_at) ({{ $booking->template_sent_at->format('g:i A') }}) @endif
                    @elseif($booking->template_status === 'failed')
                        <span class="text-rose-300">{{ $booking->template_error ?? 'خطأ في إرسال القالب' }}</span>
                    @else
                        يُرسل تلقائياً لفتح نافذة واتساب الرسمية.
                    @endif
                </p>
            </div>

            {{-- Step 2: Customer Tap --}}
            <div class="p-3.5 rounded-xl bg-black/40 border border-white/10 space-y-1">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-400 font-medium">2. نقر العميل على الاستلام</span>
                    @if($claimedCount > 0)
                        <span class="text-emerald-400 font-bold">✓ نقر العميل</span>
                    @elseif($booking->status === 'approved')
                        <span class="text-sky-400 font-medium">بانتظار النقر</span>
                    @else
                        <span class="text-slate-500">معلق</span>
                    @endif
                </div>
                <p class="text-[11px] text-slate-400 leading-tight">
                    @if($claimedCount > 0)
                        تم استقبال نقرة العميل عبر الـ Webhook.
                    @else
                        فور نقر العميل، تُرسل صور التذاكر فورياً.
                    @endif
                </p>
            </div>

            {{-- Step 3: Direct Ticket Delivery --}}
            <div class="p-3.5 rounded-xl bg-black/40 border border-white/10 space-y-1">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-400 font-medium">3. تسليم صور التذاكر (QR)</span>
                    <span class="font-bold tabular-nums {{ $sentCount === $totalCount && $totalCount > 0 ? 'text-emerald-400' : ($failedCount > 0 ? 'text-rose-400' : 'text-slate-300') }}">
                        {{ $sentCount }}/{{ $totalCount }}
                    </span>
                </div>
                <p class="text-[11px] text-slate-400 leading-tight">
                    @if($sentCount === $totalCount && $totalCount > 0)
                        وصلت التذاكر على هاتف العميل بالكامل.
                    @elseif($failedCount > 0)
                        <span class="text-rose-300">يوجد {{ $failedCount }} تذكرة لم تُسلّم بعد.</span>
                    @else
                        بانتظار اكتمال تسليم التذاكر.
                    @endif
                </p>
            </div>
        </div>

        {{-- Collapsible Ticket Operational Breakdown --}}
        <details class="group rounded-2xl bg-black/30 border border-white/10 overflow-hidden" open>
            <summary class="cursor-pointer flex items-center justify-between p-3.5 sm:p-4 bg-white/[0.03] hover:bg-white/[0.06] transition text-xs sm:text-sm font-bold text-white select-none">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                    </svg>
                    <span>تفاصيل التذاكر الفردية ({{ $totalCount }})</span>
                </span>
                <span class="flex items-center gap-1.5 text-xs text-slate-400 font-normal">
                    <span>عرض / إخفاء تفاصيل التذاكر</span>
                    <svg class="w-4 h-4 transition-transform duration-200 group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </span>
            </summary>

            <div class="p-3 sm:p-4 space-y-3 border-t border-white/5">
                @forelse($booking->tickets as $ticket)
                    @php
                        $isTicketDelivered = $ticket->whatsapp_sent || $ticket->delivery_status === 'sent';
                        $isTicketFailed    = $ticket->delivery_status === 'failed';
                    @endphp
                    <div class="adm2-stub">
                        <div class="who min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="name">{{ $ticket->name }}</span>
                                @if($ticket->is_scanned)
                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 font-bold">
                                        ✓ تم الدخول على الباب
                                    </span>
                                @endif
                            </div>

                            <p class="phone" dir="ltr">{{ $ticket->phone }}</p>

                            @if($ticket->ticket_code)
                                <span class="code" dir="ltr">{{ $ticket->ticket_code }}</span>
                            @endif

                            {{-- Detailed Specific Error Display --}}
                            @if($ticket->delivery_error)
                                <div class="mt-2.5 p-2.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-200 text-xs space-y-1">
                                    <div class="font-bold flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                        <span>سبب تعذر تسليم التذكرة:</span>
                                    </div>
                                    <p class="text-rose-300 text-[11px] leading-relaxed">{{ $ticket->delivery_error }}</p>
                                </div>
                            @endif
                        </div>

                        {{-- LED Indicator & Status --}}
                        <div class="led shrink-0">
                            <div class="bulb" data-tone="{{ $isTicketDelivered ? 'ok' : ($isTicketFailed ? 'fail' : 'wait') }}" aria-hidden="true"></div>
                            <div class="text-[11px] font-semibold {{ $isTicketDelivered ? 'text-emerald-300' : ($isTicketFailed ? 'text-rose-300' : 'text-sky-300') }}">
                                @if($isTicketDelivered)
                                    تم الاستلام
                                @elseif($isTicketFailed)
                                    تعذر التسليم
                                @else
                                    بانتظار الاستلام
                                @endif
                            </div>
                        </div>

                        {{-- Per-Ticket Operational Actions --}}
                        @if($booking->status === 'approved')
                            <div class="adm2-stub-acts">
                                @if($ticket->qr_image_path)
                                    <a href="{{ $ticket->qr_image_path }}"
                                       target="_blank" rel="noopener"
                                       class="admin-btn admin-btn--secondary admin-btn--sm">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <span>معاينة صورة التذكرة</span>
                                    </a>
                                @else
                                    <span class="admin-btn admin-btn--secondary admin-btn--sm opacity-50 cursor-not-allowed">
                                        <span class="btn-spinner"></span>
                                        <span>جاري إنشاء التذكرة…</span>
                                    </span>
                                @endif

                                <form action="{{ route('admin.resend.ticket', $ticket->id) }}"
                                      method="POST"
                                      data-processing-message="جاري إعادة إرسال التذكرة للرقم عبر واتساب…">
                                    @csrf
                                    <button type="submit" class="admin-btn admin-btn--warning admin-btn--sm">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                        <span>إعادة إرسال التذكرة</span>
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-center py-6 text-xs text-slate-400">
                        لا توجد تذاكر مرتبطة بهذا الحجز.
                    </div>
                @endforelse
            </div>
        </details>
    </div>

</section>

{{-- =========================================================
     3. FLOATING ACTION DOCK
     ========================================================= --}}
<div class="adm2-dock" role="region" aria-label="شريط الإجراءات الرئيسي للحجز">
    <div class="adm2-dock-inner">
        @if($booking->status === 'pending')
            {{-- Reject Form --}}
            <form action="{{ route('admin.bookings.reject', $booking) }}" method="POST"
                  class="flex-1"
                  data-processing-message="جاري رفض الحجز وتحديث حالة المقاعد…">
                @csrf
                <button type="submit" class="admin-btn admin-btn--danger w-full">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    <span>رفض الحجز</span>
                </button>
            </form>

            {{-- Approve Form --}}
            <form action="{{ route('admin.bookings.approve', $booking) }}" method="POST"
                  class="flex-1"
                  data-processing-message="جاري اعتماد الحجز وإنشاء التذاكر وإرسال واتساب…">
                @csrf
                <button type="submit" class="admin-btn admin-btn--primary w-full">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>اعتماد وإرسال التذاكر</span>
                </button>
            </form>

        @elseif($booking->status === 'approved')
            {{-- Resend unreceived tickets action (Primary operational action if any tickets unreceived) --}}
            @if($hasUnreceived)
                <form action="{{ route('admin.bookings.resend-unreceived', $booking->id) }}"
                      method="POST"
                      class="flex-1"
                      data-processing-message="جاري إعادة إرسال التذاكر غير المستلمة عبر واتساب…">
                    @csrf
                    <button type="submit" class="admin-btn admin-btn--warning w-full" title="إعادة إرسال التذاكر غير المستلمة فقط">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span><span class="hidden sm:inline">إعادة إرسال غير المستلمة</span><span class="sm:hidden">غير المستلمة</span></span>
                    </button>
                </form>
            @endif

            {{-- Resend all tickets action --}}
            <form action="{{ route('admin.bookings.resend-all', $booking->id) }}"
                  method="POST"
                  class="flex-1"
                  data-processing-message="جاري فحص حالة واتساب وإعادة إرسال التذاكر…">
                @csrf
                <button type="submit" class="admin-btn admin-btn--secondary w-full" title="إعادة إرسال كل تذاكر الحجز">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span><span class="hidden sm:inline">إعادة إرسال التذاكر</span><span class="sm:hidden">إعادة الكل</span></span>
                </button>
            </form>

            {{-- Two-step Delete button --}}
            <div class="flex-1" data-confirm>
                <button type="button" class="admin-btn admin-btn--danger adm2-confirm-trigger w-full" title="حذف الحجز نهائياً">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    <span><span class="hidden sm:inline">حذف الحجز</span><span class="sm:hidden">حذف</span></span>
                </button>

                <div class="adm2-confirm-armed">
                    <button type="button" class="admin-btn admin-btn--secondary adm2-confirm-cancel">
                        إلغاء
                    </button>
                    <form action="{{ route('admin.booking.delete', $booking->id) }}" method="POST"
                          class="flex-1"
                          data-processing-message="جاري حذف الحجز نهائياً…">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="admin-btn admin-btn--danger w-full">
                            تأكيد الحذف
                        </button>
                    </form>
                </div>
            </div>

        @elseif($booking->status === 'rejected')
            {{-- Two-step Delete button for rejected bookings --}}
            <div class="w-full" data-confirm>
                <button type="button" class="admin-btn admin-btn--danger adm2-confirm-trigger w-full">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    <span>حذف الحجز المرفوض</span>
                </button>

                <div class="adm2-confirm-armed">
                    <button type="button" class="admin-btn admin-btn--secondary adm2-confirm-cancel">
                        إلغاء
                    </button>
                    <form action="{{ route('admin.booking.delete', $booking->id) }}" method="POST"
                          class="flex-1"
                          data-processing-message="جاري حذف الحجز…">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="admin-btn admin-btn--danger w-full">
                            تأكيد الحذف نهائياً
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>

{{-- =========================================================
     IN-PAGE PAYMENT PROOF LIGHTBOX MODAL
     ========================================================= --}}
@if($booking->transfer_screenshot_path)
<div id="adm-proof-modal"
     class="fixed inset-0 z-50 hidden items-center justify-center p-3 sm:p-6 bg-black/85 backdrop-blur-md transition-opacity duration-200"
     role="dialog"
     aria-modal="true"
     aria-labelledby="proofModalTitle">
    <div class="relative max-w-xl w-full bg-[#0b0f19] border border-white/15 rounded-2xl overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
        <div class="flex items-center justify-between p-3.5 border-b border-white/10 bg-white/5">
            <h3 id="proofModalTitle" class="text-sm sm:text-base font-bold text-white truncate pe-2">
                إثبات تحويل #{{ $booking->public_number ?? $booking->id }} — {{ $booking->full_name }}
            </h3>
            <button type="button"
                    id="closeProofBtn"
                    class="w-9 h-9 rounded-xl bg-white/10 hover:bg-white/20 text-slate-200 flex items-center justify-center transition focus:outline-none"
                    aria-label="إغلاق المعاينة">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="p-3 overflow-auto flex-1 flex items-center justify-center bg-black/60">
            <img src="{{ $booking->transfer_screenshot_path }}"
                 alt="لقطة شاشة تحويل الحجز بالحجم الكامل"
                 class="max-w-full max-h-[72vh] object-contain rounded-lg border border-white/10">
        </div>

        <div class="p-3 border-t border-white/10 bg-white/5 flex items-center justify-between flex-wrap gap-2">
            <span class="text-xs text-slate-400">انقر خارج النافذة أو زر الإغلاق للخروج</span>
            <a href="{{ $booking->transfer_screenshot_path }}"
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
@endif

{{-- Processing Overlay --}}
<div id="adm2-processing" class="adm2-processing" role="dialog" aria-modal="true">
    <div class="panel">
        <div class="ring" aria-hidden="true"></div>
        <h3 id="adm2-proc-title" class="text-base font-bold text-white mb-2 font-heading">جاري المعالجة…</h3>
        <p class="text-xs text-slate-400">يرجى الانتظار حتى اكتمال الطلب والتواصل مع خوادم واتساب.</p>
    </div>
</div>

<script>
(function () {
    // Lightbox modal logic
    var proofModal = document.getElementById('adm-proof-modal');
    var openBtn    = document.getElementById('openProofBtn');
    var closeBtn   = document.getElementById('closeProofBtn');

    function openModal() {
        if (!proofModal) return;
        proofModal.classList.remove('hidden');
        proofModal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        if (!proofModal) return;
        proofModal.classList.add('hidden');
        proofModal.classList.remove('flex');
        document.body.style.overflow = '';
    }

    if (openBtn) openBtn.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);

    if (proofModal) {
        proofModal.addEventListener('click', function (e) {
            if (e.target === proofModal) closeModal();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !proofModal.classList.contains('hidden')) {
                closeModal();
            }
        });
    }

    // Two-step Delete Confirm
    document.querySelectorAll('[data-confirm]').forEach(function (wrap) {
        var trigger = wrap.querySelector('.adm2-confirm-trigger');
        var cancel  = wrap.querySelector('.adm2-confirm-cancel');
        var timer   = null;

        function disarm() {
            wrap.removeAttribute('data-armed');
            if (timer) { clearTimeout(timer); timer = null; }
        }
        if (trigger) {
            trigger.addEventListener('click', function () {
                wrap.setAttribute('data-armed', 'true');
                if (timer) clearTimeout(timer);
                timer = setTimeout(disarm, 7000);
            });
        }
        if (cancel) cancel.addEventListener('click', disarm);
    });

    // Processing Overlay
    var overlay = document.getElementById('adm2-processing');
    var procTitle = document.getElementById('adm2-proc-title');

    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            var msg = form.getAttribute('data-processing-message');
            if (msg && overlay && procTitle) {
                procTitle.textContent = msg;
                overlay.setAttribute('data-open', 'true');
            }
        });
    });
})();
</script>
@endsection
