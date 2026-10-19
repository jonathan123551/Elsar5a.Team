<!DOCTYPE html>
<html lang="ar" dir="rtl" class="no-js">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'فريق الصرخة المسرحي')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#020617">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @hasSection('meta_robots')
        <meta name="robots" content="@yield('meta_robots')">
    @endif
    <meta name="description" content="@yield('meta_description', 'فريق الصرخة المسرحي — حجز تذاكر العروض أونلاين.')">
    <link rel="icon" type="image/png" href="{{ asset('images/sarkha-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/sarkha-logo.png') }}">
    @if(request()->routeIs('home'))
        <link rel="preload" as="image" href="{{ asset('images/cinematic/curtain/closed-velvet.webp') }}" fetchpriority="high">
        <link rel="preload" as="image" href="{{ asset('images/cinematic/theatre/empty-stage.webp') }}" fetchpriority="high">
    @endif

    <script>document.documentElement.classList.replace('no-js', 'js');</script>

    {{-- Tailwind CSS CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Google Fonts: Alexandria (700;800;900) & Readex Pro (400;500;600;700) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@700;800;900&family=Readex+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Elsar5a Stage Architecture, 3D Curtain Engine & Motion System --}}
    <link rel="stylesheet" href="{{ asset('css/elsar5a-stage.css') }}?v={{ file_exists(public_path('css/elsar5a-stage.css')) ? filemtime(public_path('css/elsar5a-stage.css')) : '1.0' }}">

    <style>
        /* =========================================================
           MOBILE-ZOOM / VIEWPORT STABILITY BASELINE
           ---------------------------------------------------------
           Goals (in priority order):
             1. NEVER auto-zoom on input focus (iOS Safari does this
                whenever a form control's computed font-size is
                < 16px).
             2. NEVER fire the 300ms double-tap zoom delay on
                interactive elements.
             3. NEVER let `:hover` styles stick after a tap on
                touch devices (the "I tapped a card and now it's
                permanently bigger" bug).
             4. NEVER bounce the layout when the iOS address bar
                collapses (use 100dvh, not 100vh).
             5. Preserve pinch-to-zoom for accessibility. We do NOT
                set `user-scalable=no` or `maximum-scale=1` in the
                viewport meta — those break low-vision users for
                no real product benefit.

           See viewport <meta> above for the meta-tag side of this:
           `width=device-width, initial-scale=1, viewport-fit=cover`
           with NO max/min scale, so pinch-zoom remains available.
           ========================================================= */
        html {
            -webkit-text-size-adjust: 100%;
            text-size-adjust: 100%;
            /* Stage colour anchored on the root element so the iOS
               overscroll rubber-band doesn't reveal a white strip. */
            background-color: #020617;
            /* Use the dynamic viewport so the iOS address-bar
               collapse doesn't trigger a layout shift. 100vh is the
               fallback for older browsers. */
            min-height: 100vh;
            min-height: 100dvh;
            /* CRITICAL ANTI-ZOOM: apply touch-action at the document
               root so the gesture-determination chain (touched
               element → ancestors → root) lands on `manipulation`
               for EVERY touch on the page — including non-
               interactive areas (cards, posters, gallery images,
               empty layout space) that previously had no
               touch-action declaration and therefore fell back to
               Safari's default smart-zoom-on-double-tap.

               `manipulation` allows panning + pinch-zoom (two-finger
               gesture) and disables ONLY the 300ms tap delay +
               double-tap-zoom. Pinch-to-zoom accessibility is
               preserved. */
            touch-action: manipulation;
        }
        body {
            font-family: var(--font-sans, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif);
            -webkit-tap-highlight-color: transparent;
            -webkit-text-size-adjust: 100%;
            text-size-adjust: 100%;
            min-height: 100vh;
            min-height: 100dvh;
            /* Prevent rubber-band overscroll from propagating to
               sticky elements (the navbar and sticky-action footer
               jitter without this on iOS). */
            overscroll-behavior-y: contain;
        }

        /* Mobile-friendly tap targets — every clickable element on
           the public + admin surfaces should be at least 44×44 with
           a little visual feedback. iOS in particular needs an
           explicit -webkit-tap-highlight-color override to avoid the
           default grey flash. */
        button,
        [role="button"],
        input[type="submit"],
        input[type="button"],
        a,
        label,
        summary {
            -webkit-tap-highlight-color: transparent;
            /* `touch-action: manipulation` keeps pinch-zoom alive
               globally but suppresses the 300ms double-tap-zoom
               delay *on this element*. Exactly the
               accessibility-preserving behaviour we want. */
            touch-action: manipulation;
        }

        /* iOS Safari auto-zooms into ANY form control whose
           computed font-size is below 16px on focus, which yanks
           the layout. `!important` here defeats Tailwind utility
           classes like `text-sm` / `text-[14px]` applied on parents
           (Tailwind's Preflight sets `input { font-size: 100% }`,
           so the input inherits whatever the parent says).
           Excludes hidden / range / checkbox / radio because their
           size is unrelated to the zoom heuristic. */
        @media (max-width: 639.98px) {
            input:not([type="hidden"]):not([type="range"]):not([type="checkbox"]):not([type="radio"]),
            select,
            textarea {
                font-size: 16px !important;
                /* `text-size-adjust: 100%` on inputs in particular
                   keeps Safari from rescaling text inside the input
                   while typing on landscape orientation. */
                -webkit-text-size-adjust: 100%;
                text-size-adjust: 100%;
            }
        }

        /* =========================================================
           Touch-device :hover lock fix.
           ---------------------------------------------------------
           Mobile Safari and Chrome-on-Android treat the FIRST tap
           on a hover-styled element as a "hover activation" — the
           :hover state remains matched until the user taps
           somewhere else. Tailwind utilities like
           `hover:scale-105` / `group-hover:scale-[1.035]` therefore
           leave cards visibly enlarged after a tap, which is the
           "certain sections suddenly become enlarged" symptom in
           the bug report.

           `@media (hover: none)` matches devices without a real
           hover capability (i.e. touch devices), and the selector
           below neutralises transform-based hover effects there
           while leaving non-hover animations (button spinners,
           pulse keyframes, etc.) and desktop hover effects intact.
           ========================================================= */
        @media (hover: none) {
            [class*="scale-"]:hover,
            .group:hover [class*="scale-"],
            .group:focus-within [class*="scale-"] {
                transform: none !important;
            }
        }

        /* خلفية أجواء مسرح */
        .stage-bg {
            background:
                radial-gradient(circle at top, rgba(255,255,255,0.14), transparent 55%),
                radial-gradient(circle at 20% 0, rgba(251,191,36,0.2), transparent 60%),
                radial-gradient(circle at 80% 0, rgba(239,68,68,0.25), transparent 60%),
                #020617;
        }

        .scream-hero {
            position: relative;
            overflow: hidden;
        }
        .scream-hero::before {
            content: "";
            position: absolute;
            inset: -40%;
            background:
                radial-gradient(circle at 10% 0, rgba(251,191,36,0.22), transparent 60%),
                radial-gradient(circle at 90% 10%, rgba(248,113,113,0.28), transparent 60%);
            opacity: 0.9;
            filter: blur(30px);
            z-index: -1;
        }
        .scream-border {
            border-radius: 1.5rem;
            background: linear-gradient(135deg, rgba(250,204,21,0.5), rgba(248,113,113,0.5));
            padding: 1px;
        }
        .scream-card {
            border-radius: 1.4rem;
            background: radial-gradient(circle at top, rgba(15,23,42,0.95), rgba(2,6,23,0.96));
        }

        @keyframes screamGlow {
            0%, 100% { text-shadow: 0 0 10px rgba(250,204,21,0.4); transform: translateY(0); }
            50% { text-shadow: 0 0 22px rgba(248,113,113,0.9); transform: translateY(-2px); }
        }
        .scream-title {
            animation: screamGlow 2.4s ease-in-out infinite;
        }

        @keyframes screamPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(250,204,21,0.0); }
            50% { box-shadow: 0 0 35px 0 rgba(250,204,21,0.45); }
        }
        .scream-pulse {
            animation: screamPulse 3s ease-in-out infinite;
        }

        .logo-light {
            filter: drop-shadow(0 0 25px rgba(255,255,255,0.7));
        }
    </style>
    <style>
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }

/* =========================================================
   STICKY ACTION FOOTER
   ---------------------------------------------------------
   Native-feeling sticky action bar pattern used across the
   public booking flow and admin approval/edit screens.

   How it works
   ------------
   Pages add `data-sticky-action` to the natural in-flow
   action container (Approve/Reject buttons, "Submit
   booking" button, "Save changes" button, etc.).

   On boot we clone each natural action into a fixed footer
   pinned to the bottom of the viewport. While the natural
   action is OFF-SCREEN we fade the floating clone in; the
   instant the user scrolls far enough to see the real
   action in its natural place at the bottom of the page,
   we fade the clone back out so the layout never has two
   competing CTAs visible at once.

   Clones don't double-submit — they synthesize a click on
   the original control, which goes through the original
   form's submit handler (including any in-progress /
   disabled state).
   ========================================================= */
#sticky-action-footer {
    position: fixed;
    inset-inline: 0;
    bottom: 0;
    z-index: 60;
    padding: 10px 14px max(10px, env(safe-area-inset-bottom)) 14px;
    background: linear-gradient(180deg, rgba(2,6,23,0) 0%, rgba(2,6,23,0.85) 35%, rgba(2,6,23,0.96) 100%);
    border-top: 1px solid rgba(255,255,255,0.08);
    backdrop-filter: blur(14px) saturate(140%);
    -webkit-backdrop-filter: blur(14px) saturate(140%);
    opacity: 0;
    pointer-events: none;
    transform: translateY(12px);
    transition: opacity .22s cubic-bezier(.2,.7,.2,1), transform .22s cubic-bezier(.2,.7,.2,1);
}
#sticky-action-footer.is-visible {
    opacity: 1;
    pointer-events: auto;
    transform: translateY(0);
}
#sticky-action-footer .sa-inner {
    max-width: 64rem;
    margin: 0 auto;
}
#sticky-action-footer .sa-inner > * {
    width: 100%;
}
#sticky-action-footer .sa-inner button,
#sticky-action-footer .sa-inner a,
#sticky-action-footer .sa-inner input[type="submit"] {
    min-height: 48px;
}

/* Tiny inline spinner used by submit buttons while their
   handler is in-flight. Kept generic so any form on the site
   can opt in via `is-loading` on the button. */
.btn-spinner {
    display: inline-block;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 2px solid currentColor;
    border-top-color: transparent;
    animation: btnSpin .7s linear infinite;
    vertical-align: -2px;
    margin-inline-end: 6px;
}
@keyframes btnSpin { to { transform: rotate(360deg); } }
button.is-loading,
input[type="submit"].is-loading {
    opacity: 0.85;
    cursor: progress;
}

/* When a `data-sticky-action` block is present we pad the
   bottom of the main content so the floating CTA never
   covers the last paragraph / submit button on short
   viewports. Calculated to clear the footer + safe area. */
body.has-sticky-action main {
    padding-bottom: calc(110px + env(safe-area-inset-bottom));
}
</style>

</head>
<body class="stage-bg min-h-screen text-gray-100 @yield('body_class')">

    {{-- Stage Cyclorama & Volumetric Overhead Rig --}}
    <div class="stage-light-rig" aria-hidden="true">
        <div class="stage-beam-center"></div>
        <div class="stage-beam-crimson"></div>
    </div>

    {{-- Camera Z-Push Stage World Container --}}
    <div id="elsar5a-stage-world" class="elsar5a-stage-world">

        {{-- Navbar --}}
        @unless(request()->routeIs('admin.scanner'))
        <header class="site-header border-b border-white/10 bg-black/40 backdrop-blur sticky top-0 z-40">
            <div class="site-header-inner max-w-5xl mx-auto px-3 sm:px-4 flex items-center justify-between gap-2">

                {{-- اللوجو + الاسم --}}
                <a href="{{ url('/') }}"
                   class="site-brand flex items-center gap-2.5 group focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60 rounded-xl"
                   aria-label="الصفحة الرئيسية">
                    <img src="{{ asset('images/sarkha-logo.png') }}"
                         class="site-logo object-contain invert brightness-125
                                drop-shadow-[0_0_10px_rgba(255,255,255,0.45)]
                                transition-transform duration-300 group-hover:scale-105"
                         alt="فريق الصرخة المسرحي"
                         width="40" height="40">

                    <div class="site-brand-text hidden sm:flex flex-col leading-tight">
                        <div class="site-brand-title text-sm font-semibold whitespace-nowrap text-gray-100">
                            فريق الصرخة المسرحي
                        </div>
                        <div class="site-brand-subtitle text-[10px] text-amber-200/80">
                            حجز تذاكر العروض أونلاين
                        </div>
                    </div>
                </a>

                {{-- الناف بار --}}
                <nav aria-label="التنقل" class="site-nav">
                    {{-- Home --}}
                    <a href="{{ url('/') }}"
                       class="nav-link {{ request()->routeIs('home') || request()->is('/') ? 'is-active' : '' }}">
                        الرئيسية
                    </a>

                    {{-- العروض السابقة --}}
                    <a href="{{ route('archive') }}"
                       class="nav-link {{ request()->routeIs('archive') ? 'is-active' : '' }}">
                        العروض السابقة
                    </a>

                    {{-- About --}}
                    <a href="{{ route('about') }}"
                       class="nav-link {{ request()->routeIs('about') ? 'is-active' : '' }}">
                        عن الفريق
                    </a>
                </nav>

            </div>
        </header>
        @endunless


    {{-- المحتوى الرئيسي --}}
    <main class="@yield('main_class', request()->routeIs('admin.scanner') ? 'w-full p-0 m-0' : 'max-w-5xl mx-auto px-4 py-6 md:py-10')">
        @yield('content')
    </main>

    @unless(request()->routeIs('admin.scanner'))
    {{-- ================= Footer =================
         Compact, elegant, centered closing footer.
         Presents a restrained closing message with subtle typographic hierarchy,
         grounded by the team brand, final slogan, social links, and copyright. --}}
    {{-- ================= Footer =================
         Theatrical Glass Plaque closing footer matching reference design.
         Compact, elegant, centered floating card with fine golden hairline frame,
         brand lockup, subtle manifesto hierarchy, illuminated slogan, and glass social buttons. --}}
    <footer class="site-footer mt-10 sm:mt-14 pb-8 sm:pb-12" role="contentinfo">
        <div class="footer-ambient-wrapper px-3 sm:px-4">
            <div class="stage-footer-card">
                {{-- Inner decorative hairline frame with side stars --}}
                <div class="stage-footer-frame" aria-hidden="true">
                    <span class="frame-star frame-star--left">✦</span>
                    <span class="frame-star frame-star--right">✦</span>
                </div>

                {{-- Brand Identity Lockup (Logo on right, text on left matching reference) --}}
                <div class="stage-footer-brand">
                    <img src="{{ asset('images/sarkha-logo.png') }}"
                         class="brand-logo"
                         alt="شعار فريق الصرخة المسرحي"
                         width="38" height="38" loading="lazy">
                    <div class="brand-text-col">
                        <span class="brand-name">فريق الصرخة المسرحي</span>
                        <span class="brand-sub">LIVE &bull; THEATER &bull; SCREAM</span>
                    </div>
                </div>

                @if(request()->routeIs('home') || request()->is('/'))
                {{-- Compact Theatrical Manifesto Note --}}
                <div class="footer-compact-manifesto">
                    <p class="manifesto-note-line">
                        كثيرا ما فسدت عقولنا من ما حملته لها مدخلاتنا ، ذلك هو ما نريد تغييره، <span class="manifesto-note-hl">فقط بالصراخ</span> ، الصراخ هنا و هناك.
                    </p>
                    <p class="manifesto-note-line">
                        نصرخ ندعوا الجميع للمجيء الينا و منحنا من وقتهم القليل، <span class="manifesto-note-hl">فنحن لا نريد سوى حواسكم</span>.
                    </p>
                    <p class="manifesto-note-line">
                        ثم نصرخ نبحث فى مدخلاتكم لنخرج ما هو فاسد و نزرع بدلا منه ثمرا صالحنا ، <span class="manifesto-note-hl">لا نريد سو عقولكم</span>.
                    </p>
                    <p class="manifesto-note-line">
                        والان نصرخ <span class="manifesto-note-hl">بالتعاليم الصاحه لنغير ما فسد</span>.
                    </p>
                    <p class="manifesto-note-line">
                        و فقط ما نحتاجه من الجميع هو المجيء إلى مصدر الصراخ، <span class="manifesto-note-hl">فدائما يكون على المسرح</span>.
                    </p>
                    <p class="manifesto-note-line manifesto-note-line--quiet">
                        و قد كان سيدنا و معلمنا و الهنا وهو على الأرض يجول يصنع خيرا و نحن على خطاه.
                    </p>
                </div>
                @endif

                {{-- Final Closing Slogan Lockup with Ornamental Wings & Stars --}}
                <div class="footer-slogan-lockup">
                    <span class="slogan-wing slogan-wing--left" aria-hidden="true"></span>
                    <span class="slogan-star" aria-hidden="true">✦</span>
                    <p class="footer-final-slogan">نجول نصرخ فيزداد العقل وعياً</p>
                    <span class="slogan-star" aria-hidden="true">✦</span>
                    <span class="slogan-wing slogan-wing--right" aria-hidden="true"></span>
                </div>

                @php
                    $siteAbout = $about ?? \App\Models\About::first();
                    $ytUrl = $siteAbout?->youtube ?: 'https://youtube.com/@sar5ateam611?si=ioONwMrKfYJx-Bks';
                    $fbUrl = $siteAbout?->facebook ?: 'https://www.facebook.com/share/17WQUehRZG/?mibextid=wwXIfr';
                    $igUrl = $siteAbout?->instagram ?: 'https://www.instagram.com/elsar5ateam?igsh=cjc0aG94Nmk1YTBt';
                @endphp

                {{-- Frosted Circular Social Buttons (Instagram, Facebook, YouTube) --}}
                <div class="footer-social-row" aria-label="روابط التواصل الاجتماعي">
                    @if($igUrl)
                        <a href="{{ $igUrl }}" target="_blank" rel="noopener noreferrer"
                           class="footer-social-btn" aria-label="حساب فريق الصرخة على Instagram">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 2.2c3.2 0 3.6 0 4.9.1 1.2 0 1.8.2 2.3.4.6.2 1 .5 1.4.9.4.4.7.8.9 1.4.2.5.3 1.1.4 2.3 0 1.3.1 1.7.1 4.9s0 3.6-.1 4.9c0 1.2-.2 1.8-.4 2.3-.2.6-.5 1-.9 1.4-.4.4-.8.7-1.4.9-.5.2-1.1.3-2.3.4-1.3 0-1.7.1-4.9.1s-3.6 0-4.9-.1c-1.2 0-1.8-.2-2.3-.4-.6-.2-1-.5-1.4-.9-.4-.4-.7-.8-.9-1.4-.2-.5-.3-1.1-.4-2.3 0-1.3-.1-1.7-.1-4.9s0-3.6.1-4.9c0-1.2.2-1.8.4-2.3.2-.6.5-1 .9-1.4.4-.4.8-.7 1.4-.9.5-.2 1.1-.3 2.3-.4 1.3-.1 1.7-.1 4.9-.1M12 0C8.7 0 8.3 0 7.1.1 5.8.1 5 .3 4.2.6c-.8.3-1.5.7-2.2 1.4C1.3 2.7.9 3.4.6 4.2.3 5 .1 5.8.1 7.1 0 8.3 0 8.7 0 12s0 3.7.1 4.9c.1 1.3.3 2.1.6 2.9.3.8.7 1.5 1.4 2.2.7.7 1.4 1.1 2.2 1.4.8.3 1.6.5 2.9.6 1.2.1 1.6.1 4.9.1s3.7 0 4.9-.1c1.3-.1 2.1-.3 2.9-.6.8-.3 1.5-.7 2.2-1.4.7-.7 1.1-1.4 1.4-2.2.3-.8.5-1.6.6-2.9.1-1.2.1-1.6.1-4.9s0-3.7-.1-4.9c-.1-1.3-.3-2.1-.6-2.9-.3-.8-.7-1.5-1.4-2.2C21.3 1.3 20.6.9 19.8.6 19 .3 18.2.1 16.9.1 15.7 0 15.3 0 12 0zm0 5.8a6.2 6.2 0 1 0 0 12.4 6.2 6.2 0 0 0 0-12.4zm0 10.2a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.4-11.8a1.4 1.4 0 1 0 0 2.9 1.4 1.4 0 0 0 0-2.9z"/>
                            </svg>
                        </a>
                    @endif
                    @if($fbUrl)
                        <a href="{{ $fbUrl }}" target="_blank" rel="noopener noreferrer"
                           class="footer-social-btn" aria-label="صفحة فريق الصرخة على Facebook">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M24 12a12 12 0 1 0-13.9 11.9v-8.4H7v-3.5h3.1V9.4c0-3 1.8-4.7 4.6-4.7 1.3 0 2.7.2 2.7.2v3h-1.5c-1.5 0-2 .9-2 1.9v2.3h3.4l-.5 3.5h-2.9v8.4A12 12 0 0 0 24 12"/>
                            </svg>
                        </a>
                    @endif
                    @if($ytUrl)
                        <a href="{{ $ytUrl }}" target="_blank" rel="noopener noreferrer"
                           class="footer-social-btn" aria-label="قناة فريق الصرخة على YouTube">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.5 12 3.5 12 3.5s-7.5 0-9.4.6A3 3 0 0 0 .5 6.2C0 8.1 0 12 0 12s0 3.9.5 5.8a3 3 0 0 0 2.1 2.1c1.9.6 9.4.6 9.4.6s7.5 0 9.4-.6a3 3 0 0 0 2.1-2.1c.5-1.9.5-5.8.5-5.8s0-3.9-.5-5.8zM9.6 15.6V8.4l6.3 3.6-6.3 3.6z"/>
                            </svg>
                        </a>
                    @endif
                </div>

                {{-- Subtle Centered Copyright --}}
                <p class="footer-copyright">
                    &copy; {{ now()->year }} فريق الصرخة المسرحي
                </p>
            </div>
        </div>
    </footer>
    @endunless
    </div> {{-- /#elsar5a-stage-world --}}

    {{--
        Sticky action footer bootstrapper. See the CSS comment above
        for the full design rationale. The script is intentionally
        plain, dependency-free, and gated on the existence of at
        least one `[data-sticky-action]` element so we don't pay
        any runtime cost on pages that don't opt in.
    --}}
    <script>
    (function () {
        function init() {
            var anchors = document.querySelectorAll('[data-sticky-action]');
            if (!anchors.length) return;
            if (!('IntersectionObserver' in window)) return; // graceful no-op on ancient browsers

            document.body.classList.add('has-sticky-action');

            var footer = document.createElement('div');
            footer.id = 'sticky-action-footer';
            footer.setAttribute('role', 'region');
            footer.setAttribute('aria-label', 'إجراءات سريعة');
            footer.innerHTML = '<div class="sa-inner"></div>';
            document.body.appendChild(footer);

            var inner = footer.querySelector('.sa-inner');

            function wireClone(anchor, clone) {
                clone.removeAttribute('data-sticky-action');
                clone.removeAttribute('id');
                clone.querySelectorAll('[id]').forEach(function (n) { n.removeAttribute('id'); });

                var originals = anchor.querySelectorAll('button, a, input[type=submit], input[type=button]');
                var clones    = clone.querySelectorAll('button, a, input[type=submit], input[type=button]');

                clones.forEach(function (c, i) {
                    var orig = originals[i];
                    if (!orig) return;
                    // Demote to a plain button so the clone never tries to
                    // submit its own (cloned, detached) form. We delegate
                    // back to the original control, which preserves any
                    // double-submit guard / disabled state.
                    if (c.tagName === 'BUTTON' || c.tagName === 'INPUT') {
                        c.type = 'button';
                    }
                    // Mirror disabled state explicitly. cloneNode picks up
                    // the original's `disabled` attribute, but it does
                    // NOT pick up `.disabled` set via JS — so a button
                    // that's been programmatically enabled by the form
                    // (e.g. the booking submit becoming amber once the
                    // screenshot is attached) needs us to copy the
                    // current state at clone time.
                    if ('disabled' in c) {
                        c.disabled = !!orig.disabled;
                    }
                    c.addEventListener('click', function (e) {
                        e.preventDefault();
                        if (orig.disabled || orig.classList.contains('is-loading')) return;
                        orig.click();
                    });
                });
            }

            function render(anchor) {
                // Always re-clone on each show so the floating CTA
                // reflects the latest state of the original control
                // (enabled/disabled, label, classes, spinner, etc.).
                inner.innerHTML = '';
                var clone = anchor.cloneNode(true);
                wireClone(anchor, clone);
                inner.appendChild(clone);
            }

            function update() {
                // Hide the floating footer if ANY natural anchor is in view.
                var anyVisible = false;
                var lastAnchor = anchors[anchors.length - 1];
                anchors.forEach(function (a) {
                    var r = a.getBoundingClientRect();
                    var visible = r.top < (window.innerHeight - 40) && r.bottom > 0;
                    if (visible) anyVisible = true;
                });

                if (anyVisible) {
                    footer.classList.remove('is-visible');
                    inner.innerHTML = '';
                } else {
                    render(lastAnchor);
                    footer.classList.add('is-visible');
                }
            }

            // Initial paint + react to scroll, resize, and DOM mutations
            // that affect anchor layout (e.g. form fields expanding).
            update();
            window.addEventListener('scroll', update, { passive: true });
            window.addEventListener('resize', update);
            window.addEventListener('orientationchange', update);

            // ResizeObserver catches cases like "ticket count goes from 1
            // to 3 and the form grows" without us having to manually
            // notify the sticky footer.
            if ('ResizeObserver' in window) {
                var ro = new ResizeObserver(update);
                anchors.forEach(function (a) { ro.observe(a); });
                ro.observe(document.body);
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();
    </script>

    {{-- Homepage-only cinematic choreography --}}
    @if(request()->routeIs('home'))
        <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js" defer></script>
        <script src="{{ asset('js/elsar5a-stage.js') }}?v={{ file_exists(public_path('js/elsar5a-stage.js')) ? filemtime(public_path('js/elsar5a-stage.js')) : '1.0' }}" defer></script>
    @endif
</body>
</html>
