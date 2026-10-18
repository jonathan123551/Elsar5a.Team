@extends('layouts.app')

@section('title', 'تم إرسال طلب الحجز')
@section('body_class', 'cg-body-locked')
@section('main_class', 'cg-main-viewport')

@section('content')
<style>
/* ============================================================
   ELSAR5A CINEMATIC CONFIRMATION — APPLE DARK GLASS (SINGLE SCREEN)
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

.cg-main-viewport {
    padding: 0 !important;
    margin: 0 !important;
    max-width: 100% !important;
    height: calc(100vh - 58px) !important;
    height: calc(100dvh - 58px) !important;
    max-height: calc(100vh - 58px) !important;
    max-height: calc(100dvh - 58px) !important;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden !important;
    box-sizing: border-box;
}

/* 2. MAIN CONFIRMATION STAGE WRAPPER */
.cg-stage {
    width: 100%;
    max-width: 440px;
    padding: 0 14px;
    margin: auto;
    box-sizing: border-box;
    display: flex;
    justify-content: center;
}

@media (min-width: 768px) {
    .cg-stage {
        max-width: 500px;
        padding: 0 20px;
    }
}

/* 3. PRIMARY LEVEL-2 APPLE DARK GLASS CARD */
.cg-card {
    position: relative;
    width: 100%;
    background: rgba(24, 10, 16, 0.52);
    backdrop-filter: blur(32px) saturate(210%) contrast(105%);
    -webkit-backdrop-filter: blur(32px) saturate(210%) contrast(105%);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 22px;
    padding: clamp(14px, 2.4vh, 22px) clamp(14px, 3.2vw, 24px);
    box-shadow:
        0 24px 60px -12px rgba(0, 0, 0, 0.85),
        0 0 35px -8px rgba(245, 158, 11, 0.08),
        inset 0 1px 1px 0 rgba(255, 255, 255, 0.22);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    gap: clamp(8px, 1.6vh, 13px);
    animation: cgCardReveal 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
}

@media (min-width: 640px) {
    .cg-card {
        border-radius: 26px;
    }
}

/* Subtle ambient gold specular rim light */
.cg-card::before {
    content: "";
    position: absolute;
    top: 0;
    inset-inline: 18%;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(253, 230, 138, 0.55), transparent);
    pointer-events: none;
}

/* 4. SUCCESS HEADER & ANIMATED EMBLEM */
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
    background: radial-gradient(circle at 35% 30%, rgba(16, 185, 129, 0.28) 0%, rgba(16, 185, 129, 0.08) 65%, rgba(10, 4, 7, 0.5) 100%);
    border: 1.5px solid rgba(52, 211, 153, 0.5);
    box-shadow:
        0 8px 20px -3px rgba(0, 0, 0, 0.55),
        0 0 20px -2px rgba(16, 185, 129, 0.32),
        inset 0 1px 1px 0 rgba(255, 255, 255, 0.38);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #34d399;
    margin-bottom: 7px;
    animation: cgBadgePop 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.cg-check-svg {
    width: 22px;
    height: 22px;
    stroke-dasharray: 40;
    stroke-dashoffset: 40;
    animation: cgCheckDraw 0.45s 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.cg-title {
    font-family: 'Alexandria', -apple-system, BlinkMacSystemFont, "Readex Pro", sans-serif;
    font-size: clamp(1.12rem, 3.3vw, 1.35rem);
    font-weight: 800;
    line-height: 1.25;
    letter-spacing: -0.015em;
    color: #fce7b0;
    text-shadow: 0 2px 14px rgba(245, 158, 11, 0.25);
    margin: 0;
    animation: cgFadeUp 0.42s 0.12s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.cg-greeting {
    font-size: clamp(0.78rem, 2.2vw, 0.86rem);
    color: #c9c3c7;
    margin-top: 3px;
    margin-bottom: 0;
    font-weight: 400;
    animation: cgFadeUp 0.42s 0.18s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.cg-customer-name {
    color: #ffffff;
    font-weight: 600;
}

/* 5. LEVEL-3 GLASS: BOOKING SUMMARY (2-COLUMN GRID - GUARANTEED ZERO COLLISION) */
.cg-summary {
    display: grid;
    grid-template-columns: 1.15fr 0.85fr;
    gap: 8px;
    background: rgba(255, 255, 255, 0.035);
    border: 1px solid rgba(255, 255, 255, 0.075);
    border-radius: 14px;
    padding: 7px 11px;
    box-shadow:
        inset 0 1px 1px 0 rgba(255, 255, 255, 0.05),
        0 4px 14px -3px rgba(0, 0, 0, 0.35);
    animation: cgFadeUp 0.44s 0.22s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.cg-sum-col {
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-width: 0;
}

.cg-sum-label {
    font-size: 0.68rem;
    color: #9c9096;
    font-weight: 500;
    margin-bottom: 2px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.cg-sum-value-wrap {
    display: flex;
    align-items: center;
    gap: 5px;
    min-width: 0;
}

.cg-ref-code {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: clamp(0.76rem, 2.4vw, 0.88rem);
    font-weight: 700;
    color: #fbbf24;
    letter-spacing: 0.02em;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    user-select: all;
}

.cg-copy-btn {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    border-radius: 7px;
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
    font-size: clamp(0.86rem, 2.7vw, 0.98rem);
    font-weight: 700;
    color: #34d399;
    letter-spacing: -0.01em;
    white-space: nowrap;
}

/* 6. LEVEL-3 GLASS: TIMELINE STATUS ("الخطوة الجاية") */
.cg-timeline {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.07);
    border-radius: 14px;
    padding: 8px 11px 9px;
    box-shadow:
        inset 0 1px 1px 0 rgba(255, 255, 255, 0.04),
        0 4px 16px -4px rgba(0, 0, 0, 0.35);
    animation: cgFadeUp 0.44s 0.28s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.cg-tl-header {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    font-weight: 700;
    color: #fde68a;
    padding-bottom: 6px;
    margin-bottom: 7px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.cg-tl-list {
    position: relative;
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 7px;
}

/* Elegant status connecting hairline */
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
    font-size: clamp(0.72rem, 2.1vw, 0.78rem);
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

/* 7. LEVEL-3 GLASS: SUPPORT & REASSURANCE NOTE */
.cg-support {
    display: flex;
    align-items: center;
    gap: 7px;
    background: rgba(245, 158, 11, 0.04);
    border: 1px solid rgba(245, 158, 11, 0.15);
    border-radius: 11px;
    padding: 6px 10px;
    animation: cgFadeUp 0.44s 0.34s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.cg-support-icon {
    width: 14px;
    height: 14px;
    color: #fbbf24;
    flex-shrink: 0;
}

.cg-support-text {
    font-size: clamp(0.68rem, 1.95vw, 0.74rem);
    line-height: 1.35;
    color: #bfb5bb;
    margin: 0;
}

.cg-support-text strong {
    color: #f3eff2;
    font-weight: 500;
}

/* 8. LEVEL-4 GLASS: FINAL TACTILE HOMEPAGE BUTTON */
.cg-btn-home {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    width: 100%;
    min-height: 43px;
    padding: 8px 16px;
    border-radius: 9999px;
    background: linear-gradient(180deg, rgba(245, 158, 11, 0.20) 0%, rgba(217, 119, 6, 0.10) 100%);
    border: 1.5px solid rgba(245, 158, 11, 0.45);
    color: #fef3c7;
    font-family: 'Alexandria', -apple-system, BlinkMacSystemFont, "Readex Pro", sans-serif;
    font-size: 0.88rem;
    font-weight: 700;
    letter-spacing: -0.01em;
    text-decoration: none;
    box-shadow:
        0 8px 20px -4px rgba(0, 0, 0, 0.5),
        0 0 16px -3px rgba(245, 158, 11, 0.22),
        inset 0 1px 1px 0 rgba(255, 255, 255, 0.20);
    transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
    -webkit-tap-highlight-color: transparent;
    animation: cgFadeUp 0.44s 0.40s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.cg-btn-home:hover {
    background: linear-gradient(180deg, rgba(245, 158, 11, 0.30) 0%, rgba(217, 119, 6, 0.18) 100%);
    border-color: rgba(245, 158, 11, 0.75);
    color: #ffffff;
    box-shadow:
        0 12px 26px -4px rgba(0, 0, 0, 0.6),
        0 0 24px -2px rgba(245, 158, 11, 0.38),
        inset 0 1px 1px 0 rgba(255, 255, 255, 0.30);
    transform: translateY(-1px);
}

.cg-btn-home:active {
    transform: translateY(1px);
    background: rgba(245, 158, 11, 0.16);
    box-shadow: 0 4px 10px -2px rgba(0, 0, 0, 0.4);
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

/* 9. KEYFRAME ANIMATIONS */
@keyframes cgCardReveal {
    from {
        opacity: 0;
        transform: translateY(12px) scale(0.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes cgBadgePop {
    from {
        opacity: 0;
        transform: scale(0.8);
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
        transform: translateY(8px);
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
    .cg-summary,
    .cg-timeline,
    .cg-support,
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

        {{-- 1. Hero / Success State --}}
        <div class="cg-hero">
            <div class="cg-emblem" aria-hidden="true">
                <svg class="cg-check-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
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

        {{-- 2. Booking Information Summary (Two-column grid - zero overlap) --}}
        <div class="cg-summary" role="region" aria-label="بيانات الحجز">
            <div class="cg-sum-col">
                <span class="cg-sum-label">
                    <svg class="w-3 h-3 text-amber-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect width="14" height="18" x="5" y="3" rx="2"></rect>
                        <line x1="9" x2="15" y1="7" y2="7"></line>
                        <line x1="9" x2="15" y1="11" y2="11"></line>
                        <line x1="9" x2="13" y1="15" y2="15"></line>
                    </svg>
                    <span>رقم الحجز</span>
                </span>
                <div class="cg-sum-value-wrap">
                    <span id="bookingRefCode" class="cg-ref-code" title="{{ $booking->reference_code }}">{{ $booking->reference_code }}</span>
                    <button type="button"
                            id="btnCopyRef"
                            class="cg-copy-btn"
                            onclick="copyBookingRef('{{ $booking->reference_code }}')"
                            aria-label="نسخ رقم الحجز"
                            title="نسخ رقم الحجز">
                        <svg id="copyIcon" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect width="14" height="14" x="8" y="8" rx="2" ry="2"></rect>
                            <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="cg-sum-col">
                <span class="cg-sum-label">
                    <svg class="w-3 h-3 text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"></path>
                        <path d="M12 18V6"></path>
                    </svg>
                    <span>إجمالي المبلغ</span>
                </span>
                <div class="cg-sum-value-wrap">
                    <span class="cg-amount-val">
                        {{ is_numeric($booking->total_price) ? number_format((float)$booking->total_price, 2) : $booking->total_price }} جنيه
                    </span>
                </div>
            </div>
        </div>

        {{-- 3. Next Step Timeline ("الخطوة الجاية") --}}
        <div class="cg-timeline" role="region" aria-label="الخطوة الجاية">
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
                        بعد <span class="accent-emerald">تأكيد الحجز</span>، سيتم إرسال <strong>التذكرة</strong> مباشرة على <strong>رقم الواتساب المُسجَّل</strong>.
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

        {{-- 4. Reassurance / Support Note --}}
        <div class="cg-support" role="note">
            <svg class="cg-support-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="16" x2="12" y2="12"></line>
                <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            <p class="cg-support-text">
                لو في أي استفسار هنتواصل معاك قبل الرفض · <strong>طلبك محفوظ ✨</strong>
            </p>
        </div>

        {{-- 5. Final CTA Button — Return to Website Homepage --}}
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
