@extends('layouts.app')

@section('title', 'تم إرسال طلب الحجز')

@section('content')
<style>
/* ============================================================
   ELSAR5A BOOKING SUCCESS / CONFIRMATION — REFINED APPLE GLASS
   ============================================================ */
:root {
    --apple-font: -apple-system, BlinkMacSystemFont, "SF Pro Text", "SF Pro Display", "SF Arabic", system-ui, -apple-system-subpixel, sans-serif;
    --th-amber: #f59e0b;
    --th-amber-light: #fde68a;
    --th-amber-glow: rgba(245, 158, 11, 0.25);
    --th-emerald: #10b981;
    --th-emerald-light: #34d399;
    --th-emerald-glow: rgba(16, 185, 129, 0.25);
    --th-sky: #38bdf8;
    --th-sky-glow: rgba(56, 189, 248, 0.25);
}

.bo-confirm-wrap {
    max-width: 490px;
    margin-inline: auto;
    padding: 0.25rem 0.5rem 2.5rem;
    font-family: var(--apple-font);
    color: #f9fafb;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

@media (min-width: 640px) {
    .bo-confirm-wrap {
        padding: 1.25rem 1rem 3.5rem;
    }
}

/* 1. MAIN APPLE-STYLE GLASS CARD */
.bo-glass-card {
    position: relative;
    background: rgba(22, 11, 15, 0.74);
    backdrop-filter: blur(28px) saturate(190%);
    -webkit-backdrop-filter: blur(28px) saturate(190%);
    border: 1px solid rgba(255, 255, 255, 0.10);
    border-radius: 1.75rem;
    padding: 1.5rem 1.15rem 1.35rem;
    box-shadow: 0 20px 50px -12px rgba(0, 0, 0, 0.75),
                0 0 35px -12px rgba(245, 158, 11, 0.08),
                inset 0 1px 1px 0 rgba(255, 255, 255, 0.18);
    overflow: hidden;
    animation: confirmFadeUp 0.45s cubic-bezier(0.16, 1, 0.3, 1) both;
}

@media (min-width: 640px) {
    .bo-glass-card {
        padding: 2rem 1.75rem 1.75rem;
        border-radius: 2rem;
    }
}

/* Subtle top ambient rim light */
.bo-glass-card::before {
    content: "";
    position: absolute;
    top: 0;
    inset-inline: 15%;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(253, 230, 138, 0.45), transparent);
    pointer-events: none;
}

/* 2. HERO / SUCCESS AREA */
.bo-hero-area {
    text-align: center;
    margin-bottom: 1.35rem;
}

/* Ticket Icon: Refined glass physical badge */
.bo-ticket-badge {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 62px;
    height: 62px;
    margin-inline: auto;
    margin-bottom: 1rem;
    border-radius: 50%;
    background: radial-gradient(circle at 35% 30%, rgba(253, 230, 138, 0.25) 0%, rgba(245, 158, 11, 0.10) 65%, rgba(18, 8, 12, 0.6) 100%);
    border: 1.5px solid rgba(245, 158, 11, 0.45);
    box-shadow: 0 10px 25px -4px rgba(0, 0, 0, 0.55),
                0 0 24px -2px rgba(245, 158, 11, 0.28),
                inset 0 1px 1px 0 rgba(255, 255, 255, 0.35);
    animation: badgePop 0.55s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.bo-ticket-art {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 24px;
    border-radius: 4px;
    background: linear-gradient(135deg, #f472b6, #ec4899);
    border: 1px dashed rgba(255, 255, 255, 0.65);
    color: #ffffff;
    font-size: 7.5px;
    font-weight: 900;
    letter-spacing: 0.06em;
    box-shadow: 0 2px 8px rgba(236, 72, 153, 0.35);
}

.bo-title-success {
    font-size: clamp(1.3rem, 3.8vw, 1.55rem);
    font-weight: 800;
    line-height: 1.25;
    letter-spacing: -0.015em;
    color: #fbbf24;
    text-shadow: 0 2px 14px rgba(245, 158, 11, 0.3);
    margin: 0;
}

.bo-customer-greeting {
    margin-top: 0.35rem;
    font-size: clamp(0.85rem, 2.5vw, 0.92rem);
    color: #d1d5db;
    font-weight: 400;
}

.bo-customer-name {
    color: #ffffff;
    font-weight: 600;
}

/* 3. BOOKING INFORMATION SUB-CARD */
.bo-info-subcard {
    background: rgba(13, 5, 8, 0.68);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 1.15rem;
    padding: 0.85rem 1rem;
    margin-bottom: 1.15rem;
    box-shadow: inset 0 1px 1px 0 rgba(255, 255, 255, 0.06),
                0 6px 16px -4px rgba(0, 0, 0, 0.35);
}

.bo-info-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 0.35rem 0;
}

.bo-info-row + .bo-info-row {
    border-top: 1px solid rgba(255, 255, 255, 0.06);
    margin-top: 0.35rem;
    padding-top: 0.65rem;
}

.bo-info-side-right {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    flex-shrink: 0;
}

.bo-info-icon-badge {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.10);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fde68a;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.bo-info-label {
    font-size: 0.82rem;
    color: #a3999d;
    font-weight: 500;
    white-space: nowrap;
}

.bo-info-side-left {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    min-width: 0;
    justify-content: flex-end;
}

.bo-ref-text {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-weight: 700;
    font-size: clamp(0.82rem, 2.9vw, 0.96rem);
    color: #fbbf24;
    letter-spacing: 0.02em;
    white-space: nowrap;
    user-select: all;
}

.bo-copy-pill {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    border-radius: 0.55rem;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: #e5e7eb;
    cursor: pointer;
    flex-shrink: 0;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    -webkit-tap-highlight-color: transparent;
}

.bo-copy-pill:hover {
    background: rgba(245, 158, 11, 0.16);
    border-color: rgba(245, 158, 11, 0.45);
    color: #fde68a;
}

.bo-copy-pill:active {
    transform: scale(0.94);
}

.bo-copy-pill.is-copied {
    background: rgba(16, 185, 129, 0.2);
    border-color: rgba(16, 185, 129, 0.5);
    color: #34d399;
}

.bo-amount-text {
    font-family: var(--apple-font);
    font-size: clamp(0.92rem, 3vw, 1.05rem);
    font-weight: 700;
    color: #34d399;
    letter-spacing: -0.01em;
    white-space: nowrap;
}

/* 4. "الخطوة الجاية" SECTION — CLEAN GLASS TIMELINE */
.bo-timeline-card {
    background: rgba(15, 6, 9, 0.60);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 1.15rem;
    padding: 1rem 1rem 1.15rem;
    margin-bottom: 1.15rem;
    box-shadow: inset 0 1px 1px 0 rgba(255, 255, 255, 0.05),
                0 6px 20px -6px rgba(0, 0, 0, 0.4);
}

.bo-timeline-header {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    padding-bottom: 0.75rem;
    margin-bottom: 0.9rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    color: #fde68a;
    font-size: 0.92rem;
    font-weight: 700;
    letter-spacing: -0.01em;
}

.bo-timeline-list {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
    list-style: none;
    margin: 0;
    padding: 0;
}

/* Timeline vertical connector line */
.bo-timeline-list::before {
    content: "";
    position: absolute;
    top: 14px;
    bottom: 14px;
    right: 51px;
    width: 2px;
    background: linear-gradient(180deg,
        rgba(245, 158, 11, 0.5) 0%,
        rgba(16, 185, 129, 0.5) 50%,
        rgba(56, 189, 248, 0.4) 100%
    );
    border-radius: 2px;
}

.bo-timeline-item {
    position: relative;
    display: flex;
    align-items: flex-start;
    gap: 0.65rem;
}

/* Semantic icon badge on the far right */
.bo-timeline-icon-box {
    flex-shrink: 0;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2;
}

.bo-timeline-icon-box.icon-amber {
    background: rgba(245, 158, 11, 0.12);
    border: 1px solid rgba(245, 158, 11, 0.35);
    color: #fbbf24;
}

.bo-timeline-icon-box.icon-emerald {
    background: rgba(16, 185, 129, 0.12);
    border: 1px solid rgba(16, 185, 129, 0.35);
    color: #34d399;
}

.bo-timeline-icon-box.icon-sky {
    background: rgba(56, 189, 248, 0.12);
    border: 1px solid rgba(56, 189, 248, 0.35);
    color: #38bdf8;
}

/* Status node on the vertical line */
.bo-timeline-node-wrap {
    flex-shrink: 0;
    width: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding-top: 11px;
    z-index: 2;
}

.bo-timeline-node {
    width: 9px;
    height: 9px;
    border-radius: 50%;
}

.bo-timeline-node.node-amber {
    background: #f59e0b;
    box-shadow: 0 0 10px 2px rgba(245, 158, 11, 0.75);
}

.bo-timeline-node.node-emerald {
    background: #10b981;
    box-shadow: 0 0 10px 2px rgba(16, 185, 129, 0.75);
}

.bo-timeline-node.node-sky {
    background: #38bdf8;
    box-shadow: 0 0 10px 2px rgba(56, 189, 248, 0.75);
}

.bo-timeline-text {
    flex: 1;
    font-size: clamp(0.8rem, 2.3vw, 0.86rem);
    line-height: 1.55;
    color: #e5e7eb;
    text-align: right;
    padding-top: 5px;
}

/* 5. SUPPORT / HELP SECONDARY GLASS SUB-CARD */
.bo-support-card {
    display: flex;
    align-items: flex-start;
    gap: 0.65rem;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.06);
    border-radius: 1rem;
    padding: 0.75rem 0.9rem;
    margin-bottom: 1.35rem;
    text-align: right;
}

.bo-support-icon-wrap {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: rgba(245, 158, 11, 0.10);
    border: 1px solid rgba(245, 158, 11, 0.20);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fbbf24;
    flex-shrink: 0;
    margin-top: 1px;
}

.bo-support-body {
    font-size: 0.78rem;
    line-height: 1.5;
    color: #a3999d;
}

.bo-support-body strong {
    color: #e5e7eb;
    font-weight: 500;
}

/* 6. FINAL NAVIGATION BUTTON — GLASS HOMEPAGE RETURN */
.bo-btn-home {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    width: 100%;
    min-height: 48px;
    padding: 0.7rem 1.25rem;
    border-radius: 9999px;
    background: rgba(245, 158, 11, 0.12);
    border: 1.5px solid rgba(245, 158, 11, 0.40);
    color: #fde68a;
    font-family: var(--apple-font);
    font-size: 0.92rem;
    font-weight: 700;
    letter-spacing: -0.01em;
    text-decoration: none;
    box-shadow: 0 8px 22px -4px rgba(0, 0, 0, 0.45),
                0 0 18px -4px rgba(245, 158, 11, 0.20),
                inset 0 1px 1px 0 rgba(255, 255, 255, 0.15);
    transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
    -webkit-tap-highlight-color: transparent;
}

.bo-btn-home:hover {
    background: rgba(245, 158, 11, 0.22);
    border-color: rgba(245, 158, 11, 0.70);
    color: #ffffff;
    box-shadow: 0 12px 28px -4px rgba(0, 0, 0, 0.55),
                0 0 26px -2px rgba(245, 158, 11, 0.35),
                inset 0 1px 1px 0 rgba(255, 255, 255, 0.25);
    transform: translateY(-1px);
}

.bo-btn-home:active {
    transform: translateY(0);
    background: rgba(245, 158, 11, 0.18);
    box-shadow: 0 4px 12px -2px rgba(0, 0, 0, 0.4);
}

.bo-btn-home:focus-visible {
    outline: 2px solid #fbbf24;
    outline-offset: 3px;
}

.bo-btn-arrow {
    width: 17px;
    height: 17px;
    transition: transform 0.2s ease;
}

.bo-btn-home:hover .bo-btn-arrow {
    transform: translateX(-3px);
}

/* Animations & Reduced Motion */
@keyframes confirmFadeUp {
    from {
        opacity: 0;
        transform: translateY(14px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes badgePop {
    from {
        opacity: 0;
        transform: scale(0.85);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

@media (prefers-reduced-motion: reduce) {
    .bo-glass-card,
    .bo-ticket-badge,
    .bo-btn-home,
    .bo-btn-arrow {
        animation: none !important;
        transition: none !important;
        transform: none !important;
    }
}
</style>

<section class="bo-confirm-wrap" aria-labelledby="confirmation-heading">
    <div class="bo-glass-card">

        {{-- 1. Hero / Success Area --}}
        <div class="bo-hero-area">
            {{-- Ticket Icon as a physical glass object --}}
            <div class="bo-ticket-badge" aria-hidden="true">
                <div class="bo-ticket-art">
                    <span>ADMIT</span>
                    <span>ONE</span>
                </div>
            </div>

            <h1 id="confirmation-heading" class="bo-title-success">
                تم إرسال طلب الحجز بنجاح
            </h1>

            <p class="bo-customer-greeting">
                شكراً يا <span class="bo-customer-name">{{ $booking->full_name }}</span>
            </p>
        </div>

        {{-- 2. Booking Information Sub-Card --}}
        <div class="bo-info-subcard" role="region" aria-label="بيانات الحجز">
            <div class="bo-info-row">
                <div class="bo-info-side-right">
                    <div class="bo-info-icon-badge" aria-hidden="true">
                        <svg class="w-4 h-4 text-amber-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="14" height="18" x="5" y="3" rx="2"></rect>
                            <line x1="9" x2="15" y1="7" y2="7"></line>
                            <line x1="9" x2="15" y1="11" y2="11"></line>
                            <line x1="9" x2="13" y1="15" y2="15"></line>
                        </svg>
                    </div>
                    <span class="bo-info-label">رقم الحجز</span>
                </div>
                <div class="bo-info-side-left">
                    <span id="bookingRefCode" class="bo-ref-text">{{ $booking->reference_code }}</span>
                    <button type="button"
                            id="btnCopyRef"
                            class="bo-copy-pill"
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

            <div class="bo-info-row">
                <div class="bo-info-side-right">
                    <div class="bo-info-icon-badge" aria-hidden="true">
                        <svg class="w-4 h-4 text-amber-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"></path>
                            <path d="M12 18V6"></path>
                        </svg>
                    </div>
                    <span class="bo-info-label">إجمالي المبلغ</span>
                </div>
                <div class="bo-info-side-left">
                    <span class="bo-amount-text">
                        {{ is_numeric($booking->total_price) ? number_format((float)$booking->total_price, 2) : $booking->total_price }} جنيه
                    </span>
                </div>
            </div>
        </div>

        {{-- 3. "الخطوة الجاية" Timeline Status Section --}}
        <div class="bo-timeline-card" role="region" aria-label="الخطوة الجاية">
            <div class="bo-timeline-header">
                <span class="text-base" aria-hidden="true">⏳</span>
                <span>الخطوة الجاية</span>
            </div>

            <ul class="bo-timeline-list">
                {{-- Step 1: Current / Pending Review --}}
                <li class="bo-timeline-item">
                    <div class="bo-timeline-icon-box icon-amber" aria-hidden="true">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="20" height="14" x="2" y="5" rx="2"></rect>
                            <line x1="2" x2="22" y1="10" y2="10"></line>
                        </svg>
                    </div>
                    <div class="bo-timeline-node-wrap" aria-hidden="true">
                        <span class="bo-timeline-node node-amber"></span>
                    </div>
                    <div class="bo-timeline-text">
                        يتم <span class="text-white font-semibold">مراجعة عملية الدفع</span>
                        والتأكد من التحويل.
                    </div>
                </li>

                {{-- Step 2: Confirmation & WhatsApp Delivery --}}
                <li class="bo-timeline-item">
                    <div class="bo-timeline-icon-box icon-emerald" aria-hidden="true">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                        </svg>
                    </div>
                    <div class="bo-timeline-node-wrap" aria-hidden="true">
                        <span class="bo-timeline-node node-emerald"></span>
                    </div>
                    <div class="bo-timeline-text">
                        بعد <span class="text-emerald-400 font-semibold">تأكيد الحجز</span>،
                        سيتم إرسال <span class="text-white font-semibold">التذكرة</span>
                        مباشرة على <span class="text-white font-semibold">رقم الواتساب المُسجَّل</span>.
                    </div>
                </li>

                {{-- Step 3: Maximum Duration --}}
                <li class="bo-timeline-item">
                    <div class="bo-timeline-icon-box icon-sky" aria-hidden="true">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                    <div class="bo-timeline-node-wrap" aria-hidden="true">
                        <span class="bo-timeline-node node-sky"></span>
                    </div>
                    <div class="bo-timeline-text">
                        عملية المراجعة قد تستغرق بحد أقصى
                        <span class="text-white font-semibold">24 ساعة</span>.
                    </div>
                </li>
            </ul>
        </div>

        {{-- 4. Support / Help Message Sub-Card --}}
        <div class="bo-support-card" role="note">
            <div class="bo-support-icon-wrap" aria-hidden="true">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2v1"></path>
                    <path d="M12 21v1"></path>
                    <path d="M4.22 4.22l.71.71"></path>
                    <path d="M19.07 19.07l.71.71"></path>
                    <path d="M2 12h1"></path>
                    <path d="M21 12h1"></path>
                    <path d="M4.22 19.78l.71-.71"></path>
                    <path d="M19.07 4.93l.71-.71"></path>
                    <circle cx="12" cy="12" r="5"></circle>
                </svg>
            </div>
            <div class="bo-support-body">
                <strong>لو في أي مشكلة في التحويل أو البيانات، هنتواصل معاك قبل رفض الطلب.</strong>
                <span class="block text-gray-400 mt-0.5">متقلقش، طلبك محفوظ على السيستم ✨</span>
            </div>
        </div>

        {{-- 5. Final Navigation Button — Return to Website Homepage --}}
        <a href="{{ route('home') }}" id="btnReturnHome" class="bo-btn-home" aria-label="الرجوع للصفحة الرئيسية">
            <svg class="bo-btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m15 18-6-6 6-6"></path>
            </svg>
            <span>الرجوع للصفحة الرئيسية</span>
        </a>

        {{-- Accessibility Live Region for Copy Feedback --}}
        <div id="copyLiveRegion" class="sr-only" aria-live="polite"></div>

    </div>
</section>

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
