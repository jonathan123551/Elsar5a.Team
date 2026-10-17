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
   ELSAR5A THEATRE BOX OFFICE — CALM, PREMIUM THEATRICAL DESIGN
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

/* Prevent layout jump and fix touch ergonomics */
html {
    scroll-behavior: smooth;
    scroll-padding-top: 80px;
}

.boxoffice-container {
    max-width: 820px;
    margin-inline: auto;
    padding: 1rem 1rem 3.5rem;
    color: var(--th-text-main);
    font-family: inherit;
}

@media (min-width: 640px) {
    .boxoffice-container {
        padding: 1.5rem 1.5rem 4rem;
    }
}

/* 1. CINEMATIC PRODUCTION BANNER */
.bo-hero {
    position: relative;
    background: linear-gradient(180deg, rgba(22, 27, 40, 0.95), rgba(12, 16, 24, 0.98));
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 1.5rem;
    padding: 1.25rem 1.25rem 1.1rem;
    margin-bottom: 1.25rem;
    box-shadow: 0 20px 40px -20px rgba(0, 0, 0, 0.7);
    overflow: hidden;
}

.bo-hero::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    left: 0;
    height: 3px;
    background: linear-gradient(90deg, #f59e0b, #ef4444, #f59e0b);
}

.bo-hero-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.bo-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.75rem;
    font-weight: 700;
    color: #fbbf24;
    background: rgba(245, 158, 11, 0.12);
    border: 1px solid rgba(245, 158, 11, 0.3);
    padding: 0.25rem 0.65rem;
    border-radius: 9999px;
    letter-spacing: 0.02em;
}

.bo-hero-title {
    font-size: clamp(1.4rem, 4vw, 1.85rem);
    font-weight: 900;
    line-height: 1.25;
    margin-top: 0.45rem;
    color: #ffffff;
    text-shadow: 0 2px 10px rgba(0,0,0,0.5);
}

.bo-hero-meta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin-top: 0.65rem;
    font-size: 0.85rem;
    color: var(--th-text-muted);
}

.bo-hero-meta-item {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
}

.bo-price-tag {
    margin-inline-start: auto;
    background: rgba(245, 158, 11, 0.15);
    border: 1px solid rgba(245, 158, 11, 0.4);
    border-radius: 0.75rem;
    padding: 0.4rem 0.8rem;
    text-align: left;
}

.bo-price-tag-label {
    font-size: 0.65rem;
    color: #fde68a;
    display: block;
    line-height: 1;
}

.bo-price-tag-value {
    font-size: 1.15rem;
    font-weight: 900;
    color: #fbbf24;
    line-height: 1.2;
}

/* 2. COMPACT STEP PROGRESS RAIL */
.bo-stepper {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.4rem;
    margin-bottom: 1.5rem;
}

@media (min-width: 640px) {
    .bo-stepper {
        gap: 0.65rem;
    }
}

.bo-step-item {
    background: rgba(15, 20, 30, 0.6);
    border: 1px solid var(--th-card-border);
    border-radius: 0.9rem;
    padding: 0.55rem 0.4rem;
    text-align: center;
    transition: all 0.25s ease;
    cursor: pointer;
    user-select: none;
}

.bo-step-item.is-upcoming {
    opacity: 0.5;
}

.bo-step-item.is-current {
    background: rgba(245, 158, 11, 0.12);
    border-color: rgba(245, 158, 11, 0.5);
    box-shadow: 0 0 16px -4px var(--th-amber-glow);
    opacity: 1;
}

.bo-step-item.is-completed {
    background: rgba(16, 185, 129, 0.12);
    border-color: rgba(16, 185, 129, 0.45);
    box-shadow: 0 0 16px -4px var(--th-emerald-glow);
    opacity: 1;
}

.bo-step-indicator {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    border-radius: 9999px;
    font-size: 0.68rem;
    font-weight: 800;
    margin-bottom: 0.2rem;
    background: rgba(255, 255, 255, 0.1);
    color: var(--th-text-muted);
}

.bo-step-item.is-current .bo-step-indicator {
    background: #f59e0b;
    color: #000;
}

.bo-step-item.is-completed .bo-step-indicator {
    background: #10b981;
    color: #fff;
}

.bo-step-name {
    display: block;
    font-size: 0.72rem;
    font-weight: 700;
    line-height: 1.2;
    color: var(--th-text-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.bo-step-item.is-current .bo-step-name {
    color: #fde68a;
}

.bo-step-item.is-completed .bo-step-name {
    color: #34d399;
}

/* 3. SECTION CARDS */
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

/* 4. SHOWTIME SELECTOR — 100% STABLE POSITIONING */
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

/* 5. TACTILE TICKET COUNTER */
.bo-counter-box {
    background: rgba(0, 0, 0, 0.25);
    border: 1px solid rgba(255, 255, 255, 0.06);
    border-radius: 1rem;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
}

.bo-counter-controls {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1.25rem;
    width: 100%;
}

.bo-counter-btn {
    width: 52px;
    height: 52px;
    border-radius: 1rem;
    border: 1.5px solid rgba(255, 255, 255, 0.15);
    background: rgba(255, 255, 255, 0.06);
    color: #ffffff;
    font-size: 1.5rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s ease;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
}

.bo-counter-btn:hover {
    background: rgba(255, 255, 255, 0.12);
    border-color: rgba(255, 255, 255, 0.3);
}

.bo-counter-btn:active {
    transform: scale(0.92);
}

.bo-counter-display {
    min-width: 70px;
    text-align: center;
}

.bo-counter-number {
    font-size: 2.25rem;
    font-weight: 900;
    color: #ffffff;
    line-height: 1;
    font-variant-numeric: tabular-nums;
}

.bo-counter-sub {
    font-size: 0.75rem;
    color: var(--th-text-muted);
    margin-top: 0.25rem;
}

.bo-counter-ledger {
    font-size: 0.95rem;
    font-weight: 700;
    color: #fde68a;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

/* 6. PAYMENT SECTION */
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
    padding: 0.4rem 0.75rem;
    border-radius: 0.65rem;
    font-size: 0.75rem;
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
    font-size: 0.82rem;
    color: #fde68a;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin-bottom: 1rem;
}

/* 7. RECEIPT DROPZONE & COMPLETED STATE */
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

/* 8. CUSTOMER FORM FIELDS */
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

/* 9. COMPACT "NEXT" BUTTON */
.bo-next-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    width: 100%;
    margin-top: 1rem;
    padding: 0.65rem 1rem;
    border-radius: 0.85rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: var(--th-text-muted);
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
}

.bo-next-btn:hover {
    background: rgba(245, 158, 11, 0.1);
    border-color: rgba(245, 158, 11, 0.4);
    color: #fbbf24;
}

/* 10. FINAL SUBMISSION CTA */
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

/* Trust strip */
.bo-trust-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.5rem;
    margin-top: 1.5rem;
    text-align: center;
}

.bo-trust-item {
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 0.75rem;
    padding: 0.65rem 0.4rem;
    font-size: 0.72rem;
    color: var(--th-text-muted);
}

.bo-trust-icon {
    display: block;
    font-size: 1.1rem;
    margin-bottom: 0.2rem;
}

/* Toast */
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

/* Spinner */
.bo-spinner {
    width: 20px;
    height: 20px;
    border: 2px solid rgba(0,0,0,0.2);
    border-top-color: #000;
    border-radius: 50%;
    animation: boSpin 0.7s linear infinite;
    display: inline-block;
}

@keyframes boSpin {
    to { transform: rotate(360deg); }
}
</style>

<div class="boxoffice-container">

    {{-- 1. THEATRE & PRODUCTION HERO --}}
    <header class="bo-hero">
        <div class="bo-hero-header">
            <div>
                <span class="bo-hero-badge">
                    <span>🎭</span> شباك التذاكر الرسمي
                </span>
                <h1 class="bo-hero-title">{{ $showTime->show->title }}</h1>
            </div>

            {{-- Single primary price tag --}}
            <div class="bo-price-tag">
                <span class="bo-price-tag-label">سعر التذكرة</span>
                <span class="bo-price-tag-value">{{ $showTime->ticket_price }} <span class="text-xs font-normal">ج.م</span></span>
            </div>
        </div>

        <div class="bo-hero-meta">
            <span class="bo-hero-meta-item">
                <span>📅</span> {{ $currentDate->format('d-m-Y') }}
            </span>
            <span>•</span>
            <span class="bo-hero-meta-item">
                <span>⏰</span> {{ $timeFormatted }}
            </span>
            <span>•</span>
            <span class="bo-hero-meta-item">
                @if ($remaining <= 10)
                    <span class="text-amber-400 font-bold">🔥 متبقي {{ $remaining }} مقاعد فقط</span>
                @else
                    <span class="text-emerald-400 font-bold">✓ متاح للحجز ({{ $remaining }} مقعد)</span>
                @endif
            </span>
        </div>
    </header>

    {{-- 2. COMPACT STEP PROGRESS RAIL --}}
    <nav class="bo-stepper" aria-label="مراحل الحجز">
        <div class="bo-step-item is-completed" id="stepIndicator-1" onclick="scrollToSection('section-tickets')">
            <span class="bo-step-indicator" id="stepNum-1">✓</span>
            <span class="bo-step-name">01 التذاكر</span>
        </div>
        <div class="bo-step-item is-current" id="stepIndicator-2" onclick="scrollToSection('section-payment')">
            <span class="bo-step-indicator" id="stepNum-2">02</span>
            <span class="bo-step-name">02 التحويل</span>
        </div>
        <div class="bo-step-item is-upcoming" id="stepIndicator-3" onclick="scrollToSection('section-details')">
            <span class="bo-step-indicator" id="stepNum-3">03</span>
            <span class="bo-step-name">03 البيانات</span>
        </div>
        <div class="bo-step-item is-upcoming" id="stepIndicator-4" onclick="scrollToSection('section-submit')">
            <span class="bo-step-indicator" id="stepNum-4">04</span>
            <span class="bo-step-name">04 الإرسال</span>
        </div>
    </nav>

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
             SECTION 1: الموعد والتذاكر (Showtime & Tickets)
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

            {{-- Tactile Ticket Counter --}}
            <div class="bo-counter-box">
                <span class="text-xs text-gray-400">حدد عدد التذاكر المطلوبة:</span>
                <div class="bo-counter-controls">
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

                {{-- Live Ledger --}}
                <div class="bo-counter-ledger">
                    <span>الإجمالي:</span>
                    <span class="text-white font-extrabold text-lg" id="totalPriceDisplay">{{ $showTime->ticket_price }}</span>
                    <span>جنيه مصري</span>
                </div>
            </div>

            <button type="button"
                    class="bo-next-btn"
                    onclick="scrollToSection('section-payment')">
                <span>التالي: التحويل والدفع</span>
                <span>↓</span>
            </button>
        </section>

        {{-- ============================================================
             SECTION 2: التحويل والإيصال (Payment & Screenshot)
        ============================================================ --}}
        <section id="section-payment" class="bo-section">
            <div class="bo-section-header">
                <div class="bo-section-title">
                    <span class="bo-section-title-badge">2</span>
                    <span>التحويل وإرفاق الإيصال</span>
                </div>
                <span class="bo-status-pill status-pending" id="status-badge-2">بانتظار الإيصال</span>
            </div>

            {{-- Dynamic instructions with live calculated total --}}
            <div class="bo-pay-instructions">
                <span>المبلغ المطلوب تحويله: <strong class="text-white text-base" id="requiredPayAmount">{{ $showTime->ticket_price }}</strong> ج.م</span>
                <span class="text-xs text-amber-200/90">حوّل المبلغ على أحد الأرقام التالية:</span>
            </div>

            {{-- Payment Accounts --}}
            <div class="bo-payment-list">
                @if ($transferWallet)
                    <div class="bo-pay-item">
                        <div class="bo-pay-info">
                            <span class="bo-pay-name">📱 فودافون كاش / محفظة</span>
                            <span class="bo-pay-num" dir="ltr">{{ $transferWallet }}</span>
                        </div>
                        <button type="button"
                                class="bo-copy-btn"
                                onclick="copyText('{{ $transferWallet }}', this)">
                            نسخ الرقم
                        </button>
                    </div>
                @endif

                @if ($transferInsta)
                    <div class="bo-pay-item">
                        <div class="bo-pay-info">
                            <span class="bo-pay-name">⚡ حساب InstaPay</span>
                            <span class="bo-pay-num" dir="ltr">{{ $transferInsta }}</span>
                        </div>
                        <button type="button"
                                class="bo-copy-btn"
                                onclick="copyText('{{ $transferInsta }}', this)">
                            نسخ الحساب
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
                    <span id="submitSpinner" class="hidden bo-spinner" aria-hidden="true"></span>
                    <span id="submitText">إرسال طلب الحجز 🎟️</span>
                </button>

                <p id="submitHint" class="bo-submit-hint">
                    يرجى إكمال البيانات وإرفاق إيصال التحويل لتفعيل زر الحجز.
                </p>
            </div>

            {{-- Trust Strip --}}
            <div class="bo-trust-row" aria-hidden="true">
                <div class="bo-trust-item">
                    <span class="bo-trust-icon">🔒</span>
                    <span>دفع وتحويل آمن</span>
                </div>
                <div class="bo-trust-item">
                    <span class="bo-trust-icon">⚡</span>
                    <span>معالجة فورية للمقاعد</span>
                </div>
                <div class="bo-trust-item">
                    <span class="bo-trust-icon">📱</span>
                    <span>تذكرة إلكترونية على واتساب</span>
                </div>
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
    var submitSpinner     = document.getElementById('submitSpinner');
    var submitHint        = document.getElementById('submitHint');
    var bookingForm       = document.getElementById('bookingForm');
    var copyToast         = document.getElementById('copyToast');

    // Stepper Indicators
    var step1 = document.getElementById('stepIndicator-1');
    var step2 = document.getElementById('stepIndicator-2');
    var step3 = document.getElementById('stepIndicator-3');
    var step4 = document.getElementById('stepIndicator-4');

    var stepNum1 = document.getElementById('stepNum-1');
    var stepNum2 = document.getElementById('stepNum-2');
    var stepNum3 = document.getElementById('stepNum-3');
    var stepNum4 = document.getElementById('stepNum-4');

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
        totalPriceDisplay.textContent = total.toLocaleString('ar-EG');
        if (requiredPayAmount) requiredPayAmount.textContent = total.toLocaleString('ar-EG');

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

    // --- Render Attendee Fields ---
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
                        '<span>التذكرة 1 — صاحب الحجز الأساسي</span>' +
                    '</div>' +
                    '<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">' +
                        '<div class="bo-field-group">' +
                            '<label class="bo-field-label">الاسم بالكامل (عربي أو English) *</label>' +
                            '<input type="text" name="names[]" value="' + valName + '"' +
                                   ' class="bo-input" placeholder="مثال: يوناثان ماجد" required autocomplete="name">' +
                        '</div>' +
                        '<div class="bo-field-group">' +
                            '<label class="bo-field-label">رقم الواتساب (لتأكيد واستلام التذاكر) *</label>' +
                            '<input type="tel" name="phones[]" value="' + valPhone + '"' +
                                   ' class="bo-input" placeholder="مثال: 01012345678" required autocomplete="tel" dir="ltr">' +
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
                                   ' class="bo-input" placeholder="اسم المرافق بالكامل" required>' +
                        '</div>' +
                        '<div class="bo-field-group mb-0">' +
                            '<label class="bo-field-label">رقم واتساب المرافق ' + i + ' *</label>' +
                            '<input type="tel" name="phones[]" value="' + valPhone + '"' +
                                   ' class="bo-input" placeholder="رقم واتساب المرافق" required dir="ltr">' +
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
                    btn.textContent = original;
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
        setStepState(step1, stepNum1, badge1, section1, true, '01', '✓ تم التحديد');

        // Step 2: Payment
        if (step2Done) {
            setStepState(step2, stepNum2, badge2, section2, true, '02', '✓ الإيصال مرفوع');
        } else {
            setStepState(step2, stepNum2, badge2, section2, false, '02', 'بانتظار الإيصال', true);
        }

        // Step 3: Attendees
        if (step3Done) {
            setStepState(step3, stepNum3, badge3, section3, true, '03', '✓ مكتملة (' + count + ')');
        } else {
            setStepState(step3, stepNum3, badge3, section3, false, '03', 'أدخل البيانات', step2Done);
        }

        // Step 4: Submission
        if (allReady) {
            submitBtn.disabled = false;
            submitHint.textContent = '✓ جميع البيانات جاهزة — اضغط لإرسال طلب الحجز فوراً.';
            submitHint.classList.add('ready');

            step4.className = 'bo-step-item is-completed';
            stepNum4.textContent = '✓';
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

            step4.className = 'bo-step-item is-upcoming';
            stepNum4.textContent = '04';
            badge4.className = 'bo-status-pill status-pending';
            badge4.textContent = 'في انتظار الإكمال';
            section4.classList.remove('is-section-done');
        }
    }

    function setStepState(stepEl, numEl, badgeEl, sectionEl, isDone, stepNumber, badgeDoneText, isCurrent) {
        if (isDone) {
            stepEl.className = 'bo-step-item is-completed';
            numEl.textContent = '✓';
            badgeEl.className = 'bo-status-pill status-done';
            badgeEl.textContent = badgeDoneText;
            sectionEl.classList.add('is-section-done');
        } else if (isCurrent) {
            stepEl.className = 'bo-step-item is-current';
            numEl.textContent = stepNumber;
            badgeEl.className = 'bo-status-pill status-pending';
            badgeEl.textContent = badgeDoneText;
            sectionEl.classList.remove('is-section-done');
        } else {
            stepEl.className = 'bo-step-item is-upcoming';
            numEl.textContent = stepNumber;
            badgeEl.className = 'bo-status-pill status-pending';
            badgeEl.textContent = badgeDoneText;
            sectionEl.classList.remove('is-section-done');
        }
    }

    // --- Form Submission Handling ---
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
        submitSpinner.classList.remove('hidden');
        submitText.textContent = 'جاري تأكيد وإرسال الحجز...';
        submitHint.textContent = 'يرجى الانتظار ولا تغلق الصفحة حتى اكتمال معالجة الطلب.';
    });

    // Reset state on bfcache navigation (iOS Safari back-button)
    window.addEventListener('pageshow', function(e) {
        if (e.persisted) {
            isSubmitting = false;
            submitBtn.disabled = false;
            submitSpinner.classList.add('hidden');
            submitText.textContent = 'إرسال طلب الحجز 🎟️';
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
