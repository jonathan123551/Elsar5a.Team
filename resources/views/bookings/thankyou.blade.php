@extends('layouts.app')

@section('title', 'تم إرسال طلب الحجز')
@section('body_class', 'cg-body-locked')
@section('main_class', 'cg-main-viewport')

@section('content')
<style>
/* ============================================================
   ELSAR5A CINEMATIC CONFIRMATION — REFINED APPLE DARK GLASS
   True Visual Centering • Layered Depth • Single-Screen Experience
   ============================================================ */

/* 1. VIEWPORT & NO-SCROLL ARCHITECTURE */
footer[role="contentinfo"],
#sticky-action-footer {
    display: none !important;
}

body.cg-body-locked {
    overflow: hidden !important;
    height: 100vh !important;
    height: 100dvh !important;
    touch-action: manipulation;
}

/* Container precisely calculates available viewport below fixed site header */
.cg-main-viewport {
    padding: 0 !important;
    margin: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
    height: calc(100vh - 58px) !important;
    height: calc(100dvh - 58px) !important;
    max-height: calc(100vh - 58px) !important;
    max-height: calc(100dvh - 58px) !important;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    overflow: hidden !important;
    box-sizing: border-box;
    padding-bottom: max(16px, env(safe-area-inset-bottom, 0px)) !important;
}

/* 2. MAIN STAGE WRAPPER (BALANCED NEGATIVE SPACE) */
.cg-stage {
    width: 100%;
    max-width: 440px;
    padding: 0 16px;
    box-sizing: border-box;
    display: flex;
    justify-content: center;
}

@media (min-width: 768px) {
    .cg-stage {
        max-width: 490px;
        padding: 0 24px;
    }
}

/* 3. PRIMARY APPLE DARK GLASS SURFACE */
.cg-card {
    position: relative;
    width: 100%;
    /* Translucent, luminous frosted dark glass allowing ambient stage glow to breathe */
    background: linear-gradient(145deg, rgba(255, 255, 255, 0.07) 0%, rgba(255, 255, 255, 0.02) 100%), rgba(22, 10, 16, 0.40);
    backdrop-filter: blur(32px) saturate(190%) contrast(104%);
    -webkit-backdrop-filter: blur(32px) saturate(190%) contrast(104%);
    border: 1px solid rgba(255, 255, 255, 0.14);
    border-radius: 26px;
    padding: clamp(20px, 3vh, 28px) clamp(18px, 4.2vw, 28px);
    box-shadow:
        0 24px 60px -12px rgba(0, 0, 0, 0.82),
        0 0 35px -8px rgba(245, 158, 11, 0.08),
        inset 0 1px 0 rgba(255, 255, 255, 0.32),
        inset 0 -1px 0 rgba(0, 0, 0, 0.22);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    gap: clamp(12px, 2vh, 18px);
    animation: cgCardReveal 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
}

@media (min-width: 640px) {
    .cg-card {
        border-radius: 30px;
        padding: 30px 32px;
        gap: 20px;
    }
}

/* Ambient top specular rim */
.cg-card::before {
    content: "";
    position: absolute;
    top: 0;
    inset-inline: 14%;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.45) 25%, rgba(253, 230, 138, 0.7) 50%, rgba(255, 255, 255, 0.45) 75%, transparent);
    pointer-events: none;
}

/* 4. SUCCESS HEADER & REFINED EMBLEM */
.cg-hero {
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.cg-emblem {
    position: relative;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: radial-gradient(circle at 35% 30%, rgba(52, 211, 153, 0.25) 0%, rgba(16, 185, 129, 0.06) 65%, rgba(10, 4, 7, 0.4) 100%);
    border: 1px solid rgba(52, 211, 153, 0.45);
    box-shadow:
        0 6px 18px -3px rgba(0, 0, 0, 0.55),
        0 0 18px -2px rgba(16, 185, 129, 0.28),
        inset 0 1px 0 rgba(255, 255, 255, 0.38);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #34d399;
    margin-bottom: 9px;
    animation: cgBadgePop 0.45s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.cg-check-svg {
    width: 22px;
    height: 22px;
    stroke-dasharray: 40;
    stroke-dashoffset: 40;
    animation: cgCheckDraw 0.42s 0.18s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.cg-title {
    font-family: 'Alexandria', -apple-system, BlinkMacSystemFont, "Readex Pro", sans-serif;
    font-size: clamp(1.18rem, 3.6vw, 1.42rem);
    font-weight: 800;
    line-height: 1.25;
    letter-spacing: -0.015em;
    color: #fce7b0;
    text-shadow: 0 2px 14px rgba(245, 158, 11, 0.22);
    margin: 0;
    animation: cgFadeUp 0.4s 0.1s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.cg-greeting {
    font-size: clamp(0.82rem, 2.3vw, 0.90rem);
    color: #c4bcc1;
    margin-top: 4px;
    margin-bottom: 0;
    font-weight: 400;
    animation: cgFadeUp 0.4s 0.16s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.cg-customer-name {
    color: #ffffff;
    font-weight: 600;
}

/* 5. SECONDARY GLASS LAYER: BOOKING SUMMARY (CLEAN FULL-WIDTH ROWS, ZERO TRUNCATION) */
.cg-summary-shelf {
    background: rgba(255, 255, 255, 0.038);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    padding: 10px 14px;
    box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.06),
        0 4px 14px -3px rgba(0, 0, 0, 0.3);
    display: flex;
    flex-direction: column;
    gap: 8px;
    animation: cgFadeUp 0.42s 0.2s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.cg-summary-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.cg-summary-row + .cg-summary-row {
    border-top: 1px solid rgba(255, 255, 255, 0.06);
    padding-top: 8px;
}

.cg-sum-label {
    font-size: 0.74rem;
    color: #a89ba2;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 5px;
    flex-shrink: 0;
}

.cg-sum-val-wrap {
    display: flex;
    align-items: center;
    gap: 7px;
    justify-content: flex-end;
}

.cg-ref-code {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: clamp(0.82rem, 2.7vw, 0.94rem);
    font-weight: 700;
    color: #fbbf24;
    letter-spacing: 0.03em;
    white-space: nowrap;
    user-select: all;
}

.cg-copy-btn {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.07);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: #d1d5db;
    cursor: pointer;
    flex-shrink: 0;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    -webkit-tap-highlight-color: transparent;
    padding: 0;
}

.cg-copy-btn:hover {
    background: rgba(245, 158, 11, 0.18);
    border-color: rgba(245, 158, 11, 0.5);
    color: #fde68a;
}

.cg-copy-btn:active {
    transform: scale(0.92);
}

.cg-copy-btn.is-copied {
    background: rgba(16, 185, 129, 0.25);
    border-color: rgba(16, 185, 129, 0.6);
    color: #34d399;
}

.cg-amount-val {
    font-size: clamp(0.92rem, 2.9vw, 1.04rem);
    font-weight: 700;
    color: #34d399;
    letter-spacing: -0.01em;
    white-space: nowrap;
}

/* 6. NEXT STEP STATUS TIMELINE (INTEGRATED, NON-BOXED FLOW) */
.cg-timeline-flow {
    display: flex;
    flex-direction: column;
    gap: 9px;
    padding: 2px 4px;
    animation: cgFadeUp 0.42s 0.26s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.cg-tl-header {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.82rem;
    font-weight: 700;
    color: #fde68a;
    padding-bottom: 6px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.07);
}

.cg-tl-list {
    position: relative;
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 9px;
}

/* Connecting hairline */
.cg-tl-list::before {
    content: "";
    position: absolute;
    top: 9px;
    bottom: 9px;
    right: 5px;
    width: 2px;
    background: linear-gradient(180deg,
        rgba(245, 158, 11, 0.55) 0%,
        rgba(16, 185, 129, 0.55) 50%,
        rgba(56, 189, 248, 0.45) 100%
    );
    border-radius: 2px;
}

.cg-tl-item {
    position: relative;
    display: flex;
    align-items: flex-start;
    gap: 10px;
}

.cg-tl-node {
    width: 12px;
    height: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    margin-top: 3px;
    z-index: 2;
}

.cg-dot {
    width: 7.5px;
    height: 7.5px;
    border-radius: 50%;
}

.dot-amber {
    background: #f59e0b;
    box-shadow: 0 0 8px 1.5px rgba(245, 158, 11, 0.8);
}

.dot-emerald {
    background: #10b981;
    box-shadow: 0 0 8px 1.5px rgba(16, 185, 129, 0.8);
}

.dot-sky {
    background: #38bdf8;
    box-shadow: 0 0 8px 1.5px rgba(56, 189, 248, 0.8);
}

.cg-tl-body {
    flex: 1;
    font-size: clamp(0.75rem, 2.2vw, 0.82rem);
    line-height: 1.45;
    color: #e5e1e4;
    text-align: right;
}

.cg-tl-body strong {
    color: #ffffff;
    font-weight: 600;
}

.cg-tl-body .accent-emerald {
    color: #34d399;
    font-weight: 600;
}

/* 7. LIGHTWEIGHT SUPPORT ROW (INLINE, NON-BOXED) */
.cg-support-row {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    text-align: center;
    padding: 2px 4px;
    animation: cgFadeUp 0.42s 0.32s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.cg-support-icon {
    width: 14px;
    height: 14px;
    color: #fbbf24;
    flex-shrink: 0;
}

.cg-support-text {
    font-size: clamp(0.72rem, 2.05vw, 0.78rem);
    line-height: 1.35;
    color: #b5abb1;
    margin: 0;
}

.cg-support-text strong {
    color: #f1ecf0;
    font-weight: 500;
}

/* 8. REFINED APPLE-STYLE CTA PILL (BALANCED PROPORTIONS) */
.cg-btn-home {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    width: 100%;
    min-height: 42px;
    padding: 9px 22px;
    border-radius: 9999px;
    background: linear-gradient(180deg, rgba(245, 158, 11, 0.16) 0%, rgba(217, 119, 6, 0.08) 100%);
    border: 1px solid rgba(245, 158, 11, 0.38);
    color: #fef3c7;
    font-family: 'Alexandria', -apple-system, BlinkMacSystemFont, "Readex Pro", sans-serif;
    font-size: 0.88rem;
    font-weight: 700;
    letter-spacing: -0.01em;
    text-decoration: none;
    box-shadow:
        0 6px 18px -4px rgba(0, 0, 0, 0.45),
        0 0 16px -3px rgba(245, 158, 11, 0.18),
        inset 0 1px 0 rgba(255, 255, 255, 0.20);
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    -webkit-tap-highlight-color: transparent;
    animation: cgFadeUp 0.42s 0.38s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.cg-btn-home:hover {
    background: linear-gradient(180deg, rgba(245, 158, 11, 0.26) 0%, rgba(217, 119, 6, 0.14) 100%);
    border-color: rgba(245, 158, 11, 0.70);
    color: #ffffff;
    box-shadow:
        0 10px 24px -4px rgba(0, 0, 0, 0.55),
        0 0 22px -2px rgba(245, 158, 11, 0.30),
        inset 0 1px 0 rgba(255, 255, 255, 0.30);
    transform: translateY(-1px);
}

.cg-btn-home:active {
    transform: translateY(1px);
    background: rgba(245, 158, 11, 0.14);
    box-shadow: 0 3px 8px -2px rgba(0, 0, 0, 0.4);
}

.cg-btn-home:focus-visible {
    outline: 2px solid #fbbf24;
    outline-offset: 3px;
}

.cg-btn-chevron {
    width: 15px;
    height: 15px;
    transition: transform 0.2s ease;
}

.cg-btn-home:hover .cg-btn-chevron {
    transform: translateX(-3px);
}

/* 9. MOTION KEYFRAMES */
@keyframes cgCardReveal {
    from {
        opacity: 0;
        transform: translateY(10px) scale(0.985);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes cgBadgePop {
    from {
        opacity: 0;
        transform: scale(0.88);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

@keyframes cgCheckDraw {
    to {
        stroke-dashoffset: 0;
    }
}

@keyframes cgFadeUp {
    from {
        opacity: 0;
        transform: translateY(6px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* 10. ACCESSIBILITY: PREFERS-REDUCED-MOTION */
@media (prefers-reduced-motion: reduce) {
    .cg-card,
    .cg-emblem,
    .cg-check-svg,
    .cg-title,
    .cg-greeting,
    .cg-summary-shelf,
    .cg-timeline-flow,
    .cg-support-row,
    .cg-btn-home,
    .cg-btn-chevron {
        animation: none !important;
        transition: none !important;
        transform: none !important;
        stroke-dashoffset: 0 !important;
        opacity: 1 !important;
    }
}
</style>

<div class="cg-stage">
    <div class="cg-card" role="region" aria-labelledby="cg-heading">

        {{-- 1. Success State Header --}}
        <div class="cg-hero">
            <div class="cg-emblem" aria-hidden="true">
                <svg class="cg-check-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>

            <h1 id="cg-heading" class="cg-title">
                تم إرسال طلب الحجز بنجاح
            </h1>

            <p class="cg-greeting">
                شكراً يا <span class="cg-customer-name">{{ $booking->full_name }}</span>
            </p>
        </div>

        {{-- 2. Secondary Glass Layer: Booking Summary Shelf (Full width rows - Zero Truncation) --}}
        <div class="cg-summary-shelf" role="region" aria-label="بيانات الحجز">
            <div class="cg-summary-row">
                <span class="cg-sum-label">
                    <svg class="w-3.5 h-3.5 text-amber-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect width="14" height="18" x="5" y="3" rx="2"></rect>
                        <line x1="9" x2="15" y1="7" y2="7"></line>
                        <line x1="9" x2="15" y1="11" y2="11"></line>
                        <line x1="9" x2="13" y1="15" y2="15"></line>
                    </svg>
                    <span>رقم الحجز</span>
                </span>
                <div class="cg-sum-val-wrap">
                    <span id="bookingRefCode" class="cg-ref-code" title="{{ $booking->reference_code }}">{{ $booking->reference_code }}</span>
                    <button type="button"
                            id="btnCopyRef"
                            class="cg-copy-btn"
                            onclick="copyBookingRef('{{ $booking->reference_code }}')"
                            aria-label="نسخ رقم الحجز"
                            title="نسخ رقم الحجز">
                        <svg id="copyIcon" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect width="14" height="14" x="8" y="8" rx="2" ry="2"></rect>
                            <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="cg-summary-row">
                <span class="cg-sum-label">
                    <svg class="w-3.5 h-3.5 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"></path>
                        <path d="M12 18V6"></path>
                    </svg>
                    <span>إجمالي المبلغ</span>
                </span>
                <div class="cg-sum-val-wrap">
                    <span class="cg-amount-val">
                        {{ is_numeric($booking->total_price) ? number_format((float)$booking->total_price, 2) : $booking->total_price }} جنيه
                    </span>
                </div>
            </div>
        </div>

        {{-- 3. Next Step Timeline (Seamless flow within card) --}}
        <div class="cg-timeline-flow" role="region" aria-label="الخطوة الجاية">
            <div class="cg-tl-header">
                <svg class="w-3.5 h-3.5 text-amber-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <span>الخطوة الجاية</span>
            </div>

            <ul class="cg-tl-list">
                {{-- Step 1: Payment Review --}}
                <li class="cg-tl-item">
                    <div class="cg-tl-node" aria-hidden="true">
                        <span class="cg-dot dot-amber"></span>
                    </div>
                    <div class="cg-tl-body">
                        يتم <strong>مراجعة عملية الدفع</strong> والتأكد من صحة التحويل.
                    </div>
                </li>

                {{-- Step 2: WhatsApp Ticket Delivery --}}
                <li class="cg-tl-item">
                    <div class="cg-tl-node" aria-hidden="true">
                        <span class="cg-dot dot-emerald"></span>
                    </div>
                    <div class="cg-tl-body">
                        بعد <span class="accent-emerald">تأكيد الحجز</span>، سيتم إرسال <strong>التذكرة</strong> على <strong>رقم الواتساب المُسجَّل</strong>.
                    </div>
                </li>

                {{-- Step 3: Duration --}}
                <li class="cg-tl-item">
                    <div class="cg-tl-node" aria-hidden="true">
                        <span class="cg-dot dot-sky"></span>
                    </div>
                    <div class="cg-tl-body">
                        المراجعة تتم في أسرع وقت وبحد أقصى <strong>24 ساعة</strong>.
                    </div>
                </li>
            </ul>
        </div>

        {{-- 4. Reassurance / Support (Lightweight inline row) --}}
        <div class="cg-support-row" role="note">
            <svg class="cg-support-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="16" x2="12" y2="12"></line>
                <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            <p class="cg-support-text">
                لو في أي استفسار هنتواصل معاك قبل الرفض · <strong>طلبك محفوظ ✨</strong>
            </p>
        </div>

        {{-- 5. Final CTA Button — Refined Apple Glass Pill --}}
        <a href="{{ route('home') }}" id="btnReturnHome" class="cg-btn-home" aria-label="الرجوع للصفحة الرئيسية">
            <svg class="cg-btn-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m15 18-6-6 6-6"></path>
            </svg>
            <span>الرجوع للصفحة الرئيسية</span>
        </a>

        {{-- Live Region for Copy Feedback --}}
        <div id="copyLiveRegion" class="sr-only" aria-live="polite"></div>

    </div>
</div>

<script>
function copyBookingRef(text) {
    if (!text) return;
    const btn = document.getElementById('btnCopyRef');
    const copyIcon = document.getElementById('copyIcon');
    const liveRegion = document.getElementById('copyLiveRegion');

    const onSuccess = function() {
        if (btn) {
            btn.classList.add('is-copied');
            btn.setAttribute('title', 'تم النسخ!');
        }
        if (copyIcon) {
            copyIcon.innerHTML = '<polyline points="20 6 9 17 4 12"></polyline>';
        }
        if (liveRegion) liveRegion.textContent = 'تم نسخ رقم الحجز بنجاح';

        setTimeout(function() {
            if (btn) {
                btn.classList.remove('is-copied');
                btn.setAttribute('title', 'نسخ رقم الحجز');
            }
            if (copyIcon) {
                copyIcon.innerHTML = '<rect width="14" height="14" x="8" y="8" rx="2" ry="2"></rect><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"></path>';
            }
        }, 2200);
    };

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(onSuccess).catch(function() {
            fallbackCopy(text, onSuccess);
        });
    } else {
        fallbackCopy(text, onSuccess);
    }
}

function fallbackCopy(text, cb) {
    try {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.position = "fixed";
        textArea.style.left = "-999999px";
        textArea.style.top = "-999999px";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        document.execCommand('copy');
        textArea.remove();
        if (cb) cb();
    } catch (e) {
        console.error('Copy fallback failed', e);
    }
}
</script>
@endsection
