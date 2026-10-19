@extends('layouts.app')

@section('title', 'فاحص التذاكر — بوابة الدخول المسرحية')
@section('body_class', 'scanner-ops-screen')
@section('main_class', 'scanner-main-wrap')

@section('content')
{{--
    Elsar5a Team — Theatrical Admission Gate Console
    Bespoke, full-bleed operational scanner for stage doors and theatre entrances.

    Creative Architecture:
      - 3-Wing Layout on Desktop: [Show Marquee Wing] + [Proscenium Gate Aperture] + [Live Admission Ledger]
      - Single Unified Monolithic Portal on Mobile: [Transom Marquee] + [Integrated Aperture & Sill Controls] + [Admission Log]
      - 100dvh Zero-Scroll Guarantee: Every pixel has purpose; zero dead space; zero page scrolling required.
      - Real Event Identity: Live Show and ShowTime from Eloquent query (no fake/hardcoded data).
      - iOS Safari Scroll-Lock Engine: Complete body freeze & touchmove neutralization while modal is open.
      - Preserved Scanning Engines (LOCKED): BarcodeDetector (Native) -> ZXing-js (Loop) -> Html5Qrcode (Fallback).
--}}

@php
    $currentShowTime = \App\Models\ShowTime::with('show')
        ->whereHas('show', fn($q) => $q->where('is_active', true))
        ->whereDate('date', '>=', now()->toDateString())
        ->orderBy('date', 'asc')
        ->orderBy('time', 'asc')
        ->first() ?? \App\Models\ShowTime::with('show')->latest('id')->first();
    $currentShow = $currentShowTime?->show ?? \App\Models\Show::where('is_active', true)->first();
    $showTitle = $currentShow?->title ?: 'عرض الصرخة المسرحي';

    $formattedDate = '';
    if ($currentShowTime?->date) {
        $formattedDate = \Carbon\Carbon::parse($currentShowTime->date)->translatedFormat('j F Y');
    }
    $formattedTime = '';
    if ($currentShowTime?->time) {
        $formattedTime = \Carbon\Carbon::parse($currentShowTime->time)->translatedFormat('g:i A');
    }
@endphp

<section data-scanner-root class="theatre-gate-console">

    {{-- ==============================================================
         DESKTOP LEFT MARQUEE WING (Hidden on mobile, visible on desktop)
         ============================================================== --}}
    <aside class="gate-marquee-wing">
        <div class="marquee-brand-stamp">
            <div class="marquee-logo-badge">
                <img src="{{ asset('images/sarkha-logo.png') }}" class="marquee-logo-img" alt="" width="26" height="26" aria-hidden="true">
            </div>
            <div class="marquee-brand-text">
                <span class="marquee-team-name">فريق الصرخة المسرحي</span>
                <span class="marquee-console-label">بوابة الدخول المباشرة</span>
            </div>
        </div>

        <div class="marquee-show-bill" data-event-banner>
            <div class="show-bill-tag">الفعالية المسرحية الحالية</div>
            <h1 class="show-bill-title" data-event-title>{{ $showTitle }}</h1>
            <div class="show-bill-meta">
                @if($formattedDate)
                    <div class="bill-meta-row" data-event-date>
                        <span class="meta-icon" aria-hidden="true">🗓️</span>
                        <span>{{ $formattedDate }}</span>
                    </div>
                @endif
                @if($formattedTime)
                    <div class="bill-meta-row" data-event-time>
                        <span class="meta-icon" aria-hidden="true">🕘</span>
                        <span>{{ $formattedTime }}</span>
                    </div>
                @endif
            </div>
        </div>

        <div class="marquee-session-stats">
            <div class="stat-badge is-ok">
                <span class="stat-num" id="desk-session-ok-count">0</span>
                <span class="stat-lbl">مقبول</span>
            </div>
            <div class="stat-badge is-used">
                <span class="stat-num" id="desk-session-used-count">0</span>
                <span class="stat-lbl">مكرر</span>
            </div>
            <div class="stat-badge is-error">
                <span class="stat-num" id="desk-session-error-count">0</span>
                <span class="stat-lbl">مرفوض</span>
            </div>
            <div class="stat-badge is-total">
                <span class="stat-num" id="desk-session-total-count">0</span>
                <span class="stat-lbl">المجموع</span>
            </div>
        </div>
    </aside>

    {{-- ==============================================================
         CENTER ARCHITECTURAL PROSCENIUM PORTAL (HERO SCANNING UNIT)
         ============================================================== --}}
    <main class="gate-portal-core">

        {{-- INTEGRATED ARCH TRANSOM CROWN (MOBILE & TABLET PRIMARY HEADER) --}}
        <header class="portal-transom-crown">
            <div class="transom-brand">
                <div class="transom-logo-gem">
                    <img src="{{ asset('images/sarkha-logo.png') }}" class="transom-logo" alt="فريق الصرخة المسرحي" width="22" height="22">
                </div>
                <div class="transom-title-wrap">
                    <span class="transom-team-title">فريق الصرخة المسرحي</span>
                </div>
            </div>
            <div class="transom-status-live" title="حالة الاتصال المباشر">
                <span class="live-beacon" aria-hidden="true"></span>
                <span>متصل مباشر</span>
            </div>
        </header>

        {{-- THE MONOLITHIC APERTURE HOUSING WITH DOCKED THRESHOLD CONTROLS --}}
        <div class="portal-aperture-housing" data-scanner-stage>

            {{-- 1. Camera Video Stream Mount --}}
            <div id="qr-reader" class="portal-video-stream"></div>

            {{-- 2. Pinch Zoom Floating Indicator --}}
            <div id="scan-zoom-chip" class="scan-zoom-chip" aria-hidden="true">
                <span data-zoom-text>1.0×</span>
            </div>

            {{-- 3. Architectural Theatrical Reticle with Proscenium Corner Joints --}}
            <div class="portal-optical-reticle" aria-hidden="true">
                <span class="arch-bracket top-left"></span>
                <span class="arch-bracket top-right"></span>
                <span class="arch-bracket bottom-left"></span>
                <span class="arch-bracket bottom-right"></span>
                <div class="optical-scan-beam"></div>
            </div>

            {{-- 4. Embedded Operational Status Badge --}}
            <div id="status" class="portal-status-pill state-ready" role="status" aria-live="polite">
                جاهز للمسح
            </div>

            {{-- 5. Camera Loading Shield --}}
            <div id="scanner-loading" class="portal-loading-shield">
                <div class="portal-spinner" aria-hidden="true"></div>
                <div class="portal-loading-msg">جاري تشغيل عدسة المسح…</div>
            </div>

            {{-- 6. DOCKED THRESHOLD SILL CONTROLS (INTEGRATED INTO BASE OF CASING) --}}
            <div class="portal-threshold-controls">
                <button id="flashBtn" class="portal-threshold-btn" type="button" aria-label="تشغيل كشاف الإضاءة">
                    <span class="btn-icon" aria-hidden="true">🔦</span>
                    <span class="btn-txt">كشاف الإضاءة</span>
                </button>
                <button id="restartBtn" class="portal-threshold-btn" type="button" aria-label="إعادة تهيئة الكاميرا">
                    <span class="btn-icon" aria-hidden="true">🔄</span>
                    <span class="btn-txt">إعادة تشغيل</span>
                </button>
            </div>

        </div>

    </main>

    {{-- ==============================================================
         RIGHT / BOTTOM WING: THE LIVE GATE ADMISSION LEDGER
         ============================================================== --}}
    <aside class="gate-admission-ledger" aria-label="سجل عمليات الدخول">
        <div class="ledger-header-bar">
            <div class="ledger-title-group">
                <span class="ledger-icon" aria-hidden="true">📋</span>
                <span class="ledger-heading">سجل العبور المسرحي</span>
            </div>
            <div class="ledger-tallies-strip" aria-label="إحصائيات المسح">
                <span class="tally-tag is-ok" title="دخول مصرح به"><strong id="session-ok-count">0</strong> مقبول</span>
                <span class="tally-tag is-used" title="تذاكر مستخدمة"><strong id="session-used-count">0</strong> مكرر</span>
                <span class="tally-tag is-error" title="تذاكر مرفوضة"><strong id="session-error-count">0</strong> مرفوض</span>
                <span class="tally-tag is-total" title="إجمالي المسح"><strong id="session-total-count">0</strong> كلي</span>
            </div>
        </div>

        <div class="ledger-stream-body">
            <div id="recent-scans-empty" class="ledger-empty-state">
                <span class="empty-halo" aria-hidden="true">●</span>
                <span>في انتظار مسح التذاكر عبر الكاميرا...</span>
            </div>
            <div id="recent-scans-list" class="ledger-stream-list" role="log" aria-live="polite"></div>
        </div>
    </aside>

</section>

{{-- ==============================================================
     THEATRICAL ADMISSION PASS RESULT MODAL (WITH SAFARI SCROLL LOCK)
     ============================================================== --}}
<div id="scan-sheet"
     class="scan-sheet"
     data-state="hidden"
     role="dialog"
     aria-modal="true"
     aria-live="polite"
     aria-labelledby="scan-sheet-title">

    {{-- Atmospheric Ambient Glow behind card keyed to state --}}
    <div class="scan-sheet-ambient" aria-hidden="true"></div>

    <div class="scan-sheet-card" data-scan-card>

        {{-- 1. HERO STATUS EMBLEM & BANNER (DOMINANT VISUAL ELEMENT) --}}
        <div class="scan-sheet-hero-status" data-scan-badge>
            <div class="status-emblem-ring">
                <span class="status-emblem-icon" data-scan-icon>✓</span>
            </div>
            <div class="status-title-group">
                <h2 class="status-headline" data-scan-badge-text>دخول مسموح</h2>
                <span class="status-subline" data-scan-subline>تذكرة صالحة ومؤكدة</span>
            </div>
        </div>

        {{-- 2. TICKET HOLDER & REFERENCE IDENTIFIER --}}
        <div class="scan-sheet-attendee-block">
            <div class="attendee-label">حامل التذكرة</div>
            <div class="scan-sheet-name" id="scan-sheet-title" data-scan-name>—</div>
            <div class="scan-sheet-ref" data-scan-ref dir="ltr"></div>
        </div>

        {{-- 3. THEATRICAL EVENT & SHOW IDENTITY BLOCK (CLEAN & PROMINENT) --}}
        <div class="scan-sheet-event-block" data-scan-event-pill>
            <div class="event-block-header">
                <span class="event-theatre-icon" aria-hidden="true">🎭</span>
                <span class="event-theatre-show" data-scan-show>{{ $showTitle }}</span>
            </div>
            <div class="event-block-timing" data-scan-when>
                @if($formattedDate)
                    <span class="timing-badge">
                        <span class="timing-icon" aria-hidden="true">🗓️</span>
                        <span class="timing-text">{{ $formattedDate }}</span>
                    </span>
                @endif
                @if($formattedTime)
                    <span class="timing-badge">
                        <span class="timing-icon" aria-hidden="true">🕘</span>
                        <span class="timing-text">{{ $formattedTime }}</span>
                    </span>
                @endif
            </div>
        </div>

        {{-- 4. ALREADY SCANNED WARNING NOTE (SHOWN ONLY ON 'USED' RESULT) --}}
        <div class="scan-sheet-used-note" data-scan-used-note hidden>
            <span class="used-icon" aria-hidden="true">⚠️</span>
            <div class="used-text">
                <span class="used-title">تنبيه: تم استخدام التذكرة مسبقاً</span>
                <span class="used-time-stamp" data-scan-used-time></span>
            </div>
        </div>

        {{-- 5. PRIMARY NEXT ACTION BUTTON (FAST OPERATIONAL FLOW) --}}
        <div class="scan-sheet-foot">
            <button type="button"
                    class="scan-sheet-done"
                    data-scan-dismiss
                    aria-label="مسح التذكرة التالية">
                <span class="btn-primary-text">مسح التذكرة التالية</span>
                <span class="btn-primary-arrow" aria-hidden="true">←</span>
            </button>
        </div>

    </div>
</div>

{{-- Self-hosted decode engines (LOCKED) --}}
<script src="{{ asset('vendor/zxing/library-0.21.3.min.js') }}"></script>
<script src="{{ asset('vendor/html5-qrcode/html5-qrcode-2.3.8.min.js') }}"></script>

<style>
/* =========================================================
   THEATRICAL ADMISSION GATE CONSOLE — HIGH-CRAFT DESIGN SYSTEM
   ========================================================= */

:root {
    --gate-bg-deep: #050208;
    --gate-bg-surface: #0e0714;
    --gate-bg-card: rgba(18, 9, 26, 0.88);
    --gate-border-subtle: rgba(197, 160, 89, 0.18);
    --gate-border-highlight: rgba(197, 160, 89, 0.45);
    --gate-gold: #c5a059;
    --gate-gold-glow: #e6ca65;
    --gate-crimson: #59142e;
    --gate-text-bright: #ffffff;
    --gate-text-body: #e2d9cf;
    --gate-text-muted: #8d8095;
    --gate-ease: cubic-bezier(0.16, 1, 0.3, 1);
}

body.scanner-ops-screen {
    background-color: var(--gate-bg-deep) !important;
    background-image:
        radial-gradient(ellipse 80% 50% at 50% -10%, rgba(89, 20, 46, 0.35), transparent 70%),
        radial-gradient(ellipse 60% 40% at 50% 110%, rgba(197, 160, 89, 0.12), transparent 70%);
    min-height: 100vh;
    min-height: 100dvh;
    height: 100dvh;
    margin: 0 !important;
    padding: 0 !important;
    overflow: hidden !important;
    font-family: 'Cairo', system-ui, -apple-system, sans-serif;
    color: var(--gate-text-body);
}

body.scanner-ops-screen .site-header,
body.scanner-ops-screen .site-footer,
body.scanner-ops-screen #sticky-action-footer {
    display: none !important;
}

.scanner-main-wrap {
    width: 100% !important;
    max-width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
    height: 100dvh;
}

/* =========================================================
   CONSOLE SHELL & SPATIAL ARCHITECTURE
   ========================================================= */

.theatre-gate-console {
    display: flex;
    flex-direction: column;
    width: 100%;
    height: 100dvh;
    max-height: 100dvh;
    box-sizing: border-box;
    padding:
        max(8px, env(safe-area-inset-top))
        max(10px, env(safe-area-inset-right))
        max(8px, env(safe-area-inset-bottom))
        max(10px, env(safe-area-inset-left));
    gap: 8px;
    overflow: hidden;
}

/* On Desktop (>= 1024px): 3-Wing Command Console */
@media (min-width: 1024px) {
    .theatre-gate-console {
        display: grid;
        grid-template-columns: 310px 1fr 340px;
        align-items: stretch;
        max-width: 1380px;
        margin: 0 auto;
        padding: 24px;
        gap: 20px;
    }
}

/* =========================================================
   1. DESKTOP LEFT MARQUEE WING
   ========================================================= */

.gate-marquee-wing {
    display: none;
}

@media (min-width: 1024px) {
    .gate-marquee-wing {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        background: var(--gate-bg-card);
        border: 1px solid var(--gate-border-subtle);
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 16px 36px -12px rgba(0, 0, 0, 0.8), inset 0 1px 0 rgba(255, 255, 255, 0.06);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
    }

    .marquee-brand-stamp {
        display: flex;
        align-items: center;
        gap: 12px;
        padding-bottom: 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .marquee-logo-badge {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.25);
        box-shadow: 0 4px 14px rgba(255, 255, 255, 0.12);
    }

    .marquee-logo-img {
        width: 28px;
        height: 28px;
        object-fit: contain;
        filter: invert(1) brightness(1.3) drop-shadow(0 0 6px rgba(255, 255, 255, 0.6));
    }

    .marquee-brand-text {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .marquee-team-name {
        font-family: 'Alexandria', system-ui, sans-serif;
        font-weight: 800;
        font-size: 15px;
        color: #fff;
    }

    .marquee-console-label {
        font-size: 11px;
        color: var(--gate-gold);
        letter-spacing: 0.04em;
    }

    .marquee-show-bill {
        margin: auto 0;
        padding: 20px 0;
    }

    .show-bill-tag {
        font-size: 11px;
        text-transform: uppercase;
        color: var(--gate-gold);
        letter-spacing: 0.08em;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .show-bill-title {
        font-family: 'Alexandria', system-ui, sans-serif;
        font-size: 26px;
        font-weight: 900;
        color: #fff;
        line-height: 1.25;
        margin: 0 0 14px 0;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.7);
    }

    .show-bill-meta {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .bill-meta-row {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: var(--gate-text-body);
        background: rgba(255, 255, 255, 0.03);
        padding: 6px 12px;
        border-radius: 8px;
        border: 1px solid rgba(255, 255, 255, 0.04);
    }

    .marquee-session-stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        padding-top: 20px;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
    }

    .stat-badge {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 10px;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.05);
    }

    .stat-badge.is-ok    { border-color: rgba(16, 185, 129, 0.25); background: rgba(16, 185, 129, 0.05); }
    .stat-badge.is-used  { border-color: rgba(245, 158, 11, 0.25); background: rgba(245, 158, 11, 0.05); }
    .stat-badge.is-error { border-color: rgba(244, 63, 94, 0.25); background: rgba(244, 63, 94, 0.05); }
    .stat-badge.is-total { border-color: rgba(197, 160, 89, 0.25); background: rgba(197, 160, 89, 0.05); }

    .stat-num {
        font-family: 'Alexandria', system-ui, sans-serif;
        font-size: 18px;
        font-weight: 800;
        color: #fff;
    }
    .stat-badge.is-ok .stat-num { color: #34d399; }
    .stat-badge.is-used .stat-num { color: #fbbf24; }
    .stat-badge.is-error .stat-num { color: #fb7185; }
    .stat-badge.is-total .stat-num { color: var(--gate-gold-glow); }

    .stat-lbl {
        font-size: 10px;
        color: var(--gate-text-muted);
        margin-top: 2px;
    }
}

/* =========================================================
   2. CENTER ARCHITECTURAL PROSCENIUM PORTAL (CORE UNIT)
   ========================================================= */

.gate-portal-core {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    width: 100%;
    box-sizing: border-box;
}

@media (min-width: 1024px) {
    .gate-portal-core {
        flex: 1;
        justify-content: center;
    }
}

/* Transom Crown (Integrated Mobile / Tablet Header) */
.portal-transom-crown {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    max-width: min(420px, 96vw);
    padding: 7px 12px;
    margin-bottom: 6px;
    border-radius: 16px;
    background: linear-gradient(135deg, rgba(42, 11, 24, 0.95) 0%, rgba(14, 6, 20, 0.95) 100%);
    border: 1px solid rgba(197, 160, 89, 0.35);
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.75), inset 0 1px 0 rgba(255, 255, 255, 0.12);
    box-sizing: border-box;
}

@media (min-width: 1024px) {
    .portal-transom-crown {
        display: none; /* Desktop uses left marquee wing */
    }
}

.transom-brand {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
}

.transom-logo-gem {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    border-radius: 9px;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.25);
    flex-shrink: 0;
    overflow: hidden;
    box-shadow: 0 0 8px rgba(255, 255, 255, 0.15);
}

.transom-logo {
    width: 22px;
    height: 22px;
    object-fit: contain;
    filter: invert(1) brightness(1.3) drop-shadow(0 0 6px rgba(255, 255, 255, 0.6));
}

.transom-title-wrap {
    display: flex;
    align-items: center;
    min-width: 0;
}

.transom-team-title {
    font-family: 'Alexandria', system-ui, sans-serif;
    font-weight: 800;
    font-size: 13px;
    color: #ffffff;
    letter-spacing: 0.01em;
    white-space: nowrap;
}

.transom-status-live {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 8px;
    border-radius: 999px;
    background: rgba(16, 185, 129, 0.12);
    border: 1px solid rgba(16, 185, 129, 0.35);
    color: #6ee7b7;
    font-size: 10px;
    font-weight: 700;
    flex-shrink: 0;
}

.live-beacon {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #10b981;
    box-shadow: 0 0 8px #10b981;
    animation: beaconPulse 2s infinite;
}

@keyframes beaconPulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(0.85); }
}

/* =========================================================
   MONOLITHIC APERTURE HOUSING & DOCKED THRESHOLD SILL
   ========================================================= */

.portal-aperture-housing {
    position: relative;
    width: 100%;
    max-width: min(420px, 96vw);
    height: clamp(320px, 46dvh, 420px);
    border-radius: 22px;
    background: #02040a;
    border: 1.5px solid var(--gate-border-highlight);
    box-shadow:
        0 0 0 1px rgba(0, 0, 0, 0.8),
        0 14px 34px -10px rgba(0, 0, 0, 0.95),
        inset 0 1px 0 rgba(255, 255, 255, 0.15);
    overflow: hidden;
    box-sizing: border-box;
    transition: border-color 0.25s var(--gate-ease), box-shadow 0.25s var(--gate-ease);
}

@media (min-height: 800px) {
    .portal-aperture-housing {
        height: clamp(360px, 50dvh, 480px);
    }
}

@media (min-width: 1024px) {
    .portal-aperture-housing {
        max-width: 480px;
        height: clamp(420px, 58vh, 520px);
        max-height: 62vh;
        border-radius: 24px;
    }
}

/* Video Mount */
.portal-video-stream {
    position: absolute;
    inset: 0;
    bottom: 46px; /* Reserve bottom for docked controls */
    overflow: hidden;
    background: #000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.portal-video-stream video {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
}

/* Architectural Proscenium Optical Reticle */
.portal-optical-reticle {
    position: absolute;
    inset: 12px;
    bottom: 54px;
    pointer-events: none;
    z-index: 2;
}

.arch-bracket {
    position: absolute;
    width: 30px;
    height: 30px;
    border-color: var(--gate-gold);
    border-style: solid;
    filter: drop-shadow(0 0 8px rgba(197, 160, 89, 0.7));
    transition: border-color 0.2s ease, filter 0.2s ease;
}

.arch-bracket.top-left {
    top: 0;
    inset-inline-start: 0;
    border-width: 3.5px 0 0 3.5px;
    border-top-left-radius: 10px;
}
.arch-bracket.top-right {
    top: 0;
    inset-inline-end: 0;
    border-width: 3.5px 3.5px 0 0;
    border-top-right-radius: 10px;
}
.arch-bracket.bottom-left {
    bottom: 0;
    inset-inline-start: 0;
    border-width: 0 0 3.5px 3.5px;
    border-bottom-left-radius: 10px;
}
.arch-bracket.bottom-right {
    bottom: 0;
    inset-inline-end: 0;
    border-width: 0 3.5px 3.5px 0;
    border-bottom-right-radius: 10px;
}

/* Theatrical Optical Scan Beam */
.optical-scan-beam {
    position: absolute;
    inset-inline: 4px;
    height: 2px;
    background: linear-gradient(90deg, transparent 0%, #fff 30%, var(--gate-gold-glow) 50%, #fff 70%, transparent 100%);
    box-shadow: 0 0 14px 2px rgba(230, 202, 101, 0.85);
    animation: scanBeamSweep 2.2s ease-in-out infinite alternate;
}

@keyframes scanBeamSweep {
    0%   { top: 5%; opacity: 0.3; }
    50%  { opacity: 1; }
    100% { top: 92%; opacity: 0.3; }
}

/* Embedded Status Pill */
.portal-status-pill {
    position: absolute;
    top: 10px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 5;
    padding: 4px 14px;
    border-radius: 999px;
    font-family: 'Alexandria', system-ui, sans-serif;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.02em;
    background: rgba(8, 4, 12, 0.85);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid var(--gate-border-highlight);
    color: var(--gate-gold-glow);
    white-space: nowrap;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.7);
    transition: all 0.2s var(--gate-ease);
}

.portal-status-pill.state-ready    { color: #fde68a; border-color: rgba(245, 158, 11, 0.4); }
.portal-status-pill.state-scanning { color: #93c5fd; border-color: rgba(59, 130, 246, 0.6); background: rgba(15, 23, 42, 0.9); }
.portal-status-pill.state-ok       { color: #6ee7b7; border-color: #10b981; background: rgba(6, 78, 59, 0.95); }
.portal-status-pill.state-used     { color: #fef08a; border-color: #f59e0b; background: rgba(120, 53, 15, 0.95); }
.portal-status-pill.state-error    { color: #fecdd3; border-color: #f43f5e; background: rgba(136, 19, 55, 0.95); }

/* Zoom Chip */
.scan-zoom-chip {
    position: absolute;
    top: 10px;
    inset-inline-start: 10px;
    z-index: 6;
    padding: 2px 7px;
    border-radius: 999px;
    background: rgba(5, 2, 8, 0.85);
    border: 1px solid var(--gate-border-subtle);
    color: var(--gate-gold);
    font: 700 9px/1 system-ui, sans-serif;
    opacity: 0;
    pointer-events: none;
    transform: translateY(-2px);
    transition: opacity 0.18s ease;
}
.scan-zoom-chip.is-visible { opacity: 1; transform: translateY(0); }

/* Camera Loading Shield */
.portal-loading-shield {
    position: absolute;
    inset: 0;
    bottom: 46px;
    z-index: 4;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: rgba(5, 2, 8, 0.94);
    color: var(--gate-text-muted);
    transition: opacity 0.25s ease;
}
.portal-loading-shield.is-hidden { opacity: 0; pointer-events: none; }

.portal-spinner {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    border: 2px solid rgba(197, 160, 89, 0.2);
    border-top-color: var(--gate-gold);
    animation: portalSpin 0.85s linear infinite;
}
@keyframes portalSpin { to { transform: rotate(360deg); } }

.portal-loading-msg { font-size: 11px; font-weight: 500; }

/* DOCKED THRESHOLD SILL CONTROLS */
.portal-threshold-controls {
    position: absolute;
    bottom: 0;
    inset-inline: 0;
    height: 46px;
    z-index: 5;
    display: grid;
    grid-template-columns: 1fr 1fr;
    background: linear-gradient(180deg, #170a1a, #0c040d);
    border-top: 1px solid var(--gate-border-subtle);
}

.portal-threshold-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    background: transparent;
    border: none;
    color: var(--gate-text-body);
    font-family: 'Alexandria', system-ui, sans-serif;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    touch-action: manipulation;
    transition: background 0.15s ease, color 0.15s ease;
}

.portal-threshold-btn:first-child {
    border-inline-end: 1px solid var(--gate-border-subtle);
}

.portal-threshold-btn:hover {
    background: rgba(255, 255, 255, 0.04);
    color: #fff;
}

.portal-threshold-btn:active {
    background: rgba(197, 160, 89, 0.1);
}

.portal-threshold-btn.is-on {
    color: #fde68a;
    background: rgba(245, 158, 11, 0.15);
}

/* State changes on the aperture housing */
.portal-aperture-housing[data-state="ok"] {
    border-color: #10b981;
    box-shadow: 0 0 25px rgba(16, 185, 129, 0.4), inset 0 0 15px rgba(16, 185, 129, 0.2);
}
.portal-aperture-housing[data-state="ok"] .arch-bracket {
    border-color: #34d399;
    filter: drop-shadow(0 0 8px #10b981);
}

.portal-aperture-housing[data-state="used"] {
    border-color: #f59e0b;
    box-shadow: 0 0 25px rgba(245, 158, 11, 0.4), inset 0 0 15px rgba(245, 158, 11, 0.2);
}
.portal-aperture-housing[data-state="used"] .arch-bracket {
    border-color: #fbbf24;
    filter: drop-shadow(0 0 8px #f59e0b);
}

.portal-aperture-housing[data-state="error"] {
    border-color: #f43f5e;
    box-shadow: 0 0 25px rgba(244, 63, 94, 0.4), inset 0 0 15px rgba(244, 63, 94, 0.2);
    animation: apertureShake 0.35s cubic-bezier(0.36, 0.07, 0.19, 0.97);
}
.portal-aperture-housing[data-state="error"] .arch-bracket {
    border-color: #fb7185;
    filter: drop-shadow(0 0 8px #f43f5e);
}

@keyframes apertureShake {
    10%, 90% { transform: translateX(-2px); }
    20%, 80% { transform: translateX(3px); }
    30%, 50%, 70% { transform: translateX(-4px); }
    40%, 60% { transform: translateX(4px); }
}

/* =========================================================
   3. RIGHT / BOTTOM WING: THE LIVE ADMISSION LEDGER
   ========================================================= */

.gate-admission-ledger {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;
    width: 100%;
    max-width: min(420px, 96vw);
    margin: 0 auto;
    background: var(--gate-bg-card);
    border: 1px solid var(--gate-border-subtle);
    border-radius: 16px;
    padding: 8px 10px;
    box-shadow: 0 8px 24px -6px rgba(0, 0, 0, 0.7);
    box-sizing: border-box;
    overflow: hidden;
}

@media (min-width: 1024px) {
    .gate-admission-ledger {
        max-width: 100%;
        margin: 0;
        border-radius: 20px;
        padding: 20px;
    }
}

.ledger-header-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 6px;
    margin-bottom: 6px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    flex-shrink: 0;
}

.ledger-title-group {
    display: flex;
    align-items: center;
    gap: 6px;
}

.ledger-icon { font-size: 13px; }

.ledger-heading {
    font-family: 'Alexandria', system-ui, sans-serif;
    font-size: 11px;
    font-weight: 800;
    color: #fff;
    letter-spacing: 0.02em;
}

.ledger-tallies-strip {
    display: flex;
    align-items: center;
    gap: 4px;
}

.tally-tag {
    font-size: 9px;
    padding: 1px 5px;
    border-radius: 4px;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.05);
    color: var(--gate-text-muted);
}
.tally-tag strong {
    font-family: 'Alexandria', system-ui, sans-serif;
    font-weight: 800;
}
.tally-tag.is-ok    { color: #6ee7b7; border-color: rgba(16, 185, 129, 0.3); background: rgba(16, 185, 129, 0.08); }
.tally-tag.is-used  { color: #fde68a; border-color: rgba(245, 158, 11, 0.3); background: rgba(245, 158, 11, 0.08); }
.tally-tag.is-error { color: #fca5a5; border-color: rgba(244, 63, 94, 0.3); background: rgba(244, 63, 94, 0.08); }
.tally-tag.is-total { color: #fff; }

.ledger-stream-body {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}

.ledger-empty-state {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    height: 100%;
    min-height: 50px;
    font-size: 11px;
    color: var(--gate-text-muted);
}
.empty-halo {
    font-size: 7px;
    color: var(--gate-gold);
    animation: beaconPulse 2s infinite;
}

.ledger-stream-list {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.scan-stream-row {
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    gap: 8px;
    padding: 5px 8px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid rgba(255, 255, 255, 0.04);
    font-size: 11px;
    animation: rowGlide 0.25s var(--gate-ease) both;
}

@keyframes rowGlide {
    from { opacity: 0; transform: translateY(-3px); }
    to   { opacity: 1; transform: translateY(0); }
}

.scan-stream-row.is-latest {
    border-color: rgba(197, 160, 89, 0.35);
    background: rgba(197, 160, 89, 0.06);
}

.scan-stream-row.state-ok    { border-inline-start: 2.5px solid #10b981; }
.scan-stream-row.state-used  { border-inline-start: 2.5px solid #f59e0b; }
.scan-stream-row.state-error { border-inline-start: 2.5px solid #f43f5e; }

.stream-row-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 17px;
    height: 17px;
    border-radius: 50%;
    font-size: 9px;
    font-weight: 800;
}
.stream-row-badge.state-ok    { background: rgba(16, 185, 129, 0.2); color: #34d399; }
.stream-row-badge.state-used  { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }
.stream-row-badge.state-error { background: rgba(244, 63, 94, 0.2); color: #fb7185; }

.stream-row-info {
    display: flex;
    align-items: center;
    gap: 6px;
    min-width: 0;
    overflow: hidden;
}

.stream-row-name {
    font-weight: 700;
    color: #fff;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.stream-row-ref {
    font-family: monospace;
    font-size: 10px;
    color: var(--gate-gold);
    white-space: nowrap;
}

.stream-row-time {
    font-size: 9px;
    color: var(--gate-text-muted);
    white-space: nowrap;
}

/* =========================================================
   4. THEATRICAL ADMISSION PASS MODAL (WITH SAFARI SCROLL LOCK)
   ========================================================= */

.scan-sheet {
    position: fixed;
    inset: 0;
    min-height: 100dvh;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding:
        max(16px, env(safe-area-inset-top))
        16px
        max(16px, env(safe-area-inset-bottom));
    background: rgba(3, 1, 6, 0.88);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    opacity: 0;
    pointer-events: none;
    overscroll-behavior: contain;
    transition: opacity 0.22s var(--gate-ease);
}
.scan-sheet[data-state="visible"] {
    opacity: 1;
    pointer-events: auto;
}

/* Atmospheric State-based Ambient Radial Illumination */
.scan-sheet-ambient {
    position: absolute;
    inset: 0;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.35s ease;
}
.scan-sheet[data-result="ok"] .scan-sheet-ambient {
    background:
        radial-gradient(ellipse 900px 700px at 50% 45%, rgba(16, 185, 129, 0.28) 0%, rgba(6, 78, 59, 0.12) 55%, transparent 80%),
        radial-gradient(circle 320px at 50% 36%, rgba(52, 211, 153, 0.25) 0%, transparent 70%);
    opacity: 1;
}
.scan-sheet[data-result="used"] .scan-sheet-ambient {
    background:
        radial-gradient(ellipse 900px 700px at 50% 45%, rgba(245, 158, 11, 0.28) 0%, rgba(120, 53, 15, 0.12) 55%, transparent 80%),
        radial-gradient(circle 320px at 50% 36%, rgba(251, 191, 36, 0.25) 0%, transparent 70%);
    opacity: 1;
}
.scan-sheet[data-result="error"] .scan-sheet-ambient {
    background:
        radial-gradient(ellipse 900px 700px at 50% 45%, rgba(244, 63, 94, 0.28) 0%, rgba(136, 19, 55, 0.12) 55%, transparent 80%),
        radial-gradient(circle 320px at 50% 36%, rgba(251, 113, 133, 0.25) 0%, transparent 70%);
    opacity: 1;
}

@media (min-width: 1024px) {
    .scan-sheet[data-result="ok"] .scan-sheet-ambient {
        background:
            radial-gradient(ellipse 1300px 950px at 50% 48%, rgba(16, 185, 129, 0.32) 0%, rgba(6, 78, 59, 0.16) 45%, transparent 75%),
            radial-gradient(circle 460px at 50% 36%, rgba(52, 211, 153, 0.32) 0%, transparent 70%);
    }
    .scan-sheet[data-result="used"] .scan-sheet-ambient {
        background:
            radial-gradient(ellipse 1300px 950px at 50% 48%, rgba(245, 158, 11, 0.32) 0%, rgba(120, 53, 15, 0.16) 45%, transparent 75%),
            radial-gradient(circle 460px at 50% 36%, rgba(251, 191, 36, 0.32) 0%, transparent 70%);
    }
    .scan-sheet[data-result="error"] .scan-sheet-ambient {
        background:
            radial-gradient(ellipse 1300px 950px at 50% 48%, rgba(244, 63, 94, 0.32) 0%, rgba(136, 19, 55, 0.16) 45%, transparent 75%),
            radial-gradient(circle 460px at 50% 36%, rgba(251, 113, 133, 0.32) 0%, transparent 70%);
    }
}

/* Theatrical Pass Card */
.scan-sheet-card {
    position: relative;
    width: min(92vw, 24.5rem);
    max-height: calc(100dvh - max(32px, env(safe-area-inset-top) + env(safe-area-inset-bottom) + 16px));
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    padding: 24px 20px;
    border-radius: 26px;
    background: linear-gradient(180deg, #1c0a22 0%, #0c0412 100%);
    border: 1.5px solid var(--gate-border-highlight);
    box-shadow:
        0 24px 64px -10px rgba(0, 0, 0, 0.95),
        0 0 0 1px rgba(197, 160, 89, 0.2),
        inset 0 1px 0 rgba(255, 255, 255, 0.25);
    text-align: center;
    display: flex;
    flex-direction: column;
    gap: 16px;
    transform: scale(0.95) translateY(6px);
    opacity: 0;
    transition: transform 0.22s var(--gate-ease), opacity 0.22s var(--gate-ease);
}

@media (min-width: 1024px) {
    .scan-sheet-card {
        width: min(90vw, 32rem);
        padding: 34px 30px;
        gap: 20px;
        border-radius: 30px;
    }
}

.scan-sheet[data-state="visible"] .scan-sheet-card {
    transform: scale(1) translateY(0);
    opacity: 1;
}

.scan-sheet[data-result="ok"] .scan-sheet-card {
    border-color: rgba(16, 185, 129, 0.55);
    box-shadow:
        0 24px 64px -10px rgba(0, 0, 0, 0.95),
        0 0 35px rgba(16, 185, 129, 0.28),
        0 0 0 1px rgba(16, 185, 129, 0.45),
        inset 0 1px 0 rgba(255, 255, 255, 0.25);
}
.scan-sheet[data-result="used"] .scan-sheet-card {
    border-color: rgba(245, 158, 11, 0.55);
    box-shadow:
        0 24px 64px -10px rgba(0, 0, 0, 0.95),
        0 0 35px rgba(245, 158, 11, 0.28),
        0 0 0 1px rgba(245, 158, 11, 0.45),
        inset 0 1px 0 rgba(255, 255, 255, 0.25);
}
.scan-sheet[data-result="error"] .scan-sheet-card {
    border-color: rgba(244, 63, 94, 0.55);
    box-shadow:
        0 24px 64px -10px rgba(0, 0, 0, 0.95),
        0 0 35px rgba(244, 63, 94, 0.28),
        0 0 0 1px rgba(244, 63, 94, 0.45),
        inset 0 1px 0 rgba(255, 255, 255, 0.25);
}

/* 1. Hero Status Emblem (Dominant Visual Element) */
.scan-sheet-hero-status {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    padding-bottom: 2px;
}

.status-emblem-ring {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 72px;
    height: 72px;
    border-radius: 50%;
    border: 2.5px solid transparent;
    box-shadow: 0 0 35px rgba(0, 0, 0, 0.6);
    animation: emblemPulseIn 0.35s var(--gate-ease) both;
}

@media (min-width: 1024px) {
    .status-emblem-ring {
        width: 86px;
        height: 86px;
    }
}

@keyframes emblemPulseIn {
    0%   { transform: scale(0.65); opacity: 0; }
    70%  { transform: scale(1.08); }
    100% { transform: scale(1); opacity: 1; }
}

.status-emblem-icon {
    font-size: 34px;
    font-weight: 900;
    line-height: 1;
}

@media (min-width: 1024px) {
    .status-emblem-icon {
        font-size: 42px;
    }
}

.status-title-group {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
}

.status-headline {
    font-family: 'Alexandria', system-ui, sans-serif;
    font-size: 26px;
    font-weight: 900;
    line-height: 1.2;
    margin: 0;
    letter-spacing: -0.01em;
}

@media (min-width: 1024px) {
    .status-headline {
        font-size: 30px;
    }
}

.status-subline {
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0.02em;
}

/* Success Semantic Styling & Confirmation Pulse (under 450ms) */
.scan-sheet[data-result="ok"] .status-emblem-ring {
    background: radial-gradient(circle, rgba(16, 185, 129, 0.3) 0%, rgba(6, 78, 59, 0.4) 100%);
    border-color: #10b981;
    box-shadow: 0 0 35px rgba(16, 185, 129, 0.6), inset 0 0 15px rgba(16, 185, 129, 0.35);
    animation: successEmblemConfirm 0.44s var(--gate-ease) both;
}

@keyframes successEmblemConfirm {
    0%   { transform: scale(0.65); opacity: 0; filter: brightness(1.5); }
    55%  { transform: scale(1.12); filter: brightness(1.2); box-shadow: 0 0 45px rgba(16, 185, 129, 0.85); }
    100% { transform: scale(1); opacity: 1; filter: brightness(1); box-shadow: 0 0 35px rgba(16, 185, 129, 0.6); }
}

.scan-sheet[data-result="ok"] .status-emblem-icon {
    color: #34d399;
}
.scan-sheet[data-result="ok"] .status-headline {
    color: #6ee7b7;
    text-shadow: 0 2px 14px rgba(16, 185, 129, 0.5);
}
.scan-sheet[data-result="ok"] .status-subline {
    color: #a7f3d0;
}

/* Warning Semantic Styling & Cautionary Pulse (under 450ms) */
.scan-sheet[data-result="used"] .status-emblem-ring {
    background: radial-gradient(circle, rgba(245, 158, 11, 0.3) 0%, rgba(120, 53, 15, 0.4) 100%);
    border-color: #f59e0b;
    box-shadow: 0 0 35px rgba(245, 158, 11, 0.6), inset 0 0 15px rgba(245, 158, 11, 0.35);
    animation: warningEmblemPulse 0.44s var(--gate-ease) both;
}

@keyframes warningEmblemPulse {
    0%   { transform: scale(0.7); opacity: 0; }
    45%  { transform: scale(1.10); box-shadow: 0 0 45px rgba(245, 158, 11, 0.85); }
    100% { transform: scale(1); opacity: 1; box-shadow: 0 0 35px rgba(245, 158, 11, 0.6); }
}

.scan-sheet[data-result="used"] .status-emblem-icon {
    color: #fbbf24;
}
.scan-sheet[data-result="used"] .status-headline {
    color: #fde68a;
    text-shadow: 0 2px 14px rgba(245, 158, 11, 0.5);
}
.scan-sheet[data-result="used"] .status-subline {
    color: #fef08a;
}

/* Error Semantic Styling & Controlled Shake Response (under 450ms) */
.scan-sheet[data-result="error"] .status-emblem-ring {
    background: radial-gradient(circle, rgba(244, 63, 94, 0.3) 0%, rgba(136, 19, 55, 0.4) 100%);
    border-color: #f43f5e;
    box-shadow: 0 0 35px rgba(244, 63, 94, 0.6), inset 0 0 15px rgba(244, 63, 94, 0.35);
    animation: errorEmblemShake 0.40s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
}

@keyframes errorEmblemShake {
    0%        { transform: scale(0.75); opacity: 0; }
    20%, 60%  { transform: scale(1.05) translateX(-4px); box-shadow: 0 0 45px rgba(244, 63, 94, 0.85); }
    40%, 80%  { transform: scale(1.05) translateX(4px); }
    100%      { transform: scale(1) translateX(0); opacity: 1; box-shadow: 0 0 35px rgba(244, 63, 94, 0.6); }
}

.scan-sheet[data-result="error"] .status-emblem-icon {
    color: #fb7185;
}
.scan-sheet[data-result="error"] .status-headline {
    color: #fca5a5;
    text-shadow: 0 2px 14px rgba(244, 63, 94, 0.5);
}
.scan-sheet[data-result="error"] .status-subline {
    color: #fecdd3;
}

/* 2. Attendee Block */
.scan-sheet-attendee-block {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;
    padding: 10px 14px;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.06);
}

.attendee-label {
    font-size: 10px;
    color: var(--gate-text-muted);
    letter-spacing: 0.04em;
    font-weight: 600;
}

.scan-sheet-name {
    font-family: 'Alexandria', system-ui, sans-serif;
    font-size: 20px;
    font-weight: 800;
    color: #ffffff;
    line-height: 1.3;
}

@media (min-width: 1024px) {
    .scan-sheet-name {
        font-size: 24px;
    }
}

.scan-sheet-ref {
    direction: ltr !important;
    unicode-bidi: isolate;
    font-family: monospace;
    font-size: 13px;
    font-weight: 700;
    color: var(--gate-gold-glow);
    letter-spacing: 0.06em;
}
.scan-sheet-ref:empty { display: none; }

/* 3. Real Event & Showtime Block */
.scan-sheet-event-block {
    display: flex;
    flex-direction: column;
    gap: 7px;
    padding: 10px 14px;
    border-radius: 14px;
    background: rgba(197, 160, 89, 0.08);
    border: 1px solid rgba(197, 160, 89, 0.28);
}

.event-block-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.event-theatre-icon { font-size: 15px; }

.event-theatre-show {
    font-family: 'Alexandria', system-ui, sans-serif;
    font-weight: 800;
    font-size: 14px;
    color: #ffffff;
}

.event-block-timing {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    flex-wrap: wrap;
}

.timing-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    font-weight: 600;
    color: var(--gate-gold-glow);
    background: rgba(0, 0, 0, 0.35);
    padding: 3px 8px;
    border-radius: 6px;
    border: 1px solid rgba(197, 160, 89, 0.18);
}

/* 4. Already-Scanned Alert Note (ONLY visible on 'used') */
.scan-sheet-used-note {
    display: none;
    align-items: flex-start;
    gap: 10px;
    padding: 10px 14px;
    border-radius: 12px;
    background: rgba(245, 158, 11, 0.14);
    border: 1px solid rgba(245, 158, 11, 0.45);
    color: #fef3c7;
    text-align: start;
}

.scan-sheet-used-note .used-icon { font-size: 16px; flex-shrink: 0; }

.scan-sheet-used-note .used-text { display: flex; flex-direction: column; gap: 2px; }

.used-title { font-weight: 800; font-size: 12px; color: #fbbf24; }

.used-time-stamp { font-size: 11px; color: #fde68a; }

.scan-sheet[data-result="used"] .scan-sheet-used-note { display: flex !important; }
.scan-sheet:not([data-result="used"]) .scan-sheet-used-note { display: none !important; }

/* 5. Primary Next Action Button */
.scan-sheet-foot {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    padding-top: 2px;
}

.scan-sheet-done {
    width: 100%;
    min-height: 52px;
    padding: 14px 20px;
    border-radius: 16px;
    font-family: 'Alexandria', system-ui, sans-serif;
    font-size: 15px;
    font-weight: 800;
    color: #120904;
    background: linear-gradient(180deg, #fef08a 0%, #d4af37 50%, #b48328 100%);
    border: 1px solid rgba(255, 255, 255, 0.6);
    box-shadow: 0 8px 24px -4px rgba(197, 160, 89, 0.65), inset 0 1px 0 rgba(255, 255, 255, 0.7);
    cursor: pointer;
    touch-action: manipulation;
    transition: all 0.2s var(--gate-ease);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.scan-sheet-done:hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 28px -4px rgba(197, 160, 89, 0.85);
}

.scan-sheet-done:active {
    transform: scale(0.98);
}

.btn-primary-text {
    letter-spacing: 0.02em;
}

.btn-primary-arrow {
    font-size: 18px;
    font-weight: 900;
}

/* Accessibility & Reduced Motion */
@media (prefers-reduced-motion: reduce) {
    .optical-scan-beam,
    .scan-sheet,
    .scan-sheet-card,
    .portal-status-pill,
    .scan-stream-row,
    .live-beacon,
    .status-emblem-ring {
        animation: none !important;
        transition: none !important;
    }
    .portal-aperture-housing[data-state="error"] { animation: none !important; }
    .scan-sheet-card { transform: none !important; opacity: 1 !important; }
}
</style>

<script>
(() => {
    'use strict';

    /* ============================================================
       Scanner config (PRESERVED)
       ============================================================ */
    const COOLDOWN_MS = 700;

    let busy = false;
    let lastCode = null;
    let lastScanTime = 0;
    let sheetOpen = false;
    let qrInstance = null;

    /* ============================================================
       MULTI-SENSORY FEEDBACK ENGINE (APPLE PAY / PREMIUM POS SPEC)
       ============================================================ */
    let audioCtx = null;
    let audioUnlocked = false;

    function getAudioContext() {
        if (!audioCtx) {
            try {
                const AudioCtxClass = window.AudioContext || window.webkitAudioContext;
                if (AudioCtxClass) {
                    audioCtx = new AudioCtxClass();
                }
            } catch (_) {}
        }
        return audioCtx;
    }

    function unlockAudio() {
        try {
            const ctx = getAudioContext();
            if (!ctx) return;
            if (ctx.state === 'suspended' && typeof ctx.resume === 'function') {
                ctx.resume().then(() => {
                    audioUnlocked = true;
                }).catch(() => {});
            } else if (ctx.state === 'running') {
                audioUnlocked = true;
            }
        } catch (_) {}
    }

    // Proactively prime and unlock AudioContext on first user gesture (iOS Safari & Android compliant)
    ['touchstart', 'touchend', 'pointerdown', 'mousedown', 'keydown', 'click'].forEach((evt) => {
        document.addEventListener(evt, unlockAudio, { passive: true, capture: true });
    });

    // 1. Subtle Precheck Optical Click (30ms)
    function softTick() {
        try {
            const ctx = getAudioContext();
            if (!ctx || ctx.state !== 'running') return;
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.type = 'square';
            osc.frequency.value = 1600;
            const now = ctx.currentTime;
            gain.gain.setValueAtTime(0.0001, now);
            gain.gain.exponentialRampToValueAtTime(0.06, now + 0.004);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.035);
            osc.start(now);
            osc.stop(now + 0.04);
        } catch (_) {}
    }

    // 2. Synthesized Apple Pay / POS Chime (SUCCESS — ~160ms)
    function playSuccessChime(ctx) {
        const now = ctx.currentTime;
        const master = ctx.createGain();
        master.connect(ctx.destination);
        master.gain.setValueAtTime(0.20, now);

        // Harmonic Tone 1: C6 (1046.50 Hz) — crystal bell foundation
        const osc1 = ctx.createOscillator();
        const gain1 = ctx.createGain();
        osc1.type = 'sine';
        osc1.frequency.setValueAtTime(1046.50, now);
        gain1.gain.setValueAtTime(0.001, now);
        gain1.gain.exponentialRampToValueAtTime(0.70, now + 0.006);
        gain1.gain.exponentialRampToValueAtTime(0.0001, now + 0.135);
        osc1.connect(gain1);
        gain1.connect(master);
        osc1.start(now);
        osc1.stop(now + 0.14);

        // Harmonic Tone 2: E6 (1318.51 Hz) — radiant major third chime
        const osc2 = ctx.createOscillator();
        const gain2 = ctx.createGain();
        osc2.type = 'sine';
        osc2.frequency.setValueAtTime(1318.51, now + 0.024);
        gain2.gain.setValueAtTime(0.001, now);
        gain2.gain.setValueAtTime(0.001, now + 0.024);
        gain2.gain.exponentialRampToValueAtTime(0.85, now + 0.032);
        gain2.gain.exponentialRampToValueAtTime(0.0001, now + 0.160);
        osc2.connect(gain2);
        gain2.connect(master);
        osc2.start(now + 0.024);
        osc2.stop(now + 0.17);
    }

    // 3. Synthesized Cautionary Chime (WARNING / USED — ~175ms)
    function playWarningCue(ctx) {
        const now = ctx.currentTime;
        const master = ctx.createGain();
        master.connect(ctx.destination);
        master.gain.setValueAtTime(0.18, now);

        // Tone 1: D5 (587.33 Hz) — caution step
        const osc1 = ctx.createOscillator();
        const gain1 = ctx.createGain();
        osc1.type = 'triangle';
        osc1.frequency.setValueAtTime(587.33, now);
        gain1.gain.setValueAtTime(0.001, now);
        gain1.gain.exponentialRampToValueAtTime(0.75, now + 0.006);
        gain1.gain.exponentialRampToValueAtTime(0.0001, now + 0.075);
        osc1.connect(gain1);
        gain1.connect(master);
        osc1.start(now);
        osc1.stop(now + 0.08);

        // Tone 2: A4 (440.00 Hz) — descending minor interval
        const osc2 = ctx.createOscillator();
        const gain2 = ctx.createGain();
        osc2.type = 'triangle';
        osc2.frequency.setValueAtTime(440.00, now + 0.078);
        gain2.gain.setValueAtTime(0.001, now);
        gain2.gain.setValueAtTime(0.001, now + 0.078);
        gain2.gain.exponentialRampToValueAtTime(0.75, now + 0.085);
        gain2.gain.exponentialRampToValueAtTime(0.0001, now + 0.170);
        osc2.connect(gain2);
        gain2.connect(master);
        osc2.start(now + 0.078);
        osc2.stop(now + 0.18);
    }

    // 4. Synthesized Low Rejection Tone (ERROR / INVALID — ~150ms)
    function playErrorCue(ctx) {
        const now = ctx.currentTime;
        const master = ctx.createGain();
        master.connect(ctx.destination);
        master.gain.setValueAtTime(0.20, now);

        // Pulse 1: Low A3 (220.00 Hz)
        const osc1 = ctx.createOscillator();
        const gain1 = ctx.createGain();
        osc1.type = 'sine';
        osc1.frequency.setValueAtTime(220.00, now);
        gain1.gain.setValueAtTime(0.001, now);
        gain1.gain.exponentialRampToValueAtTime(0.65, now + 0.006);
        gain1.gain.exponentialRampToValueAtTime(0.0001, now + 0.045);
        osc1.connect(gain1);
        gain1.connect(master);
        osc1.start(now);
        osc1.stop(now + 0.05);

        // Pulse 2: Muted F3 (174.61 Hz)
        const osc2 = ctx.createOscillator();
        const gain2 = ctx.createGain();
        osc2.type = 'sine';
        osc2.frequency.setValueAtTime(174.61, now + 0.060);
        gain2.gain.setValueAtTime(0.001, now);
        gain2.gain.setValueAtTime(0.001, now + 0.060);
        gain2.gain.exponentialRampToValueAtTime(0.70, now + 0.066);
        gain2.gain.exponentialRampToValueAtTime(0.0001, now + 0.145);
        osc2.connect(gain2);
        gain2.connect(master);
        osc2.start(now + 0.060);
        osc2.stop(now + 0.15);
    }

    // Safe sound dispatch with state resolution
    function triggerSound(status) {
        try {
            const ctx = getAudioContext();
            if (!ctx) return;
            const play = () => {
                if (status === 'ok') playSuccessChime(ctx);
                else if (status === 'used') playWarningCue(ctx);
                else playErrorCue(ctx);
            };
            if (ctx.state === 'suspended' && typeof ctx.resume === 'function') {
                ctx.resume().then(play).catch(() => {});
            } else if (ctx.state === 'running') {
                play();
            }
        } catch (_) {}
    }

    // 5. Tactile Haptic Vibration Patterns
    function triggerHaptic(type) {
        if (!('vibrate' in navigator) || typeof navigator.vibrate !== 'function') return;
        try {
            if (type === 'precheck') {
                navigator.vibrate(25);
            } else if (type === 'ok') {
                // Short, clean confirmation vibration: tap -> confirmation (short -> short)
                navigator.vibrate([40, 45, 45]);
            } else if (type === 'used') {
                // Distinct medium -> short warning buzz
                navigator.vibrate([110, 60, 50]);
            } else if (type === 'error') {
                // Firm rejection double vibration (longer -> short)
                navigator.vibrate([160, 60, 120]);
            }
        } catch (_) {}
    }

    // Backward compatibility aliases
    function beep(type) { triggerSound(type); }
    function vibrate(type) { triggerHaptic(type); }

    /* ============================================================
       Status pill (PRESERVED)
       ============================================================ */
    const $status  = document.getElementById('status');
    const $stage   = document.querySelector('[data-scanner-stage]');
    const $loading = document.getElementById('scanner-loading');

    function setStatus(text, type) {
        if ($status) {
            $status.textContent = text;
            $status.classList.remove('state-ready', 'state-scanning', 'state-ok', 'state-used', 'state-error', 'is-pop');
            $status.classList.add('state-' + (type || 'ready'), 'is-pop');
            setTimeout(() => $status.classList.remove('is-pop'), 200);
        }
        if ($stage) {
            if (type === 'ok' || type === 'used' || type === 'error') {
                $stage.dataset.state = type;
            } else {
                delete $stage.dataset.state;
            }
        }
    }

    /* ============================================================
       Session Scans & Live History Feed (COMPACT OPERATIONS STREAM)
       ============================================================ */
    const sessionScans = [];
    const stats = { total: 0, ok: 0, used: 0, error: 0 };
    const $scansList  = document.getElementById('recent-scans-list');
    const $scansEmpty = document.getElementById('recent-scans-empty');
    const $statTotal  = document.getElementById('session-total-count');
    const $statOk     = document.getElementById('session-ok-count');
    const $statUsed   = document.getElementById('session-used-count');
    const $statError  = document.getElementById('session-error-count');

    // Desktop counterparts
    const $deskTotal = document.getElementById('desk-session-total-count');
    const $deskOk    = document.getElementById('desk-session-ok-count');
    const $deskUsed  = document.getElementById('desk-session-used-count');
    const $deskError = document.getElementById('desk-session-error-count');

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function updateRecentScansUI() {
        if ($statTotal) $statTotal.textContent = stats.total;
        if ($statOk)    $statOk.textContent    = stats.ok;
        if ($statUsed)  $statUsed.textContent  = stats.used;
        if ($statError) $statError.textContent = stats.error;

        if ($deskTotal) $deskTotal.textContent = stats.total;
        if ($deskOk)    $deskOk.textContent    = stats.ok;
        if ($deskUsed)  $deskUsed.textContent  = stats.used;
        if ($deskError) $deskError.textContent = stats.error;

        if (!$scansList) return;
        if (sessionScans.length === 0) {
            if ($scansEmpty) $scansEmpty.style.display = 'flex';
            $scansList.innerHTML = '';
            return;
        }
        if ($scansEmpty) $scansEmpty.style.display = 'none';

        // Keep 4-5 compact rows visible
        $scansList.innerHTML = sessionScans.slice(0, 5).map((item, idx) => {
            const isLatest = idx === 0;
            const statusIcon = item.status === 'ok' ? '✓' : item.status === 'used' ? '⚠️' : '✕';

            return `
                <div class="scan-stream-row state-${escapeHtml(item.status)} ${isLatest ? 'is-latest' : ''}">
                    <span class="stream-row-badge state-${escapeHtml(item.status)}">${statusIcon}</span>
                    <div class="stream-row-info">
                        <span class="stream-row-name">${escapeHtml(item.name || '—')}</span>
                        ${item.reference ? `<span class="stream-row-ref">#${escapeHtml(item.reference)}</span>` : ''}
                    </div>
                    <span class="stream-row-time" dir="ltr">${escapeHtml(item.time)}</span>
                </div>
            `;
        }).join('');
    }

    function addRecentScan(status, payload, code) {
        stats.total++;
        if (status === 'ok') stats.ok++;
        else if (status === 'used') stats.used++;
        else stats.error++;

        const p = payload || {};
        const now = new Date();
        const timeStr = now.toLocaleTimeString('ar-EG', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        });

        sessionScans.unshift({
            status: status,
            name: p.name || (status === 'error' ? 'تذكرة غير صالحة' : '—'),
            reference: p.reference || (code ? String(code).substring(0, 10) : ''),
            show_title: p.show_title || '',
            time: timeStr,
            scanned_at: p.scanned_at || null,
        });

        if (sessionScans.length > 20) {
            sessionScans.pop();
        }

        updateRecentScansUI();
    }

    /* ============================================================
       iOS Safari Modal Scroll-Locking Engine (ACCEPTANCE CRITERIA)
       ============================================================ */
    let savedScrollY = 0;

    function lockPageScroll() {
        savedScrollY = window.scrollY || window.pageYOffset || document.documentElement.scrollTop || 0;
        document.body.style.position = 'fixed';
        document.body.style.top = `-${savedScrollY}px`;
        document.body.style.left = '0';
        document.body.style.right = '0';
        document.body.style.width = '100%';
        document.body.style.overflow = 'hidden';
        document.documentElement.style.overflow = 'hidden';
    }

    function unlockPageScroll() {
        document.body.style.position = '';
        document.body.style.top = '';
        document.body.style.left = '';
        document.body.style.right = '';
        document.body.style.width = '';
        document.body.style.overflow = '';
        document.documentElement.style.overflow = '';
        window.scrollTo(0, savedScrollY);
    }

    /* ============================================================
       Result sheet (cinematic popup modal - PRESERVED SELECTORS)
       ============================================================ */
    const $sheet         = document.getElementById('scan-sheet');
    const $sheetBadge    = $sheet ? $sheet.querySelector('[data-scan-badge-text]') : null;
    const $sheetSubline  = $sheet ? $sheet.querySelector('[data-scan-subline]') : null;
    const $sheetIcon     = $sheet ? $sheet.querySelector('[data-scan-icon]') : null;
    const $sheetName     = $sheet ? $sheet.querySelector('[data-scan-name]') : null;
    const $sheetRef      = $sheet ? $sheet.querySelector('[data-scan-ref]') : null;
    const $sheetShow     = $sheet ? $sheet.querySelector('[data-scan-show]') : null;
    const $sheetWhen     = $sheet ? $sheet.querySelector('[data-scan-when]') : null;
    const $sheetUsedNote = $sheet ? $sheet.querySelector('[data-scan-used-note]') : null;
    const $sheetUsedTime = $sheet ? $sheet.querySelector('[data-scan-used-time]') : null;
    const $sheetDismiss  = $sheet ? $sheet.querySelector('[data-scan-dismiss]') : null;

    function showSheet(result, payload) {
        if (!$sheet) return;
        $sheet.dataset.result = result; // ok | used | error
        $sheet.dataset.state  = 'visible';
        sheetOpen = true;

        // Lock background scroll immediately (freeze document)
        lockPageScroll();

        if ($sheetBadge) {
            $sheetBadge.textContent =
                result === 'ok'   ? 'دخول مسموح' :
                result === 'used' ? 'تذكرة مستخدمة مسبقاً' :
                                    'تذكرة غير صالحة';
        }
        if ($sheetSubline) {
            $sheetSubline.textContent =
                result === 'ok'   ? 'تذكرة صالحة ومؤكدة' :
                result === 'used' ? 'تم مسحها والتحقق منها مسبقاً' :
                                    'الرمز غير مسجل أو غير معتمد';
        }
        if ($sheetIcon) {
            $sheetIcon.textContent =
                result === 'ok'   ? '✓' :
                result === 'used' ? '⚠️' :
                                    '✕';
        }

        const p = payload || {};

        if ($sheetName) $sheetName.textContent = p.name || (result === 'error' ? 'تذكرة غير مسجلة' : '—');
        if ($sheetRef)  $sheetRef.textContent  = p.reference ? '#' + p.reference : '';

        if (p.show_title && $sheetShow) {
            $sheetShow.textContent = p.show_title;
        }
        const dateStr = p.date || '';
        const timeStr = p.time || '';
        if ($sheetWhen) {
            if (dateStr || timeStr) {
                $sheetWhen.innerHTML = `
                    ${dateStr ? `<span class="timing-badge"><span class="timing-icon" aria-hidden="true">🗓️</span><span class="timing-text">${escapeHtml(dateStr)}</span></span>` : ''}
                    ${timeStr ? `<span class="timing-badge"><span class="timing-icon" aria-hidden="true">🕘</span><span class="timing-text">${escapeHtml(timeStr)}</span></span>` : ''}
                `;
            }
        }

        if (result === 'used' && p.scanned_at) {
            if ($sheetUsedNote) $sheetUsedNote.hidden = false;
            if ($sheetUsedTime) $sheetUsedTime.textContent = 'تاريخ المسح السابق: ' + p.scanned_at;
        } else {
            if ($sheetUsedNote) $sheetUsedNote.hidden = true;
            if ($sheetUsedTime) $sheetUsedTime.textContent = '';
        }

        if (qrInstance) {
            try { qrInstance.pause(true); } catch (_) {}
        }
    }

    function hideSheet() {
        if (!$sheet) return;
        $sheet.dataset.state = 'hidden';
        sheetOpen = false;
        unlockPageScroll();
        if ($stage) delete $stage.dataset.state;
        setStatus('جاهز للمسح', 'ready');
        lastCode = null;
        lastScanTime = 0;
        lastFeedbackCode = null;
        lastFeedbackTime = 0;
        if (qrInstance) {
            try { qrInstance.resume(); } catch (_) {}
        }
    }

    // Expose helpers for test automation & manual override
    window.showSheet = showSheet;
    window.hideSheet = hideSheet;

    if ($sheetDismiss) $sheetDismiss.addEventListener('click', hideSheet);
    if ($sheet) {
        $sheet.addEventListener('click', (e) => {
            if (e.target === $sheet) hideSheet();
        });
        // Touch scroll lock on iOS backdrop
        $sheet.addEventListener('touchmove', (e) => {
            const card = e.target.closest('[data-scan-card]');
            if (!card) {
                e.preventDefault();
                return;
            }
            if (card.scrollHeight <= card.clientHeight) {
                e.preventDefault();
            }
        }, { passive: false });
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sheetOpen) hideSheet();
    });

    /* ============================================================
       CENTRALIZED MULTI-SENSORY FEEDBACK ORCHESTRATOR
       (VISUAL + HAPTIC + SOUND SYNCHRONIZATION & DEBOUNCE GUARD)
       ============================================================ */
    let lastFeedbackCode = null;
    let lastFeedbackTime = 0;
    const FEEDBACK_DEBOUNCE_MS = 1200;

    function triggerScanFeedback(status, payload, code) {
        const now = Date.now();

        // 1. Debounce guard: prevent repeated feedback if the same QR stays in view
        if (sheetOpen && code && code === lastFeedbackCode) {
            return;
        }
        if (code && code === lastFeedbackCode && now - lastFeedbackTime < FEEDBACK_DEBOUNCE_MS) {
            return;
        }

        lastFeedbackCode = code || null;
        lastFeedbackTime = now;

        // 2. Synchronized Tactile Haptic Pattern (0ms)
        triggerHaptic(status);

        // 3. Synchronized Web Audio POS Sound Cue (0ms)
        triggerSound(status);

        // 4. Synchronized Visual Status Indication (0ms)
        if (status === 'ok') {
            setStatus('✅ دخول مسموح', 'ok');
        } else if (status === 'used') {
            setStatus('⚠️ مستخدمة', 'used');
        } else {
            setStatus('❌ غير صالح', 'error');
        }

        // 5. Present Admission Pass Modal & Record Ledger Stream
        showSheet(status, payload);
        addRecentScan(status, payload, code);
    }

    // Expose helpers for automation testing & verification
    window.triggerScanFeedback = triggerScanFeedback;
    window.triggerSound = triggerSound;
    window.triggerHaptic = triggerHaptic;
    window.getAudioContext = getAudioContext;

    /* ============================================================
       Backend round-trip (PRESERVED)
       ============================================================ */
    function check(code) {
        fetch('{{ route('admin.scanner.check') }}', {
            method:  'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept':       'application/json',
            },
            body: JSON.stringify({ code }),
        })
        .then((r) => r.json())
        .then((d) => {
            if (d.status === 'ok') {
                triggerScanFeedback('ok', d, code);
            } else if (d.status === 'used') {
                triggerScanFeedback('used', d, code);
            } else {
                triggerScanFeedback('error', d || {}, code);
            }
        })
        .catch(() => {
            triggerScanFeedback('error', { name: 'تعذّر الاتصال بالخادم' }, code);
        })
        .finally(() => {
            setTimeout(() => { busy = false; }, 250);
        });
    }

    /* ============================================================
       Shared scan-success funnel (PRESERVED)
       ============================================================ */
    function onScanSuccess(text) {
        if (sheetOpen) return;
        const now = Date.now();
        if (text === lastCode && now - lastScanTime < COOLDOWN_MS) return;
        if (busy) return;
        busy = true;
        lastCode = text;
        lastScanTime = now;

        vibrate('precheck');
        softTick();

        setStatus('⏳ جارٍ التحقق', 'scanning');
        check(text);
    }

    /* ============================================================
       Capability cache + zoom / torch / pinch (PRESERVED)
       ============================================================ */
    let activeTrack = null;
    const capability = {
        torch:    false,
        zoom:     false,
        zoomMin:  1,
        zoomMax:  1,
        zoomStep: 0.1,
    };
    let zoomCurrent     = 1;
    let zoomBaseAtTouch = 1;
    let pinchStartDist  = 0;
    let lastLuminance   = 255;
    let lowLightTimer   = null;
    let pinchAttachedTo = null;

    const $zoomChip      = document.getElementById('scan-zoom-chip');
    const $zoomChipText  = $zoomChip ? $zoomChip.querySelector('[data-zoom-text]') : null;

    function setZoomChip(level) {
        if (!$zoomChip || !$zoomChipText) return;
        if (level > 1.05) {
            $zoomChipText.textContent = level.toFixed(1) + '×';
            $zoomChip.classList.add('is-visible');
        } else {
            $zoomChip.classList.remove('is-visible');
        }
    }

    function updateCapabilities(track) {
        try {
            if (!track || typeof track.getCapabilities !== 'function') return;
            const caps = track.getCapabilities();
            capability.torch = !!('torch' in caps);
            if (caps.zoom) {
                if (typeof caps.zoom === 'object') {
                    capability.zoom     = true;
                    capability.zoomMin  = caps.zoom.min  || 1;
                    capability.zoomMax  = caps.zoom.max  || 1;
                    capability.zoomStep = caps.zoom.step || 0.1;
                } else if (typeof caps.zoom === 'number') {
                    capability.zoom    = true;
                    capability.zoomMin = 1;
                    capability.zoomMax = caps.zoom;
                }
            }
            if (capability.zoomMax <= capability.zoomMin) {
                capability.zoom = false;
            }
        } catch (_) {}
    }

    async function setZoom(level) {
        if (!capability.zoom || !activeTrack ||
            typeof activeTrack.applyConstraints !== 'function') {
            return false;
        }
        const clamped = Math.max(
            capability.zoomMin,
            Math.min(capability.zoomMax, level || 1)
        );
        try {
            await activeTrack.applyConstraints({ advanced: [{ zoom: clamped }] });
            zoomCurrent = clamped;
            setZoomChip(clamped);
            return true;
        } catch (_) {
            return false;
        }
    }

    function touchDistance(touches) {
        if (!touches || touches.length < 2) return 0;
        const dx = touches[0].clientX - touches[1].clientX;
        const dy = touches[0].clientY - touches[1].clientY;
        return Math.hypot(dx, dy);
    }

    function attachPinchZoom(host) {
        if (!host || !capability.zoom) return;
        if (pinchAttachedTo === host) return;
        pinchAttachedTo = host;

        host.addEventListener('touchstart', (e) => {
            if (e.touches.length === 2) {
                pinchStartDist  = touchDistance(e.touches);
                zoomBaseAtTouch = zoomCurrent;
            }
        }, { passive: true });

        host.addEventListener('touchmove', (e) => {
            if (e.touches.length === 2 && pinchStartDist > 0) {
                try { e.preventDefault(); } catch (_) {}
                const newDist = touchDistance(e.touches);
                if (newDist > 0) {
                    const scale = newDist / pinchStartDist;
                    setZoom(zoomBaseAtTouch * scale);
                }
            }
        }, { passive: false });

        host.addEventListener('touchend', () => {
            pinchStartDist  = 0;
            zoomBaseAtTouch = zoomCurrent;
        }, { passive: true });

        host.addEventListener('touchcancel', () => {
            pinchStartDist  = 0;
            zoomBaseAtTouch = zoomCurrent;
        }, { passive: true });
    }

    function startLowLightWatchdog() {
        if (lowLightTimer) return;
        lowLightTimer = setInterval(() => {
            try {
                const $btn = document.getElementById('flashBtn');
                if (!$btn) return;
                if (!capability.torch || flashOn) {
                    $btn.classList.remove('is-suggest');
                    return;
                }
                if (lastLuminance < 60) {
                    $btn.classList.add('is-suggest');
                } else {
                    $btn.classList.remove('is-suggest');
                }
            } catch (_) {}
        }, 1000);
    }

    function setupCapabilityFeatures(track, hostEl) {
        if (!track) return;
        updateCapabilities(track);
        if (capability.zoom && hostEl) attachPinchZoom(hostEl);
        startLowLightWatchdog();
    }

    /* ============================================================
       Luminance + image-preprocessing helpers (PRESERVED)
       ============================================================ */
    function packLuminance(rgbaData) {
        const len = rgbaData.length >>> 2;
        const out = new Uint8ClampedArray(len);
        for (let i = 0, j = 0; i < rgbaData.length; i += 4, j++) {
            out[j] = (
                rgbaData[i]     * 0.299 +
                rgbaData[i + 1] * 0.587 +
                rgbaData[i + 2] * 0.114
            ) | 0;
        }
        return out;
    }

    function meanLuminance(lum) {
        if (!lum || lum.length === 0) return 255;
        let sum = 0, count = 0;
        for (let i = 0; i < lum.length; i += 16) {
            sum += lum[i];
            count++;
        }
        return count > 0 ? (sum / count) | 0 : 255;
    }

    function thresholdLuminance(lum, mean) {
        const t = mean;
        const out = new Uint8ClampedArray(lum.length);
        for (let i = 0; i < lum.length; i++) {
            out[i] = lum[i] >= t ? 255 : 0;
        }
        return out;
    }

    function invertLuminance(lum) {
        const out = new Uint8ClampedArray(lum.length);
        for (let i = 0; i < lum.length; i++) {
            out[i] = 255 - lum[i];
        }
        return out;
    }

    function stretchContrastLuminance(lum) {
        let min = 255, max = 0;
        for (let i = 0; i < lum.length; i += 8) {
            const v = lum[i];
            if (v < min) min = v;
            if (v > max) max = v;
        }
        if (max - min < 30) return null;
        const range = max - min;
        const out = new Uint8ClampedArray(lum.length);
        for (let i = 0; i < lum.length; i++) {
            out[i] = (((lum[i] - min) * 255) / range) | 0;
        }
        return out;
    }

    /* ============================================================
       Decode Engine A: Native BarcodeDetector (PRESERVED)
       ============================================================ */
    async function startNativeBarcodeDetector() {
        if (!('BarcodeDetector' in window)) return null;
        try {
            const formats = await BarcodeDetector.getSupportedFormats();
            if (!formats || !formats.includes('qr_code')) return null;
        } catch (_) {
            return null;
        }

        const detector = new BarcodeDetector({ formats: ['qr_code'] });
        const stream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: { ideal: 'environment' },
                width:      { ideal: 1280 },
                height:     { ideal: 720 },
            },
            audio: false,
        });

        const video = document.createElement('video');
        video.setAttribute('playsinline', 'true');
        video.setAttribute('webkit-playsinline', 'true');
        video.muted = true;
        video.srcObject = stream;

        const container = document.getElementById('qr-reader');
        container.innerHTML = '';
        container.appendChild(video);

        await video.play();

        const track = stream.getVideoTracks()[0] || null;
        activeTrack = track;
        setupCapabilityFeatures(track, container);

        let running = true;
        let paused  = false;

        const loop = async () => {
            if (!running) return;
            if (!paused && video.readyState >= HTMLMediaElement.HAVE_CURRENT_DATA) {
                try {
                    const barcodes = await detector.detect(video);
                    if (barcodes && barcodes.length > 0) {
                        const raw = barcodes[0].rawValue;
                        if (raw) onScanSuccess(raw);
                    }
                } catch (_) {}
            }
            requestAnimationFrame(loop);
        };
        requestAnimationFrame(loop);

        return {
            pause(sh) { paused = true; },
            resume()   { paused = false; },
            stop()     {
                running = false;
                try { stream.getTracks().forEach((t) => t.stop()); } catch (_) {}
            },
            applyVideoConstraints(c) {
                if (track && track.applyConstraints) return track.applyConstraints(c);
                return Promise.resolve();
            },
        };
    }

    /* ============================================================
       Decode Engine B: ZXing-js manual frame loop (PRESERVED)
       ============================================================ */
    async function startZXing() {
        if (typeof ZXing === 'undefined') return null;

        const hints = new Map();
        if (ZXing.DecodeHintType && ZXing.BarcodeFormat) {
            hints.set(ZXing.DecodeHintType.POSSIBLE_FORMATS, [ZXing.BarcodeFormat.QR_CODE]);
            hints.set(ZXing.DecodeHintType.TRY_HARDER, true);
        }

        const reader = new ZXing.BrowserQRCodeReader(hints, 250);
        const stream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: { ideal: 'environment' },
                width:      { ideal: 1280 },
                height:     { ideal: 720 },
            },
            audio: false,
        });

        const video = document.createElement('video');
        video.setAttribute('playsinline', 'true');
        video.setAttribute('webkit-playsinline', 'true');
        video.muted = true;
        video.srcObject = stream;

        const container = document.getElementById('qr-reader');
        container.innerHTML = '';
        container.appendChild(video);

        await video.play();

        const track = stream.getVideoTracks()[0] || null;
        activeTrack = track;
        setupCapabilityFeatures(track, container);

        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d', { willReadFrequently: true });

        let running = true;
        let paused = false;
        let pass = 0;

        const zxingReader = new ZXing.QRCodeReader();

        function tryDecodeLuminance(lum, width, height) {
            try {
                const source = new ZXing.RGBLuminanceSource(lum, width, height);
                const binarizer = new ZXing.HybridBinarizer(source);
                const bitmap = new ZXing.BinaryBitmap(binarizer);
                return zxingReader.decode(bitmap, hints);
            } catch (_) {
                return null;
            }
        }

        const loop = () => {
            if (!running) return;
            if (!paused && video.readyState >= HTMLMediaElement.HAVE_CURRENT_DATA) {
                const vw = video.videoWidth;
                const vh = video.videoHeight;
                if (vw > 0 && vh > 0) {
                    const targetW = Math.min(640, vw);
                    const targetH = Math.round((vh / vw) * targetW);
                    if (canvas.width !== targetW || canvas.height !== targetH) {
                        canvas.width  = targetW;
                        canvas.height = targetH;
                    }

                    ctx.drawImage(video, 0, 0, targetW, targetH);
                    const img = ctx.getImageData(0, 0, targetW, targetH);
                    const lum = packLuminance(img.data);
                    const mean = meanLuminance(lum);
                    lastLuminance = mean;

                    // Fast path: attempt immediate standard decode on every frame
                    let result = tryDecodeLuminance(lum, targetW, targetH);

                    // Fallback path: if standard decode didn't catch it, rotate through specialized filters
                    if (!result) {
                        pass = (pass + 1) % 3;
                        if (pass === 0) {
                            const bin = thresholdLuminance(lum, mean);
                            result = tryDecodeLuminance(bin, targetW, targetH);
                        } else if (pass === 1) {
                            const inv = invertLuminance(lum);
                            result = tryDecodeLuminance(inv, targetW, targetH);
                        } else {
                            const stretched = stretchContrastLuminance(lum);
                            if (stretched) {
                                result = tryDecodeLuminance(stretched, targetW, targetH);
                            }
                        }
                    }

                    if (result && result.getText && result.getText()) {
                        onScanSuccess(result.getText());
                    }
                }
            }
            requestAnimationFrame(loop);
        };
        requestAnimationFrame(loop);

        return {
            pause(sh) { paused = true; },
            resume()   { paused = false; },
            stop()     {
                running = false;
                try { stream.getTracks().forEach((t) => t.stop()); } catch (_) {}
            },
            applyVideoConstraints(c) {
                if (track && track.applyConstraints) return track.applyConstraints(c);
                return Promise.resolve();
            },
        };
    }

    /* ============================================================
       Decode Engine C: html5-qrcode fallback (PRESERVED)
       ============================================================ */
    async function startHtml5QrcodeFallback() {
        if (typeof Html5Qrcode === 'undefined') return null;

        const qr = new Html5Qrcode('qr-reader', {
            verbose: false,
            formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
        });

        const config = {
            fps: 24,
            qrbox: (viewfinderWidth, viewfinderHeight) => {
                const minEdge = Math.min(viewfinderWidth, viewfinderHeight);
                const edge = Math.floor(minEdge * 0.88);
                return { width: edge, height: edge };
            },
            aspectRatio: 1.0,
            showTorchButtonIfSupported: false,
            videoConstraints: {
                facingMode: { ideal: 'environment' },
                width:      { ideal: 1280 },
                height:     { ideal: 720 },
                advanced: [
                    { focusMode: 'continuous' },
                    { focusMode: 'continuous-picture' },
                ],
            },
        };

        return qr.start(
            { facingMode: 'environment' },
            config,
            onScanSuccess,
            () => {}
        ).then(() => {
            qrInstance = qr;
            $loading.classList.add('is-hidden');
            try {
                const v = document.querySelector('#qr-reader video');
                if (v) {
                    v.setAttribute('playsinline', 'true');
                    v.setAttribute('webkit-playsinline', 'true');
                    v.muted = true;
                    try {
                        const s = v.srcObject;
                        if (s && s.getVideoTracks) {
                            activeTrack = s.getVideoTracks()[0] || null;
                        }
                    } catch (_) {}
                    try { setupCapabilityFeatures(activeTrack, v); } catch (_) {}
                }
            } catch (_) {}
            try {
                qr.applyVideoConstraints({
                    advanced: [
                        { focusMode: 'continuous' },
                        { focusMode: 'continuous-picture' },
                        { focusDistance: { ideal: 0.05 } },
                        { exposureMode: 'continuous' },
                        { whiteBalanceMode: 'continuous' },
                    ],
                }).catch(() => {});
            } catch (_) {}
            return qr;
        });
    }

    /* ============================================================
       Bootstrap — Path A → Path B → Path C (PRESERVED)
       ============================================================ */
    let scannerStarted = false;

    function markScannerReady() {
        scannerStarted = true;
        if ($loading) $loading.classList.add('is-hidden');
        setStatus('جاهز للمسح', 'ready');
    }

    function markScannerFailed() {
        if (scannerStarted) return;
        scannerStarted = true;
        if ($loading) $loading.classList.add('is-hidden');
        setStatus('⚠️ تعذّر تشغيل الكاميرا', 'error');
    }

    const BOOTSTRAP_TIMEOUT_MS = 6000;
    const bootstrapTimeoutId = setTimeout(markScannerFailed, BOOTSTRAP_TIMEOUT_MS);

    window.addEventListener('error', markScannerFailed);
    window.addEventListener('unhandledrejection', markScannerFailed);

    (async () => {
        try {
            const native = await startNativeBarcodeDetector();
            if (native) {
                qrInstance = native;
                clearTimeout(bootstrapTimeoutId);
                markScannerReady();
                return;
            }
        } catch (_) {}

        try {
            const zx = await startZXing();
            if (zx) {
                qrInstance = zx;
                clearTimeout(bootstrapTimeoutId);
                markScannerReady();
                return;
            }
        } catch (_) {}

        try {
            await startHtml5QrcodeFallback();
            clearTimeout(bootstrapTimeoutId);
            scannerStarted = true;
        } catch (_) {
            clearTimeout(bootstrapTimeoutId);
            markScannerFailed();
        }
    })();

    /* ============================================================
       Flash / restart controls (PRESERVED)
       ============================================================ */
    let flashOn = false;
    const $flashBtn = document.getElementById('flashBtn');
    if ($flashBtn) {
        $flashBtn.addEventListener('click', async () => {
            try {
                if (!activeTrack || !activeTrack.applyConstraints) {
                    alert('الفلاش غير مدعوم على هذا الجهاز');
                    return;
                }
                const caps = activeTrack.getCapabilities ? activeTrack.getCapabilities() : {};
                if (!('torch' in caps)) {
                    alert('الفلاش غير مدعوم على هذا الجهاز');
                    return;
                }
                flashOn = !flashOn;
                await activeTrack.applyConstraints({ advanced: [{ torch: flashOn }] });
                $flashBtn.classList.toggle('is-on', flashOn);
                $flashBtn.classList.remove('is-suggest');
            } catch (_) {
                alert('الفلاش غير مدعوم على هذا الجهاز');
                flashOn = false;
                $flashBtn.classList.remove('is-on');
                $flashBtn.classList.remove('is-suggest');
            }
        });
    }

    const $restartBtn = document.getElementById('restartBtn');
    if ($restartBtn) {
        $restartBtn.addEventListener('click', () => location.reload());
    }
})();
</script>
@endsection
