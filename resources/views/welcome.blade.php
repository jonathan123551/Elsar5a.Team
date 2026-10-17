<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="description" content="فريق الصرخة المسرحي — نجول نصرخ فيزداد العقل وعيا. تعرف على العرض المسرحي الحالي واحجز تذكرتك الآن.">
    <title>فريق الصرخة المسرحي | العرض الحالي وحجز التذاكر</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@500;600;700;800;900&family=Amiri:ital,wght@0,400;0,700;1,400&family=Readex+Pro:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/elsar5a-stage.css') }}?v={{ file_exists(public_path('css/elsar5a-stage.css')) ? filemtime(public_path('css/elsar5a-stage.css')) : '1.0' }}">
</head>
<body class="theatre-body">

    <!-- SVG Dynamic Parabolic Curtain Tie-Back Clip Paths (Approved Intro Physics) -->
    <svg width="0" height="0" class="stage-svg-defs" aria-hidden="true">
        <defs>
            <clipPath id="curtainLeftClip" clipPathUnits="objectBoundingBox">
                <path id="curtainLeftPath" d="M 0,0 L 0.505,0 C 0.505,0.32 0.505,0.58 0.505,0.74 C 0.505,0.86 0.505,0.94 0.505,1 L 0,1 Z"></path>
            </clipPath>
            <clipPath id="curtainRightClip" clipPathUnits="objectBoundingBox">
                <path id="curtainRightPath" d="M 1,0 L 0.495,0 C 0.495,0.32 0.495,0.58 0.495,0.74 C 0.495,0.86 0.495,0.94 0.495,1 L 1,1 Z"></path>
            </clipPath>
        </defs>
    </svg>

    <!-- =======================================================================
         PERSISTENT PROSCENIUM HEADER & NAVIGATION DRAWER
         ======================================================================= -->
    <header class="proscenium-header" id="proscenium-header">
        <a href="{{ route('home') }}" class="proscenium-brand" aria-label="فريق الصرخة المسرحي">
            <span class="proscenium-brand-emblem" aria-hidden="true">🎭</span>
            <span class="proscenium-brand-title">فريق الصرخة المسرحي</span>
        </a>

        <div class="proscenium-actions">
            @if($featuredShow)
                <a href="{{ route('shows.book', $featuredShow) }}" id="fast-track-book-btn" class="proscenium-book-pill">
                    <span>احجز الآن</span>
                    <span aria-hidden="true">🎟</span>
                </a>
            @else
                <a href="{{ route('shows.index') }}" id="fast-track-book-btn" class="proscenium-book-pill">
                    <span>العروض</span>
                    <span aria-hidden="true">🎟</span>
                </a>
            @endif

            <button type="button" id="menu-drawer-btn" class="proscenium-menu-btn" aria-label="فتح القائمة" aria-expanded="false" aria-controls="theatre-menu-drawer">
                <span class="menu-bar"></span>
                <span class="menu-bar"></span>
                <span class="menu-bar menu-bar-short"></span>
            </button>
        </div>
    </header>

    <!-- Slide-Over Theatre Navigation Drawer -->
    <div id="theatre-menu-drawer" class="theatre-drawer" aria-hidden="true">
        <div class="theatre-drawer-backdrop" id="drawer-backdrop"></div>
        <div class="theatre-drawer-panel" role="dialog" aria-label="قائمة المسرح">
            <div class="theatre-drawer-header">
                <span class="theatre-drawer-title">قائمة المسرح</span>
                <button type="button" id="close-menu-drawer-btn" class="theatre-drawer-close" aria-label="إغلاق القائمة">✕</button>
            </div>

            <nav class="theatre-drawer-nav">
                <a href="{{ route('home') }}" class="drawer-nav-item is-active">الرئيسية — خشبة المسرح</a>
                @if($featuredShow)
                    <a href="{{ route('shows.book', $featuredShow) }}" class="drawer-nav-item is-highlight">
                        حجز عرض « {{ $featuredShow->title }} » 🎟
                    </a>
                @endif
                <a href="{{ route('shows.index') }}" class="drawer-nav-item">العروض المتاحة</a>
                <a href="{{ route('archive') }}" class="drawer-nav-item">أرشيف العروض</a>
                <a href="{{ route('about') }}" class="drawer-nav-item">من نحن</a>
                <a href="{{ route('join') }}" class="drawer-nav-item">انضم للفريق</a>
            </nav>

            <div class="theatre-drawer-footer">
                ❤ نجول نصرخ فيزداد العقل وعيا ❤
            </div>
        </div>
    </div>

    <!-- =======================================================================
         TWO-ACT CAMERA JOURNEY (235VH):
         ACT I (0% -> 42%): APPROVED CURTAIN + LOGO + MOTTO INTRO
         ACT II (34% -> 100%): DIRECT PRODUCTION REVEAL + INFORMATION + BOOK
         ======================================================================= -->
    <main id="stage-scroll-track" class="stage-scroll-track">
        <div id="sticky-stage-viewport" class="sticky-stage-viewport">

            <!-- LAYER 1: OPEN LIT WOODEN STAGE & VOLUMETRIC SPOTLIGHT -->
            <div id="layer-open-stage" class="stage-layer layer-open-stage">
                <div class="stage-proscenium-arch" aria-hidden="true"></div>
                <div class="spotlight-cone" aria-hidden="true"></div>
                <div class="stage-wood-floor" aria-hidden="true"></div>
            </div>

            <!-- LAYER 2 (ACT I): APPROVED LOGO & IDENTITY INTRO -->
            <section id="stage-logo-scene" class="stage-layer layer-logo-scene" aria-label="مقدمة فريق الصرخة المسرحي">
                <div class="logo-spotlight-halo" aria-hidden="true"></div>
                <div class="stage-logo-lockup">
                    <div class="stage-emblem-ring" id="intro-logo">
                        <span class="stage-emblem-icon" aria-hidden="true">🎭</span>
                    </div>
                    <p class="stage-kicker">خشبة المسرح ترحب بكم</p>
                    <h1 class="stage-main-title" id="intro-title">فريق الصرخة المسرحي</h1>
                    <div class="stage-gold-divider" aria-hidden="true"></div>
                    <p class="stage-identity-brief" id="intro-words">
                        مسرح حي يحمل الرسالة من قلب الخشبة إلى وجدان المشاهد
                    </p>
                    <p class="stage-slogan-text" id="intro-motto">❤ نجول نصرخ فيزداد العقل وعيا ❤</p>
                </div>
            </section>

            <!-- LAYER 3 (ACT II): CURRENT PRODUCTION PLAYBILL REVEAL -->
            <section id="stage-show-scene" class="stage-layer layer-show-scene" aria-label="العرض المسرحي الحالي">
                @if($featuredShow)
                    @php
                        $activePrice = $featuredShowtime?->price ?? $featuredShow->EffectivePrice;
                        $scarcityLevel = $scarcityState['level'] ?? 'normal';
                        $displayDate = $featuredShowtime?->starts_at && $featuredShowtime->starts_at->isFuture()
                            ? $featuredShowtime->starts_at->locale('ar')->translatedFormat('l j F Y')
                            : ($upcomingShowtimes->isEmpty() ? 'يعلن قريباً' : 'مواعيد متعددة');
                        $displayTime = $featuredShowtime?->starts_at && $featuredShowtime->starts_at->isFuture()
                            ? $featuredShowtime->starts_at->locale('ar')->translatedFormat('g:i A')
                            : ($upcomingShowtimes->isEmpty() ? 'راجع تفاصيل العرض' : 'راجع جدول الحفلات');
                    @endphp

                    <article class="playbill-showcase" id="emerging-boxoffice-card">
                        <!-- Visual Hero: Production Poster Monolith -->
                        <div class="playbill-poster-column" id="emerging-poster-card">
                            <div class="stage-poster-monolith">
                                @if($featuredPosterUrl)
                                    <img
                                        src="{{ $featuredPosterUrl }}"
                                        alt="بوستر عرض {{ $featuredShow->title }}"
                                        class="poster-img"
                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                    >
                                    <div class="poster-fallback" style="display: none;">
                                        <span class="poster-fallback-kicker">فريق الصرخة المسرحي يقدم</span>
                                        <span class="poster-fallback-title">{{ $featuredShow->title }}</span>
                                        <span class="poster-fallback-sub">عرض مسرحي حي على الخشبة</span>
                                    </div>
                                @else
                                    <div class="poster-fallback">
                                        <span class="poster-fallback-kicker">فريق الصرخة المسرحي يقدم</span>
                                        <span class="poster-fallback-title">{{ $featuredShow->title }}</span>
                                        <span class="poster-fallback-sub">عرض مسرحي حي على الخشبة</span>
                                    </div>
                                @endif

                                <div class="poster-spotlight-sheen" aria-hidden="true"></div>
                                <div class="poster-caption-gradient">
                                    <span class="poster-caption-kicker">العرض الحالي على الخشبة</span>
                                </div>
                            </div>
                        </div>

                        <!-- Secondary Hierarchy: Clear Show Information & Strong Booking CTA -->
                        <div class="playbill-info-column">
                            <div class="playbill-top-meta">
                                <span class="playbill-kicker">فريق الصرخة المسرحي يقدم</span>
                                <span class="scarcity-badge scarcity-{{ $scarcityLevel }} scarcity-badge--{{ $scarcityLevel === 'normal' ? 'healthy' : ($scarcityLevel === 'medium' ? 'low' : ($scarcityLevel === 'high' ? 'critical' : 'soldout')) }}" id="stage-scarcity-badge">
                                    ● {{ $scarcityState['label'] }}
                                </span>
                            </div>

                            <h2 class="playbill-title">{{ $featuredShow->title }}</h2>

                            @if(filled($featuredShow->description))
                                <p class="playbill-description">
                                    {{ \Illuminate\Support\Str::limit($featuredShow->description, 155) }}
                                </p>
                            @endif

                            <!-- Ticket-Stub Metadata Grid (Date / Showtime / Price) -->
                            <div class="playbill-ticket-grid" role="list" aria-label="معلومات العرض">
                                <div class="ticket-meta-cell" role="listitem">
                                    <span class="ticket-meta-label">📅 التاريخ</span>
                                    <strong class="ticket-meta-value">{{ $displayDate }}</strong>
                                </div>

                                <div class="ticket-meta-cell" role="listitem">
                                    <span class="ticket-meta-label">🕖 الموعد</span>
                                    <strong class="ticket-meta-value">{{ $displayTime }}</strong>
                                </div>

                                <div class="ticket-meta-cell" role="listitem">
                                    <span class="ticket-meta-label">🎟 سعر التذكرة</span>
                                    <strong class="ticket-meta-value">
                                        @if(!is_null($activePrice))
                                            {{ number_format((float) $activePrice, 0) }} ج.م
                                        @else
                                            يحدد عند الحجز
                                        @endif
                                    </strong>
                                </div>
                            </div>

                            @if($upcomingShowtimes->count() > 1)
                                <div class="playbill-showtimes-strip" aria-label="جميع الحفلات المتاحة">
                                    <span class="showtimes-strip-label">الحفلات المتاحة:</span>
                                    <div class="showtimes-pills-row">
                                        @foreach($upcomingShowtimes->take(3) as $st)
                                            <span class="showtime-mini-pill">
                                                {{ $st->starts_at->locale('ar')->translatedFormat('j M — g:i A') }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Primary Booking CTA -->
                            <div class="playbill-cta-row">
                                @if($isBookableNow)
                                    <a href="{{ route('shows.book', $featuredShow) }}" id="primary-stage-book-cta" class="stage-primary-cta">
                                        <span>احجز تذكرتك الآن</span>
                                        <span aria-hidden="true">🎟</span>
                                    </a>
                                @elseif($scarcityLevel === 'sold_out')
                                    <a href="{{ route('shows.book', $featuredShow) }}" id="primary-stage-book-cta" class="stage-primary-cta is-sold-out">
                                        <span>اكتمل العدد — عرض تفاصيل المسرحية</span>
                                    </a>
                                @else
                                    <a href="{{ route('shows.book', $featuredShow) }}" id="primary-stage-book-cta" class="stage-primary-cta">
                                        <span>تفاصيل العرض والحجز</span>
                                        <span aria-hidden="true">🎟</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </article>
                @else
                    <!-- Honest Fallback Only When Zero Active Plays Exist in DB -->
                    <article class="playbill-showcase playbill-empty-state" id="emerging-boxoffice-card">
                        <div class="playbill-info-column text-center">
                            <span class="scarcity-badge scarcity-upcoming">● الموسم المسرحي الجديد</span>
                            <h2 class="playbill-title">ترقبوا العرض المسرحي القادم قريباً</h2>
                            <p class="playbill-description">
                                نجهز حالياً لرفع الستار عن عملنا المسرحي الجديد. تصفح صفحة العروض أو الأرشيف لمشاهدة أعمال الفريق.
                            </p>
                            <div class="playbill-cta-row">
                                <a href="{{ route('shows.index') }}" id="primary-stage-book-cta" class="stage-primary-cta">
                                    <span>استعرض صفحة العروض</span>
                                    <span aria-hidden="true">🎭</span>
                                </a>
                            </div>
                        </div>
                    </article>
                @endif
            </section>

            <!-- LAYER 4: CLOSED VELVET CURTAINS WITH PARABOLIC TIE-BACK GATHERING -->
            <div id="layer-closed-curtains" class="stage-layer layer-closed-curtains">
                <div id="curtain-left" class="curtain-wing curtain-wing-left">
                    <div class="curtain-velvet-surface"></div>
                    <div class="curtain-gold-trim"></div>
                </div>

                <div id="curtain-right" class="curtain-wing curtain-wing-right">
                    <div class="curtain-velvet-surface"></div>
                    <div class="curtain-gold-trim"></div>
                </div>

                <!-- Golden Center Seam Light Flare -->
                <div id="curtain-seam" class="seam-flare-beam" aria-hidden="true"></div>

                <!-- Interactive Scroll / Tap Prompt -->
                <button type="button" id="curtain-scroll-prompt" class="curtain-scroll-prompt" aria-label="مرر للأسفل لفتح الستار ومشاهدة العرض الحالي">
                    <span class="scroll-mouse-shell" aria-hidden="true">
                        <span class="scroll-wheel-dot"></span>
                    </span>
                    <span class="scroll-prompt-text">مرر للأسفل أو اضغط لمشاهدة العرض الحالي</span>
                </button>
            </div>

        </div>
    </main>

    <!-- =======================================================================
         COMPACT NAVIGATION BAR BELOW THE STAGE (NO MANIFESTO/ARCHIVE BLOAT)
         ======================================================================= -->
    <footer class="theatre-compact-footer" aria-label="روابط المسرح">
        <div class="compact-footer-inner">
            <div class="compact-footer-brand">
                <span class="compact-footer-title">فريق الصرخة المسرحي</span>
                <span class="compact-footer-motto">❤ نجول نصرخ فيزداد العقل وعيا ❤</span>
            </div>

            <nav class="compact-footer-links" aria-label="التنقل السريع">
                <a href="{{ route('shows.index') }}">العروض</a>
                <a href="{{ route('archive') }}">الأرشيف</a>
                <a href="{{ route('about') }}">من نحن</a>
                <a href="{{ route('join') }}">انضم للفريق</a>
            </nav>
        </div>
    </footer>

    <script src="{{ asset('js/elsar5a-stage.js') }}?v={{ file_exists(public_path('js/elsar5a-stage.js')) ? filemtime(public_path('js/elsar5a-stage.js')) : '1.0' }}" defer></script>
</body>
</html>
