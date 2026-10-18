@extends('layouts.app')

@section('title', 'حجز تذاكر - ' . $showTime->show->title)

@section('content')
@php
    use Carbon\Carbon;

    // Stable showtime list: deterministic sort order ensures physical positions NEVER swap
    $allShowTimes = $showTime->show->showTimes()
        ->where('is_sold_out', false)
        ->orderBy('date', 'asc')
        ->orderBy('time', 'asc')
        ->get();

    $currentDate = Carbon::parse($showTime->date);
    $currentTime = Carbon::parse($showTime->time);
    $timeFormatted = $currentTime->format('g:i') . ($currentTime->format('A') === 'PM' ? ' مساءً' : ' صباحاً');
@endphp

<style>
/* ============================================================
   ELSAR5A THEATRE BOX OFFICE — APPLE-INSPIRED GLASS & TYPOGRAPHY
   ============================================================ */
:root {
    --th-bg: #090406;
    /* Deep theatrical burgundy/maroon container gradient matching background */
    --th-card-bg: linear-gradient(180deg, rgba(34, 15, 20, 0.90) 0%, rgba(18, 8, 12, 0.94) 100%);
    --th-card-border: rgba(255, 255, 255, 0.08);
    --th-card-shadow: 0 16px 40px -12px rgba(0, 0, 0, 0.75), 0 0 35px -8px rgba(120, 20, 35, 0.12);
    /* Surface hierarchy */
    --th-surface-dark: rgba(13, 5, 8, 0.65);
    --th-surface-elevated: rgba(32, 13, 18, 0.75);
    --th-amber: #f59e0b;
    --th-amber-light: #fde68a;
    --th-amber-glow: rgba(245, 158, 11, 0.25);
    --th-emerald: #10b981;
    --th-emerald-glow: rgba(16, 185, 129, 0.25);
    --th-text-main: #f9fafb;
    --th-text-muted: #a3999d;
    --th-text-dim: #786e72;
    --apple-font: -apple-system, BlinkMacSystemFont, "SF Pro Text", "SF Pro Display", "SF Arabic", system-ui, -apple-system-subpixel, sans-serif;
}

html {
    scroll-behavior: smooth;
    scroll-padding-top: 130px;
}

.boxoffice-container {
    max-width: 780px;
    margin-inline: auto;
    padding: 0 1rem 3.5rem;
    color: var(--th-text-main);
    font-family: var(--apple-font);
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

@media (min-width: 640px) {
    .boxoffice-container {
        padding: 0 1.5rem 4rem;
    }
}

/* 1. APPLE-INSPIRED TRANSLUCENT GLASS FLOATING STEP NAVIGATION */
.bo-sticky-nav-wrap {
    position: sticky;
    top: 50px;
    z-index: 30;
    display: flex;
    justify-content: center;
    padding: 0.5rem 0.5rem;
    margin-bottom: 1.25rem;
    pointer-events: none;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

.bo-glass-pill {
    pointer-events: auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    max-width: 440px;
    padding: 0.35rem 0.75rem;
    border-radius: 9999px;
    background: rgba(26, 11, 16, 0.72);
    backdrop-filter: blur(20px) saturate(180%);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    border: 1px solid rgba(255, 255, 255, 0.10);
    box-shadow: 0 10px 30px -4px rgba(0, 0, 0, 0.55),
        0 4px 12px rgba(0, 0, 0, 0.35),
        inset 0 1px 1px 0 rgba(255, 255, 255, 0.14),
        inset 0 -1px 1px 0 rgba(0, 0, 0, 0.3);
    font-family: var(--apple-font);
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

.bo-sticky-nav-wrap.is-scrolled .bo-glass-pill {
    background: rgba(18, 7, 11, 0.88);
    border-color: rgba(255, 255, 255, 0.14);
    box-shadow: 0 14px 40px -6px rgba(0, 0, 0, 0.65),
        0 6px 16px rgba(0, 0, 0, 0.4),
        inset 0 1px 1px 0 rgba(255, 255, 255, 0.18);
}

.bo-step-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    cursor: pointer;
    user-select: none;
    transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
    background: transparent;
    border: none;
    box-shadow: none;
    padding: 0.25rem 0.65rem;
    border-radius: 9999px;
    -webkit-tap-highlight-color: transparent;
    touch-action: manipulation;
}

.bo-step-num {
    font-family: var(--apple-font);
    font-size: 1.15rem;
    font-weight: 700;
    line-height: 1.1;
    margin-bottom: 0.15rem;
    font-variant-numeric: tabular-nums;
    transition: all 0.22s ease;
}

.bo-step-name {
    font-family: var(--apple-font);
    font-size: 0.78rem;
    font-weight: 500;
    line-height: 1.2;
    transition: all 0.22s ease;
    letter-spacing: -0.01em;
}

/* INACTIVE STEP: Dimmed / Muted */
.bo-step-item.is-inactive {
    opacity: 0.4;
    color: rgba(255, 255, 255, 0.45);
}

.bo-step-item.is-inactive .bo-step-num {
    color: rgba(255, 255, 255, 0.45);
}

.bo-step-item.is-inactive .bo-step-name {
    color: rgba(255, 255, 255, 0.45);
}

.bo-step-item.is-inactive:hover {
    opacity: 0.75;
}

/* COMPLETED STEP: Semantic Emerald Green */
.bo-step-item.is-completed {
    opacity: 0.95;
    color: #34d399;
}

.bo-step-item.is-completed .bo-step-num {
    color: #10b981;
    text-shadow: 0 0 10px rgba(16, 185, 129, 0.4);
}

.bo-step-item.is-completed .bo-step-name {
    color: #34d399;
    font-weight: 600;
}

/* CURRENT ACTIVE STEP: Apple Warm Amber/Yellow with soft glass bloom */
.bo-step-item.is-active {
    opacity: 1;
    background: rgba(245, 158, 11, 0.14);
    box-shadow: 0 0 16px -2px rgba(245, 158, 11, 0.35),
        inset 0 1px 0 rgba(253, 230, 138, 0.25);
}

.bo-step-item.is-active .bo-step-num {
    color: #fbbf24;
    font-size: 1.25rem;
    font-weight: 800;
    text-shadow: 0 0 12px rgba(245, 158, 11, 0.6);
}

.bo-step-item.is-active .bo-step-name {
    color: #fde68a;
    font-weight: 700;
}

/* 2. SECTION CARDS — MINIMAL HEADERS, NO NUMBER BOXES */
.bo-section {
    background: var(--th-card-bg);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid var(--th-card-border);
    border-radius: 1.25rem;
    padding: 1.25rem;
    margin-bottom: 1.25rem;
    box-shadow: var(--th-card-shadow);
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

@media (min-width: 640px) {
    .bo-section {
        padding: 1.5rem;
    }
}

.bo-section.is-active-focus {
    border-color: rgba(245, 158, 11, 0.45);
    box-shadow: 0 0 25px -6px var(--th-amber-glow), var(--th-card-shadow);
}

.bo-section.is-section-done {
    border-color: rgba(16, 185, 129, 0.35);
}

.bo-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.bo-section-title {
    font-family: var(--apple-font);
    font-size: 1.05rem;
    font-weight: 700;
    color: #ffffff;
    margin: 0;
    letter-spacing: -0.01em;
}

.bo-status-pill {
    font-size: 0.72rem;
    font-weight: 600;
    padding: 0.2rem 0.55rem;
    border-radius: 9999px;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    flex-shrink: 0;
}

.bo-status-pill.status-pending {
    background: rgba(255, 255, 255, 0.04);
    color: var(--th-text-dim);
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.bo-status-pill.status-done {
    background: rgba(16, 185, 129, 0.12);
    color: #34d399;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

/* 3. SHOWTIME SELECTOR — 100% STABLE POSITIONING */
.bo-showtimes-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.65rem;
    margin-bottom: 1.25rem;
}

@media (min-width: 480px) {
    .bo-showtimes-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

.bo-showtime-card {
    position: relative;
    display: block;
    text-align: right;
    padding: 0.9rem 1rem;
    border-radius: 1rem;
    border: 1.5px solid rgba(255, 255, 255, 0.07);
    background: var(--th-surface-dark);
    text-decoration: none;
    transition: all 0.2s ease;
    -webkit-tap-highlight-color: transparent;
}

.bo-showtime-card.is-active {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.15), rgba(34, 14, 20, 0.85));
    border-color: rgba(245, 158, 11, 0.70);
    box-shadow: 0 0 20px -5px var(--th-amber-glow);
}

.bo-showtime-card:not(.is-active) {
    opacity: 0.72;
}

.bo-showtime-card:not(.is-active):hover {
    opacity: 0.95;
    background: var(--th-surface-elevated);
    border-color: rgba(255, 255, 255, 0.16);
}

.bo-showtime-label {
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--th-text-dim);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.bo-showtime-card.is-active .bo-showtime-label {
    color: #fbbf24;
}

.bo-showtime-time {
    font-family: var(--apple-font);
    font-size: 1.15rem;
    font-weight: 700;
    color: #ffffff;
    margin-top: 0.25rem;
    display: block;
    font-variant-numeric: tabular-nums;
}

.bo-showtime-date {
    font-size: 0.75rem;
    color: var(--th-text-muted);
    margin-top: 0.15rem;
    font-variant-numeric: tabular-nums;
}

/* 4. TACTILE TICKET COUNTER — STRICT PLUS ON RIGHT, MINUS ON LEFT */
.bo-counter-box {
    background: var(--th-surface-dark);
    border: 1px solid rgba(255, 255, 255, 0.06);
    border-radius: 1rem;
    padding: 1.1rem 1rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.85rem;
    transition: all 0.2s ease;
}

.bo-counter-controls {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1.35rem;
    width: 100%;
    direction: ltr; /* GUARANTEES: Minus (-) on physical LEFT, Plus (+) on physical RIGHT */
}

.bo-counter-btn {
    width: 52px;
    height: 52px;
    border-radius: 1rem;
    border: 1.5px solid rgba(255, 255, 255, 0.12);
    background: var(--th-surface-elevated);
    color: #ffffff;
    font-size: 1.6rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s ease;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    user-select: none;
}

.bo-counter-btn:hover {
    background: rgba(48, 19, 27, 0.9);
    border-color: rgba(245, 158, 11, 0.35);
    color: #fde68a;
}

.bo-counter-btn:active {
    transform: scale(0.92);
}

.bo-counter-display {
    min-width: 80px;
    text-align: center;
    direction: rtl;
}

.bo-counter-number {
    font-family: var(--apple-font);
    font-size: 2.35rem;
    font-weight: 800;
    color: #ffffff;
    line-height: 1;
    font-variant-numeric: tabular-nums;
}

.bo-counter-sub {
    font-size: 0.75rem;
    color: var(--th-text-muted);
    margin-top: 0.25rem;
    display: block;
}

.bo-counter-ledger {
    font-size: 0.95rem;
    font-weight: 600;
    color: #fde68a;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.bo-counter-price-val {
    font-family: var(--apple-font);
    font-size: 1.2rem;
    font-weight: 800;
    color: #ffffff;
    font-variant-numeric: tabular-nums;
}

/* 5. PAYMENT SECTION */
.bo-payment-list {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

@media (min-width: 540px) {
    .bo-payment-list {
        grid-template-columns: repeat(2, 1fr);
    }
}

.bo-pay-item {
    background: var(--th-surface-dark);
    border: 1px solid rgba(255, 255, 255, 0.07);
    border-radius: 1rem;
    padding: 0.85rem 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    transition: all 0.2s ease;
}

.bo-pay-item:hover {
    border-color: rgba(245, 158, 11, 0.30);
    background: var(--th-surface-elevated);
}

.bo-pay-info {
    min-width: 0;
}

.bo-pay-name {
    font-size: 0.76rem;
    color: var(--th-text-muted);
    font-weight: 600;
    display: block;
}

.bo-pay-num {
    font-size: 1rem;
    font-weight: 700;
    color: #ffffff;
    letter-spacing: 0.02em;
    font-family: var(--apple-font);
    font-variant-numeric: tabular-nums;
}

.bo-copy-btn {
    flex-shrink: 0;
    padding: 0.4rem 0.85rem;
    border-radius: 0.65rem;
    font-size: 0.78rem;
    font-weight: 700;
    background: rgba(245, 158, 11, 0.12);
    border: 1px solid rgba(245, 158, 11, 0.4);
    color: #fbbf24;
    cursor: pointer;
    transition: all 0.15s ease;
}

.bo-copy-btn:hover {
    background: rgba(245, 158, 11, 0.2);
    color: #fde68a;
}

.bo-copy-btn:active {
    transform: scale(0.95);
}

.bo-pay-instructions {
    background: rgba(245, 158, 11, 0.06);
    border: 1px dashed rgba(245, 158, 11, 0.3);
    border-radius: 0.9rem;
    padding: 0.75rem 0.9rem;
    font-size: 0.85rem;
    color: #fde68a;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin-bottom: 1rem;
}

.bo-required-amount-val {
    font-family: var(--apple-font);
    font-size: 1.15rem;
    font-weight: 800;
    color: #ffffff;
    font-variant-numeric: tabular-nums;
}

/* 6. RECEIPT DROPZONE & COMPLETED STATE */
.bo-upload-wrap {
    margin-top: 0.75rem;
}

.bo-dropzone {
    display: block;
    cursor: pointer;
    border: 2px dashed rgba(255, 255, 255, 0.14);
    border-radius: 1rem;
    background: var(--th-surface-dark);
    padding: 1.1rem 1rem;
    text-align: center;
    transition: all 0.25s ease;
    -webkit-tap-highlight-color: transparent;
}

.bo-dropzone:hover {
    border-color: rgba(245, 158, 11, 0.5);
    background: rgba(32, 13, 18, 0.65);
}

.bo-dropzone.has-file {
    border-style: solid;
    border-color: rgba(16, 185, 129, 0.5);
    background: rgba(16, 185, 129, 0.06);
}

.bo-drop-icon {
    font-size: 2rem;
    line-height: 1;
    margin-bottom: 0.35rem;
    display: block;
}

.bo-drop-title {
    font-size: 0.9rem;
    font-weight: 700;
    color: #ffffff;
}

.bo-drop-subtitle {
    font-size: 0.72rem;
    color: var(--th-text-muted);
    margin-top: 0.2rem;
}

.bo-preview-box {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    padding: 0.25rem;
    text-align: right;
}

.bo-preview-thumb {
    width: 58px;
    height: 58px;
    border-radius: 0.65rem;
    object-fit: cover;
    border: 1px solid rgba(255, 255, 255, 0.2);
    flex-shrink: 0;
}

.bo-preview-meta {
    min-width: 0;
    flex: 1;
}

.bo-preview-status {
    font-size: 0.85rem;
    font-weight: 700;
    color: #34d399;
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

.bo-preview-name {
    font-size: 0.75rem;
    color: var(--th-text-muted);
    margin-top: 0.15rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.bo-preview-change-btn {
    font-size: 0.75rem;
    font-weight: 600;
    color: #fbbf24;
    text-decoration: underline;
    margin-top: 0.2rem;
    display: inline-block;
}

/* 7. CUSTOMER FORM FIELDS */
.bo-field-group {
    margin-bottom: 0.85rem;
}

.bo-field-label {
    display: block;
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--th-text-muted);
    margin-bottom: 0.35rem;
}

.bo-input {
    width: 100%;
    background: rgba(11, 4, 7, 0.65);
    border: 1px solid rgba(255, 255, 255, 0.10);
    border-radius: 0.85rem;
    padding: 0.75rem 0.9rem;
    font-size: 0.95rem;
    color: #ffffff;
    font-family: var(--apple-font);
    transition: all 0.2s ease;
    box-sizing: border-box;
    -webkit-appearance: none;
}

.bo-input:focus {
    outline: none;
    border-color: #f59e0b;
    background: rgba(18, 7, 11, 0.90);
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.18);
}

.bo-input::placeholder {
    color: var(--th-text-dim);
}

/* Attendee card */
.bo-attendee-card {
    background: var(--th-surface-dark);
    border: 1px solid rgba(255, 255, 255, 0.06);
    border-radius: 1rem;
    padding: 0.9rem;
    margin-bottom: 0.75rem;
}

.bo-attendee-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.65rem;
    font-size: 0.8rem;
    font-weight: 700;
    color: #fbbf24;
}

/* 8. NEXT BUTTON WITH VISUAL ACTIVATION */
.bo-next-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    width: 100%;
    margin-top: 1rem;
    padding: 0.85rem 1rem;
    border-radius: 0.85rem;
    background: rgba(18, 7, 11, 0.50);
    border: 1px dashed rgba(255, 255, 255, 0.10);
    color: var(--th-text-dim);
    font-size: 0.9rem;
    font-weight: 600;
    font-family: var(--apple-font);
    cursor: pointer;
    transition: all 0.25s ease;
    opacity: 0.7;
    touch-action: manipulation;
}

.bo-next-btn.is-active-cta {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), rgba(245, 158, 11, 0.08));
    border: 1.5px solid rgba(245, 158, 11, 0.65);
    color: #fbbf24;
    font-weight: 700;
    box-shadow: 0 4px 20px -3px rgba(245, 158, 11, 0.35);
    opacity: 1;
}

.bo-next-btn.is-active-cta:hover {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.28), rgba(245, 158, 11, 0.15));
    border-color: #fbbf24;
    color: #fde68a;
    transform: translateY(-1px);
    box-shadow: 0 6px 25px -4px rgba(245, 158, 11, 0.45);
}

/* 9. FINAL SUBMISSION CTA — CLEAN WITHOUT DECORATIVE SPINNER */
.bo-submit-area {
    margin-top: 0.75rem;
    text-align: center;
}

#submitBtn {
    width: 100%;
    padding: 1.05rem 1.5rem;
    border-radius: 1.15rem;
    font-size: 1.05rem;
    font-weight: 800;
    font-family: var(--apple-font);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    cursor: pointer;
    transition: all 0.25s ease;
    border: none;
    -webkit-tap-highlight-color: transparent;
    touch-action: manipulation;
}

#submitBtn.is-muted {
    background: rgba(18, 7, 11, 0.55);
    color: var(--th-text-dim);
    border: 1px dashed rgba(255, 255, 255, 0.12);
    box-shadow: none;
}

#submitBtn.is-ready {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 60%, #d97706 100%);
    color: #000000;
    box-shadow: 0 10px 30px -5px rgba(245, 158, 11, 0.5), 0 0 15px rgba(251, 191, 36, 0.3);
}

#submitBtn.is-ready:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 35px -5px rgba(245, 158, 11, 0.6), 0 0 25px rgba(251, 191, 36, 0.4);
}

#submitBtn.is-ready:active {
    transform: scale(0.98);
}

#submitBtn.is-submitting {
    opacity: 0.85;
    pointer-events: none;
}

.bo-submit-hint {
    margin-top: 0.65rem;
    font-size: 0.78rem;
    color: var(--th-text-dim);
    line-height: 1.4;
}

.bo-submit-hint.ready {
    color: #34d399;
}

/* Real submit network spinner */
.bo-spinner {
    width: 20px;
    height: 20px;
    border: 2.5px solid rgba(0, 0, 0, 0.25);
    border-top-color: #000;
    border-radius: 50%;
    animation: boSpin 0.7s linear infinite;
    display: inline-block;
}

@keyframes boSpin {
    to { transform: rotate(360deg); }
}

/* Guided Attention Shake */
@keyframes boShake {
    0%, 100% { transform: translateX(0); }
    20%, 60% { transform: translateX(-6px); }
    40%, 80% { transform: translateX(6px); }
}

.bo-shake {
    animation: boShake 0.45s cubic-bezier(.36,.07,.19,.97) both;
    border-color: #ef4444 !important;
}

/* Global copy toast */
.bo-toast {
    position: fixed;
    bottom: 2rem;
    left: 50%;
    transform: translateX(-50%) translateY(20px);
    background: #10b981;
    color: #ffffff;
    font-weight: 700;
    font-size: 0.85rem;
    font-family: var(--apple-font);
    padding: 0.6rem 1.25rem;
    border-radius: 9999px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
    opacity: 0;
    visibility: hidden;
    transition: all 0.25s ease;
    z-index: 999;
    pointer-events: none;
}

.bo-toast.show {
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) translateY(0);
}
</style>

<div class="boxoffice-container">

    {{-- 1. APPLE-INSPIRED TRANSLUCENT GLASS FLOATING STEP NAVIGATION --}}
    <div class="bo-sticky-nav-wrap" id="stickyNavWrap">
        <nav class="bo-glass-pill" aria-label="مراحل الحجز">
            <div class="bo-step-item is-active" id="stepNav-1" onclick="navigateToStep(1)">
                <span class="bo-step-num">1</span>
                <span class="bo-step-name">التذاكر</span>
            </div>
            <div class="bo-step-item is-inactive" id="stepNav-2" onclick="navigateToStep(2)">
                <span class="bo-step-num">2</span>
                <span class="bo-step-name">التحويل</span>
            </div>
            <div class="bo-step-item is-inactive" id="stepNav-3" onclick="navigateToStep(3)">
                <span class="bo-step-num">3</span>
                <span class="bo-step-name">البيانات</span>
            </div>
            <div class="bo-step-item is-inactive" id="stepNav-4" onclick="navigateToStep(4)">
                <span class="bo-step-num">4</span>
                <span class="bo-step-name">الإرسال</span>
            </div>
        </nav>
    </div>

    {{-- Error messages if any --}}
    @if ($errors->any())
        <div role="alert" class="mb-5 p-4 rounded-xl bg-red-950/60 border border-red-500/40 text-red-200 text-sm">
            <p class="font-bold mb-1">يرجى مراجعة الملاحظات التالية:</p>
            <ul class="list-disc list-inside space-y-1 text-xs">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('bookings.store', $showTime) }}"
          method="POST"
          enctype="multipart/form-data"
          id="bookingForm"
          novalidate>
        @csrf

        {{-- Idempotency token --}}
        <input type="hidden" name="idempotency_token" id="idempotencyToken" value="">
        <input type="hidden" name="tickets_count" id="tickets_count" value="{{ (int) old('tickets_count', 1) }}">

        {{-- ============================================================
             SECTION 1: الموعد وعدد التذاكر (Showtime & Tickets)
        ============================================================ --}}
        <section id="section-tickets" class="bo-section is-active-focus is-section-done">
            <div class="bo-section-header">
                <h2 class="bo-section-title">الموعد وعدد التذاكر</h2>
                <span class="bo-status-pill status-done" id="status-badge-1">✓ تم التحديد</span>
            </div>

            {{-- Stable Showtime Switcher (Deterministic positions) --}}
            @if ($allShowTimes->count() > 1)
                <label class="bo-field-label">اختر موعد العرض المناسب:</label>
                <div class="bo-showtimes-grid">
                    @foreach ($allShowTimes as $index => $st)
                        @php
                            $isCurrent = ($st->id === $showTime->id);
                            $stDate = Carbon::parse($st->date);
                            $stTime = Carbon::parse($st->time);
                            $stTimeStr = $stTime->format('g:i') . ($stTime->format('A') === 'PM' ? ' مساءً' : ' صباحاً');
                            $slotName = ($index === 0) ? 'العرض الأول' : 'العرض الثاني';
                        @endphp

                        @if ($isCurrent)
                            <div class="bo-showtime-card is-active" aria-current="true">
                                <div class="bo-showtime-label">
                                    <span>{{ $slotName }}</span>
                                    <span class="text-xs text-amber-300 font-bold">✓ الموعد المختار</span>
                                </div>
                                <span class="bo-showtime-time">{{ $stTimeStr }}</span>
                                <span class="bo-showtime-date">{{ $stDate->format('d-m-Y') }}</span>
                            </div>
                        @else
                            <a href="{{ route('bookings.create', $st) }}"
                               class="bo-showtime-card"
                               onclick="preserveTicketsCount(this)">
                                <div class="bo-showtime-label">
                                    <span>{{ $slotName }}</span>
                                    <span class="text-xs text-gray-400">اضغط للاختيار</span>
                                </div>
                                <span class="bo-showtime-time">{{ $stTimeStr }}</span>
                                <span class="bo-showtime-date">{{ $stDate->format('d-m-Y') }}</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            @endif

            {{-- Tactile Ticket Counter — Strict Minus (-) on LEFT, Plus (+) on RIGHT --}}
            <div class="bo-counter-box" id="ticketCounterBox">
                <span class="text-xs text-gray-400">حدد عدد التذاكر المطلوبة:</span>
                <div class="bo-counter-controls" dir="ltr">
                    <button type="button"
                            class="bo-counter-btn"
                            onclick="changeTicketCount(-1)"
                            aria-label="إنقاص عدد التذاكر">−</button>

                    <div class="bo-counter-display">
                        <span class="bo-counter-number" id="ticketCountDisplay">1</span>
                        <span class="bo-counter-sub" id="ticketWordLabel">تذكرة واحدة</span>
                    </div>

                    <button type="button"
                            class="bo-counter-btn"
                            onclick="changeTicketCount(1)"
                            aria-label="زيادة عدد التذاكر">+</button>
                </div>

                {{-- Live Ledger with English digits --}}
                <div class="bo-counter-ledger">
                    <span>الإجمالي:</span>
                    <span class="bo-counter-price-val" id="totalPriceDisplay">{{ (int)$showTime->ticket_price }}</span>
                    <span>جنيه مصري</span>
                </div>
            </div>

            <button type="button"
                    id="btnNextToPayment"
                    class="bo-next-btn is-active-cta">
                <span>التالي: التحويل والدفع</span>
                <span>↓</span>
            </button>
        </section>

        {{-- ============================================================
             SECTION 2: التحويل وإرفاق الإيصال (Payment & Screenshot)
        ============================================================ --}}
        <section id="section-payment" class="bo-section">
            <div class="bo-section-header">
                <h2 class="bo-section-title">التحويل وإرفاق الإيصال</h2>
                <span class="bo-status-pill status-pending" id="status-badge-2">بانتظار الإيصال</span>
            </div>

            {{-- Dynamic instructions with English digits --}}
            <div class="bo-pay-instructions">
                <span>المبلغ المطلوب تحويله: <strong class="bo-required-amount-val" id="requiredPayAmount">{{ (int)$showTime->ticket_price }}</strong> ج.م</span>
                <span class="text-xs text-amber-200/90">حوّل المبلغ على أحد الأرقام التالية:</span>
            </div>

            {{-- Payment Accounts: only show configured real numbers, button says simply "نسخ" --}}
            <div class="bo-payment-list">
                @if ($transferWallet && !preg_match('/^0+$/', $transferWallet))
                    <div class="bo-pay-item">
                        <div class="bo-pay-info">
                            <span class="bo-pay-name">محفظة</span>
                            <span class="bo-pay-num" dir="ltr">{{ $transferWallet }}</span>
                        </div>
                        <button type="button"
                                class="bo-copy-btn"
                                onclick="copyText('{{ $transferWallet }}', this)">
                            نسخ
                        </button>
                    </div>
                @endif

                @if ($transferInsta && !preg_match('/^0+$/', $transferInsta))
                    <div class="bo-pay-item">
                        <div class="bo-pay-info">
                            <span class="bo-pay-name">حساب InstaPay</span>
                            <span class="bo-pay-num" dir="ltr">{{ $transferInsta }}</span>
                        </div>
                        <button type="button"
                                class="bo-copy-btn"
                                onclick="copyText('{{ $transferInsta }}', this)">
                            نسخ
                        </button>
                    </div>
                @endif
            </div>

            {{-- Screenshot Upload Area --}}
            <div class="bo-upload-wrap">
                <label for="screenshot"
                       id="dropzoneLabel"
                       class="bo-dropzone">
                    {{-- Empty State --}}
                    <div id="dropzoneEmpty">
                        <span class="bo-drop-icon" aria-hidden="true">📎</span>
                        <p class="bo-drop-title">اضغط لرفع لقطة شاشة التحويل (إيصال الدفع)</p>
                        <p class="bo-drop-subtitle">حجم أقل من 20MB • لقطات iPhone وجميع الأجهزة مدعومة</p>
                    </div>

                    {{-- Preview State --}}
                    <div id="dropzonePreview" class="hidden">
                        <div class="bo-preview-box">
                            <img id="previewImg" class="bo-preview-thumb" alt="معاينة الإيصال">
                            <div class="bo-preview-meta">
                                <span class="bo-preview-status">✓ تم إرفاق الإيصال بنجاح</span>
                                <p id="previewFileName" class="bo-preview-name"></p>
                                <span class="bo-preview-change-btn">اضغط لاستبدال الصورة</span>
                            </div>
                        </div>
                    </div>
                </label>

                <input type="file"
                       name="payment_screenshot"
                       id="screenshot"
                       accept="image/*"
                       class="sr-only"
                       required>

                <p id="screenshotError" class="hidden text-xs text-red-400 mt-2 font-semibold"></p>
            </div>

            <button type="button"
                    id="btnNextToDetails"
                    class="bo-next-btn">
                <span>التالي: بيانات الحجز</span>
                <span>↓</span>
            </button>
        </section>

        {{-- ============================================================
             SECTION 3: بيانات الحجز (Customer Information)
             Strictly titled: بيانات الحجز (No Next / Confirm buttons inside)
        ============================================================ --}}
        <section id="section-details" class="bo-section">
            <div class="bo-section-header">
                <h2 class="bo-section-title">بيانات الحجز</h2>
                <span class="bo-status-pill status-pending" id="status-badge-3">أدخل البيانات</span>
            </div>

            {{-- Attendee Fields Container --}}
            <div id="attendeesContainer" class="space-y-3">
                {{-- Injected dynamically by JS --}}
            </div>
        </section>

        {{-- ============================================================
             SECTION 4: تأكيد الحجز النهائي (Final Submission CTA)
        ============================================================ --}}
        <section id="section-submit" class="bo-section">
            <div class="bo-section-header">
                <h2 class="bo-section-title">تأكيد الحجز النهائي</h2>
                <span class="bo-status-pill status-pending" id="status-badge-4">في انتظار الإكمال</span>
            </div>

            <div class="bo-submit-area">
                <button type="button"
                        id="submitBtn"
                        class="is-muted">
                    <span id="submitText">إرسال طلب الحجز 🎟️</span>
                </button>

                <p id="submitHint" class="bo-submit-hint">
                    يرجى إكمال البيانات وإرفاق إيصال التحويل لتفعيل زر الحجز.
                </p>
            </div>
        </section>

    </form>

</div>

{{-- Global Copy Toast --}}
<div id="copyToast" class="bo-toast">✓ تم نسخ الرقم بنجاح</div>

<script>
(function() {
    'use strict';

    // --- Core State ---
    var maxTickets = {{ (int) $remaining }};
    var ticketPrice = {{ (int) $showTime->ticket_price }};
    var count = {{ (int) old('tickets_count', 1) }};
    if (count < 1) count = 1;
    if (count > maxTickets && maxTickets > 0) count = maxTickets;

    var currentStep = 1; // 1: Tickets, 2: Payment, 3: Details, 4: Submit

    // Elements
    var countDisplay      = document.getElementById('ticketCountDisplay');
    var wordLabel         = document.getElementById('ticketWordLabel');
    var totalPriceDisplay = document.getElementById('totalPriceDisplay');
    var requiredPayAmount = document.getElementById('requiredPayAmount');
    var ticketsInput      = document.getElementById('tickets_count');
    var ticketCounterBox  = document.getElementById('ticketCounterBox');
    var attendeesContainer= document.getElementById('attendeesContainer');
    var screenshotInput   = document.getElementById('screenshot');
    var dropzoneLabel     = document.getElementById('dropzoneLabel');
    var dropzoneEmpty     = document.getElementById('dropzoneEmpty');
    var dropzonePreview   = document.getElementById('dropzonePreview');
    var previewImg        = document.getElementById('previewImg');
    var previewFileName   = document.getElementById('previewFileName');
    var screenshotError   = document.getElementById('screenshotError');
    var submitBtn         = document.getElementById('submitBtn');
    var submitText        = document.getElementById('submitText');
    var submitHint        = document.getElementById('submitHint');
    var bookingForm       = document.getElementById('bookingForm');
    var copyToast         = document.getElementById('copyToast');
    var stickyNavWrap     = document.getElementById('stickyNavWrap');

    // Next CTA Buttons
    var btnNextToPayment  = document.getElementById('btnNextToPayment');
    var btnNextToDetails  = document.getElementById('btnNextToDetails');

    // Sticky Step Nav Items (Pure Typography, 1-4)
    var stepNav1 = document.getElementById('stepNav-1');
    var stepNav2 = document.getElementById('stepNav-2');
    var stepNav3 = document.getElementById('stepNav-3');
    var stepNav4 = document.getElementById('stepNav-4');

    var badge1 = document.getElementById('status-badge-1');
    var badge2 = document.getElementById('status-badge-2');
    var badge3 = document.getElementById('status-badge-3');
    var badge4 = document.getElementById('status-badge-4');

    var section1 = document.getElementById('section-tickets');
    var section2 = document.getElementById('section-payment');
    var section3 = document.getElementById('section-details');
    var section4 = document.getElementById('section-submit');

    var sections = [
        { num: 1, el: section1 },
        { num: 2, el: section2 },
        { num: 3, el: section3 },
        { num: 4, el: section4 }
    ];

    var oldNames  = @json(old('names', []));
    var oldPhones = @json(old('phones', []));

    // --- Idempotency Token ---
    var tokenKey = 'elsar5a_booking_token_show_{{ $showTime->id }}';
    var tokenEl  = document.getElementById('idempotencyToken');
    var savedToken = null;
    try { savedToken = sessionStorage.getItem(tokenKey); } catch (e) {}
    if (!savedToken) {
        if (window.crypto && crypto.randomUUID) {
            savedToken = crypto.randomUUID();
        } else {
            savedToken = 'idem_' + Date.now() + '_' + Math.random().toString(36).slice(2, 10);
        }
        try { sessionStorage.setItem(tokenKey, savedToken); } catch (e) {}
    }
    if (tokenEl) tokenEl.value = savedToken;

    // --- Smooth Scrolling with Section Focus ---
    var isScrollingProgrammatically = false;
    window.scrollToSection = function(id) {
        var el = document.getElementById(id);
        if (el) {
            isScrollingProgrammatically = true;
            el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            el.classList.add('is-active-focus');
            setTimeout(function() {
                el.classList.remove('is-active-focus');
                isScrollingProgrammatically = false;
            }, 800);
        }
    };

    // --- Ticket Counter Handling ---
    window.changeTicketCount = function(delta) {
        var nextCount = count + delta;
        if (nextCount < 1) return;
        if (nextCount > maxTickets) {
            flashMaxNotice();
            return;
        }

        // Preserve current inputs before re-render
        stashCurrentInputs();

        count = nextCount;
        updateTicketUI();
        renderAttendeeFields();
        recomputeAllStates();
    };

    function stashCurrentInputs() {
        var nInputs = attendeesContainer.querySelectorAll('input[name="names[]"]');
        var pInputs = attendeesContainer.querySelectorAll('input[name="phones[]"]');
        oldNames = Array.prototype.map.call(nInputs, function(inp) { return inp.value; });
        oldPhones = Array.prototype.map.call(pInputs, function(inp) { return inp.value; });
    }

    function updateTicketUI() {
        countDisplay.textContent = count;
        ticketsInput.value = count;

        var total = count * ticketPrice;
        // MUST use English digits for all monetary numbers
        totalPriceDisplay.textContent = total.toLocaleString('en-US');
        if (requiredPayAmount) requiredPayAmount.textContent = total.toLocaleString('en-US');

        if (count === 1) {
            wordLabel.textContent = 'تذكرة واحدة';
        } else if (count === 2) {
            wordLabel.textContent = 'تذكرتان';
        } else if (count <= 10) {
            wordLabel.textContent = count + ' تذاكر';
        } else {
            wordLabel.textContent = count + ' تذكرة';
        }
    }

    function flashMaxNotice() {
        if (ticketCounterBox) {
            ticketCounterBox.classList.add('bo-shake');
            setTimeout(function() { ticketCounterBox.classList.remove('bo-shake'); }, 600);
        }
        if (countDisplay.animate) {
            countDisplay.animate([
                { transform: 'scale(1)', color: '#fff' },
                { transform: 'scale(1.25)', color: '#ef4444' },
                { transform: 'scale(1)', color: '#fff' }
            ], { duration: 300 });
        }
    }

    // Preserve ticket count across showtime switch links
    window.preserveTicketsCount = function(link) {
        try {
            sessionStorage.setItem('elsar5a_preferred_tickets', count);
        } catch (e) {}
    };

    // Restore preferred tickets if set
    try {
        var savedTickets = sessionStorage.getItem('elsar5a_preferred_tickets');
        if (savedTickets) {
            var parsed = parseInt(savedTickets, 10);
            if (!isNaN(parsed) && parsed >= 1 && parsed <= maxTickets) {
                count = parsed;
            }
            sessionStorage.removeItem('elsar5a_preferred_tickets');
        }
    } catch (e) {}

    // --- Render Ticket Attendee Fields ---
    // Every ticket is clearly numbered: "التذكرة 1", "التذكرة 2", "التذكرة 3", etc. (NO "مرافق")
    // WhatsApp field label explicitly states purpose: "رقم الواتساب لاستلام التذكرة *"
    function renderAttendeeFields() {
        attendeesContainer.innerHTML = '';

        for (var i = 1; i <= count; i++) {
            var idx = i - 1;
            var valName = String(oldNames[idx] || '').replace(/"/g, '&quot;');
            var valPhone = String(oldPhones[idx] || '').replace(/"/g, '&quot;');

            var card = document.createElement('div');
            card.className = 'bo-attendee-card';

            card.innerHTML =
                '<div class="bo-attendee-header">' +
                    '<span>🎟️</span>' +
                    '<span>التذكرة ' + i + '</span>' +
                '</div>' +
                '<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">' +
                    '<div class="bo-field-group">' +
                        '<label class="bo-field-label">الاسم *</label>' +
                        '<input type="text" name="names[]" value="' + valName + '"' +
                               ' class="bo-input" placeholder="الاسم" required autocomplete="name">' +
                    '</div>' +
                    '<div class="bo-field-group">' +
                        '<label class="bo-field-label">رقم الواتساب لاستلام التذكرة *</label>' +
                        '<input type="tel" name="phones[]" value="' + valPhone + '"' +
                               ' class="bo-input" placeholder="01xxxxxxxxx" required ' + (i === 1 ? 'autocomplete="tel"' : '') + ' dir="ltr">' +
                    '</div>' +
                '</div>';

            attendeesContainer.appendChild(card);
        }

        // Attach listeners
        var inputs = attendeesContainer.querySelectorAll('input');
        inputs.forEach(function(inp) {
            inp.addEventListener('input', recomputeAllStates);
            inp.addEventListener('blur', recomputeAllStates);
        });
    }

    // --- Screenshot Upload ---
    screenshotInput.addEventListener('change', function() {
        screenshotError.classList.add('hidden');
        var file = screenshotInput.files && screenshotInput.files[0];

        if (!file) {
            dropzoneEmpty.classList.remove('hidden');
            dropzonePreview.classList.add('hidden');
            dropzoneLabel.classList.remove('has-file');
            recomputeAllStates();
            return;
        }

        // Check size: 20MB limit
        if (file.size > 20 * 1024 * 1024) {
            screenshotError.textContent = 'حجم الصورة أكبر من 20 ميجابايت — يرجى اختيار صورة أصغر.';
            screenshotError.classList.remove('hidden');
            screenshotInput.value = '';
            dropzoneEmpty.classList.remove('hidden');
            dropzonePreview.classList.add('hidden');
            dropzoneLabel.classList.remove('has-file');
            recomputeAllStates();
            return;
        }

        var objUrl = URL.createObjectURL(file);
        previewImg.src = objUrl;
        previewImg.onload = function() { URL.revokeObjectURL(objUrl); };

        previewFileName.textContent = file.name + ' (' + (file.size / 1024).toFixed(0) + ' KB)';
        dropzoneEmpty.classList.add('hidden');
        dropzonePreview.classList.remove('hidden');
        dropzoneLabel.classList.add('has-file');

        recomputeAllStates();
    });

    // --- Clipboard Copy ---
    window.copyText = function(text, btn) {
        if (!text) return;
        var copySuccess = function() {
            showToast('✓ تم نسخ الرقم بنجاح');
            if (btn) {
                btn.textContent = 'تم النسخ ✓';
                btn.classList.add('bg-emerald-500/20', 'text-emerald-400', 'border-emerald-500/50');
                setTimeout(function() {
                    btn.textContent = 'نسخ';
                    btn.classList.remove('bg-emerald-500/20', 'text-emerald-400', 'border-emerald-500/50');
                }, 1500);
            }
        };

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(copySuccess, copySuccess);
        } else {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy'); } catch (e) {}
            document.body.removeChild(ta);
            copySuccess();
        }
    };

    function showToast(msg) {
        copyToast.textContent = msg;
        copyToast.classList.add('show');
        setTimeout(function() {
            copyToast.classList.remove('show');
        }, 1800);
    }

    // --- State & Validation Computation ---
    function validateStep1() {
        return (count >= 1 && count <= maxTickets);
    }

    function validateStep2() {
        return (screenshotInput.files && screenshotInput.files.length > 0);
    }

    function validateStep3() {
        var names = attendeesContainer.querySelectorAll('input[name="names[]"]');
        var phones = attendeesContainer.querySelectorAll('input[name="phones[]"]');
        if (names.length === 0 || phones.length === 0) return false;

        for (var i = 0; i < names.length; i++) {
            if (!names[i].value.trim()) return false;
        }
        for (var j = 0; j < phones.length; j++) {
            var p = phones[j].value.trim();
            if (!p || p.length < 8) return false;
        }
        return true;
    }

    // --- Dynamic Step Navigation Color Management ---
    // Rule:
    // ACTIVE / CURRENT STEP  -> YELLOW (#fbbf24) "YOU ARE HERE"
    // COMPLETED STEP         -> GREEN  (#10b981)
    // INACTIVE / NOT REACHED -> DIMMED (rgba(255,255,255,0.45))
    function updateStepNavDisplay() {
        var navItems = [
            { el: stepNav1, isDone: validateStep1() },
            { el: stepNav2, isDone: validateStep2() },
            { el: stepNav3, isDone: validateStep3() },
            { el: stepNav4, isDone: (validateStep1() && validateStep2() && validateStep3()) }
        ];

        for (var i = 0; i < navItems.length; i++) {
            var stepNum = i + 1;
            var item = navItems[i];

            if (stepNum === currentStep) {
                // Current section being viewed: MUST be YELLOW
                item.el.className = 'bo-step-item is-active';
            } else if (item.isDone) {
                // Completed section: GREEN
                item.el.className = 'bo-step-item is-completed';
            } else {
                // Inactive / unreached: DIM
                item.el.className = 'bo-step-item is-inactive';
            }
        }
    }

    function recomputeAllStates() {
        var step1Done = validateStep1();
        var step2Done = validateStep2();
        var step3Done = validateStep3();
        var allReady  = step1Done && step2Done && step3Done && (maxTickets > 0);

        // Section 1: Tickets
        badge1.className = 'bo-status-pill status-done';
        badge1.textContent = '✓ تم التحديد';
        section1.classList.add('is-section-done');
        btnNextToPayment.classList.add('is-active-cta');

        // Section 2: Payment
        if (step2Done) {
            badge2.className = 'bo-status-pill status-done';
            badge2.textContent = '✓ الإيصال مرفوع';
            section2.classList.add('is-section-done');
            btnNextToDetails.classList.add('is-active-cta');
            screenshotError.classList.add('hidden');
        } else {
            badge2.className = 'bo-status-pill status-pending';
            badge2.textContent = 'بانتظار الإيصال';
            section2.classList.remove('is-section-done');
            btnNextToDetails.classList.remove('is-active-cta');
        }

        // Section 3: Attendees
        if (step3Done) {
            badge3.className = 'bo-status-pill status-done';
            badge3.textContent = '✓ مكتملة (' + count + ')';
            section3.classList.add('is-section-done');
        } else {
            badge3.className = 'bo-status-pill status-pending';
            badge3.textContent = 'أدخل البيانات';
            section3.classList.remove('is-section-done');
        }

        // Section 4: Final Submission
        if (allReady) {
            submitBtn.classList.remove('is-muted');
            submitBtn.classList.add('is-ready');
            submitHint.textContent = '✓ جميع البيانات جاهزة — اضغط لإرسال طلب الحجز فوراً.';
            submitHint.classList.add('ready');

            badge4.className = 'bo-status-pill status-done';
            badge4.textContent = 'جاهز للإرسال';
            section4.classList.add('is-section-done');
        } else {
            submitBtn.classList.remove('is-ready');
            submitBtn.classList.add('is-muted');
            submitHint.classList.remove('ready');

            var missing = [];
            if (!step2Done) missing.push('إرفاق إيصال التحويل');
            if (!step3Done) missing.push('استكمال بيانات الأسماء وأرقام الواتساب');

            submitHint.textContent = 'المتبقي لإرسال الحجز: ' + missing.join(' و ') + '.';

            badge4.className = 'bo-status-pill status-pending';
            badge4.textContent = 'في انتظار الإكمال';
            section4.classList.remove('is-section-done');
        }

        updateStepNavDisplay();
    }

    // --- Step Navigation Click Handlers ---
    window.navigateToStep = function(stepNum) {
        if (stepNum === 1) {
            currentStep = 1;
            updateStepNavDisplay();
            scrollToSection('section-tickets');
        } else if (stepNum === 2) {
            currentStep = 2;
            updateStepNavDisplay();
            scrollToSection('section-payment');
        } else if (stepNum === 3) {
            if (!validateStep2()) {
                screenshotError.textContent = 'يرجى إرفاق إيصال التحويل أولاً للمتابعة.';
                screenshotError.classList.remove('hidden');
                dropzoneLabel.classList.add('bo-shake');
                setTimeout(function() { dropzoneLabel.classList.remove('bo-shake'); }, 600);
                currentStep = 2;
                updateStepNavDisplay();
                scrollToSection('section-payment');
                return;
            }
            currentStep = 3;
            updateStepNavDisplay();
            scrollToSection('section-details');
        } else if (stepNum === 4) {
            if (!validateStep2()) {
                screenshotError.textContent = 'يرجى إرفاق إيصال التحويل أولاً للمتابعة.';
                screenshotError.classList.remove('hidden');
                dropzoneLabel.classList.add('bo-shake');
                setTimeout(function() { dropzoneLabel.classList.remove('bo-shake'); }, 600);
                currentStep = 2;
                updateStepNavDisplay();
                scrollToSection('section-payment');
                return;
            }
            if (!validateStep3()) {
                currentStep = 3;
                updateStepNavDisplay();
                scrollToSection('section-details');
                triggerMissingInputHighlight();
                return;
            }
            currentStep = 4;
            updateStepNavDisplay();
            scrollToSection('section-submit');
        }
    };

    // --- Next Button Handlers with User Guidance ---
    btnNextToPayment.addEventListener('click', function(e) {
        e.preventDefault();
        if (!validateStep1()) {
            flashMaxNotice();
            currentStep = 1;
            updateStepNavDisplay();
            scrollToSection('section-tickets');
            return;
        }
        currentStep = 2;
        updateStepNavDisplay();
        scrollToSection('section-payment');
    });

    btnNextToDetails.addEventListener('click', function(e) {
        e.preventDefault();
        if (!validateStep2()) {
            screenshotError.textContent = 'يرجى إرفاق صورة إيصال التحويل أولاً للمتابعة.';
            screenshotError.classList.remove('hidden');
            dropzoneLabel.classList.add('bo-shake');
            setTimeout(function() { dropzoneLabel.classList.remove('bo-shake'); }, 600);
            currentStep = 2;
            updateStepNavDisplay();
            scrollToSection('section-payment');
            return;
        }
        currentStep = 3;
        updateStepNavDisplay();
        scrollToSection('section-details');
        setTimeout(function() {
            var firstInput = attendeesContainer.querySelector('input');
            if (firstInput) firstInput.focus();
        }, 400);
    });

    function triggerMissingInputHighlight() {
        setTimeout(function() {
            var inputs = attendeesContainer.querySelectorAll('input');
            for (var i = 0; i < inputs.length; i++) {
                var inp = inputs[i];
                if (!inp.value.trim() || (inp.type === 'tel' && inp.value.trim().length < 8)) {
                    inp.classList.add('bo-shake');
                    inp.focus();
                    setTimeout(function(target) { target.classList.remove('bo-shake'); }, 600, inp);
                    break;
                }
            }
        }, 300);
    }

    // --- Scroll Listener for Sticky State & Viewport Step Tracking ---
    window.addEventListener('scroll', function() {
        var scrollY = window.scrollY || window.pageYOffset;

        // Sticky glass elevation activation
        if (stickyNavWrap) {
            if (scrollY > 30) {
                stickyNavWrap.classList.add('is-scrolled');
            } else {
                stickyNavWrap.classList.remove('is-scrolled');
            }
        }

        if (isScrollingProgrammatically) return;

        // Viewport scroll-spy: determine which section user is currently viewing
        var triggerPoint = scrollY + 160;
        for (var i = sections.length - 1; i >= 0; i--) {
            var sec = sections[i];
            if (sec.el && sec.el.offsetTop <= triggerPoint) {
                if (currentStep !== sec.num) {
                    currentStep = sec.num;
                    updateStepNavDisplay();
                }
                break;
            }
        }
    }, { passive: true });

    // --- Submission Button Click: Auto-jump to missing requirement if incomplete ---
    var isSubmitting = false;

    submitBtn.addEventListener('click', function(e) {
        if (isSubmitting) return;

        var step1Done = validateStep1();
        var step2Done = validateStep2();
        var step3Done = validateStep3();

        if (!step1Done) {
            flashMaxNotice();
            currentStep = 1;
            updateStepNavDisplay();
            scrollToSection('section-tickets');
            return;
        }

        if (!step2Done) {
            screenshotError.textContent = 'يرجى إرفاق إيصال التحويل أولاً لإتمام الحجز.';
            screenshotError.classList.remove('hidden');
            dropzoneLabel.classList.add('bo-shake');
            setTimeout(function() { dropzoneLabel.classList.remove('bo-shake'); }, 600);
            currentStep = 2;
            updateStepNavDisplay();
            scrollToSection('section-payment');
            return;
        }

        if (!step3Done) {
            currentStep = 3;
            updateStepNavDisplay();
            scrollToSection('section-details');
            triggerMissingInputHighlight();
            return;
        }

        // All valid: Execute real submission with network spinner
        isSubmitting = true;
        submitBtn.classList.remove('is-ready');
        submitBtn.classList.add('is-submitting');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="bo-spinner" aria-hidden="true"></span> <span>جاري إرسال طلب الحجز...</span>';
        submitHint.textContent = 'يرجى الانتظار ولا تغلق الصفحة حتى اكتمال معالجة الطلب.';
        bookingForm.submit();
    });

    bookingForm.addEventListener('submit', function(e) {
        if (!validateStep1() || !validateStep2() || !validateStep3()) {
            e.preventDefault();
            return false;
        }
    });

    // Reset state on bfcache navigation (iOS Safari back-button)
    window.addEventListener('pageshow', function(e) {
        if (e.persisted) {
            isSubmitting = false;
            submitBtn.disabled = false;
            submitBtn.classList.remove('is-submitting');
            submitBtn.innerHTML = '<span id="submitText">إرسال طلب الحجز 🎟️</span>';
            recomputeAllStates();
        }
    });

    // --- Initialize ---
    updateTicketUI();
    renderAttendeeFields();
    recomputeAllStates();

})();
</script>
@endsection
