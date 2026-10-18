@extends('layouts.app')

@section('title', 'فاحص تذاكر الدخول — فريق الصرخة')

@section('content')
{{--
    Elsar5a Team — Premium Gate Scanner
    Professional mobile-first operational interface for gate check-in.

    Structure:
      1. Top operational bar with brand status and quick navigation.
      2. Console layout (2-column on desktop, single-column on mobile):
         - Primary: Camera viewport (#qr-reader) + Reticle + Status pill (#status) + Quick controls (#flashBtn, #restartBtn).
         - Secondary: Real-time session stats + Recent scans activity feed ("آخر عمليات المسح").
      3. Modal verification sheet (#scan-sheet) centered on viewport.

    Scanning engines (LOCKED & PRESERVED):
      - Path A: Native BarcodeDetector (Chrome/Edge Android)
      - Path B: ZXing-js manual frame loop (iOS Safari & Desktop)
      - Path C: html5-qrcode fallback
--}}

<section data-scanner-root class="scanner-root-container">

    {{-- TOP OPERATIONAL BAR --}}
    <header class="scanner-top-bar">
        <div class="scanner-brand-lockup">
            <div class="scanner-live-chip">
                <span class="scanner-live-pulse" aria-hidden="true"></span>
                <span>فاحص البوابات المباشر</span>
            </div>
            <h1 class="scanner-title">
                فحص تذاكر الدخول
                <span class="scanner-title-badge">Elsar5a Gate</span>
            </h1>
        </div>

        <div class="scanner-top-actions">
            @auth
                <a href="{{ route('admin.dashboard') }}" class="scanner-back-btn" aria-label="العودة للوحة التحكم">
                    <span aria-hidden="true" class="pt-arrow-rtl">←</span>
                    <span>لوحة التحكم</span>
                </a>
            @else
                <a href="{{ url('/') }}" class="scanner-back-btn" aria-label="العودة للصفحة الرئيسية">
                    <span aria-hidden="true" class="pt-arrow-rtl">←</span>
                    <span>الرئيسية</span>
                </a>
            @endauth
        </div>
    </header>

    {{-- OPERATIONAL CONSOLE LAYOUT --}}
    <div class="scanner-console-layout">

        {{-- PRIMARY COLUMN: CAMERA & CONTROLS --}}
        <div class="scanner-primary-column">

            {{-- CAMERA STAGE --}}
            <div class="scanner-stage" data-scanner-stage>

                {{-- Camera mount. html5-qrcode / ZXing / BarcodeDetector injects <video> here. --}}
                <div id="qr-reader" class="scanner-video"></div>

                {{-- Live zoom indicator --}}
                <div id="scan-zoom-chip" class="scan-zoom-chip" aria-hidden="true">
                    <span data-zoom-text>1.0×</span>
                </div>

                {{-- Theatrical reticle frame + gold laser sweep --}}
                <div class="scanner-overlay" aria-hidden="true">
                    <div class="scanner-reticle">
                        <span class="reticle-corner tl"></span>
                        <span class="reticle-corner tr"></span>
                        <span class="reticle-corner bl"></span>
                        <span class="reticle-corner br"></span>
                        <span class="reticle-line"></span>
                    </div>
                </div>

                {{-- Floating Status Pill --}}
                <div id="status"
                     class="scanner-status state-ready"
                     role="status"
                     aria-live="polite">
                    جاهز للفحص
                </div>

                {{-- Camera Loading Overlay --}}
                <div id="scanner-loading" class="scanner-loading">
                    <div class="scanner-spinner" aria-hidden="true"></div>
                    <div class="scanner-loading-text">
                        جاري تشغيل الكاميرا…
                    </div>
                </div>
            </div>

            {{-- QUICK CONTROLS --}}
            <div class="scanner-controls">
                <button id="flashBtn" class="scanner-ctrl-btn" type="button" aria-label="تشغيل كشاف الإضاءة">
                    <span class="ctrl-icon" aria-hidden="true">🔦</span>
                    <span class="ctrl-label">كشاف الإضاءة</span>
                </button>
                <button id="restartBtn" class="scanner-ctrl-btn" type="button" aria-label="إعادة تهيئة الكاميرا">
                    <span class="ctrl-icon" aria-hidden="true">🔄</span>
                    <span class="ctrl-label">إعادة تشغيل</span>
                </button>
            </div>

        </div>

        {{-- SECONDARY COLUMN: SESSION STATS & RECENT SCANS --}}
        <div class="scanner-secondary-column">

            <div class="scanner-history-card">
                <div class="history-card-header">
                    <div class="history-header-title">
                        <span class="history-header-icon" aria-hidden="true">📋</span>
                        <h2>آخر عمليات المسح</h2>
                    </div>

                    {{-- Quick Session Stats --}}
                    <div class="history-stats-pills" aria-label="إحصائيات الجلسة الحالية">
                        <div class="stat-pill is-ok" title="تذاكر صالحة مصرح بدخولها">
                            <span class="stat-label">مقبول:</span>
                            <strong id="session-ok-count">0</strong>
                        </div>
                        <div class="stat-pill is-used" title="تذاكر مستخدمة مسبقاً">
                            <span class="stat-label">مكرر:</span>
                            <strong id="session-used-count">0</strong>
                        </div>
                        <div class="stat-pill is-error" title="تذاكر غير صالحة أو مرفوضة">
                            <span class="stat-label">مرفوض:</span>
                            <strong id="session-error-count">0</strong>
                        </div>
                        <div class="stat-pill is-total" title="إجمالي عمليات الفحص">
                            <span class="stat-label">الإجمالي:</span>
                            <strong id="session-total-count">0</strong>
                        </div>
                    </div>
                </div>

                <div class="history-card-body">
                    {{-- Empty State --}}
                    <div id="recent-scans-empty" class="history-empty-state">
                        <div class="empty-icon" aria-hidden="true">🎫</div>
                        <p class="empty-text">لم يتم مسح أي تذاكر في هذه الجلسة بعد</p>
                        <span class="empty-sub">وجّه الكاميرا نحو رمز QR على التذكرة لبدء الفحص التلقائي</span>
                    </div>

                    {{-- Populated List --}}
                    <div id="recent-scans-list" class="history-scans-list" role="log" aria-live="polite"></div>
                </div>
            </div>

        </div>

    </div>

</section>

{{-- PREMIUM SCAN-RESULT SHEET (MODAL POPUP) --}}
<div id="scan-sheet"
     class="scan-sheet"
     data-state="hidden"
     role="dialog"
     aria-modal="true"
     aria-live="polite"
     aria-labelledby="scan-sheet-title">

    <div class="scan-sheet-card" data-scan-card>

        {{-- Status badge — color reflects ok / used / error --}}
        <div class="scan-sheet-badge" data-scan-badge>
            <span class="scan-sheet-badge-icon" data-scan-icon>✓</span>
            <span class="scan-sheet-badge-text" data-scan-badge-text>
                دخول مسموح
            </span>
        </div>

        {{-- Attendee details --}}
        <div class="scan-sheet-info-group">
            <div class="scan-sheet-name" id="scan-sheet-title" data-scan-name>—</div>
            <div class="scan-sheet-ref" data-scan-ref></div>
        </div>

        {{-- Show / showtime metadata --}}
        <div class="scan-sheet-details">
            <div class="scan-sheet-row">
                <span class="scan-sheet-row-icon" aria-hidden="true">🎭</span>
                <span class="scan-sheet-row-text" data-scan-show>—</span>
            </div>
            <div class="scan-sheet-row">
                <span class="scan-sheet-row-icon" aria-hidden="true">🕒</span>
                <span class="scan-sheet-row-text" data-scan-when>—</span>
            </div>
        </div>

        {{-- Already-scanned note --}}
        <div class="scan-sheet-used-note" data-scan-used-note hidden>
            <span class="used-icon" aria-hidden="true">⚠️</span>
            <div class="used-text">
                <span>هذه التذكرة تم استخدامها سابقًا</span>
                <strong data-scan-used-time></strong>
            </div>
        </div>

        {{-- Footer dismiss button --}}
        <div class="scan-sheet-foot">
            <button type="button"
                    class="scan-sheet-done"
                    data-scan-dismiss>
                <span>تم — التالي</span>
                <span class="done-arrow" aria-hidden="true">✓</span>
            </button>
            <span class="scan-sheet-hint">
                اضغط في أي مكان لمتابعة المسح
            </span>
        </div>
    </div>
</div>

{{-- Self-hosted decode engines (LOCKED) --}}
<script src="{{ asset('vendor/zxing/library-0.21.3.min.js') }}"></script>
<script src="{{ asset('vendor/html5-qrcode/html5-qrcode-2.3.8.min.js') }}"></script>

<style>
/* =========================================================
   ELSAR5A SCANNER THEME TOKENS
   Grounding the operational scanner in the dark navy, velvet
   burgundy, and restrained warm theatrical gold identity.
   ========================================================= */
[data-scanner-root] {
    --stage-bg: #020617;
    --stage-card: linear-gradient(180deg, rgba(17, 24, 39, 0.75) 0%, rgba(9, 12, 22, 0.92) 100%);
    --stage-card-alt: linear-gradient(180deg, rgba(24, 30, 48, 0.6) 0%, rgba(11, 15, 27, 0.8) 100%);
    --stage-border: rgba(255, 255, 255, 0.08);
    --stage-border-gold: rgba(245, 158, 11, 0.28);
    --stage-gold: #fbbf24;
    --stage-gold-glow: rgba(251, 191, 36, 0.35);
    --stage-text: #f8fafc;
    --stage-text-muted: #94a3b8;
    --stage-text-dim: #64748b;

    --scan-ok: #10b981;
    --scan-ok-bg: rgba(16, 185, 129, 0.16);
    --scan-ok-border: rgba(16, 185, 129, 0.45);

    --scan-used: #f59e0b;
    --scan-used-bg: rgba(245, 158, 11, 0.16);
    --scan-used-border: rgba(245, 158, 11, 0.45);

    --scan-error: #f43f5e;
    --scan-error-bg: rgba(244, 63, 94, 0.16);
    --scan-error-border: rgba(244, 63, 94, 0.45);

    --ease-out-quint: cubic-bezier(0.22, 1, 0.36, 1);
    --scan-shell-radius: 22px;
}

/* Base Container */
.scanner-root-container {
    max-width: 1080px;
    margin: 0 auto;
    padding: 8px 12px 28px;
    padding-bottom: max(28px, env(safe-area-inset-bottom));
    display: flex;
    flex-direction: column;
    gap: 16px;
    color: var(--stage-text);
    font-family: 'Readex Pro', system-ui, -apple-system, sans-serif;
    animation: scannerFadeUp 0.4s var(--ease-out-quint) both;
}

@keyframes scannerFadeUp {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* =========================================================
   TOP OPERATIONAL BAR
   ========================================================= */
.scanner-top-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 16px;
    border-radius: 18px;
    background: var(--stage-card);
    border: 1px solid var(--stage-border);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    box-shadow: 0 10px 24px -10px rgba(0, 0, 0, 0.5);
}

.scanner-brand-lockup {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.scanner-live-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    font-weight: 600;
    color: #a7f3d0;
    letter-spacing: 0.02em;
}

.scanner-live-pulse {
    width: 7px;
    height: 7px;
    border-radius: 999px;
    background: #10b981;
    box-shadow: 0 0 10px #10b981;
    animation: livePulse 1.8s ease-in-out infinite;
}

@keyframes livePulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50%      { opacity: 0.4; transform: scale(0.85); }
}

.scanner-title {
    font-family: 'Alexandria', system-ui, sans-serif;
    font-size: 15px;
    font-weight: 700;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
}

.scanner-title-badge {
    font-family: system-ui, sans-serif;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 2px 7px;
    border-radius: 6px;
    background: rgba(245, 158, 11, 0.12);
    border: 1px solid rgba(245, 158, 11, 0.3);
    color: #fde68a;
}

.scanner-back-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    color: var(--stage-text-muted);
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid var(--stage-border);
    transition: all 0.2s ease;
    text-decoration: none;
    min-height: 40px;
}

.scanner-back-btn:hover {
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(255, 255, 255, 0.18);
    color: #fff;
    transform: translateY(-1px);
}

.pt-arrow-rtl { display: inline-block; }
:root[dir="ltr"] .pt-arrow-rtl { transform: scaleX(-1); }

/* =========================================================
   CONSOLE LAYOUT (Responsive Grid)
   ========================================================= */
.scanner-console-layout {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
}

@media (min-width: 1024px) {
    .scanner-console-layout {
        grid-template-columns: 440px 1fr;
        align-items: start;
        gap: 20px;
    }
}

.scanner-primary-column {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

/* =========================================================
   CAMERA STAGE & RETICLE
   ========================================================= */
.scanner-stage {
    position: relative;
    overflow: hidden;
    border-radius: var(--scan-shell-radius);
    background: #040711;
    border: 1px solid rgba(245, 158, 11, 0.22);
    box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.08),
        0 20px 40px -18px rgba(0, 0, 0, 0.85);
    aspect-ratio: 3 / 4;
    isolation: isolate;
    transition: box-shadow 0.25s ease, border-color 0.25s ease;
}

@media (min-width: 480px) and (max-width: 1023px) {
    .scanner-stage {
        aspect-ratio: 4 / 5;
        max-height: 520px;
        margin: 0 auto;
        width: 100%;
        max-width: 440px;
    }
}

@media (min-width: 1024px) {
    .scanner-stage {
        aspect-ratio: 4 / 5;
    }
}

.scanner-video,
.scanner-video > video,
#qr-reader > video {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    background: #040711;
    border-radius: var(--scan-shell-radius);
}
#qr-reader { width: 100%; height: 100%; }

/* Hide default html5-qrcode controls */
#qr-reader__dashboard,
#qr-reader__header_message,
#qr-reader__camera_selection,
#qr-reader__scan_region img { display: none !important; }

/* Overlay & Reticle */
.scanner-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
    z-index: 2;
}

.scanner-reticle {
    position: relative;
    width: min(72%, 270px);
    aspect-ratio: 1 / 1;
    border-radius: 20px;
}

.reticle-corner {
    --c-len: 26px;
    --c-thick: 3.5px;
    position: absolute;
    width: var(--c-len);
    height: var(--c-len);
    border-color: #fbbf24;
    box-shadow: 0 0 14px rgba(251, 191, 36, 0.45);
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}
.reticle-corner.tl { top: 0; left: 0;  border-top:    var(--c-thick) solid; border-left:  var(--c-thick) solid; border-top-left-radius:  18px; }
.reticle-corner.tr { top: 0; right: 0; border-top:    var(--c-thick) solid; border-right: var(--c-thick) solid; border-top-right-radius: 18px; }
.reticle-corner.bl { bottom: 0; left: 0;  border-bottom: var(--c-thick) solid; border-left:  var(--c-thick) solid; border-bottom-left-radius:  18px; }
.reticle-corner.br { bottom: 0; right: 0; border-bottom: var(--c-thick) solid; border-right: var(--c-thick) solid; border-bottom-right-radius: 18px; }

/* Theatrical laser sweep line */
.reticle-line {
    position: absolute;
    left: 4%;
    right: 4%;
    height: 2px;
    background: linear-gradient(90deg,
        transparent,
        rgba(251, 191, 36, 0.2) 15%,
        #fde68a 50%,
        rgba(251, 191, 36, 0.2) 85%,
        transparent
    );
    box-shadow: 0 0 16px rgba(251, 191, 36, 0.75), 0 0 30px rgba(245, 158, 11, 0.4);
    border-radius: 999px;
    animation: scanLine 1.7s cubic-bezier(0.4, 0, 0.2, 1) infinite;
}

@keyframes scanLine {
    0%, 100% { top: 8%;  opacity: 0.5; }
    50%      { top: 88%; opacity: 1; }
}

/* Floating Status Pill */
.scanner-status {
    position: absolute;
    top: 14px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 4;
    padding: 7px 18px;
    border-radius: 999px;
    font-family: 'Alexandria', system-ui, sans-serif;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.02em;
    border: 1px solid rgba(255, 255, 255, 0.16);
    background: rgba(8, 12, 22, 0.88);
    color: var(--stage-text);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    transition: background 0.2s, color 0.2s, border-color 0.2s, transform 0.2s, box-shadow 0.2s;
    box-shadow: 0 12px 28px -10px rgba(0, 0, 0, 0.7);
    white-space: nowrap;
}

.scanner-status.state-ready {
    color: #e2e8f0;
    border-color: rgba(255, 255, 255, 0.14);
}

.scanner-status.state-scanning {
    background: rgba(245, 158, 11, 0.92);
    color: #1a0f02;
    border-color: #fde68a;
    box-shadow: 0 0 20px rgba(245, 158, 11, 0.5);
}

.scanner-status.state-ok {
    background: rgba(16, 185, 129, 0.95);
    color: #022c22;
    border-color: #a7f3d0;
    box-shadow: 0 0 22px rgba(16, 185, 129, 0.55);
}

.scanner-status.state-used {
    background: rgba(245, 158, 11, 0.95);
    color: #1a0f02;
    border-color: #fde68a;
    box-shadow: 0 0 22px rgba(245, 158, 11, 0.55);
}

.scanner-status.state-error {
    background: rgba(244, 63, 94, 0.95);
    color: #fff1f2;
    border-color: #fecdd3;
    box-shadow: 0 0 22px rgba(244, 63, 94, 0.55);
}

.scanner-status.is-pop {
    transform: translateX(-50%) scale(1.06);
}

/* Stage Result Border Glows */
.scanner-stage[data-state="ok"] {
    border-color: rgba(16, 185, 129, 0.7);
    box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.4), 0 20px 40px -18px rgba(0, 0, 0, 0.85);
}
.scanner-stage[data-state="ok"] .reticle-corner {
    border-color: #34d399;
    box-shadow: 0 0 16px rgba(52, 211, 153, 0.7);
}

.scanner-stage[data-state="used"] {
    border-color: rgba(245, 158, 11, 0.7);
    box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.4), 0 20px 40px -18px rgba(0, 0, 0, 0.85);
}
.scanner-stage[data-state="used"] .reticle-corner {
    border-color: #fbbf24;
    box-shadow: 0 0 16px rgba(251, 191, 36, 0.7);
}

.scanner-stage[data-state="error"] {
    border-color: rgba(244, 63, 94, 0.7);
    box-shadow: 0 0 0 2px rgba(244, 63, 94, 0.4), 0 20px 40px -18px rgba(0, 0, 0, 0.85);
    animation: stageShake 0.35s cubic-bezier(0.36, 0.07, 0.19, 0.97);
}
.scanner-stage[data-state="error"] .reticle-corner {
    border-color: #fb7185;
    box-shadow: 0 0 16px rgba(251, 113, 133, 0.7);
}

@keyframes stageShake {
    10%, 90% { transform: translateX(-2px); }
    20%, 80% { transform: translateX(3px);  }
    30%, 50%, 70% { transform: translateX(-4px); }
    40%, 60% { transform: translateX(4px);  }
}

/* Loading Overlay */
.scanner-loading {
    position: absolute;
    inset: 0;
    z-index: 3;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    color: var(--stage-text-muted);
    background: rgba(4, 7, 17, 0.92);
    transition: opacity 0.25s ease;
}
.scanner-loading.is-hidden { opacity: 0; pointer-events: none; }

.scanner-spinner {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    border: 3px solid rgba(245, 158, 11, 0.18);
    border-top-color: #fbbf24;
    animation: spin 0.85s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.scanner-loading-text {
    font-size: 13px;
    font-weight: 500;
}

/* Live Zoom Chip */
.scan-zoom-chip {
    position: absolute;
    top: 14px;
    inset-inline-start: 14px;
    z-index: 5;
    padding: 4px 10px;
    border-radius: 999px;
    background: rgba(4, 7, 17, 0.75);
    -webkit-backdrop-filter: blur(8px);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(245, 158, 11, 0.3);
    color: #fde68a;
    font: 600 11px/1 system-ui, sans-serif;
    letter-spacing: 0.02em;
    opacity: 0;
    pointer-events: none;
    transform: translateY(-4px);
    transition: opacity 0.18s ease, transform 0.18s ease;
}
.scan-zoom-chip.is-visible {
    opacity: 1;
    transform: translateY(0);
}

/* =========================================================
   SCANNER CONTROLS (Ergonomic Thumb Targets)
   ========================================================= */
.scanner-controls {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.scanner-ctrl-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 18px;
    border-radius: 16px;
    background: var(--stage-card);
    border: 1px solid var(--stage-border);
    color: var(--stage-text);
    font-family: 'Readex Pro', system-ui, sans-serif;
    font-size: 13px;
    font-weight: 600;
    min-height: 48px;
    cursor: pointer;
    touch-action: manipulation;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.35);
}

.scanner-ctrl-btn:hover {
    background: rgba(255, 255, 255, 0.07);
    border-color: rgba(245, 158, 11, 0.3);
    transform: translateY(-1px);
}

.scanner-ctrl-btn:active {
    transform: translateY(1px);
}

.scanner-ctrl-btn.is-on {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.25), rgba(245, 158, 11, 0.08));
    border-color: #fbbf24;
    color: #fef3c7;
    box-shadow: 0 0 16px rgba(245, 158, 11, 0.35);
}

.scanner-ctrl-btn.is-suggest:not(.is-on) {
    animation: flashSuggest 1.5s ease-in-out infinite;
    border-color: rgba(253, 224, 71, 0.6);
    color: #fef3c7;
}

@keyframes flashSuggest {
    0%, 100% { box-shadow: 0 0 0 0 rgba(253, 224, 71, 0.4); }
    50%      { box-shadow: 0 0 0 7px rgba(253, 224, 71, 0); }
}

.ctrl-icon { font-size: 16px; }

/* =========================================================
   SECONDARY COLUMN: ACTIVITY CARD & RECENT SCANS
   ========================================================= */
.scanner-secondary-column {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.scanner-history-card {
    background: var(--stage-card);
    border: 1px solid var(--stage-border);
    border-radius: var(--scan-shell-radius);
    padding: 16px;
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    box-shadow: 0 12px 28px -12px rgba(0, 0, 0, 0.6);
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.history-card-header {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding-bottom: 12px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.history-header-title {
    display: flex;
    align-items: center;
    gap: 8px;
}

.history-header-title h2 {
    font-family: 'Alexandria', system-ui, sans-serif;
    font-size: 15px;
    font-weight: 700;
    color: #fff;
    margin: 0;
}

.history-header-icon {
    font-size: 16px;
}

/* Quick Session Stats Bar */
.history-stats-pills {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 6px;
}

.stat-pill {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 6px 4px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.06);
    text-align: center;
}

.stat-pill .stat-label {
    font-size: 10px;
    color: var(--stage-text-dim);
    margin-bottom: 1px;
}

.stat-pill strong {
    font-family: 'Alexandria', system-ui, sans-serif;
    font-size: 13px;
    font-weight: 800;
    line-height: 1.1;
}

.stat-pill.is-ok    { border-color: rgba(16, 185, 129, 0.25); background: rgba(16, 185, 129, 0.06); }
.stat-pill.is-ok strong { color: #34d399; }

.stat-pill.is-used  { border-color: rgba(245, 158, 11, 0.25); background: rgba(245, 158, 11, 0.06); }
.stat-pill.is-used strong { color: #fbbf24; }

.stat-pill.is-error { border-color: rgba(244, 63, 94, 0.25); background: rgba(244, 63, 94, 0.06); }
.stat-pill.is-error strong { color: #fb7185; }

.stat-pill.is-total { border-color: rgba(255, 255, 255, 0.1); background: rgba(255, 255, 255, 0.04); }
.stat-pill.is-total strong { color: #f8fafc; }

/* Empty state */
.history-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 36px 16px;
    color: var(--stage-text-muted);
    gap: 8px;
}

.history-empty-state .empty-icon {
    font-size: 32px;
    opacity: 0.6;
    margin-bottom: 4px;
}

.history-empty-state .empty-text {
    font-size: 13px;
    font-weight: 600;
    color: #e2e8f0;
    margin: 0;
}

.history-empty-state .empty-sub {
    font-size: 11px;
    color: var(--stage-text-dim);
    max-width: 280px;
    line-height: 1.4;
}

/* Scans List */
.history-scans-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-height: 420px;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    padding-inline-end: 2px;
}

.scan-history-item {
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.06);
    transition: all 0.2s ease;
    animation: historyFadeIn 0.3s var(--ease-out-quint) both;
}

@keyframes historyFadeIn {
    from { opacity: 0; transform: translateY(-6px); }
    to   { opacity: 1; transform: translateY(0); }
}

.scan-history-item.is-latest {
    border-color: rgba(245, 158, 11, 0.35);
    background: rgba(245, 158, 11, 0.05);
    box-shadow: 0 4px 14px -4px rgba(245, 158, 11, 0.15);
}

.scan-history-item.state-ok    { border-inline-start: 3px solid #10b981; }
.scan-history-item.state-used  { border-inline-start: 3px solid #f59e0b; }
.scan-history-item.state-error { border-inline-start: 3px solid #f43f5e; }

.scan-history-icon {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 800;
}
.scan-history-icon.state-ok    { background: var(--scan-ok-bg);    color: #34d399; }
.scan-history-icon.state-used  { background: var(--scan-used-bg);  color: #fbbf24; }
.scan-history-icon.state-error { background: var(--scan-error-bg); color: #fb7185; }

.scan-history-body {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.scan-history-top {
    display: flex;
    align-items: center;
    gap: 8px;
}

.scan-history-name {
    font-size: 13px;
    font-weight: 700;
    color: #fff;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.scan-history-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 10px;
    font-weight: 600;
    padding: 1px 6px;
    border-radius: 999px;
}
.scan-history-badge.is-ok    { background: var(--scan-ok-bg);    color: #6ee7b7; border: 1px solid var(--scan-ok-border); }
.scan-history-badge.is-used  { background: var(--scan-used-bg);  color: #fde68a; border: 1px solid var(--scan-used-border); }
.scan-history-badge.is-error { background: var(--scan-error-bg); color: #fca5a5; border: 1px solid var(--scan-error-border); }

.scan-history-badge .badge-dot {
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: currentColor;
}

.scan-history-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 11px;
    color: var(--stage-text-dim);
}

.scan-history-ref {
    font-family: monospace;
    letter-spacing: 0.05em;
    color: var(--stage-text-muted);
}

.scan-history-time {
    font-size: 11px;
    font-weight: 500;
    color: var(--stage-text-dim);
    white-space: nowrap;
}

/* =========================================================
   PREMIUM RESULT SHEET (Centered Modal Card)
   ========================================================= */
.scan-sheet {
    position: fixed;
    inset: 0;
    min-height: 100dvh;
    z-index: 99;
    display: flex;
    align-items: center;
    justify-content: center;
    padding:
        max(16px, env(safe-area-inset-top))
        16px
        max(16px, env(safe-area-inset-bottom));
    background: rgba(2, 6, 23, 0.72);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    opacity: 0;
    pointer-events: none;
    overscroll-behavior: contain;
    transition: opacity 0.25s var(--ease-out-quint);
}
.scan-sheet[data-state="visible"] {
    opacity: 1;
    pointer-events: auto;
}

.scan-sheet-card {
    width: min(92vw, 22rem);
    max-height: calc(100dvh - max(32px, env(safe-area-inset-top) + env(safe-area-inset-bottom) + 24px));
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    padding: 22px;
    border-radius: 24px;
    background: linear-gradient(180deg, rgba(19, 26, 46, 0.98), rgba(9, 13, 24, 0.98));
    border: 1px solid rgba(245, 158, 11, 0.35);
    box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.08),
        0 30px 60px -20px rgba(0, 0, 0, 0.95),
        0 0 36px rgba(245, 158, 11, 0.2);
    display: flex;
    flex-direction: column;
    gap: 14px;
    transform: translateY(16px) scale(0.95);
    opacity: 0;
    transition:
        transform 0.32s var(--ease-out-quint),
        opacity 0.2s ease;
    will-change: transform, opacity;
}
.scan-sheet[data-state="visible"] .scan-sheet-card {
    transform: translateY(0) scale(1);
    opacity: 1;
}

/* Card States */
.scan-sheet[data-result="ok"] .scan-sheet-card {
    border-color: rgba(16, 185, 129, 0.55);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08), 0 24px 50px -20px rgba(0, 0, 0, 0.9), 0 0 40px rgba(16, 185, 129, 0.3);
}

.scan-sheet[data-result="used"] .scan-sheet-card {
    border-color: rgba(245, 158, 11, 0.55);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08), 0 24px 50px -20px rgba(0, 0, 0, 0.9), 0 0 40px rgba(245, 158, 11, 0.3);
}

.scan-sheet[data-result="error"] .scan-sheet-card {
    border-color: rgba(244, 63, 94, 0.55);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08), 0 24px 50px -20px rgba(0, 0, 0, 0.9), 0 0 40px rgba(244, 63, 94, 0.3);
}

/* Result Badge */
.scan-sheet-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px 6px 10px;
    align-self: flex-start;
    border-radius: 999px;
    border: 1px solid rgba(255, 255, 255, 0.12);
    font-family: 'Alexandria', system-ui, sans-serif;
    font-weight: 800;
    font-size: 13px;
    letter-spacing: 0.04em;
}

.scan-sheet-badge-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    font-weight: 800;
    font-size: 13px;
}

.scan-sheet[data-result="ok"] .scan-sheet-badge {
    background: rgba(16, 185, 129, 0.2);
    border-color: rgba(16, 185, 129, 0.5);
    color: #a7f3d0;
}
.scan-sheet[data-result="ok"] .scan-sheet-badge-icon {
    background: #10b981;
    color: #022c22;
}

.scan-sheet[data-result="used"] .scan-sheet-badge {
    background: rgba(245, 158, 11, 0.2);
    border-color: rgba(245, 158, 11, 0.5);
    color: #fde68a;
}
.scan-sheet[data-result="used"] .scan-sheet-badge-icon {
    background: #f59e0b;
    color: #1b1208;
}

.scan-sheet[data-result="error"] .scan-sheet-badge {
    background: rgba(244, 63, 94, 0.2);
    border-color: rgba(244, 63, 94, 0.5);
    color: #fecdd3;
}
.scan-sheet[data-result="error"] .scan-sheet-badge-icon {
    background: #f43f5e;
    color: #fff1f2;
}

.scan-sheet-info-group {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.scan-sheet-name {
    font-family: 'Alexandria', system-ui, sans-serif;
    font-size: 19px;
    font-weight: 800;
    color: #fff;
    line-height: 1.25;
}

.scan-sheet-ref {
    font-family: monospace;
    font-size: 12px;
    font-weight: 600;
    color: var(--stage-gold);
    letter-spacing: 0.08em;
}
.scan-sheet-ref:empty { display: none; }

.scan-sheet-details {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.scan-sheet-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 12px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.06);
    color: #e2e8f0;
    font-size: 13px;
}

.scan-sheet-row-icon { font-size: 14px; opacity: 0.9; }
.scan-sheet-row-text { font-weight: 600; }

.scan-sheet-used-note {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 12px;
    background: rgba(245, 158, 11, 0.12);
    border: 1px solid rgba(245, 158, 11, 0.4);
    color: #fef3c7;
    font-size: 12px;
}

.scan-sheet-used-note .used-icon { font-size: 16px; line-height: 1; }
.scan-sheet-used-note .used-text { display: flex; flex-direction: column; gap: 2px; }

.scan-sheet-foot {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: 8px;
    padding-top: 6px;
}

.scan-sheet-done {
    width: 100%;
    min-height: 50px;
    padding: 14px 20px;
    border-radius: 999px;
    font-family: 'Alexandria', system-ui, sans-serif;
    font-size: 14px;
    font-weight: 800;
    letter-spacing: 0.02em;
    color: #160e05;
    background: linear-gradient(180deg, #fde68a 0%, #f59e0b 100%);
    border: 1px solid rgba(255, 255, 255, 0.6);
    box-shadow: 0 8px 24px -6px rgba(245, 158, 11, 0.6), inset 0 1px 0 rgba(255, 255, 255, 0.6);
    cursor: pointer;
    touch-action: manipulation;
    transition: all 0.2s var(--ease-out-quint);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.scan-sheet-done:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px -6px rgba(245, 158, 11, 0.75), 0 0 20px rgba(251, 191, 36, 0.4);
}

.scan-sheet-done:active {
    transform: translateY(1px);
}

.scan-sheet-hint {
    font-size: 11px;
    color: var(--stage-text-dim);
    text-align: center;
}

.scan-sheet[data-result="ok"] .scan-sheet-used-note { display: none !important; }

/* Reduced Motion Safety */
@media (prefers-reduced-motion: reduce) {
    .reticle-line,
    .scan-sheet,
    .scan-sheet-card,
    .scanner-status,
    .scanner-root-container,
    .scan-history-item,
    .scanner-live-pulse {
        animation: none !important;
        transition: none !important;
    }
    .scanner-stage[data-state="error"] { animation: none !important; }
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

    let busy = false;          // mid-flight backend round-trip
    let lastCode = null;
    let lastScanTime = 0;
    let sheetOpen = false;     // pause scans while showing a result
    let qrInstance = null;     // assigned once a decode path boots

    /* ============================================================
       Audio + haptic feedback (PRESERVED)
       ============================================================ */
    let audioCtx = null;
    let audioPrimed = false;

    function ensureAudio() {
        try {
            if (!audioCtx) {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
            if (audioCtx.state === 'suspended' && typeof audioCtx.resume === 'function') {
                audioCtx.resume().catch(() => {});
            }
        } catch (_) {}
    }

    function primeAudio() {
        if (audioPrimed) return;
        audioPrimed = true;
        ensureAudio();
    }

    ['touchstart', 'pointerdown', 'click', 'keydown'].forEach((ev) => {
        document.addEventListener(ev, primeAudio, { once: true, passive: true });
    });

    function beep(type) {
        try {
            ensureAudio();
            if (!audioCtx) return;
            const osc  = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.frequency.value = type === 'ok' ? 950 : type === 'used' ? 500 : 250;
            gain.gain.value = 0.22;
            osc.start();
            setTimeout(() => osc.stop(), 150);
        } catch (_) {}
    }

    function softTick() {
        try {
            ensureAudio();
            if (!audioCtx) return;
            const osc  = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.type            = 'square';
            osc.frequency.value = 1500;
            const now = audioCtx.currentTime;
            gain.gain.setValueAtTime(0.0001, now);
            gain.gain.exponentialRampToValueAtTime(0.10, now + 0.005);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.045);
            osc.start(now);
            osc.stop(now + 0.05);
        } catch (_) {}
    }

    function vibrate(type) {
        if (!('vibrate' in navigator)) return;
        if      (type === 'precheck') navigator.vibrate(40);
        else if (type === 'ok')       navigator.vibrate(120);
        else if (type === 'used')     navigator.vibrate([100, 50, 100]);
        else                          navigator.vibrate(220);
    }

    /* ============================================================
       Status pill (PRESERVED)
       ============================================================ */
    const $status  = document.getElementById('status');
    const $stage   = document.querySelector('[data-scanner-stage]');
    const $loading = document.getElementById('scanner-loading');

    function setStatus(text, type) {
        $status.textContent = text;
        $status.classList.remove('state-ready', 'state-scanning', 'state-ok', 'state-used', 'state-error', 'is-pop');
        $status.classList.add('state-' + (type || 'ready'), 'is-pop');
        setTimeout(() => $status.classList.remove('is-pop'), 200);
        if (type === 'ok' || type === 'used' || type === 'error') {
            $stage.dataset.state = type;
        } else {
            delete $stage.dataset.state;
        }
    }

    /* ============================================================
       Session Scans & Live History Feed (IN-MEMORY CLIENT SESSION)
       ============================================================ */
    const sessionScans = [];
    const stats = { total: 0, ok: 0, used: 0, error: 0 };
    const $scansList  = document.getElementById('recent-scans-list');
    const $scansEmpty = document.getElementById('recent-scans-empty');
    const $statTotal  = document.getElementById('session-total-count');
    const $statOk     = document.getElementById('session-ok-count');
    const $statUsed   = document.getElementById('session-used-count');
    const $statError  = document.getElementById('session-error-count');

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

        if (!$scansList) return;
        if (sessionScans.length === 0) {
            if ($scansEmpty) $scansEmpty.style.display = 'flex';
            $scansList.innerHTML = '';
            return;
        }
        if ($scansEmpty) $scansEmpty.style.display = 'none';

        $scansList.innerHTML = sessionScans.map((item, idx) => {
            const isLatest = idx === 0;
            const statusBadge =
                item.status === 'ok'
                    ? '<span class="scan-history-badge is-ok"><span class="badge-dot"></span>مقبول</span>'
                    : item.status === 'used'
                    ? '<span class="scan-history-badge is-used"><span class="badge-dot"></span>مكرر</span>'
                    : '<span class="scan-history-badge is-error"><span class="badge-dot"></span>مرفوض</span>';

            const statusIcon = item.status === 'ok' ? '✓' : item.status === 'used' ? '⚠️' : '✕';

            return `
                <div class="scan-history-item state-${escapeHtml(item.status)} ${isLatest ? 'is-latest' : ''}">
                    <div class="scan-history-icon state-${escapeHtml(item.status)}">${statusIcon}</div>
                    <div class="scan-history-body">
                        <div class="scan-history-top">
                            <span class="scan-history-name">${escapeHtml(item.name || '—')}</span>
                            ${statusBadge}
                        </div>
                        <div class="scan-history-meta">
                            ${item.reference ? `<span class="scan-history-ref">#${escapeHtml(item.reference)}</span>` : ''}
                            ${item.show_title ? `<span class="scan-history-show">${escapeHtml(item.show_title)}</span>` : ''}
                        </div>
                    </div>
                    <div class="scan-history-time" dir="ltr">${escapeHtml(item.time)}</div>
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
            name: p.name || (status === 'error' ? 'تذكرة غير معروفة' : '—'),
            reference: p.reference || (code ? String(code).substring(0, 12) : ''),
            show_title: p.show_title || '',
            time: timeStr,
            scanned_at: p.scanned_at || null,
        });

        if (sessionScans.length > 10) {
            sessionScans.pop();
        }

        updateRecentScansUI();
    }

    /* ============================================================
       Result sheet (cinematic popup modal - PRESERVED SELECTORS)
       ============================================================ */
    const $sheet         = document.getElementById('scan-sheet');
    const $sheetBadge    = $sheet.querySelector('[data-scan-badge-text]');
    const $sheetIcon     = $sheet.querySelector('[data-scan-icon]');
    const $sheetName     = $sheet.querySelector('[data-scan-name]');
    const $sheetRef      = $sheet.querySelector('[data-scan-ref]');
    const $sheetShow     = $sheet.querySelector('[data-scan-show]');
    const $sheetWhen     = $sheet.querySelector('[data-scan-when]');
    const $sheetUsedNote = $sheet.querySelector('[data-scan-used-note]');
    const $sheetUsedTime = $sheet.querySelector('[data-scan-used-time]');
    const $sheetDismiss  = $sheet.querySelector('[data-scan-dismiss]');

    function showSheet(result, payload) {
        $sheet.dataset.result = result; // ok | used | error
        $sheet.dataset.state  = 'visible';
        sheetOpen = true;

        const badgeText =
            result === 'ok'   ? '✅ دخول مسموح' :
            result === 'used' ? '⚠️ مستخدمة'   :
                                '❌ غير صالح';
        $sheetBadge.textContent = badgeText.replace(/^[^\p{L}\p{N}]+\s*/u, '');
        $sheetIcon.textContent  = result === 'ok' ? '✓' : result === 'used' ? '!' : '✕';

        const p = payload || {};

        $sheetName.textContent = p.name || '—';
        $sheetRef.textContent  = p.reference ? '#' + p.reference : '';

        $sheetShow.textContent = p.show_title || '—';
        const dateStr = p.date || '';
        const timeStr = p.time || '';
        $sheetWhen.textContent = [dateStr, timeStr].filter(Boolean).join(' · ') || '—';

        if (result === 'used' && p.scanned_at) {
            $sheetUsedNote.hidden = false;
            $sheetUsedTime.textContent = ' · ' + p.scanned_at;
        } else {
            $sheetUsedNote.hidden = true;
            $sheetUsedTime.textContent = '';
        }

        if (qrInstance) {
            try { qrInstance.pause(true); } catch (_) {}
        }
    }

    function hideSheet() {
        $sheet.dataset.state = 'hidden';
        sheetOpen = false;
        delete $stage.dataset.state;
        setStatus('جاهز للفحص', 'ready');
        lastCode = null;
        lastScanTime = 0;
        if (qrInstance) {
            try { qrInstance.resume(); } catch (_) {}
        }
    }
    $sheetDismiss.addEventListener('click', hideSheet);
    $sheet.addEventListener('click', (e) => {
        if (e.target === $sheet) hideSheet();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sheetOpen) hideSheet();
    });

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
                setStatus('✅ دخول مسموح', 'ok');
                vibrate('ok');  beep('ok');
                showSheet('ok', d);
                addRecentScan('ok', d, code);
            } else if (d.status === 'used') {
                setStatus('⚠️ مستخدمة', 'used');
                vibrate('used'); beep('used');
                showSheet('used', d);
                addRecentScan('used', d, code);
            } else {
                setStatus('❌ غير صالح', 'error');
                vibrate('error'); beep('error');
                showSheet('error', d || {});
                addRecentScan('error', d || {}, code);
            }
        })
        .catch(() => {
            setStatus('⚠️ تعذّر الاتصال', 'error');
            vibrate('error');
            addRecentScan('error', { name: 'تعذّر الاتصال بالخادم' }, code);
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

    function centerRoiUpscale(lum, w, h) {
        const x0 = w >> 2;
        const y0 = h >> 2;
        const rw = w >> 1;
        const rh = h >> 1;
        const ow = w;
        const oh = h;
        const out = new Uint8ClampedArray(ow * oh);
        const sx = rw / ow;
        const sy = rh / oh;
        const xLast = x0 + rw - 1;
        const yLast = y0 + rh - 1;
        for (let y = 0; y < oh; y++) {
            const fy = y * sy + y0;
            const iy = fy | 0;
            const ay = fy - iy;
            const iyn = iy + 1 < yLast ? iy + 1 : yLast;
            const rowA = iy  * w;
            const rowB = iyn * w;
            const outRow = y * ow;
            for (let x = 0; x < ow; x++) {
                const fx = x * sx + x0;
                const ix = fx | 0;
                const ax = fx - ix;
                const ixn = ix + 1 < xLast ? ix + 1 : xLast;
                const p00 = lum[rowA + ix ];
                const p10 = lum[rowA + ixn];
                const p01 = lum[rowB + ix ];
                const p11 = lum[rowB + ixn];
                const top = p00 + (p10 - p00) * ax;
                const bot = p01 + (p11 - p01) * ax;
                out[outRow + x] = (top + (bot - top) * ay) | 0;
            }
        }
        return out;
    }

    function tryZXingDecode(lum, w, h, mfr, hints) {
        if (!lum || !mfr) return null;
        let lumSource;
        try {
            lumSource = new ZXing.RGBLuminanceSource(lum, w, h);
        } catch (_) {
            try { lumSource = new ZXing.RGBLuminanceSource(w, h, lum); }
            catch (__) { return null; }
        }
        let result = null;
        try {
            const binarizer = new ZXing.HybridBinarizer(lumSource);
            const bitmap    = new ZXing.BinaryBitmap(binarizer);
            result = mfr.decode(bitmap, hints || undefined);
        } catch (_) {
            result = null;
        } finally {
            try { mfr.reset(); } catch (_) {}
        }
        return result;
    }

    function releaseStream(stream) {
        if (!stream) return;
        try {
            stream.getTracks().forEach((t) => {
                try { t.stop(); } catch (_) {}
                if (activeTrack === t) activeTrack = null;
            });
        } catch (_) {}
    }

    /* ============================================================
       Path A — Direct BarcodeDetector (PRESERVED)
       ============================================================ */
    async function startNativeBarcodeDetector() {
        if (!('BarcodeDetector' in window)) return null;
        let supported;
        try { supported = await BarcodeDetector.getSupportedFormats(); }
        catch (_) { return null; }
        if (!supported || !supported.includes('qr_code')) return null;

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) return null;

        let stream = null;
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: { ideal: 'environment' },
                    width:     { ideal: 1920 },
                    height:    { ideal: 1080 },
                    frameRate: { ideal: 60 },
                },
                audio: false,
            });

            const track = stream.getVideoTracks()[0];
            if (!track) {
                releaseStream(stream);
                return null;
            }
            activeTrack = track;

            const reader = document.getElementById('qr-reader');
            reader.innerHTML = '';
            const video = document.createElement('video');
            video.setAttribute('playsinline', 'true');
            video.setAttribute('webkit-playsinline', 'true');
            video.setAttribute('autoplay', 'true');
            video.setAttribute('muted', 'true');
            video.muted    = true;
            video.autoplay = true;
            Object.assign(video.style, {
                width:     '100%',
                height:    '100%',
                objectFit: 'cover',
                display:   'block',
            });
            reader.appendChild(video);
            video.srcObject = stream;
            try { await video.play(); } catch (_) {}

            try {
                await track.applyConstraints({
                    advanced: [
                        { focusMode: 'continuous' },
                        { focusMode: 'continuous-picture' },
                        { focusDistance: { ideal: 0.05 } },
                        { exposureMode: 'continuous' },
                        { whiteBalanceMode: 'continuous' },
                    ],
                });
            } catch (_) {}

            try { setupCapabilityFeatures(track, video); } catch (_) {}

            let detector;
            try {
                detector = new BarcodeDetector({ formats: ['qr_code'] });
            } catch (_) {
                releaseStream(stream);
                return null;
            }

            let stopped = false;
            let paused  = false;

            const schedule = (fn) => {
                if (typeof video.requestVideoFrameCallback === 'function') {
                    video.requestVideoFrameCallback(() => fn());
                } else {
                    requestAnimationFrame(fn);
                }
            };

            const tick = async () => {
                if (stopped) return;
                if (paused || sheetOpen || video.readyState < 2) {
                    schedule(tick);
                    return;
                }
                try {
                    const codes = await detector.detect(video);
                    if (codes && codes.length) {
                        let best = codes[0];
                        if (codes.length > 1) {
                            const area = (b) => (b && b.width && b.height) ? b.width * b.height : 0;
                            for (const c of codes) {
                                if (area(c.boundingBox) > area(best.boundingBox)) best = c;
                            }
                        }
                        if (best && best.rawValue) onScanSuccess(best.rawValue);
                    }
                } catch (_) {}
                schedule(tick);
            };
            schedule(tick);

            return {
                pause:  () => { paused  = true; },
                resume: () => { paused  = false; },
                stop:   () => {
                    stopped = true;
                    releaseStream(stream);
                },
                track,
            };
        } catch (_) {
            releaseStream(stream);
            return null;
        }
    }

    /* ============================================================
       Path B — ZXing-js driven by MANUAL frame loop (PRESERVED)
       ============================================================ */
    async function startZXing() {
        if (typeof ZXing === 'undefined') return null;
        if (!ZXing.MultiFormatReader || !ZXing.BinaryBitmap ||
            !ZXing.HybridBinarizer || !ZXing.RGBLuminanceSource) return null;
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) return null;

        let stream = null;

        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: { ideal: 'environment' },
                    width:     { ideal: 1920 },
                    height:    { ideal: 1080 },
                    frameRate: { ideal: 30 },
                },
                audio: false,
            });

            const track = stream.getVideoTracks()[0];
            if (!track) {
                releaseStream(stream);
                return null;
            }
            activeTrack = track;

            const reader = document.getElementById('qr-reader');
            reader.innerHTML = '';
            const video = document.createElement('video');
            video.setAttribute('playsinline', 'true');
            video.setAttribute('webkit-playsinline', 'true');
            video.setAttribute('autoplay', 'true');
            video.setAttribute('muted', 'true');
            video.muted    = true;
            video.autoplay = true;
            Object.assign(video.style, {
                width:     '100%',
                height:    '100%',
                objectFit: 'cover',
                display:   'block',
            });
            reader.appendChild(video);
            video.srcObject = stream;
            try { await video.play(); } catch (_) {}

            try {
                await track.applyConstraints({
                    advanced: [
                        { focusMode: 'continuous' },
                        { focusMode: 'continuous-picture' },
                        { focusDistance: { ideal: 0.05 } },
                        { exposureMode: 'continuous' },
                        { whiteBalanceMode: 'continuous' },
                    ],
                });
            } catch (_) {}

            let hints = null;
            try {
                hints = new Map();
                if (ZXing.DecodeHintType && ZXing.BarcodeFormat) {
                    hints.set(ZXing.DecodeHintType.POSSIBLE_FORMATS, [ZXing.BarcodeFormat.QR_CODE]);
                    hints.set(ZXing.DecodeHintType.TRY_HARDER, true);
                    if ('ALSO_INVERTED' in ZXing.DecodeHintType) {
                        hints.set(ZXing.DecodeHintType.ALSO_INVERTED, true);
                    }
                }
            } catch (_) { hints = null; }

            let mfr;
            try {
                mfr = new ZXing.MultiFormatReader();
                if (hints) mfr.setHints(hints);
            } catch (_) {
                releaseStream(stream);
                return null;
            }

            const canvas = document.createElement('canvas');
            const ctx    = canvas.getContext('2d', { willReadFrequently: true });

            let stopped = false;
            let paused  = false;

            const schedule = (fn) => {
                if (typeof video.requestVideoFrameCallback === 'function') {
                    video.requestVideoFrameCallback(() => fn());
                } else {
                    requestAnimationFrame(fn);
                }
            };

            try { setupCapabilityFeatures(track, video); } catch (_) {}

            let lastSuccessAt   = Date.now();
            let lastLumSampleAt = 0;
            let autoZoomActive  = false;

            const tick = () => {
                if (stopped) return;
                if (paused || sheetOpen || video.readyState < 2) {
                    schedule(tick);
                    return;
                }

                try {
                    const vw = video.videoWidth | 0;
                    const vh = video.videoHeight | 0;
                    if (vw > 0 && vh > 0) {
                        const cap = 1280;
                        let cw = vw, ch = vh;
                        if (Math.max(vw, vh) > cap) {
                            const scale = cap / Math.max(vw, vh);
                            cw = Math.round(vw * scale);
                            ch = Math.round(vh * scale);
                        }
                        if (canvas.width !== cw || canvas.height !== ch) {
                            canvas.width  = cw;
                            canvas.height = ch;
                        }
                        ctx.drawImage(video, 0, 0, cw, ch);
                        const img = ctx.getImageData(0, 0, cw, ch);
                        const luminances = packLuminance(img.data);

                        const now    = Date.now();
                        const missMs = now - lastSuccessAt;

                        // Pass 1 — fast normal decode
                        let result = tryZXingDecode(luminances, cw, ch, mfr, hints);

                        // Sample mean luminance
                        if (now - lastLumSampleAt > 750) {
                            lastLuminance   = meanLuminance(luminances);
                            lastLumSampleAt = now;
                        }

                        // Pass 2 — adaptive threshold
                        if (!result && missMs > 250) {
                            const mean = meanLuminance(luminances);
                            const thr  = thresholdLuminance(luminances, mean);
                            result = tryZXingDecode(thr, cw, ch, mfr, hints);
                        }

                        // Pass 3 — inverted
                        if (!result && missMs > 400) {
                            const inv = invertLuminance(luminances);
                            result = tryZXingDecode(inv, cw, ch, mfr, hints);
                        }

                        // Pass 4 — center ROI 2× upscale
                        if (!result && missMs > 600) {
                            const up = centerRoiUpscale(luminances, cw, ch);
                            result = tryZXingDecode(up, cw, ch, mfr, hints);
                        }

                        // Auto-zoom recovery
                        if (!result && capability.zoom && !autoZoomActive &&
                            missMs > 1000 && Math.abs(zoomCurrent - 1) < 0.05) {
                            autoZoomActive = true;
                            const target = Math.min(2, capability.zoomMax);
                            setZoom(target).then((ok) => {
                                if (!ok) {
                                    autoZoomActive = false;
                                    return;
                                }
                                setTimeout(() => {
                                    autoZoomActive = false;
                                    if (Date.now() - lastSuccessAt > 800) {
                                        setZoom(capability.zoomMin);
                                    }
                                }, 1600);
                            });
                        }

                        if (result) {
                            let text = '';
                            try { text = (typeof result.getText === 'function') ? result.getText() : (result.text || ''); }
                            catch (_) { text = ''; }
                            if (text) {
                                lastSuccessAt = now;
                                onScanSuccess(text);
                            }
                        }
                    }
                } catch (_) {}

                schedule(tick);
            };
            schedule(tick);

            return {
                pause:  () => { paused  = true; },
                resume: () => { paused  = false; },
                stop:   () => {
                    stopped = true;
                    releaseStream(stream);
                },
                track,
            };
        } catch (_) {
            releaseStream(stream);
            return null;
        }
    }

    /* ============================================================
       Path C — html5-qrcode last-resort fallback (PRESERVED)
       ============================================================ */
    function startHtml5QrcodeFallback() {
        const qr = new Html5Qrcode('qr-reader', {
            verbose: false,
            experimentalFeatures: { useBarCodeDetectorIfSupported: true },
            formatsToSupport: (window.Html5QrcodeSupportedFormats && [
                Html5QrcodeSupportedFormats.QR_CODE,
            ]) || undefined,
        });

        const qrbox = (vw, vh) => {
            const side = Math.floor(Math.min(vw, vh) * 0.95);
            return {
                width:  Math.max(240, Math.min(side, 600)),
                height: Math.max(240, Math.min(side, 600)),
            };
        };

        const config = {
            fps: 30,
            qrbox: qrbox,
            aspectRatio: 1.0,
            disableFlip: false,
            rememberLastUsedCamera: true,
            videoConstraints: {
                facingMode: { ideal: 'environment' },
                width:      { ideal: 1920 },
                height:     { ideal: 1080 },
                frameRate:  { ideal: 30 },
                focusMode:        'continuous',
                exposureMode:     'continuous',
                whiteBalanceMode: 'continuous',
                advanced: [
                    { focusMode: 'continuous' },
                    { focusMode: 'continuous-picture' },
                    { focusDistance: { ideal: 0.05 } },
                    { exposureMode: 'continuous' },
                    { whiteBalanceMode: 'continuous' },
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
        $loading.classList.add('is-hidden');
        setStatus('جاهز للفحص', 'ready');
    }

    function markScannerFailed() {
        if (scannerStarted) return;
        scannerStarted = true;
        $loading.classList.add('is-hidden');
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

    document.getElementById('restartBtn').addEventListener('click', () => location.reload());
})();
</script>
@endsection
