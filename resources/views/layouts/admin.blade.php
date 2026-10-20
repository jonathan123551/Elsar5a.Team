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
            touch-action: manipulation;
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

        /* =========================================================
           RULE #28: UNIFIED ADMIN BUTTON COMPONENT SYSTEM (.admin-btn)
           ========================================================= */
        .admin-btn {
            min-height: 44px;
            padding: 0.5rem 1.125rem;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 0.8125rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.2s ease-in-out;
            touch-action: manipulation;
            cursor: pointer;
            white-space: nowrap;
            text-decoration: none;
            box-sizing: border-box;
            border: 1px solid transparent;
            font-family: inherit;
        }
        .admin-btn:active:not([disabled]) {
            transform: scale(0.98);
        }
        .admin-btn:focus-visible {
            outline: none;
            box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.5);
        }
        .admin-btn--primary {
            background: linear-gradient(180deg, #fde68a 0%, #f59e0b 60%, #d97706 100%);
            color: #0b0f19 !important;
            border-color: rgba(245, 158, 11, 0.4);
            box-shadow: 0 4px 14px -2px rgba(245, 158, 11, 0.45);
        }
        .admin-btn--primary:hover {
            box-shadow: 0 6px 20px -2px rgba(245, 158, 11, 0.6);
            filter: brightness(1.05);
        }
        .admin-btn--success {
            background: rgba(16, 185, 129, 0.16);
            color: #6ee7b7 !important;
            border-color: rgba(16, 185, 129, 0.4);
        }
        .admin-btn--success:hover {
            background: rgba(16, 185, 129, 0.25);
            color: #a7f3d0 !important;
        }
        .admin-btn--secondary {
            background: rgba(255, 255, 255, 0.06);
            color: #f1f5f9 !important;
            border-color: rgba(255, 255, 255, 0.12);
        }
        .admin-btn--secondary:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff !important;
        }
        .admin-btn--warning {
            background: rgba(245, 158, 11, 0.16);
            color: #fde68a !important;
            border-color: rgba(245, 158, 11, 0.4);
        }
        .admin-btn--warning:hover {
            background: rgba(245, 158, 11, 0.25);
        }
        .admin-btn--danger {
            background: rgba(239, 68, 68, 0.16);
            color: #fca5a5 !important;
            border-color: rgba(239, 68, 68, 0.4);
        }
        .admin-btn--danger:hover {
            background: rgba(239, 68, 68, 0.26);
            color: #fecaca !important;
        }
        .admin-btn--indigo {
            background: linear-gradient(180deg, rgba(99, 102, 241, 0.25) 0%, rgba(79, 70, 229, 0.4) 100%);
            color: #c7d2fe !important;
            border-color: rgba(99, 102, 241, 0.45);
            box-shadow: 0 4px 14px -2px rgba(79, 70, 229, 0.35);
        }
        .admin-btn--indigo:hover {
            background: linear-gradient(180deg, rgba(99, 102, 241, 0.35) 0%, rgba(79, 70, 229, 0.55) 100%);
            color: #ffffff !important;
            border-color: rgba(129, 140, 248, 0.6);
            box-shadow: 0 6px 20px -2px rgba(79, 70, 229, 0.55);
        }
        .admin-btn--ghost {
            background: transparent;
            color: #94a3b8 !important;
            border-color: transparent;
        }
        .admin-btn--ghost:hover {
            color: #ffffff !important;
            background: rgba(255, 255, 255, 0.05);
        }
        .admin-btn--sm {
            min-height: 38px;
            padding: 0.375rem 0.875rem;
            font-size: 0.75rem;
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
            <nav class="hidden lg:flex items-center gap-1.5 overflow-x-auto py-1" aria-label="أقسام الإدارة الرئيسية">
                <a href="{{ route('admin.dashboard') }}"
                   class="adm-nav-link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zM14 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/>
                    </svg>
                    <span>الرئيسية</span>
                </a>

                <a href="{{ route('admin.bookings.index') }}"
                   class="adm-nav-link {{ request()->routeIs('admin.bookings.*') || request()->routeIs('admin.resend.ticket') ? 'is-active' : '' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                    </svg>
                    <span>الحجوزات</span>
                    @if($pendingBookingsCount > 0)
                        <span class="px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-sky-500/20 text-sky-300 border border-sky-400/30 tabular-nums">
                            {{ $pendingBookingsCount }}
                        </span>
                    @endif
                </a>

                <a href="{{ route('admin.shows.index') }}"
                   class="adm-nav-link {{ request()->routeIs('admin.shows.*') || request()->routeIs('admin.shows.times.*') ? 'is-active' : '' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>
                    </svg>
                    <span>العروض</span>
                </a>

                <a href="{{ route('admin.scanner') }}"
                   class="adm-nav-link {{ request()->routeIs('admin.scanner') ? 'is-active' : '' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                    </svg>
                    <span>فحص التذاكر</span>
                </a>

                <a href="{{ route('admin.archive.index') }}"
                   class="adm-nav-link {{ request()->routeIs('admin.archive.*') ? 'is-active' : '' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <span>الأرشيف</span>
                </a>

                <a href="{{ route('admin.settings.payments.edit') }}"
                   class="adm-nav-link {{ request()->routeIs('admin.settings.*') ? 'is-active' : '' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>الدفع</span>
                </a>
            </nav>

            {{-- Left: Utilities & Mobile Hamburger --}}
            <div class="flex items-center gap-2">
                {{-- Public Site Link --}}
                <a href="{{ url('/') }}"
                   target="_blank"
                   rel="noopener"
                   class="admin-btn admin-btn--secondary admin-btn--sm hidden sm:inline-flex"
                   title="فتح الموقع العام للجمهور">
                    <svg class="w-4 h-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                    </svg>
                    <span>عرض الموقع</span>
                    <svg class="w-3.5 h-3.5 rtl:rotate-180 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                </a>

                {{-- Logout Button --}}
                <form action="{{ route('logout') }}" method="POST" class="hidden sm:inline-block">
                    @csrf
                    <button type="submit"
                            class="admin-btn admin-btn--danger admin-btn--sm">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
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
                   class="flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-medium {{ request()->routeIs('admin.dashboard') ? 'bg-amber-500/15 text-amber-300 font-bold' : 'text-slate-300 hover:bg-white/5' }}">
                    <span class="flex items-center gap-2.5">
                        <svg class="w-5 h-5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zM14 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/>
                        </svg>
                        <span>لوحة التحكم الرئيسية</span>
                    </span>
                    <svg class="w-3.5 h-3.5 rtl:rotate-180 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>

                <a href="{{ route('admin.bookings.index') }}"
                   class="flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-medium {{ request()->routeIs('admin.bookings.*') || request()->routeIs('admin.resend.ticket') ? 'bg-amber-500/15 text-amber-300 font-bold' : 'text-slate-300 hover:bg-white/5' }}">
                    <span class="flex items-center gap-2.5">
                        <svg class="w-5 h-5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                        </svg>
                        <span>إدارة الحجوزات والتحويلات</span>
                    </span>
                    @if($pendingBookingsCount > 0)
                        <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-sky-500/20 text-sky-300 border border-sky-400/30">
                            {{ $pendingBookingsCount }} معلّق
                        </span>
                    @else
                        <svg class="w-3.5 h-3.5 rtl:rotate-180 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    @endif
                </a>

                <a href="{{ route('admin.shows.index') }}"
                   class="flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-medium {{ request()->routeIs('admin.shows.*') || request()->routeIs('admin.shows.times.*') ? 'bg-amber-500/15 text-amber-300 font-bold' : 'text-slate-300 hover:bg-white/5' }}">
                    <span class="flex items-center gap-2.5">
                        <svg class="w-5 h-5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>
                        </svg>
                        <span>العروض المسرحية والمواعيد</span>
                    </span>
                    <svg class="w-3.5 h-3.5 rtl:rotate-180 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>

                <a href="{{ route('admin.scanner') }}"
                   class="flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-medium {{ request()->routeIs('admin.scanner') ? 'bg-amber-500/15 text-amber-300 font-bold' : 'text-slate-300 hover:bg-white/5' }}">
                    <span class="flex items-center gap-2.5">
                        <svg class="w-5 h-5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                        </svg>
                        <span>ماسح التذاكر على الباب (Scan)</span>
                    </span>
                    <svg class="w-3.5 h-3.5 rtl:rotate-180 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>

                <a href="{{ route('admin.archive.index') }}"
                   class="flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-medium {{ request()->routeIs('admin.archive.*') ? 'bg-amber-500/15 text-amber-300 font-bold' : 'text-slate-300 hover:bg-white/5' }}">
                    <span class="flex items-center gap-2.5">
                        <svg class="w-5 h-5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                        <span>أرشيف العروض السابقة</span>
                    </span>
                    <svg class="w-3.5 h-3.5 rtl:rotate-180 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>

                <a href="{{ route('admin.about.edit') }}"
                   class="flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-medium {{ request()->routeIs('admin.about.*') ? 'bg-amber-500/15 text-amber-300 font-bold' : 'text-slate-300 hover:bg-white/5' }}">
                    <span class="flex items-center gap-2.5">
                        <svg class="w-5 h-5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>تعديل عن الفريق</span>
                    </span>
                    <svg class="w-3.5 h-3.5 rtl:rotate-180 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>

                <a href="{{ route('admin.settings.payments.edit') }}"
                   class="flex items-center justify-between px-3.5 py-3 rounded-xl text-sm font-medium {{ request()->routeIs('admin.settings.*') ? 'bg-amber-500/15 text-amber-300 font-bold' : 'text-slate-300 hover:bg-white/5' }}">
                    <span class="flex items-center gap-2.5">
                        <svg class="w-5 h-5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>إعدادات الدفع والمحافظ</span>
                    </span>
                    <svg class="w-3.5 h-3.5 rtl:rotate-180 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>

                <div class="pt-3 mt-3 border-t border-white/10 grid grid-cols-2 gap-2">
                    <a href="{{ url('/') }}"
                       target="_blank"
                       rel="noopener"
                       class="admin-btn admin-btn--secondary admin-btn--sm w-full">
                        <svg class="w-4 h-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                        </svg>
                        <span>عرض الموقع</span>
                    </a>

                    <form action="{{ route('logout') }}" method="POST" class="w-full">
                        @csrf
                        <button type="submit"
                                class="admin-btn admin-btn--danger admin-btn--sm w-full">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
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
                <svg class="w-4 h-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                <span class="flex-1">{{ session('status') }}</span>
            </div>
        @endif

        @if(session('success'))
            <div role="status"
                 class="flex items-center gap-2.5 p-3 sm:p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-200 text-xs sm:text-sm font-medium mb-3 shadow-lg shadow-emerald-950/20">
                <svg class="w-4 h-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                <span class="flex-1">{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div role="alert"
                 class="flex items-center gap-2.5 p-3 sm:p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-200 text-xs sm:text-sm font-medium mb-3 shadow-lg shadow-rose-950/20">
                <svg class="w-4 h-4 shrink-0 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                <span class="flex-1">{{ session('error') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div role="alert"
                 class="p-3 sm:p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-200 text-xs sm:text-sm mb-3 shadow-lg shadow-rose-950/20">
                <div class="font-bold mb-1.5 flex items-center gap-2">
                    <svg class="w-4 h-4 shrink-0 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
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
