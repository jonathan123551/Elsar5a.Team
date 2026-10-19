<!DOCTYPE html>
<html lang="ar" dir="rtl" class="no-js">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'لوحة تحكم الأدمن — فريق الصرخة')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#070a11">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="{{ asset('images/sarkha-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/sarkha-logo.png') }}">

    <script>document.documentElement.classList.replace('no-js', 'js');</script>

    {{-- Tailwind CSS CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Fonts: Alexandria & Readex Pro --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@600;700;800;900&family=Readex+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --adm-bg: #070a11;
            --adm-surface: #0e131f;
            --adm-border: rgba(255, 255, 255, 0.08);
            --adm-gold: #f59e0b;
        }

        html {
            -webkit-text-size-adjust: 100%;
            text-size-adjust: 100%;
            background-color: var(--adm-bg);
            min-height: 100vh;
            min-height: 100dvh;
            touch-action: manipulation;
        }

        body {
            font-family: 'Readex Pro', system-ui, -apple-system, sans-serif;
            background-color: var(--adm-bg);
            color: #f1f5f9;
            -webkit-tap-highlight-color: transparent;
            min-height: 100vh;
            min-height: 100dvh;
            overscroll-behavior-y: contain;
        }

        h1, h2, h3, .font-heading {
            font-family: 'Alexandria', system-ui, sans-serif;
        }

        /* 44px minimum tap targets */
        button, a, input, select, textarea, [role="button"] {
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
        }

        @media (max-width: 639.98px) {
            input:not([type="hidden"]):not([type="range"]):not([type="checkbox"]):not([type="radio"]),
            select,
            textarea {
                font-size: 16px !important;
                -webkit-text-size-adjust: 100%;
                text-size-adjust: 100%;
            }
        }

        @media (hover: none) {
            [class*="scale-"]:hover,
            .group:hover [class*="scale-"] {
                transform: none !important;
            }
        }

        /* Scrollbar aesthetics */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: rgba(0, 0, 0, 0.2); }
        ::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.15); border-radius: 999px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(245, 158, 11, 0.5); }

        .adm-nav-link {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            min-height: 44px;
            padding: 0.5rem 0.875rem;
            border-radius: 0.75rem;
            font-size: 0.8125rem;
            font-weight: 500;
            color: #94a3b8;
            transition: all 0.15s ease-in-out;
            white-space: nowrap;
        }
        .adm-nav-link:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.05);
        }
        .adm-nav-link.is-active {
            color: #fde68a;
            background: rgba(245, 158, 11, 0.12);
            font-weight: 700;
            border: 1px solid rgba(245, 158, 11, 0.25);
        }

        /* Subtle animated spinner for loading buttons */
        .btn-spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 2px solid currentColor;
            border-top-color: transparent;
            animation: admSpin .7s linear infinite;
        }
        @keyframes admSpin { to { transform: rotate(360deg); } }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen flex flex-col text-slate-100 antialiased selection:bg-amber-500 selection:text-black @yield('body_class')">

    {{-- Subtle Stage Ambient Glow --}}
    <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden" aria-hidden="true">
        <div class="absolute -top-40 right-1/4 w-96 h-96 bg-amber-500/10 rounded-full blur-3xl"></div>
        <div class="absolute top-1/3 -left-40 w-96 h-96 bg-red-600/10 rounded-full blur-3xl"></div>
    </div>

    {{-- =========================================================
         OPERATIONAL ADMIN HEADER
         ========================================================= --}}
    <header class="sticky top-0 z-40 bg-[#070a11]/85 backdrop-blur-md border-b border-white/10 shadow-lg shadow-black/20">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 h-16 sm:h-18 flex items-center justify-between gap-3">

            {{-- Right: Brand Identity Lockup --}}
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.dashboard') }}"
                   class="flex items-center gap-2.5 group focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 rounded-xl"
                   aria-label="لوحة تحكم الأدمن">
                    <div class="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center p-1.5 group-hover:border-amber-400/50 transition duration-200">
                        <img src="{{ asset('images/sarkha-logo.png') }}"
                             class="w-full h-full object-contain invert brightness-125 drop-shadow-[0_0_8px_rgba(255,255,255,0.4)]"
                             alt="شعار الصرخة">
                    </div>
                    <div class="flex flex-col leading-tight">
                        <span class="text-sm sm:text-base font-bold font-heading text-white flex items-center gap-1.5">
                            إدارة الصرخة
                            <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-amber-500/15 text-amber-300 border border-amber-500/30 font-sans font-semibold">
                                أدمن
                            </span>
                        </span>
                        <span class="text-[11px] text-slate-400 hidden sm:inline">نظام التذاكر والحجوزات</span>
                    </div>
                </a>
            </div>

            {{-- Center: Desktop Operational Navigation --}}
            @php
                $pendingBookingsCount = \App\Models\Booking::where('status', 'pending')->count();
            @endphp
            <nav class="hidden lg:flex items-center gap-1 overflow-x-auto py-1" aria-label="أقسام الإدارة الرئيسية">
                <a href="{{ route('admin.dashboard') }}"
                   class="adm-nav-link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">
                    <span>📊</span>
                    <span>الرئيسية</span>
                </a>

                <a href="{{ route('admin.bookings.index') }}"
                   class="adm-nav-link {{ request()->routeIs('admin.bookings.*') || request()->routeIs('admin.resend.ticket') ? 'is-active' : '' }}">
                    <span>💳</span>
                    <span>الحجوزات</span>
                    @if($pendingBookingsCount > 0)
                        <span class="px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-sky-500/20 text-sky-300 border border-sky-400/30 tabular-nums">
                            {{ $pendingBookingsCount }}
                        </span>
                    @endif
                </a>

                <a href="{{ route('admin.shows.index') }}"
                   class="adm-nav-link {{ request()->routeIs('admin.shows.*') || request()->routeIs('admin.shows.times.*') ? 'is-active' : '' }}">
                    <span>🎭</span>
                    <span>العروض</span>
                </a>

                <a href="{{ route('admin.scanner') }}"
                   class="adm-nav-link {{ request()->routeIs('admin.scanner') ? 'is-active' : '' }}">
                    <span>📷</span>
                    <span>فحص التذاكر</span>
                </a>

                <a href="{{ route('admin.team_applications.index') }}"
                   class="adm-nav-link {{ request()->routeIs('admin.team_applications.*') ? 'is-active' : '' }}">
                    <span>👥</span>
                    <span>الانضمام</span>
                </a>

                <a href="{{ route('admin.archive.index') }}"
                   class="adm-nav-link {{ request()->routeIs('admin.archive.*') ? 'is-active' : '' }}">
                    <span>🏛️</span>
                    <span>الأرشيف</span>
                </a>

                <a href="{{ route('admin.settings.payments.edit') }}"
                   class="adm-nav-link {{ request()->routeIs('admin.settings.*') ? 'is-active' : '' }}">
                    <span>⚙️</span>
                    <span>الدفع</span>
                </a>
            </nav>

            {{-- Left: Utilities & Mobile Hamburger --}}
            <div class="flex items-center gap-2">
                {{-- Public Site Link --}}
                <a href="{{ url('/') }}"
                   target="_blank"
                   rel="noopener"
                   class="hidden sm:inline-flex items-center gap-1.5 text-xs text-slate-300 hover:text-white px-3 py-2 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 transition min-h-[44px]"
                   title="فتح الموقع العام للجمهور">
                    <span>🌐</span>
                    <span>عرض الموقع</span>
                    <svg class="w-3.5 h-3.5 rtl:rotate-180 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                </a>

                {{-- Logout Button --}}
                <form action="{{ route('logout') }}" method="POST" class="hidden sm:inline-block">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 text-xs text-red-300 hover:text-red-200 px-3 py-2 rounded-xl bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 transition min-h-[44px]">
                        <span>🚪</span>
                        <span>خروج</span>
                    </button>
                </form>

                {{-- Mobile Hamburger Toggle --}}
                <button type="button"
                        id="adm-mobile-toggle"
                        class="lg:hidden inline-flex items-center justify-center w-11 h-11 rounded-xl bg-white/5 border border-white/10 text-slate-200 hover:text-white hover:bg-white/10 transition focus:outline-none focus:ring-2 focus:ring-amber-400"
                        aria-controls="adm-mobile-drawer"
                        aria-expanded="false"
                        aria-label="قائمة الإدارة">
                    <svg class="w-6 h-6 adm-icon-menu" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg class="w-6 h-6 adm-icon-close hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

        </div>

        {{-- Mobile Drawer Navigation --}}
        <div id="adm-mobile-drawer"
             class="hidden lg:hidden border-t border-white/10 bg-[#070a11]/95 backdrop-blur-xl transition-all duration-200 shadow-2xl"
             role="dialog"
             aria-modal="true"
             aria-label="قائمة الإدارة للموبايل">
            <div class="px-4 py-4 space-y-1.5 max-h-[calc(100dvh-5rem)] overflow-y-auto">

                <a href="{{ route('admin.dashboard') }}"
                   class="flex items-center justify-between px-3 py-3 rounded-xl text-sm font-medium {{ request()->routeIs('admin.dashboard') ? 'bg-amber-500/15 text-amber-300 font-bold' : 'text-slate-300 hover:bg-white/5' }}">
                    <span class="flex items-center gap-2.5">
                        <span class="text-base">📊</span>
                        <span>لوحة التحكم الرئيسية</span>
                    </span>
                    <span class="text-xs opacity-50">←</span>
                </a>

                <a href="{{ route('admin.bookings.index') }}"
                   class="flex items-center justify-between px-3 py-3 rounded-xl text-sm font-medium {{ request()->routeIs('admin.bookings.*') || request()->routeIs('admin.resend.ticket') ? 'bg-amber-500/15 text-amber-300 font-bold' : 'text-slate-300 hover:bg-white/5' }}">
                    <span class="flex items-center gap-2.5">
                        <span class="text-base">💳</span>
                        <span>إدارة الحجوزات والتحويلات</span>
                    </span>
                    @if($pendingBookingsCount > 0)
                        <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-sky-500/20 text-sky-300 border border-sky-400/30">
                            {{ $pendingBookingsCount }} معلّق
                        </span>
                    @endif
                </a>

                <a href="{{ route('admin.shows.index') }}"
                   class="flex items-center justify-between px-3 py-3 rounded-xl text-sm font-medium {{ request()->routeIs('admin.shows.*') || request()->routeIs('admin.shows.times.*') ? 'bg-amber-500/15 text-amber-300 font-bold' : 'text-slate-300 hover:bg-white/5' }}">
                    <span class="flex items-center gap-2.5">
                        <span class="text-base">🎭</span>
                        <span>العروض المسرحية والمواعيد</span>
                    </span>
                    <span class="text-xs opacity-50">←</span>
                </a>

                <a href="{{ route('admin.scanner') }}"
                   class="flex items-center justify-between px-3 py-3 rounded-xl text-sm font-medium {{ request()->routeIs('admin.scanner') ? 'bg-amber-500/15 text-amber-300 font-bold' : 'text-slate-300 hover:bg-white/5' }}">
                    <span class="flex items-center gap-2.5">
                        <span class="text-base">📷</span>
                        <span>ماسح التذاكر على الباب (Scan)</span>
                    </span>
                    <span class="text-xs opacity-50">←</span>
                </a>

                <a href="{{ route('admin.team_applications.index') }}"
                   class="flex items-center justify-between px-3 py-3 rounded-xl text-sm font-medium {{ request()->routeIs('admin.team_applications.*') ? 'bg-amber-500/15 text-amber-300 font-bold' : 'text-slate-300 hover:bg-white/5' }}">
                    <span class="flex items-center gap-2.5">
                        <span class="text-base">👥</span>
                        <span>طلبات الانضمام للفريق</span>
                    </span>
                    <span class="text-xs opacity-50">←</span>
                </a>

                <a href="{{ route('admin.archive.index') }}"
                   class="flex items-center justify-between px-3 py-3 rounded-xl text-sm font-medium {{ request()->routeIs('admin.archive.*') ? 'bg-amber-500/15 text-amber-300 font-bold' : 'text-slate-300 hover:bg-white/5' }}">
                    <span class="flex items-center gap-2.5">
                        <span class="text-base">🏛️</span>
                        <span>أرشيف العروض السابقة</span>
                    </span>
                    <span class="text-xs opacity-50">←</span>
                </a>

                <a href="{{ route('admin.about.edit') }}"
                   class="flex items-center justify-between px-3 py-3 rounded-xl text-sm font-medium {{ request()->routeIs('admin.about.*') ? 'bg-amber-500/15 text-amber-300 font-bold' : 'text-slate-300 hover:bg-white/5' }}">
                    <span class="flex items-center gap-2.5">
                        <span class="text-base">ℹ️</span>
                        <span>تعديل عن الفريق</span>
                    </span>
                    <span class="text-xs opacity-50">←</span>
                </a>

                <a href="{{ route('admin.settings.payments.edit') }}"
                   class="flex items-center justify-between px-3 py-3 rounded-xl text-sm font-medium {{ request()->routeIs('admin.settings.*') ? 'bg-amber-500/15 text-amber-300 font-bold' : 'text-slate-300 hover:bg-white/5' }}">
                    <span class="flex items-center gap-2.5">
                        <span class="text-base">⚙️</span>
                        <span>إعدادات الدفع والمحافظ</span>
                    </span>
                    <span class="text-xs opacity-50">←</span>
                </a>

                <div class="pt-3 mt-3 border-t border-white/10 grid grid-cols-2 gap-2">
                    <a href="{{ url('/') }}"
                       target="_blank"
                       rel="noopener"
                       class="flex items-center justify-center gap-1.5 py-2.5 min-h-[44px] rounded-xl bg-white/5 border border-white/10 text-xs font-semibold text-slate-200">
                        <span>🌐</span>
                        <span>عرض الموقع</span>
                    </a>

                    <form action="{{ route('logout') }}" method="POST" class="w-full">
                        @csrf
                        <button type="submit"
                                class="w-full flex items-center justify-center gap-1.5 py-2.5 min-h-[44px] rounded-xl bg-red-500/10 border border-red-500/25 text-xs font-semibold text-red-300">
                            <span>🚪</span>
                            <span>خروج</span>
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </header>

    {{-- =========================================================
         GLOBAL FLASH MESSAGES & SYSTEM ALERTS
         ========================================================= --}}
    <div class="max-w-7xl mx-auto px-3 sm:px-6 w-full pt-4 relative z-10">
        @if(session('status'))
            <div role="status"
                 class="flex items-center gap-2.5 p-3 sm:p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-200 text-xs sm:text-sm font-medium mb-3 shadow-lg shadow-emerald-950/20">
                <span class="text-emerald-400 text-base">✓</span>
                <span class="flex-1">{{ session('status') }}</span>
            </div>
        @endif

        @if(session('success'))
            <div role="status"
                 class="flex items-center gap-2.5 p-3 sm:p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-200 text-xs sm:text-sm font-medium mb-3 shadow-lg shadow-emerald-950/20">
                <span class="text-emerald-400 text-base">✓</span>
                <span class="flex-1">{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div role="alert"
                 class="flex items-center gap-2.5 p-3 sm:p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-200 text-xs sm:text-sm font-medium mb-3 shadow-lg shadow-rose-950/20">
                <span class="text-rose-400 text-base">✕</span>
                <span class="flex-1">{{ session('error') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div role="alert"
                 class="p-3 sm:p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-200 text-xs sm:text-sm mb-3 shadow-lg shadow-rose-950/20">
                <div class="font-bold mb-1.5 flex items-center gap-2">
                    <span>⚠️</span>
                    <span>يرجى تصحيح الأخطاء التالية:</span>
                </div>
                <ul class="list-disc list-inside space-y-1 text-rose-300">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    {{-- =========================================================
         MAIN ADMIN CONTENT CONTAINER
         ========================================================= --}}
    <main class="flex-1 w-full max-w-7xl mx-auto px-3 sm:px-6 py-4 sm:py-6 relative z-10 @yield('main_class')">
        @yield('content')
    </main>

    {{-- =========================================================
         MINIMAL OPERATIONAL ADMIN FOOTER
         ========================================================= --}}
    <footer class="mt-auto border-t border-white/5 bg-[#05070c] py-4 text-center text-xs text-slate-500 relative z-10">
        <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
            <div>
                فريق الصرخة المسرحي &bull; نظام إدارة الحجوزات والتذاكر &copy; {{ date('Y') }}
            </div>
            <div class="text-[11px] text-slate-600">
                بيئة تشغيل آمنة &bull; الإصدار الداخلي للإدارة
            </div>
        </div>
    </footer>

    {{-- Mobile Drawer Script --}}
    <script>
    (function () {
        var toggle = document.getElementById('adm-mobile-toggle');
        var drawer = document.getElementById('adm-mobile-drawer');
        if (!toggle || !drawer) return;

        var menuIcon = toggle.querySelector('.adm-icon-menu');
        var closeIcon = toggle.querySelector('.adm-icon-close');

        function setOpen(open) {
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            drawer.classList.toggle('hidden', !open);
            if (menuIcon) menuIcon.classList.toggle('hidden', open);
            if (closeIcon) closeIcon.classList.toggle('hidden', !open);
        }

        toggle.addEventListener('click', function () {
            var isOpen = toggle.getAttribute('aria-expanded') === 'true';
            setOpen(!isOpen);
        });

        // Close on ESC
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
                setOpen(false);
                toggle.focus();
            }
        });

        // Close when clicking outside drawer
        document.addEventListener('click', function (e) {
            if (toggle.getAttribute('aria-expanded') === 'true') {
                if (!drawer.contains(e.target) && !toggle.contains(e.target)) {
                    setOpen(false);
                }
            }
        });
    })();
    </script>
    @stack('scripts')
</body>
</html>
