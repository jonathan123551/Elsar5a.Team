@extends('layouts.app')

@section('title', 'فريق الصرخة المسرحي | المسرح والجمهور والعرض الحالي')
@section('body_class', 'home-stage-mode')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/elsar5a-stage.css') }}?v={{ file_exists(public_path('css/elsar5a-stage.css')) ? filemtime(public_path('css/elsar5a-stage.css')) : '2.1' }}">
@endpush

@section('main_class', 'p-0 max-w-none m-0')

@section('content')
@php
    $hasShow = isset($featuredShow) && $featuredShow !== null;
    $hasShowTime = isset($featuredShowTime) && $featuredShowTime !== null;
    $isUpcomingShowTime = $hasShowTime && $featuredShowTime->starts_at && $featuredShowTime->starts_at->gte(now());
    $isSoldOut = $hasShowTime && (
        (method_exists($featuredShowTime, 'isSoldOut') ? $featuredShowTime->isSoldOut() : false) ||
        $featuredShowTime->status === 'sold_out' ||
        $featuredShowTime->is_sold_out ||
        ($featuredRemainingSeats !== null && $featuredRemainingSeats <= 0)
    );
    $isBookableNow = !empty($isFeaturedBookable) && !$isSoldOut;
    $extraShowTimesCount = max(0, (int) ($upcomingShowTimesCount ?? 0) - 1);

    // Exact Progressive Scarcity Rules:
    // - Healthy: >25 seats AND >25% capacity -> "متاح للحجز الآن" (playbill-badge--available)
    // - Low: 11–25 seats OR <= 25% capacity -> "متبقي عدد محدود من التذاكر" (playbill-badge--scarcity)
    // - Very low: 1–10 seats -> show honest exact count (e.g. "متبقي X تذاكر فقط", "متبقي تذكرة واحدة فقط", "متبقي تذكرتان فقط") (playbill-badge--critical)
    // - Sold out: 0 -> "اكتملت التذاكر" (playbill-badge--soldout)
    $capacity = (int) ($featuredShowTime?->total_tickets ?? $featuredShowTime?->capacity ?? 0);
    $remaining = $featuredRemainingSeats !== null ? (int) $featuredRemainingSeats : null;

    if ($isSoldOut || ($remaining !== null && $remaining <= 0)) {
        $badgeClass = 'playbill-badge--soldout';
        $badgeText = 'اكتملت التذاكر';
    } elseif ($isBookableNow && $remaining !== null && $remaining >= 1 && $remaining <= 10) {
        $badgeClass = 'playbill-badge--critical';
        $badgeText = $remaining === 1
            ? 'متبقي تذكرة واحدة فقط'
            : ($remaining === 2 ? 'متبقي تذكرتان فقط' : "متبقي {$remaining} تذاكر فقط");
    } elseif ($isBookableNow && $remaining !== null && (($remaining >= 11 && $remaining <= 25) || ($capacity > 0 && ($remaining / $capacity) <= 0.25))) {
        $badgeClass = 'playbill-badge--scarcity';
        $badgeText = 'متبقي عدد محدود من التذاكر';
    } elseif ($isBookableNow) {
        $badgeClass = 'playbill-badge--available';
        $badgeText = 'متاح للحجز الآن';
    } else {
        $badgeClass = 'playbill-badge--active';
        $badgeText = 'عرض حالي على المسرح';
    }
@endphp

<div id="theatre-journey" dir="rtl">
    {{-- SCROLL TRACK: Direct 2-Act Journey (Act I: Approved Intro -> Act II: Current Production Reveal) --}}
    <section id="stage-scroll-track" aria-label="رحلة المسرح والعرض الحالي">
        <div id="stage-sticky-viewport">
            {{-- Layer 1: Deep Atmospheric Stage & Proscenium Lighting --}}
            <div id="stage-world" aria-hidden="true">
                <div class="stage-proscenium-arch"></div>
                <div id="stage-spotlight"></div>
                <div class="stage-SideGlow stage-SideGlow--right"></div>
                <div class="stage-SideGlow stage-SideGlow--left"></div>
                <div class="stage-dust-layer"></div>
                <div id="stage-floor"></div>
            </div>

            {{-- Layer 2: Active Stage Content (Intro -> Show Reveal) --}}
            <div id="stage-content-layer">
                {{-- ACT I: APPROVED OPENING INTRO (FROZEN) --}}
                <div id="stage-intro-scene" class="stage-scene is-active">
                    <div class="intro-emblem-shell">
                        <div class="intro-emblem-halo"></div>
                        <img src="{{ asset('images/logo.png') }}"
                             alt="شعار فريق الصرخة المسرحي"
                             class="intro-logo-img"
                             width="116"
                             height="116"
                             onerror="this.onerror=null;this.src='{{ asset('images/sarkha-logo.png') }}';"
                        >
                    </div>
                    <p class="intro-kicker">خشبة مسرح الصرخة ترحب بكم</p>
                    <h1 class="intro-main-title">فريق الصرخة المسرحي</h1>
                    <p class="intro-motto">❤ نجول نصرخ فيزداد العقل وعيا ❤</p>

                    <div id="scroll-prompt" aria-hidden="true">
                        <span class="scroll-prompt-label">مرّر لرفع الستار واكتشاف العرض الحالي</span>
                        <span class="scroll-prompt-line"></span>
                    </div>
                </div>

                {{-- ACT II: CURRENT PRODUCTION PLAYBILL REVEAL --}}
                <div id="stage-show-scene" class="stage-scene">
                    @if($hasShow)
                        <article id="playbill-showcase" class="playbill-showcase" aria-labelledby="playbill-title">
                            {{-- Visual Hero: Production Poster Column --}}
                            <div class="playbill-poster-col">
                                <div class="playbill-poster-frame">
                                    @if(!empty($featuredPosterUrl))
                                        <img src="{{ $featuredPosterUrl }}"
                                             alt="بوستر عرض {{ $featuredShow->title }}"
                                             class="playbill-poster-img"
                                             onerror="this.onerror=null;this.src='{{ asset('images/logo.png') }}';this.classList.add('is-fallback-logo');"
                                        >
                                    @else
                                        <div class="playbill-poster-fallback">
                                            <img src="{{ asset('images/logo.png') }}" alt="{{ $featuredShow->title }}" onerror="this.onerror=null;this.src='{{ asset('images/sarkha-logo.png') }}';">
                                            <span>{{ $featuredShow->title }}</span>
                                        </div>
                                    @endif
                                    <div class="playbill-poster-vignette" aria-hidden="true"></div>

                                    {{-- Live Availability / Status Badge --}}
                                    <div class="playbill-status-floating">
                                        <span class="playbill-badge {{ $badgeClass }}">
                                            <span class="playbill-badge-dot"></span>
                                            {{ $badgeText }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {{-- Secondary Hierarchy: Show Details + Strong Booking CTA --}}
                            <div class="playbill-info-col">
                                <div class="playbill-header-group">
                                    <span class="playbill-kicker">العرض المسرحي الحالي</span>
                                    <h2 id="playbill-title" class="playbill-title">{{ $featuredShow->title }}</h2>
                                </div>

                                @if(filled($featuredShow->description))
                                    <p class="playbill-synopsis">
                                        {{ \Illuminate\Support\Str::limit($featuredShow->description, 170) }}
                                    </p>
                                @endif

                                {{-- Structured Showtime & Ticket Spec Grid --}}
                                <div class="playbill-specs-grid" role="list" aria-label="بيانات العرض المسرحي">
                                    @if($hasShowTime && $featuredShowTime->starts_at)
                                        <div class="playbill-spec-item" role="listitem">
                                            <span class="playbill-spec-label">تاريخ العرض</span>
                                            <strong class="playbill-spec-value">
                                                {{ $featuredShowTime->starts_at->translatedFormat('l j F Y') }}
                                            </strong>
                                        </div>
                                        <div class="playbill-spec-item" role="listitem">
                                            <span class="playbill-spec-label">موعد رفع الستار</span>
                                            <strong class="playbill-spec-value">
                                                {{ $featuredShowTime->starts_at->translatedFormat('g:i A') }}
                                                @if($extraShowTimesCount > 0)
                                                    <small class="playbill-extra-dates">+{{ $extraShowTimesCount }} مواعيد أخرى</small>
                                                @endif
                                            </strong>
                                        </div>
                                    @else
                                        <div class="playbill-spec-item" role="listitem">
                                            <span class="playbill-spec-label">حالة المواعيد</span>
                                            <strong class="playbill-spec-value">تُعلن المواعيد عبر صفحة الحجز</strong>
                                        </div>
                                    @endif

                                    @if($featuredPrice !== null)
                                        <div class="playbill-spec-item playbill-spec-item--price" role="listitem">
                                            <span class="playbill-spec-label">سعر التذكرة</span>
                                            <strong class="playbill-spec-value playbill-price-number">
                                                {{ number_format((float) $featuredPrice, 0) }} <small>ج.م</small>
                                            </strong>
                                        </div>
                                    @endif
                                </div>

                                {{-- Primary Action Row --}}
                                <div class="playbill-actions-row">
                                    @if($isSoldOut && $extraShowTimesCount === 0)
                                        <a href="{{ route('shows.book', $featuredShow) }}" class="btn-stage-primary is-soldout-cta">
                                            <span>تفاصيل العرض والمواعيد</span>
                                        </a>
                                    @else
                                        <a href="{{ route('shows.book', $featuredShow) }}" class="btn-stage-primary">
                                            <span>احجز تذكرتك الآن</span>
                                            <svg class="btn-stage-arrow" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M19 12H5M5 12L12 19M5 12L12 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </a>
                                    @endif

                                    <a href="{{ route('shows.index') }}" class="btn-stage-secondary">
                                        جميع العروض
                                    </a>
                                </div>
                            </div>
                        </article>
                    @else
                        {{-- Clean Fallback Only When Zero Active Plays Exist in Database --}}
                        <div id="playbill-showcase" class="playbill-empty-card">
                            <span class="playbill-kicker">خشبة مسرح الصرخة</span>
                            <h2 class="playbill-title">ترقبوا العرض المسرحي القادم قريباً</h2>
                            <p class="playbill-synopsis">
                                نجهز حالياً لرفع الستار عن عمل مسرحي جديد. تصفح العروض أو أرشيف المسرحية لمشاهدة أعمال الفريق السابقة.
                            </p>
                            <div class="playbill-actions-row is-centered">
                                <a href="{{ route('shows.index') }}" class="btn-stage-primary">
                                    <span>صفحة العروض</span>
                                </a>
                                <a href="{{ route('archive') }}" class="btn-stage-secondary">
                                    أرشيف الصرخة
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Layer 3: Foreground Proscenium Velvet Curtains (Approved SVG Tie-Back Physics) --}}
            <div id="curtain-layer" aria-hidden="true">
                <div id="curtain-valance"></div>
                <div id="curtain-right" class="curtain-panel">
                    <svg class="curtain-svg" viewBox="0 0 100 100" preserveAspectRatio="none">
                        <defs>
                            <linearGradient id="curtainGradR" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" stop-color="#3a0308"/>
                                <stop offset="18%" stop-color="#7c0a14"/>
                                <stop offset="36%" stop-color="#430409"/>
                                <stop offset="54%" stop-color="#8b0d18"/>
                                <stop offset="74%" stop-color="#3c0308"/>
                                <stop offset="90%" stop-color="#6e0912"/>
                                <stop offset="100%" stop-color="#280205"/>
                            </linearGradient>
                        </defs>
                        <path id="curtain-path-r" fill="url(#curtainGradR)" d="M 100,0 L 0,0 C 0,36 0,58 0,100 L 100,100 Z"></path>
                        <path id="curtain-trim-r" fill="none" stroke="#d4af37" stroke-width="0.9" stroke-opacity="0.7" d="M 0,0 C 0,36 0,58 0,100"></path>
                    </svg>
                </div>
                <div id="curtain-left" class="curtain-panel">
                    <svg class="curtain-svg" viewBox="0 0 100 100" preserveAspectRatio="none">
                        <defs>
                            <linearGradient id="curtainGradL" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" stop-color="#280205"/>
                                <stop offset="14%" stop-color="#6e0912"/>
                                <stop offset="32%" stop-color="#3c0308"/>
                                <stop offset="52%" stop-color="#8b0d18"/>
                                <stop offset="70%" stop-color="#430409"/>
                                <stop offset="86%" stop-color="#7c0a14"/>
                                <stop offset="100%" stop-color="#3a0308"/>
                            </linearGradient>
                        </defs>
                        <path id="curtain-path-l" fill="url(#curtainGradL)" d="M 0,0 L 100,0 C 100,36 100,58 100,100 L 0,100 Z"></path>
                        <path id="curtain-trim-l" fill="none" stroke="#d4af37" stroke-width="0.9" stroke-opacity="0.7" d="M 100,0 C 100,36 100,58 100,100"></path>
                    </svg>
                </div>
            </div>
        </div>
    </section>

    {{-- Minimal Post-Stage Navigation Bar (No Manifesto or Archive Bloat) --}}
    <footer id="stage-compact-footer" aria-label="روابط سريعة">
        <div class="stage-compact-footer-inner">
            <div class="stage-compact-brand">
                <img src="{{ asset('images/logo.png') }}" alt="" width="34" height="34" onerror="this.onerror=null;this.src='{{ asset('images/sarkha-logo.png') }}';">
                <span>فريق الصرخة المسرحي</span>
            </div>
            <nav class="stage-compact-links" aria-label="التنقل في الموقع">
                <a href="{{ route('shows.index') }}">العروض والحجز</a>
                <a href="{{ route('archive') }}">أرشيف المسرح</a>
                <a href="{{ route('about') }}">من نحن</a>
                <a href="{{ route('join') }}">انضم للفريق</a>
            </nav>
        </div>
    </footer>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/elsar5a-stage.js') }}?v={{ file_exists(public_path('js/elsar5a-stage.js')) ? filemtime(public_path('js/elsar5a-stage.js')) : '2.1' }}" defer></script>
@endpush
