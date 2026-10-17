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
   ELSAR5A THEATRE BOX OFFICE — PRECISE REFINED EXPERIENCE
   ============================================================ */
:root {
    --th-bg: #07090e;
    --th-card-bg: rgba(15, 20, 30, 0.88);
    --th-card-border: rgba(255, 255, 255, 0.08);
    --th-amber: #f59e0b;
    --th-amber-light: #fde68a;
    --th-amber-glow: rgba(245, 158, 11, 0.25);
    --th-emerald: #10b981;
    --th-emerald-glow: rgba(16, 185, 129, 0.25);
    --th-text-main: #f9fafb;
    --th-text-muted: #9ca3af;
    --th-text-dim: #6b7280;
}

html {
    scroll-behavior: smooth;
    scroll-padding-top: 140px;
}

.boxoffice-container {
    max-width: 780px;
    margin-inline: auto;
    padding: 0 1rem 3.5rem;
    color: var(--th-text-main);
    font-family: inherit;
}

@media (min-width: 640px) {
    .boxoffice-container {
        padding: 0 1.5rem 4rem;
    }
}

/* 1. STICKY STEP NAVIGATION — NO BOXES, PURE TYPOGRAPHY */
.bo-sticky-nav-wrap {
    position: sticky;
    top: 50px;
    z-index: 30;
    background: rgba(7, 9, 14, 0.94);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    margin-inline: -1rem;
    padding: 0.65rem 0.75rem;
    margin-bottom: 1.5rem;
}

@media (min-width: 640px) {
    .bo-sticky-nav-wrap {
        margin-inline: -1.5rem;
        padding: 0.75rem 1.5rem;
    }
}

.bo-steps-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    max-width: 480px;
    margin-inline: auto;
    padding-inline: 0.5rem;
}

.bo-step-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    cursor: pointer;
    user-select: none;
    transition: all 0.25s ease;
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    padding: 0.2rem 0.6rem;
}

.bo-step-num {
    font-size: 1.15rem;
    font-weight: 900;
    line-height: 1;
    margin-bottom: 0.25rem;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    transition: all 0.2s ease;
}

.bo-step-name {
    font-size: 0.8rem;
    font-weight: 700;
    line-height: 1.2;
    transition: all 0.2s ease;
}

/* Inactive / Dimmed */
.bo-step-item.is-inactive {
    opacity: 0.35;
    color: #9ca3af;
}

.bo-step-item.is-inactive:hover {
    opacity: 0.65;
}

/* Active / Current step */
.bo-step-item.is-active {
    opacity: 1;
    color: #10b981;
}

.bo-step-item.is-active .bo-step-num {
    color: #34d399;
    font-size: 1.3rem;
    text-shadow: 0 0 14px rgba(16, 185, 129, 0.5);
}

.bo-step-item.is-active .bo-step-name {
    color: #ffffff;
    font-weight: 800;
}

/* Completed step */
.bo-step-item.is-completed {
    opacity: 0.85;
    color: #10b981;
}

.bo-step-item.is-completed .bo-step-num {
    color: #10b981;
}

.bo-step-item.is-completed .bo-step-name {
    color: #a7f3d0;
}

/* 2. SECTION CARDS */
.bo-section {
    background: var(--th-card-bg);
    border: 1px solid var(--th-card-border);
    border-radius: 1.25rem;
    padding: 1.25rem;
    margin-bottom: 1.25rem;
    box-shadow: 0 15px 35px -15px rgba(0, 0, 0, 0.6);
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

@media (min-width: 640px) {
    .bo-section {
        padding: 1.5rem;
    }
}

.bo-section.is-active-focus {
    border-color: rgba(245, 158, 11, 0.4);
    box-shadow: 0 0 25px -8px var(--th-amber-glow);
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
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.05rem;
    font-weight: 800;
    color: #ffffff;
}

.bo-section-title-badge {
    width: 24px;
    height: 24px;
    border-radius: 0.5rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 900;
    background: rgba(245, 158, 11, 0.15);
    color: #fbbf24;
    border: 1px solid rgba(245, 158, 11, 0.3);
}

.bo-section.is-section-done .bo-section-title-badge {
    background: rgba(16, 185, 129, 0.15);
    color: #34d399;
    border-color: rgba(16, 185, 129, 0.4);
}

.bo-status-pill {
    font-size: 0.72rem;
    font-weight: 700;
    padding: 0.2rem 0.55rem;
    border-radius: 9999px;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
}

.bo-status-pill.status-pending {
    background: rgba(255, 255, 255, 0.05);
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
    border: 1.5px solid var(--th-card-border);
    background: rgba(255, 255, 255, 0.03);
    text-decoration: none;
    transition: all 0.2s ease;
    -webkit-tap-highlight-color: transparent;
}

.bo-showtime-card.is-active {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.14), rgba(245, 158, 11, 0.05));
    border-color: rgba(245, 158, 11, 0.65);
    box-shadow: 0 0 20px -5px var(--th-amber-glow);
}

.bo-showtime-card:not(.is-active) {
    opacity: 0.65;
}

.bo-showtime-card:not(.is-active):hover {
    opacity: 0.9;
    background: rgba(255, 255, 255, 0.06);
    border-color: rgba(255, 255, 255, 0.2);
}

.bo-showtime-label {
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--th-text-dim);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.bo-showtime-card.is-active .bo-showtime-label {
    color: #fbbf24;
}

.bo-showtime-time {
    font-size: 1.15rem;
    font-weight: 800;
    color: #ffffff;
    margin-top: 0.25rem;
    display: block;
}

.bo-showtime-date {
    font-size: 0.75rem;
    color: var(--th-text-muted);
    margin-top: 0.15rem;
}

/* 4. TACTILE TICKET COUNTER — STRICT PLUS ON RIGHT, MINUS ON LEFT */
.bo-counter-box {
    background: rgba(0, 0, 0, 0.25);
    border: 1px solid rgba(255, 255, 255, 0.06);
    border-radius: 1rem;
    padding: 1.1rem 1rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.85rem;
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
    border: 1.5px solid rgba(255, 255, 255, 0.15);
    background: rgba(255, 255, 255, 0.06);
    color: #ffffff;
    font-size: 1.6rem;
    font-weight: 800;
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
    background: rgba(255, 255, 255, 0.12);
    border-color: rgba(255, 255, 255, 0.3);
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
    font-size: 2.35rem;
    font-weight: 900;
    color: #ffffff;
    line-height: 1;
    font-variant-numeric: tabular-nums;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.bo-counter-sub {
    font-size: 0.75rem;
    color: var(--th-text-muted);
    margin-top: 0.25rem;
    display: block;
}

.bo-counter-ledger {
    font-size: 0.95rem;
    font-weight: 700;
    color: #fde68a;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.bo-counter-price-val {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 1.2rem;
    font-weight: 900;
    color: #ffffff;
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
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid var(--th-card-border);
    border-radius: 1rem;
    padding: 0.85rem 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    transition: all 0.2s ease;
}

.bo-pay-item:hover {
    border-color: rgba(255, 255, 255, 0.2);
    background: rgba(255, 255, 255, 0.05);
}

.bo-pay-info {
    min-width: 0;
}

.bo-pay-name {
    font-size: 0.72rem;
    color: var(--th-text-muted);
    font-weight: 700;
    display: block;
}

.bo-pay-num {
    font-size: 1rem;
    font-weight: 800;
    color: #ffffff;
    letter-spacing: 0.02em;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.bo-copy-btn {
    flex-shrink: 0;
    padding: 0.4rem 0.85rem;
    border-radius: 0.65rem;
    font-size: 0.78rem;
    font-weight: 800;
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
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 1.15rem;
    font-weight: 900;
    color: #ffffff;
}

/* 6. RECEIPT DROPZONE & COMPLETED STATE */
.bo-upload-wrap {
    margin-top: 0.75rem;
}

.bo-dropzone {
    display: block;
    cursor: pointer;
    border: 2px dashed rgba(255, 255, 255, 0.2);
    border-radius: 1rem;
    background: rgba(0, 0, 0, 0.2);
    padding: 1.1rem 1rem;
    text-align: center;
    transition: all 0.25s ease;
    -webkit-tap-highlight-color: transparent;
}

.bo-dropzone:hover {
    border-color: rgba(245, 158, 11, 0.5);
    background: rgba(245, 158, 11, 0.04);
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
    font-weight: 800;
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
    font-weight: 800;
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
    font-weight: 700;
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
    font-weight: 700;
    color: var(--th-text-muted);
    margin-bottom: 0.35rem;
}

.bo-input {
    width: 100%;
    background: rgba(0, 0, 0, 0.35);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 0.85rem;
    padding: 0.75rem 0.9rem;
    font-size: 0.95rem;
    color: #ffffff;
    transition: all 0.2s ease;
    box-sizing: border-box;
    -webkit-appearance: none;
}

.bo-input:focus {
    outline: none;
    border-color: #f59e0b;
    background: rgba(0, 0, 0, 0.55);
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);
}

.bo-input::placeholder {
    color: var(--th-text-dim);
}

/* Attendee card */
.bo-attendee-card {
    background: rgba(0, 0, 0, 0.2);
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
    font-size: 0.75rem;
    font-weight: 800;
    color: #fbbf24;
}

/* 8. NEXT BUTTON WITH VISUAL ACTIVATION */
.bo-next-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    width: 100%;
    margin-top: 1rem;
    padding: 0.75rem 1rem;
    border-radius: 0.85rem;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.08);
    color: #6b7280;
    font-size: 0.85rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.25s ease;
}

.bo-next-btn.is-active-cta {
    background: rgba(245, 158, 11, 0.14);
    border-color: rgba(245, 158, 11, 0.55);
    color: #fbbf24;
    font-weight: 800;
    box-shadow: 0 4px 18px -4px rgba(245, 158, 11, 0.3);
}

.bo-next-btn.is-active-cta:hover {
    background: rgba(245, 158, 11, 0.22);
    color: #fde68a;
    transform: translateY(-1px);
}

/* 9. FINAL SUBMISSION CTA — CLEAN WITHOUT DECORATIVE SPINNER */
.bo-submit-area {
    margin-top: 1.5rem;
    text-align: center;
}

#submitBtn {
    width: 100%;
    padding: 1.05rem 1.5rem;
    border-radius: 1.15rem;
    font-size: 1.1rem;
    font-weight: 900;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    cursor: pointer;
    transition: all 0.25s ease;
    border: none;
    -webkit-tap-highlight-color: transparent;
}

#submitBtn:not([disabled]) {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 60%, #d97706 100%);
    color: #000000;
    box-shadow: 0 10px 30px -5px rgba(245, 158, 11, 0.5), 0 0 15px rgba(251, 191, 36, 0.3);
}

#submitBtn:not([disabled]):hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 35px -5px rgba(245, 158, 11, 0.6), 0 0 25px rgba(251, 191, 36, 0.4);
}

#submitBtn:not([disabled]):active {
    transform: scale(0.98);
}

#submitBtn[disabled] {
    background: rgba(255, 255, 255, 0.07);
    color: var(--th-text-dim);
    border: 1px dashed rgba(255, 255, 255, 0.15);
    cursor: not-allowed;
    box-shadow: none;
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
    width: 18px;
    height: 18px;
    border: 2.5px solid rgba(0, 0, 0, 0.25);
    border-top-color: #000;
    border-radius: 50%;
    animation: boSpin 0.7s linear infinite;
    display: inline-block;
}

@keyframes boSpin {
    to { transform: rotate(360deg); }
}

/* Global copy toast */
.bo-toast {
    position: fixed;
    bottom: 2rem;
    left: 50%;
    transform: translateX(-50%) translateY(20px);
    background: #10b981;
    color: #ffffff;
    font-weight: 800;
    font-size: 0.85rem;
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

    {{-- 1. STICKY STEP NAVIGATION (NO BOXES, NUMBERS ABOVE LABELS, 1-4) --}}
    <div class="bo-sticky-nav-wrap">
        <nav class="bo-steps-bar" aria-label="مراحل الحجز">
            <div class="bo-step-item is-active" id="stepNav-1" onclick="scrollToSection('section-tickets')">
                <span class="bo-step-num">1</span>
                <span class="bo-step-name">التذاكر</span>
            </div>
            <div class="bo-step-item is-inactive" id="stepNav-2" onclick="scrollToSection('section-payment')">
                <span class="bo-step-num">2</span>
                <span class="bo-step-name">التحويل</span>
            </div>
            <div class="bo-step-item is-inactive" id="stepNav-3" onclick="scrollToSection('section-details')">
                <span class="bo-step-num">3</span>
                <span class="bo-step-name">البيانات</span>
            </div>
            <div class="bo-step-item is-inactive" id="stepNav-4" onclick="scrollToSection('section-submit')">
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
                <div class="bo-section-title">
                    <span class="bo-section-title-badge">1</span>
                    <span>الموعد وعدد التذاكر</span>
                </div>
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
            <div class="bo-counter-box">
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
                    class="bo-next-btn is-active-cta"
                    onclick="scrollToSection('section-payment')">
                <span>التالي: التحويل والدفع</span>
                <span>↓</span>
            </button>
        </section>

        {{-- ============================================================
             SECTION 2: التحويل وإرفاق الإيصال (Payment & Screenshot)
        ============================================================ --}}
        <section id="section-payment" class="bo-section">
            <div class="bo-section-header">
                <div class="bo-section-title">
                    <span class="bo-section-title-badge">2</span>
                    <span>التحويل وإرفاق الإيصال</span>
                </div>
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
                            <span class="bo-pay-name">📱 فودافون كاش / محفظة</span>
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
                            <span class="bo-pay-name">⚡ حساب InstaPay</span>
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
                    class="bo-next-btn"
                    onclick="scrollToSection('section-details')">
                <span>التالي: بيانات الحجز</span>
                <span>↓</span>
            </button>
        </section>

        {{-- ============================================================
             SECTION 3: بيانات الحجز (Customer Information)
        ============================================================ --}}
        <section id="section-details" class="bo-section">
            <div class="bo-section-header">
                <div class="bo-section-title">
                    <span class="bo-section-title-badge">3</span>
                    <span>بيانات الحجز والمرافقين</span>
                </div>
                <span class="bo-status-pill status-pending" id="status-badge-3">أدخل البيانات</span>
            </div>

            {{-- Attendee Fields Container --}}
            <div id="attendeesContainer" class="space-y-3">
                {{-- Injected dynamically by JS --}}
            </div>

            <button type="button"
                    id="btnNextToSubmit"
                    class="bo-next-btn"
                    onclick="scrollToSection('section-submit')">
                <span>التالي: تأكيد وإرسال الحجز</span>
                <span>↓</span>
            </button>
        </section>

        {{-- ============================================================
             SECTION 4: تأكيد وإرسال الحجز (Final Submission CTA)
        ============================================================ --}}
        <section id="section-submit" class="bo-section">
            <div class="bo-section-header">
                <div class="bo-section-title">
                    <span class="bo-section-title-badge">4</span>
                    <span>تأكيد الحجز النهائي</span>
                </div>
                <span class="bo-status-pill status-pending" id="status-badge-4">في انتظار الإكمال</span>
            </div>

            <div class="bo-submit-area">
                <button type="submit"
                        id="submitBtn"
                        disabled>
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

    // Elements
    var countDisplay      = document.getElementById('ticketCountDisplay');
    var wordLabel         = document.getElementById('ticketWordLabel');
    var totalPriceDisplay = document.getElementById('totalPriceDisplay');
    var requiredPayAmount = document.getElementById('requiredPayAmount');
    var ticketsInput      = document.getElementById('tickets_count');
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

    // Next CTA Buttons
    var btnNextToPayment  = document.getElementById('btnNextToPayment');
    var btnNextToDetails  = document.getElementById('btnNextToDetails');
    var btnNextToSubmit   = document.getElementById('btnNextToSubmit');

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

    // --- Smooth Scrolling ---
    window.scrollToSection = function(id) {
        var el = document.getElementById(id);
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            el.classList.add('is-active-focus');
            setTimeout(function() {
                el.classList.remove('is-active-focus');
            }, 1200);
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

    // --- Render Attendee Fields (Label and Placeholder: simply "الاسم") ---
    function renderAttendeeFields() {
        attendeesContainer.innerHTML = '';

        for (var i = 1; i <= count; i++) {
            var idx = i - 1;
            var valName = String(oldNames[idx] || '').replace(/"/g, '&quot;');
            var valPhone = String(oldPhones[idx] || '').replace(/"/g, '&quot;');

            var card = document.createElement('div');
            card.className = 'bo-attendee-card';

            if (i === 1) {
                card.innerHTML =
                    '<div class="bo-attendee-header">' +
                        '<span>👤</span>' +
                        '<span>التذكرة 1 — صاحب الحجز</span>' +
                    '</div>' +
                    '<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">' +
                        '<div class="bo-field-group">' +
                            '<label class="bo-field-label">الاسم *</label>' +
                            '<input type="text" name="names[]" value="' + valName + '"' +
                                   ' class="bo-input" placeholder="الاسم" required autocomplete="name">' +
                        '</div>' +
                        '<div class="bo-field-group">' +
                            '<label class="bo-field-label">رقم الواتساب *</label>' +
                            '<input type="tel" name="phones[]" value="' + valPhone + '"' +
                                   ' class="bo-input" placeholder="01xxxxxxxxx" required autocomplete="tel" dir="ltr">' +
                        '</div>' +
                    '</div>';
            } else {
                card.innerHTML =
                    '<div class="bo-attendee-header text-amber-300/90">' +
                        '<span>🎟️</span>' +
                        '<span>التذكرة ' + i + ' — بيانات المرافق</span>' +
                    '</div>' +
                    '<div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">' +
                        '<div class="bo-field-group mb-0">' +
                            '<label class="bo-field-label">اسم المرافق ' + i + ' *</label>' +
                            '<input type="text" name="names[]" value="' + valName + '"' +
                                   ' class="bo-input" placeholder="الاسم" required>' +
                        '</div>' +
                        '<div class="bo-field-group mb-0">' +
                            '<label class="bo-field-label">رقم واتساب المرافق ' + i + ' *</label>' +
                            '<input type="tel" name="phones[]" value="' + valPhone + '"' +
                                   ' class="bo-input" placeholder="01xxxxxxxxx" required dir="ltr">' +
                        '</div>' +
                    '</div>';
            }

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
                var original = btn.textContent;
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
    function areFieldsComplete() {
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

    function isScreenshotAttached() {
        return screenshotInput.files && screenshotInput.files.length > 0;
    }

    function recomputeAllStates() {
        var step1Done = (count >= 1 && count <= maxTickets);
        var step2Done = isScreenshotAttached();
        var step3Done = areFieldsComplete();
        var allReady  = step1Done && step2Done && step3Done && (maxTickets > 0);

        // Step 1: Tickets
        setStepNavState(stepNav1, true, false);
        badge1.className = 'bo-status-pill status-done';
        badge1.textContent = '✓ تم التحديد';
        section1.classList.add('is-section-done');
        btnNextToPayment.classList.add('is-active-cta');

        // Step 2: Payment & Screenshot
        if (step2Done) {
            setStepNavState(stepNav2, true, false);
            badge2.className = 'bo-status-pill status-done';
            badge2.textContent = '✓ الإيصال مرفوع';
            section2.classList.add('is-section-done');
            btnNextToDetails.classList.add('is-active-cta');
        } else {
            setStepNavState(stepNav2, false, true);
            badge2.className = 'bo-status-pill status-pending';
            badge2.textContent = 'بانتظار الإيصال';
            section2.classList.remove('is-section-done');
            btnNextToDetails.classList.remove('is-active-cta');
        }

        // Step 3: Attendees
        if (step3Done) {
            setStepNavState(stepNav3, true, false);
            badge3.className = 'bo-status-pill status-done';
            badge3.textContent = '✓ مكتملة (' + count + ')';
            section3.classList.add('is-section-done');
            btnNextToSubmit.classList.add('is-active-cta');
        } else {
            setStepNavState(stepNav3, false, step2Done);
            badge3.className = 'bo-status-pill status-pending';
            badge3.textContent = 'أدخل البيانات';
            section3.classList.remove('is-section-done');
            btnNextToSubmit.classList.remove('is-active-cta');
        }

        // Step 4: Submission
        if (allReady) {
            submitBtn.disabled = false;
            submitHint.textContent = '✓ جميع البيانات جاهزة — اضغط لإرسال طلب الحجز فوراً.';
            submitHint.classList.add('ready');

            setStepNavState(stepNav4, true, false);
            badge4.className = 'bo-status-pill status-done';
            badge4.textContent = 'جاهز للإرسال';
            section4.classList.add('is-section-done');
        } else {
            submitBtn.disabled = true;
            submitHint.classList.remove('ready');

            var missing = [];
            if (!step2Done) missing.push('إرفاق إيصال التحويل');
            if (!step3Done) missing.push('استكمال بيانات الأسماء وأرقام الواتساب');

            submitHint.textContent = 'المتبقي لإرسال الحجز: ' + missing.join(' و ') + '.';

            setStepNavState(stepNav4, false, step3Done);
            badge4.className = 'bo-status-pill status-pending';
            badge4.textContent = 'في انتظار الإكمال';
            section4.classList.remove('is-section-done');
        }
    }

    function setStepNavState(navEl, isDone, isActive) {
        if (isDone) {
            navEl.className = 'bo-step-item is-completed';
        } else if (isActive) {
            navEl.className = 'bo-step-item is-active';
        } else {
            navEl.className = 'bo-step-item is-inactive';
        }
    }

    // --- Form Submission Handling with Real Network Loading Spinner ---
    var isSubmitting = false;

    bookingForm.addEventListener('submit', function(e) {
        if (isSubmitting) {
            e.preventDefault();
            return false;
        }

        if (submitBtn.disabled) {
            e.preventDefault();
            return false;
        }

        isSubmitting = true;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="bo-spinner" aria-hidden="true"></span> <span>جاري إرسال طلب الحجز...</span>';
        submitHint.textContent = 'يرجى الانتظار ولا تغلق الصفحة حتى اكتمال معالجة الطلب.';
    });

    // Reset state on bfcache navigation (iOS Safari back-button)
    window.addEventListener('pageshow', function(e) {
        if (e.persisted) {
            isSubmitting = false;
            submitBtn.disabled = false;
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
