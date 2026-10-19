@extends('layouts.admin')

@section('title', 'تفاصيل الحجز #' . ($booking->public_number ?? $booking->id) . ' — ' . $booking->full_name)

@section('content')
<style>
/* =========================================================
   AURORA BACKDROP & STAGE ARCHITECTURE
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
    opacity: .3;
}
.adm2-aurora::before {
    top: -20vmax;
    inset-inline-start: -10vmax;
    background: radial-gradient(circle, rgba(245,158,11,0.5), transparent 60%);
}
.adm2-aurora::after {
    bottom: -25vmax;
    inset-inline-end: -10vmax;
    background: radial-gradient(circle, rgba(220,38,38,0.4), transparent 60%);
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
   HERO MARQUEE
   ========================================================= */
.adm2-hero {
    position: relative;
    overflow: hidden;
    border-radius: 1.75rem;
    border: 1px solid rgba(255,255,255,0.1);
    padding: 22px 22px 24px;
    background:
        linear-gradient(180deg, rgba(15,23,42,0.65) 0%, rgba(2,6,23,0.9) 80%),
        rgba(15,23,42,0.6);
    backdrop-filter: blur(20px) saturate(160%);
    -webkit-backdrop-filter: blur(20px) saturate(160%);
    box-shadow: 0 30px 60px -30px rgba(0,0,0,0.8);
}
.adm2-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image: var(--adm2-poster, none);
    background-size: cover;
    background-position: center;
    opacity: .28;
    filter: blur(28px) saturate(140%);
    transform: scale(1.2);
    z-index: 0;
}
.adm2-hero::after {
    content: "";
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 0% 0%, rgba(252,211,77,0.18), transparent 50%),
        radial-gradient(circle at 100% 100%, rgba(248,113,113,0.18), transparent 50%);
    z-index: 0;
    pointer-events: none;
}
.adm2-hero > * { position: relative; z-index: 1; }

.adm2-bk-num {
    font-feature-settings: "tnum", "ss01";
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, monospace;
    font-size: clamp(44px, 12vw, 76px);
    line-height: 1;
    font-weight: 900;
    letter-spacing: -0.04em;
    background: linear-gradient(180deg, #fde68a 0%, #f59e0b 60%, #b45309 100%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    text-shadow: 0 12px 40px rgba(245,158,11,0.25);
}

.adm2-status {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 16px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.02em;
    border: 1px solid transparent;
}
.adm2-status .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: currentColor;
    box-shadow: 0 0 12px currentColor;
}
.adm2-status[data-tone="approved"] {
    color: rgb(110,231,183);
    background: rgba(16,185,129,0.12);
    border-color: rgba(16,185,129,0.45);
}
.adm2-status[data-tone="rejected"] {
    color: rgb(252,165,165);
    background: rgba(239,68,68,0.12);
    border-color: rgba(239,68,68,0.45);
}
.adm2-status[data-tone="pending"] {
    color: rgb(125,211,252);
    background: rgba(14,165,233,0.12);
    border-color: rgba(14,165,233,0.45);
}

/* =========================================================
   QUICK STATS — 3-UP STRIP
   ========================================================= */
.adm2-stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0,1fr));
    gap: 0;
    padding: 14px 8px;
    border-radius: 1.25rem;
}
.adm2-stats > div {
    padding: 4px 12px;
    text-align: center;
    border-inline-end: 1px solid rgba(255,255,255,0.07);
}
.adm2-stats > div:last-child { border-inline-end: 0; }
.adm2-stats .num {
    font-size: 22px;
    font-weight: 800;
    line-height: 1;
    font-variant-numeric: tabular-nums;
    letter-spacing: -0.01em;
}
.adm2-stats .lbl {
    font-size: 11px;
    color: rgb(156,163,175);
    margin-top: 6px;
}

/* =========================================================
   DELIVERY LIFECYCLE TIMELINE
   ========================================================= */
.adm2-timeline-step {
    position: relative;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}
.adm2-timeline-step:not(:last-child)::after {
    content: "";
    position: absolute;
    top: 30px;
    bottom: -10px;
    inset-inline-start: 17px;
    width: 2px;
    background: rgba(255, 255, 255, 0.1);
}
.adm2-step-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    shrink: 0;
    z-index: 1;
    border: 1px solid rgba(255, 255, 255, 0.15);
    background: #0f172a;
}
.adm2-step-icon.is-done {
    background: rgba(16, 185, 129, 0.2);
    border-color: rgba(16, 185, 129, 0.5);
    color: #6ee7b7;
    box-shadow: 0 0 14px rgba(16, 185, 129, 0.3);
}
.adm2-step-icon.is-active {
    background: rgba(245, 158, 11, 0.2);
    border-color: rgba(245, 158, 11, 0.5);
    color: #fde68a;
    box-shadow: 0 0 14px rgba(245, 158, 11, 0.35);
}
.adm2-step-icon.is-failed {
    background: rgba(239, 68, 68, 0.2);
    border-color: rgba(239, 68, 68, 0.5);
    color: #fca5a5;
    box-shadow: 0 0 14px rgba(239, 68, 68, 0.3);
}
.adm2-step-icon.is-pending {
    background: rgba(255, 255, 255, 0.04);
    border-color: rgba(255, 255, 255, 0.1);
    color: #64748b;
}

/* =========================================================
   TICKET STUB
   ========================================================= */
.adm2-stub {
    position: relative;
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 14px;
    padding: 16px 18px;
    border-radius: 16px;
    background:
        linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.01)),
        rgba(15,23,42,0.55);
    border: 1px solid rgba(255,255,255,0.08);
    overflow: hidden;
    transition: all .2s;
}
.adm2-stub:hover {
    border-color: rgba(252,211,77,0.3);
    box-shadow: 0 12px 30px -12px rgba(0,0,0,0.6);
}
.adm2-stub + .adm2-stub { margin-top: 12px; }

.adm2-stub::before,
.adm2-stub::after {
    content: "";
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 14px;
    height: 14px;
    background: #070a11;
    border-radius: 50%;
    border: 1px solid rgba(255,255,255,0.08);
}
.adm2-stub::before { inset-inline-start: -7px; }
.adm2-stub::after  { inset-inline-end: -7px; }

.adm2-stub .who .name {
    font-size: 15px;
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
    min-width: 72px;
}
.adm2-stub .led .bulb {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin: 0 auto 6px;
}
.adm2-stub .led .bulb[data-tone="ok"] {
    background: rgb(74,222,128);
    box-shadow: 0 0 14px rgba(74,222,128,0.7);
}
.adm2-stub .led .bulb[data-tone="fail"] {
    background: rgb(248,113,113);
    box-shadow: 0 0 12px rgba(248,113,113,0.55);
}
.adm2-stub .led .bulb[data-tone="wait"] {
    background: rgb(56,189,248);
    box-shadow: 0 0 12px rgba(56,189,248,0.55);
}

.adm2-stub-acts {
    grid-column: 1 / -1;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px dashed rgba(255,255,255,0.08);
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}
@media (max-width: 480px) {
    .adm2-stub-acts { grid-template-columns: 1fr; }
}
.adm2-stub-acts > * {
    min-height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    transition: all .15s;
}
.adm2-stub-acts .a-view {
    background: rgba(255,255,255,0.06);
    color: white;
    border: 1px solid rgba(255,255,255,0.1);
}
.adm2-stub-acts .a-view:hover { background: rgba(255,255,255,0.12); }
.adm2-stub-acts .a-resend {
    background: linear-gradient(180deg, rgba(56,189,248,0.18), rgba(14,165,233,0.18));
    color: rgb(125,211,252);
    border: 1px solid rgba(56,189,248,0.35);
}
.adm2-stub-acts .a-resend:hover { background: rgba(56,189,248,0.28); }

/* =========================================================
   POLAROID PROOF CARD
   ========================================================= */
.adm2-polaroid {
    position: relative;
    padding: 12px 12px 16px;
    background: #f8fafc;
    border-radius: 6px;
    box-shadow:
        0 1px 0 rgba(0,0,0,0.4),
        0 22px 50px -20px rgba(0,0,0,0.7);
    transform: rotate(-1deg);
    transition: transform .3s ease;
    max-width: 100%;
}
.adm2-polaroid:hover {
    transform: rotate(0) translateY(-2px);
}
.adm2-polaroid img {
    width: 100%;
    display: block;
    border-radius: 3px;
    background: #e2e8f0;
}
.adm2-polaroid .cap {
    margin-top: 10px;
    text-align: center;
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    font-size: 11px;
    letter-spacing: 0.04em;
    color: rgb(71,85,105);
}
.adm2-polaroid::before {
    content: "";
    position: absolute;
    top: -10px;
    inset-inline-start: 50%;
    transform: translateX(-50%) rotate(-2deg);
    width: 70px;
    height: 18px;
    background: rgba(252,211,77,0.55);
    border: 1px solid rgba(252,211,77,0.7);
    box-shadow: 0 2px 6px rgba(0,0,0,0.25);
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
    max-width: 40rem;
    margin: 0 auto;
    display: flex;
    gap: 10px;
    padding: 10px;
    background: rgba(7, 10, 17, 0.85);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 999px;
    backdrop-filter: blur(20px) saturate(160%);
    -webkit-backdrop-filter: blur(20px) saturate(160%);
    box-shadow: 0 30px 60px -20px rgba(0,0,0,0.8);
}
.adm2-dock-btn {
    flex: 1;
    min-height: 50px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border-radius: 999px;
    font-size: 14px;
    font-weight: 700;
    letter-spacing: 0.02em;
    transition: transform .15s, box-shadow .2s;
    border: 1px solid transparent;
}
.adm2-dock-btn:active:not([disabled]) { transform: scale(.98); }
.adm2-dock-approve {
    background: linear-gradient(180deg, #fde68a, #f59e0b 70%, #b45309);
    color: rgb(20,20,20);
    box-shadow: 0 10px 28px -8px rgba(245,158,11,0.6);
}
.adm2-dock-approve:hover { box-shadow: 0 14px 32px -8px rgba(245,158,11,0.75); }
.adm2-dock-reject {
    background: rgba(239,68,68,0.15);
    color: rgb(252,165,165);
    border-color: rgba(239,68,68,0.4);
}
.adm2-dock-resend {
    background: linear-gradient(180deg, rgba(56,189,248,0.22), rgba(14,165,233,0.22));
    color: rgb(125,211,252);
    border-color: rgba(56,189,248,0.45);
}
.adm2-dock-delete {
    background: rgba(220,38,38,0.15);
    color: rgb(252,165,165);
    border-color: rgba(220,38,38,0.45);
}
.adm2-dock-confirm {
    background: rgb(220,38,38);
    color: white;
}
.adm2-dock-cancel {
    flex: 0 0 auto;
    min-width: 90px;
    background: rgba(255,255,255,0.08);
    color: rgb(229,231,235);
    border-color: rgba(255,255,255,0.15);
}

/* Two-step confirm states */
[data-confirm] .adm2-confirm-armed { display: none; }
[data-confirm][data-armed="true"] .adm2-confirm-trigger { display: none; }
[data-confirm][data-armed="true"] .adm2-confirm-armed {
    display: flex;
    gap: 10px;
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
    width: min(400px, 100%);
    background: #0f172a;
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 1.5rem;
    padding: 28px 24px;
    text-align: center;
    box-shadow: 0 30px 80px rgba(0,0,0,0.8);
}
.adm2-processing .ring {
    width: 52px;
    height: 52px;
    margin: 0 auto 16px;
    border-radius: 50%;
    border: 3px solid rgba(245,158,11,0.2);
    border-top-color: rgb(252,211,77);
    animation: admSpin .8s linear infinite;
}
</style>

<div class="adm2-aurora" aria-hidden="true"></div>

@php
    $show       = $booking->showTime?->show;
    $showTime   = $booking->showTime;
    $posterCss  = $show?->poster_path ? "url('" . e($show->poster_path) . "')" : null;
    $sentCount  = $booking->tickets->where('whatsapp_sent', true)->count();
    $totalCount = $booking->tickets->count();
    $failedCount = $booking->tickets->where('delivery_status', 'failed')->count();
    $claimedCount = $booking->tickets->filter(fn($t) => $t->claimed_at)->count();
@endphp

<section class="adm2-stage space-y-6 max-w-4xl mx-auto">

    {{-- Top Navigation Back Affordance --}}
    <div class="flex items-center justify-between gap-3">
        @include('admin.partials.back-button', ['url' => route('admin.bookings.index'), 'label' => 'العودة لقائمة الحجوزات'])

        <div class="flex items-center gap-2">
            <span class="text-xs text-slate-400 font-mono" dir="ltr">Ref: {{ $booking->reference_code }}</span>
        </div>
    </div>

    {{-- ------------------------------------------------------
         🎭 HERO MARQUEE CARD
         ------------------------------------------------------ --}}
    <div class="adm2-hero" @if($posterCss) style="--adm2-poster: {{ $posterCss }};" @endif>
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="text-[11px] font-bold tracking-widest uppercase text-amber-300/80">
                حجز رقم &bull; BOOKING #{{ $booking->public_number ?? $booking->id }}
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

        <div class="adm2-bk-num tabular-nums mt-1">#{{ $booking->public_number ?? $booking->id }}</div>

        <div class="mt-3 text-sm text-slate-200 flex items-center gap-2 flex-wrap">
            <span class="text-amber-300">🎭</span>
            <span class="font-bold text-white text-base">{{ $show?->title ?? '—' }}</span>
            @if($showTime)
                <span class="text-slate-500">·</span>
                <span class="text-slate-300 tabular-nums">
                    🗓️ {{ $showTime->date?->format('d/m/Y') }}
                    • {{ $showTime->time ? \Carbon\Carbon::parse($showTime->time)->format('g:i A') : '' }}
                </span>
            @endif
        </div>

        {{-- Booker Info Bar --}}
        <div class="mt-5 pt-4 border-t border-white/10 flex items-center justify-between gap-3 flex-wrap">
            <div class="min-w-0">
                <p class="text-[10px] uppercase tracking-wider text-slate-400">حاجز التذاكر</p>
                <p class="text-base font-bold text-white truncate mt-0.5">{{ $booking->full_name }}</p>
                <p class="text-xs text-slate-300 font-mono mt-0.5" dir="ltr">{{ $booking->phone }}</p>
            </div>

            @if($booking->phone)
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $booking->phone) }}"
                   target="_blank" rel="noopener"
                   class="inline-flex items-center gap-1.5 text-xs font-semibold px-4 py-2.5 rounded-xl
                          bg-emerald-500/15 hover:bg-emerald-500/25 border border-emerald-500/40
                          text-emerald-200 transition min-h-[44px]">
                    <span>💬</span>
                    <span>مراسلة واتساب مباشرة</span>
                </a>
            @endif
        </div>
    </div>

    {{-- ------------------------------------------------------
         QUICK STATS (3-UP STRIP)
         ------------------------------------------------------ --}}
    <div class="adm2-glass-soft adm2-stats">
        <div>
            <div class="num text-white">{{ $booking->tickets_count }}</div>
            <div class="lbl">عدد التذاكر</div>
        </div>
        <div>
            <div class="num text-amber-300">
                {{ number_format((int) $booking->total_price) }}
                <span class="text-xs font-normal text-amber-300/80">ج.م</span>
            </div>
            <div class="lbl">إجمالي المبلغ</div>
        </div>
        <div>
            <div class="num {{ $totalCount && $sentCount === $totalCount ? 'text-emerald-300' : 'text-slate-200' }}">
                {{ $sentCount }}<span class="text-slate-500 text-sm">/{{ $totalCount }}</span>
            </div>
            <div class="lbl">تم استلامها واتساب</div>
        </div>
    </div>

    {{-- ------------------------------------------------------
         🌟 VISUAL DELIVERY LIFECYCLE TIMELINE
         ------------------------------------------------------ --}}
    <div class="adm2-glass p-5 sm:p-6 space-y-4">
        <div class="flex items-center justify-between gap-2 border-b border-white/10 pb-3">
            <h2 class="text-sm sm:text-base font-bold font-heading text-amber-300 flex items-center gap-2">
                <span>🔄</span>
                <span>مسار ومراحل دورة الحجز والتسليم</span>
            </h2>
            <span class="text-xs text-slate-400">تتبع حي للمراحل</span>
        </div>

        <div class="space-y-4 pt-1">

            {{-- 1. Creation --}}
            <div class="adm2-timeline-step">
                <div class="adm2-step-icon is-done">📝</div>
                <div class="min-w-0 flex-1">
                    <p class="font-bold text-sm text-white">تسجيل طلب الحجز</p>
                    <p class="text-xs text-slate-400 mt-0.5">
                        تم إنشاء الحجز بنجاح عبر الموقع في:
                        <span class="text-slate-300 font-mono">{{ $booking->created_at ? $booking->created_at->format('Y-m-d • h:i A') : '-' }}</span>
                    </p>
                </div>
            </div>

            {{-- 2. Payment Proof --}}
            <div class="adm2-timeline-step">
                <div class="adm2-step-icon {{ $booking->transfer_screenshot_path ? 'is-done' : 'is-active' }}">💳</div>
                <div class="min-w-0 flex-1">
                    <p class="font-bold text-sm text-white">تحويل المبلغ وإرفاق الإثبات</p>
                    <p class="text-xs text-slate-400 mt-0.5">
                        طريقة الدفع: <span class="text-slate-200 font-medium">{{ $booking->payment_method ?? 'محفظة / إنستاباي' }}</span>
                        • القيمة: <span class="text-amber-300 font-bold tabular-nums">{{ number_format((int) $booking->total_price) }} ج.م</span>
                    </p>
                    @if($booking->transfer_screenshot_path)
                        <span class="inline-block mt-1 text-[11px] text-emerald-300 font-medium">✓ تم إرفاق صورة إثبات التحويل</span>
                    @else
                        <span class="inline-block mt-1 text-[11px] text-amber-400 font-medium">⚠️ لم يتم إرفاق صورة إثبات التحويل</span>
                    @endif
                </div>
            </div>

            {{-- 3. Admin Approval --}}
            <div class="adm2-timeline-step">
                @if($booking->status === 'approved')
                    <div class="adm2-step-icon is-done">⚖️</div>
                    <div class="min-w-0 flex-1">
                        <p class="font-bold text-sm text-white">اعتماد الإدارة</p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            تم اعتماد الحجز رسميًا في:
                            <span class="text-emerald-300 font-mono">{{ $booking->approved_at ? $booking->approved_at->format('Y-m-d • h:i A') : 'نعم' }}</span>
                        </p>
                    </div>
                @elseif($booking->status === 'rejected')
                    <div class="adm2-step-icon is-failed">✖</div>
                    <div class="min-w-0 flex-1">
                        <p class="font-bold text-sm text-rose-300">تم رفض الحجز</p>
                        <p class="text-xs text-slate-400 mt-0.5">تم رفض هذا الحجز من قبل الإدارة ولم يتم إصدار التذاكر.</p>
                    </div>
                @else
                    <div class="adm2-step-icon is-active">⏳</div>
                    <div class="min-w-0 flex-1">
                        <p class="font-bold text-sm text-sky-300">بانتظار مراجعة الإدارة والاعتماد</p>
                        <p class="text-xs text-slate-400 mt-0.5">يرجى مطابقة التحويل مع المبلغ المطلوب ثم الضغط على زر "اعتماد الحجز".</p>
                    </div>
                @endif
            </div>

            {{-- 4. WhatsApp Template Delivery --}}
            <div class="adm2-timeline-step">
                @if($booking->template_status === 'sent')
                    <div class="adm2-step-icon is-done">📩</div>
                    <div class="min-w-0 flex-1">
                        <p class="font-bold text-sm text-white">إرسال قالب الاستلام الرسمي عبر واتساب (ticket_clean_final)</p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            أُرسل للعميل بنجاح في:
                            <span class="text-emerald-300 font-mono">{{ $booking->template_sent_at ? $booking->template_sent_at->format('Y-m-d • h:i A') : 'نعم' }}</span>
                        </p>
                    </div>
                @elseif($booking->template_status === 'sending')
                    <div class="adm2-step-icon is-active">🔄</div>
                    <div class="min-w-0 flex-1">
                        <p class="font-bold text-sm text-amber-300">جاري إرسال قالب الاستلام عبر خوادم واتساب…</p>
                        <p class="text-xs text-slate-400 mt-0.5">الطلب قيد المعالجة في قائمة الانتظار السريعة.</p>
                    </div>
                @elseif($booking->template_status === 'failed')
                    <div class="adm2-step-icon is-failed">⚠️</div>
                    <div class="min-w-0 flex-1">
                        <p class="font-bold text-sm text-rose-300">تعذر إرسال قالب الواتساب</p>
                        <p class="text-xs text-rose-300/80 mt-0.5">{{ $booking->template_error }}</p>
                    </div>
                @else
                    <div class="adm2-step-icon is-pending">📩</div>
                    <div class="min-w-0 flex-1">
                        <p class="font-bold text-sm text-slate-400">قالب الاستلام عبر واتساب</p>
                        <p class="text-xs text-slate-500 mt-0.5">يُرسل تلقائيًا بمجرد اعتماد الحجز.</p>
                    </div>
                @endif
            </div>

            {{-- 5. Customer Claim Tap --}}
            <div class="adm2-timeline-step">
                @if($claimedCount > 0)
                    <div class="adm2-step-icon is-done">👆</div>
                    <div class="min-w-0 flex-1">
                        <p class="font-bold text-sm text-white">نقر العميل على "أستلام التذكرة"</p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            قام العميل بالنقر على زر الاستلام وتم استقبال الإشارة عبر الـ Webhook.
                        </p>
                    </div>
                @elseif($booking->status === 'approved')
                    <div class="adm2-step-icon is-active">💬</div>
                    <div class="min-w-0 flex-1">
                        <p class="font-bold text-sm text-sky-300">بانتظار نقر العميل على "أستلام التذكرة" في واتساب</p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            تصل التذاكر المصورة فور نقر العميل على زر القالب لتجاوز نافذة الـ 24 ساعة الرسمية لـ Meta.
                        </p>
                    </div>
                @else
                    <div class="adm2-step-icon is-pending">👆</div>
                    <div class="min-w-0 flex-1">
                        <p class="font-bold text-sm text-slate-400">نقر العميل على الاستلام</p>
                        <p class="text-xs text-slate-500 mt-0.5">تلقائي بعد استلام العميل للرسالة.</p>
                    </div>
                @endif
            </div>

            {{-- 6. Ticket Image Delivery --}}
            <div class="adm2-timeline-step">
                @if($sentCount === $totalCount && $totalCount > 0)
                    <div class="adm2-step-icon is-done">🎟️</div>
                    <div class="min-w-0 flex-1">
                        <p class="font-bold text-sm text-white">تسليم التذاكر المصورة (QR Code)</p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            تم تسليم جميع التذاكر بالكامل ({{ $sentCount }}/{{ $totalCount }}) على هاتف العميل عبر واتساب.
                        </p>
                    </div>
                @elseif($failedCount > 0)
                    <div class="adm2-step-icon is-failed">⚠️</div>
                    <div class="min-w-0 flex-1">
                        <p class="font-bold text-sm text-rose-300">تعذر تسليم بعض التذاكر ({{ $sentCount }}/{{ $totalCount }})</p>
                        <p class="text-xs text-slate-400 mt-0.5">يمكنك الضغط على "إعادة إرسال" لإعادة المحاولة فورًا.</p>
                    </div>
                @else
                    <div class="adm2-step-icon is-pending">🎟️</div>
                    <div class="min-w-0 flex-1">
                        <p class="font-bold text-sm text-slate-400">تسليم صور التذاكر ({{ $sentCount }}/{{ $totalCount }})</p>
                        <p class="text-xs text-slate-500 mt-0.5">يتم إرسال الصور بدقة عالية ومجهزة للمسح على الباب.</p>
                    </div>
                @endif
            </div>

        </div>
    </div>

    {{-- ------------------------------------------------------
         🎟️ TICKET STUBS WITH ACTIONS & STATUS
         ------------------------------------------------------ --}}
    <div class="adm2-glass p-5 sm:p-6 space-y-4">
        <div class="flex items-center justify-between gap-3 border-b border-white/10 pb-3 flex-wrap">
            <div>
                <h2 class="text-sm sm:text-base font-bold font-heading text-amber-300 flex items-center gap-2">
                    <span>🎟️</span>
                    <span>تذاكر الحضور ({{ $totalCount }})</span>
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">التذاكر الصادرة وأكواد التحقق الرقمية</p>
            </div>

            @if($booking->status === 'approved' && $totalCount > 1)
                <form action="{{ route('admin.bookings.resend-all', $booking->id) }}"
                      method="POST"
                      data-processing-message="جاري إعادة إرسال كافة التذاكر عبر واتساب…">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold px-3.5 py-2 rounded-xl
                                   bg-sky-500/15 hover:bg-sky-500/25 border border-sky-500/40 text-sky-200 transition min-h-[44px]">
                        <span>↪</span>
                        <span>إعادة إرسال كل التذاكر دفعة واحدة</span>
                    </button>
                </form>
            @endif
        </div>

        <div class="space-y-3">
            @forelse($booking->tickets as $ticket)
                @php
                    $isTicketDelivered = $ticket->whatsapp_sent || $ticket->delivery_status === 'sent';
                    $isTicketFailed    = $ticket->delivery_status === 'failed';
                @endphp
                <div class="adm2-stub">
                    <div class="who">
                        <div class="flex items-center gap-2">
                            <span class="name">{{ $ticket->name }}</span>
                            @if($ticket->is_scanned)
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 font-bold">
                                    ✓ تم الدخول
                                </span>
                            @endif
                        </div>
                        <p class="phone" dir="ltr">{{ $ticket->phone }}</p>
                        @if($ticket->ticket_code)
                            <span class="code" dir="ltr">{{ $ticket->ticket_code }}</span>
                        @endif

                        @if($ticket->delivery_error)
                            <div class="mt-2.5 p-2 rounded-lg bg-rose-500/10 border border-rose-500/30 text-rose-200 text-xs">
                                ⚠️ <strong>ملاحظة واتساب:</strong> {{ $ticket->delivery_error }}
                            </div>
                        @endif
                    </div>

                    <div class="led">
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

                    @if($booking->status === 'approved')
                        <div class="adm2-stub-acts">
                            @if($ticket->qr_image_path)
                                <a href="{{ $ticket->qr_image_path }}"
                                   target="_blank" rel="noopener"
                                   class="a-view">
                                    <span>🎫</span>
                                    <span>معاينة صورة التذكرة</span>
                                </a>
                            @else
                                <span class="a-view opacity-50 cursor-not-allowed">
                                    <span>⏳</span>
                                    <span>جاري إنشاء التذكرة…</span>
                                </span>
                            @endif

                            <form action="{{ route('admin.resend.ticket', $ticket->id) }}"
                                  method="POST"
                                  class="contents"
                                  data-processing-message="جاري إعادة إرسال التذكرة للرقم عبر واتساب…">
                                @csrf
                                <button type="submit" class="a-resend">
                                    <span>↪</span>
                                    <span>إعادة إرسال التذكرة</span>
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-center py-8 text-xs text-slate-400">
                    لا توجد تذاكر مسجلة لهذا الحجز.
                </div>
            @endforelse
        </div>
    </div>

    {{-- ------------------------------------------------------
         📸 PAYMENT PROOF (POLAROID CARD)
         ------------------------------------------------------ --}}
    @if($booking->transfer_screenshot_path)
        <div class="adm2-glass p-5 sm:p-6 space-y-4">
            <div class="flex items-center justify-between gap-3 border-b border-white/10 pb-3">
                <h2 class="text-sm sm:text-base font-bold font-heading text-amber-300 flex items-center gap-2">
                    <span>📸</span>
                    <span>صورة إثبات التحويل البنكي / المحفظة</span>
                </h2>
                <a href="{{ $booking->transfer_screenshot_path }}"
                   target="_blank" rel="noopener"
                   class="text-xs px-3 py-1.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-slate-200 transition">
                    فتح بالحجم الكامل ↗
                </a>
            </div>

            <div class="py-4">
                <a href="{{ $booking->transfer_screenshot_path }}"
                   target="_blank" rel="noopener"
                   class="block mx-auto max-w-sm">
                    <div class="adm2-polaroid">
                        <img src="{{ $booking->transfer_screenshot_path }}"
                             loading="lazy" decoding="async"
                             alt="لقطة شاشة تحويل الحجز">
                        <p class="cap">TRANSFER PROOF &bull; #{{ $booking->public_number ?? $booking->id }} &bull; {{ number_format((int) $booking->total_price) }} EGP</p>
                    </div>
                </a>
            </div>
        </div>
    @endif

</section>

{{-- ------------------------------------------------------
     FLOATING ACTION DOCK
     ------------------------------------------------------ --}}
<div class="adm2-dock" role="region" aria-label="شريط الإجراءات الرئيسي للحجز">
    <div class="adm2-dock-inner">
        @if($booking->status === 'pending')
            {{-- Reject Form --}}
            <form action="{{ route('admin.bookings.reject', $booking) }}" method="POST"
                  class="flex-1"
                  data-processing-message="جاري رفض الحجز وتحديث حالة المقاعد…">
                @csrf
                <button type="submit" class="adm2-dock-btn adm2-dock-reject w-full">
                    <span>✖</span>
                    <span>رفض الحجز</span>
                </button>
            </form>

            {{-- Approve Form --}}
            <form action="{{ route('admin.bookings.approve', $booking) }}" method="POST"
                  class="flex-1"
                  data-processing-message="جاري اعتماد الحجز وإنشاء التذاكر وإرسال واتساب…">
                @csrf
                <button type="submit" class="adm2-dock-btn adm2-dock-approve w-full">
                    <span>✔</span>
                    <span>اعتماد وإرسال التذاكر</span>
                </button>
            </form>

        @elseif($booking->status === 'approved')
            {{-- Resend all tickets button --}}
            <form action="{{ route('admin.bookings.resend-all', $booking->id) }}"
                  method="POST"
                  class="flex-1"
                  data-processing-message="جاري فحص حالة واتساب وإعادة إرسال التذاكر…">
                @csrf
                <button type="submit" class="adm2-dock-btn adm2-dock-resend w-full">
                    <span>↪</span>
                    <span>إعادة إرسال التذاكر</span>
                </button>
            </form>

            {{-- Two-step Delete button --}}
            <div class="flex-1" data-confirm>
                <button type="button" class="adm2-dock-btn adm2-dock-delete adm2-confirm-trigger w-full">
                    <span>🗑️</span>
                    <span>حذف الحجز</span>
                </button>

                <div class="adm2-confirm-armed">
                    <button type="button" class="adm2-dock-btn adm2-dock-cancel adm2-confirm-cancel">
                        إلغاء
                    </button>
                    <form action="{{ route('admin.booking.delete', $booking->id) }}" method="POST"
                          class="flex-1"
                          data-processing-message="جاري حذف الحجز نهائياً…">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="adm2-dock-btn adm2-dock-confirm w-full">
                            تأكيد الحذف
                        </button>
                    </form>
                </div>
            </div>

        @elseif($booking->status === 'rejected')
            {{-- Two-step Delete button for rejected bookings --}}
            <div class="w-full" data-confirm>
                <button type="button" class="adm2-dock-btn adm2-dock-delete adm2-confirm-trigger w-full">
                    <span>🗑️</span>
                    <span>حذف الحجز المرفوض</span>
                </button>

                <div class="adm2-confirm-armed">
                    <button type="button" class="adm2-dock-btn adm2-dock-cancel adm2-confirm-cancel">
                        إلغاء
                    </button>
                    <form action="{{ route('admin.booking.delete', $booking->id) }}" method="POST"
                          class="flex-1"
                          data-processing-message="جاري حذف الحجز…">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="adm2-dock-btn adm2-dock-confirm w-full">
                            تأكيد الحذف نهائياً
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>

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
