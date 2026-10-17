@extends('layouts.app')

@section('title', 'فريق الصرخة المسرحي — العروض القادمة')
@section('meta_description', 'اكتشف عروض فريق الصرخة المسرحي واحجز تذكرتك من مسار الحجز الرسمي.')
@section('main_class', 'home-main')
@section('body_class', 'homepage')

@section('content')
@php
    $manifesto = [
        'كثيرًا ما فسدت عقولنا من ما حملته لها مدخلاتنا, ذلك هو ما نريد تغيره, فقط بالصراخ, الصراخ هنا و هناك.',
        'نصرخ ندعوا الجميع للمجيئ الينا و منحنا من وقتهم القليل, فنحن لا نريد سوى حواسكم.',
        'ثم نصرخ نبحث فى مدخلاتكم لنخرج ما هو فاسد و نزرع بدلا منه ثمرا صالحنا, لا نريد سو عقولكم.',
        'والان نصرخ بالتعاليم الصاحه لنغير ما فسد .',
        'و فقط ما نحتاجه من الجميع هو المجيئ الى مصدر الصراخ, فدائما يكون على المسرح.',
        'و قد كان سيدنا و معلمنا و الهنا وهو على الارض يجول يصنع خيرا و نحن على خطاه.',
        '❤ نجول نصرخ فيزداد العقل وعيا ❤',
    ];
    $openingMotto = 'نجول نصرخ فيزداد العقل وعياً';
    $resolvePoster = function (?string $path): ?string {
        if (!$path) return null;
        $trimmed = trim($path);
        if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://') || str_starts_with($trimmed, '//')) {
            return \App\Support\CloudinaryUrl::forDisplay($trimmed);
        }
        $normalized = ltrim($trimmed, '/');
        if (str_starts_with($normalized, 'images/') || str_starts_with($normalized, 'storage/')) {
            return asset($normalized);
        }
        return asset('storage/' . $normalized);
    };
    $featuredShow = $featuredShow ?? null;
    $featuredPoster = $resolvePoster($featuredShow?->poster_path ?? $featuredShow?->poster);
    $featuredBookableTimes = $featuredShow?->showTimes->filter(fn ($time) => !$time->is_sold_out && (int) $time->homepage_remaining_tickets > 0)->values() ?? collect();
    $featuredTime = $featuredBookableTimes->first();
    $featuredRemaining = $featuredTime ? (int) $featuredTime->homepage_remaining_tickets : 0;
    $featuredDate = $featuredTime?->date?->format('d/m/Y');
    $featuredClock = $featuredTime?->time ? \Carbon\Carbon::parse($featuredTime->time) : null;
    $featuredSoldOut = !$featuredTime || $featuredTime->is_sold_out || $featuredRemaining <= 0;
    $featuredCritical = !$featuredSoldOut && $featuredRemaining <= 10;
    $featuredLow = !$featuredSoldOut && !$featuredCritical && (
        $featuredRemaining <= 25 || ((int) $featuredTime->total_tickets > 0 && $featuredRemaining / (int) $featuredTime->total_tickets <= .25)
    );
@endphp

@if(request()->routeIs('home'))
<div class="cinema-home" dir="rtl">
    <div class="curtain-sequence" id="cinema-curtain" aria-hidden="true" data-state="closed">
        <div class="curtain-leaf curtain-leaf--left"></div>
        <div class="curtain-leaf curtain-leaf--right"></div>
        <div class="curtain-seam"></div>
    </div>
    <button class="cinema-skip" id="cinema-skip" type="button" aria-label="تخطّي الافتتاحية ومتابعة التصفح">
        تخطّي الافتتاحية <span aria-hidden="true">↓</span>
    </button>

    <section class="cinema-opening" id="opening" aria-labelledby="team-title">
        <div class="cinema-plate cinema-plate--opening" aria-hidden="true">
            <img src="{{ asset('images/cinematic/theatre/empty-stage.webp') }}" alt="" width="960" height="1440" fetchpriority="high" decoding="async">
        </div>
        <div class="opening-scrim" aria-hidden="true"></div>
        <div class="opening-copy">
            <h1 id="team-title" class="sr-only">فريق الصرخة المسرحي</h1>
            <div class="logo-reveal"><img src="{{ asset('images/sarkha-logo.png') }}" alt="شعار فريق الصرخة المسرحي" width="112" height="112" fetchpriority="high"></div>
            <div class="opening-identity" aria-label="من بيان الفريق">
                <p class="opening-line" data-reveal="manifesto-1">فنحن لا نريد سوى حواسكم.</p>
                <p class="opening-motto" data-reveal="manifesto-2">{{ $openingMotto }}</p>
            </div>
        </div>
    </section>

    <section class="camera-track" id="camera-journey" aria-label="رحلة إلى داخل المسرح">
        <div class="camera-viewport">
            <div class="camera-world" id="camera-world" aria-hidden="true">
                <img src="{{ asset('images/cinematic/theatre/empty-stage.webp') }}" alt="" width="960" height="1440" decoding="async">
                <div class="camera-grade"></div>
            </div>
            <div class="camera-plane camera-auditorium" id="camera-auditorium" aria-hidden="true"></div>
            <div class="camera-plane camera-aisle" id="camera-aisle" aria-hidden="true"></div>
            <div class="camera-plane camera-spotlight" id="camera-spotlight" aria-hidden="true"></div>
            <div class="camera-plane camera-proscenium" id="camera-proscenium" aria-hidden="true"></div>
            <div class="camera-space-layer" id="camera-space-layer" aria-hidden="true"></div>
            <div class="camera-identity" id="camera-identity" aria-hidden="true">
                <p class="identity-overline">فريق الصرخة المسرحي</p>
                <div class="identity-manifesto">
                    <p class="identity-motto">{{ $openingMotto }}</p>
                    <p class="identity-sub">فنحن لا نريد سوى حواسكم.</p>
                </div>
                <p class="identity-signoff">{{ $manifesto[6] }}</p>
            </div>

            @if($featuredShow && $featuredTime)
                <article class="stage-production" id="stage-production" aria-labelledby="featured-title">
                    <div class="stage-production-inner">
                        @if($featuredPoster)
                            <div class="production-poster-wrap">
                                <span class="production-light" aria-hidden="true"></span>
                                <div class="production-poster-frame">
                                    <img src="{{ $featuredPoster }}" alt="ملصق العرض {{ $featuredShow->title }}" width="640" height="900" loading="eager" decoding="async" fetchpriority="high">
                                </div>
                            </div>
                        @else
                            <div class="production-poster-missing" role="img" aria-label="لم ينشر الفريق ملصق هذا العرض بعد">
                                <span class="poster-missing-icon" aria-hidden="true">🎭</span>
                                <span>ملصق العرض لم يُنشر بعد</span>
                            </div>
                        @endif

                        <div class="production-information">
                            <p class="production-kicker">العرض القادم على خشبة المسرح</p>
                            <h2 id="featured-title" class="production-title">{{ $featuredShow->title }}</h2>
                            @if($featuredShow->description)
                                <p class="production-description">{{ $featuredShow->description }}</p>
                            @endif

                            {{-- Showtimes Direct Booking Interface --}}
                            @if($featuredBookableTimes->count() > 1)
                                <div class="production-showtimes-panel" aria-label="مواعيد العرض المتاحة">
                                    <div class="showtimes-status-bar">
                                        <span class="showtimes-badge-pill" data-open-modal="showtime-modal-{{ $featuredShow->id }}" role="button" tabindex="0" title="اضغط لعرض المواعيد">
                                            <span class="kicker-dot" aria-hidden="true"></span>
                                            {{ $featuredBookableTimes->count() }} مواعيد متاحة
                                        </span>
                                    </div>
                                    <div class="showtimes-grid-tactile">
                                        @foreach($featuredBookableTimes as $slotTime)
                                            @php
                                                $slotClock = $slotTime->time ? \Carbon\Carbon::parse($slotTime->time) : null;
                                                $slotRemaining = (int) $slotTime->homepage_remaining_tickets;
                                                $slotCritical = $slotRemaining > 0 && $slotRemaining <= 10;
                                                $slotLow = !$slotCritical && ($slotRemaining <= 25 || ((int) $slotTime->total_tickets > 0 && $slotRemaining / (int) $slotTime->total_tickets <= .25));
                                            @endphp
                                            <div class="showtime-tile">
                                                <div class="showtime-tile-head">
                                                    <div class="showtime-tile-timing">
                                                        @if($slotClock)
                                                            <span class="tile-clock">
                                                                <span class="tile-icon" aria-hidden="true">⏰</span>
                                                                <span>{{ $slotClock->format('g:i') }} {{ $slotClock->format('A') === 'PM' ? 'م' : 'ص' }}</span>
                                                            </span>
                                                        @endif
                                                        <span class="tile-date">
                                                            <span class="tile-icon" aria-hidden="true">📅</span>
                                                            <span>{{ $slotTime->date?->format('d/m/Y') }}</span>
                                                        </span>
                                                    </div>
                                                    @if($slotTime->ticket_price !== null)
                                                        <span class="tile-price">
                                                            <span class="price-val">{{ number_format((float) $slotTime->ticket_price, 0) }}</span>
                                                            <span class="price-cur">ج.م</span>
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="showtime-tile-sub">
                                                    @if($slotCritical)
                                                        <span class="ticket-state ticket-state--critical" role="status">متبقي {{ $slotRemaining }} {{ $slotRemaining === 1 ? 'تذكرة' : 'تذاكر' }} فقط</span>
                                                    @elseif($slotLow)
                                                        <span class="ticket-state ticket-state--low" role="status">متبقي عدد محدود</span>
                                                    @else
                                                        <span class="ticket-state ticket-state--healthy" role="status">متاح للحجز الآن</span>
                                                    @endif
                                                </div>
                                                <a href="{{ route('bookings.create', $slotTime) }}" class="showtime-tile-btn" aria-label="حجز موعد {{ $slotClock ? $slotClock->format('g:i') . ($slotClock->format('A') === 'PM' ? ' م' : ' ص') : '' }} بتاريخ {{ $slotTime->date?->format('d/m/Y') }}">
                                                    <span>احجز الآن</span>
                                                    <span class="cta-arrow" aria-hidden="true">←</span>
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @elseif($featuredBookableTimes->count() === 1)
                                <div class="production-single-booking">
                                    <dl class="production-meta">
                                        <div><dt>التاريخ</dt><dd>{{ $featuredDate }}</dd></div>
                                        @if($featuredClock)<div><dt>الوقت</dt><dd>{{ $featuredClock->format('g:i') }} {{ $featuredClock->format('A') === 'PM' ? 'م' : 'ص' }}</dd></div>@endif
                                        @if($featuredTime->ticket_price !== null)<div><dt>التذكرة</dt><dd>{{ number_format((float) $featuredTime->ticket_price, 0) }} ج.م</dd></div>@endif
                                    </dl>
                                    <div class="production-single-action">
                                        <p class="ticket-state {{ $featuredCritical ? 'ticket-state--critical' : ($featuredLow ? 'ticket-state--low' : 'ticket-state--healthy') }}" role="status">
                                            @if($featuredCritical) متبقي {{ $featuredRemaining }} {{ $featuredRemaining === 1 ? 'تذكرة' : 'تذاكر' }} فقط
                                            @elseif($featuredLow) متبقي عدد محدود من التذاكر
                                            @else متاح للحجز الآن @endif
                                        </p>
                                        <a class="booking-cta booking-cta--tactile" href="{{ route('bookings.create', $featuredTime) }}">
                                            <span>احجز تذكرتك الآن</span>
                                            <span class="cta-arrow" aria-hidden="true">←</span>
                                        </a>
                                    </div>
                                </div>
                            @else
                                <div class="production-soldout-panel">
                                    <p class="ticket-state ticket-state--soldout" role="status">اكتملت التذاكر</p>
                                    <a class="booking-cta booking-cta--quiet" href="{{ route('shows.show', $featuredShow) }}">تفاصيل العرض</a>
                                </div>
                            @endif
                        </div>
                    </div>
                </article>
            @else
                <div class="stage-intermission" id="stage-intermission">
                    <p class="intermission-kicker">الخشبة جاهزة</p>
                    <p class="intermission-title">خشبة المسرح تستعد للعرض القادم</p>
                    <p class="intermission-copy">لا يوجد عرض حقيقي مُعلن ومتاح للحجز في الوقت الحالي.</p>
                    <a href="{{ route('archive') }}" class="intermission-archive-link">اكتشف العروض السابقة <span aria-hidden="true">↗</span></a>
                </div>
            @endif
        </div>
    </section>

    @php
        $additionalShows = $featuredShow
            ? $shows->reject(fn ($s) => $s->id === $featuredShow->id)->values()
            : $shows->values();
    @endphp

    @if($additionalShows->isNotEmpty())
        <section class="upcoming-section" id="shows" aria-labelledby="upcoming-heading">
            <header class="section-heading">
                <div>
                    <div class="upcoming-kicker-badge" aria-hidden="true"><span class="kicker-dot"></span> على خشبة المسرح</div>
                    <p class="sr-only">على خشبة المسرح</p>
                    <h2 id="upcoming-heading">العروض المتاحة</h2>
                </div>
                <a class="upcoming-archive-pill" href="{{ route('archive') }}">
                    <span>العروض السابقة</span>
                    <span aria-hidden="true">↗</span>
                </a>
            </header>
            <div class="upcoming-list">
                @foreach($additionalShows as $show)
                    @php
                        $showPoster = $resolvePoster($show->poster_path ?? $show->poster);
                        $bookableTimes = $show->showTimes->filter(fn ($item) => !$item->is_sold_out && (int) $item->homepage_remaining_tickets > 0)->values();
                        $nextTime = $bookableTimes->first() ?? $show->showTimes->first();
                        $remaining = $nextTime ? (int) $nextTime->homepage_remaining_tickets : 0;
                        $clock = $nextTime?->time ? \Carbon\Carbon::parse($nextTime->time) : null;
                        $soldOut = $bookableTimes->isEmpty() || !$nextTime || $nextTime->is_sold_out || $remaining <= 0;
                        $critical = !$soldOut && $remaining <= 10;
                        $low = !$soldOut && !$critical && ($remaining <= 25 || ((int) $nextTime->total_tickets > 0 && $remaining / (int) $nextTime->total_tickets <= .25));
                        $hasMultipleTimes = $bookableTimes->count() > 1;
                    @endphp
                    <article class="upcoming-production">
                        <div class="upcoming-poster-frame">
                            @if($showPoster)
                                <img class="upcoming-poster" src="{{ $showPoster }}" alt="ملصق {{ $show->title }}" width="130" height="190" loading="lazy" decoding="async">
                            @else
                                <div class="upcoming-poster-missing" role="img" aria-label="ملصق العرض لم يُنشر بعد">
                                    <span class="poster-missing-icon" aria-hidden="true">🎭</span>
                                    <span>ملصق العرض لم يُنشر بعد</span>
                                </div>
                            @endif
                        </div>
                        <div class="upcoming-copy">
                            <div class="upcoming-meta-top">
                                <span class="upcoming-kicker">عرض مسرحي</span>
                                <p class="sr-only">عرض مسرحي</p>
                                @if($nextTime && !$soldOut)
                                    <span class="upcoming-chip-live">متاح للحجز</span>
                                @endif
                            </div>
                            <h3>{{ $show->title }}</h3>
                            @if($show->description)<p class="upcoming-description">{{ $show->description }}</p>@endif
                            @if($nextTime)
                                <div class="upcoming-meta-chips" aria-label="تفاصيل موعد العرض">
                                    <span class="meta-chip meta-chip--date">
                                        <span class="chip-icon" aria-hidden="true">📅</span>
                                        <span>{{ $nextTime->date?->format('d/m/Y') }}</span>
                                    </span>
                                    @if($clock)
                                        <span class="meta-chip meta-chip--time">
                                            <span class="chip-icon" aria-hidden="true">⏰</span>
                                            <span>{{ $clock->format('g:i') }} {{ $clock->format('A') === 'PM' ? 'م' : 'ص' }}</span>
                                        </span>
                                    @endif
                                    @if($nextTime->ticket_price !== null)
                                        <span class="meta-chip meta-chip--price">
                                            <span class="chip-icon" aria-hidden="true">🎟️</span>
                                            <span>{{ number_format((float) $nextTime->ticket_price, 0) }} ج.م</span>
                                        </span>
                                    @endif
                                    @if($hasMultipleTimes)
                                        <span class="meta-chip meta-chip--times">
                                            <span class="chip-icon" aria-hidden="true">🎭</span>
                                            <span>{{ $bookableTimes->count() }} مواعيد متاحة</span>
                                        </span>
                                    @endif
                                </div>
                                <p class="upcoming-date sr-only">{{ $nextTime->date?->format('d/m/Y') }} @if($clock) · {{ $clock->format('g:i') }} {{ $clock->format('A') === 'PM' ? 'م' : 'ص' }} @endif · {{ number_format((float) $nextTime->ticket_price, 0) }} ج.م</p>
                            @else
                                <p class="upcoming-date upcoming-date--pending">لا يوجد موعد قادم · سيتم الإعلان عنها قريباً</p>
                            @endif
                        </div>
                        <div class="upcoming-action">
                            @if($soldOut)<span class="ticket-state ticket-state--soldout">{{ $nextTime ? 'اكتملت التذاكر' : 'لا يوجد موعد قادم' }}</span>
                            @elseif($critical)<span class="ticket-state ticket-state--critical">متبقي {{ $remaining }} {{ $remaining === 1 ? 'تذكرة' : 'تذاكر' }} فقط</span>
                            @elseif($low)<span class="ticket-state ticket-state--low">متبقي عدد محدود من التذاكر</span>
                            @else<span class="ticket-state ticket-state--healthy">متاح للحجز الآن</span>@endif

                            @if(!$soldOut)
                                @if($hasMultipleTimes)
                                    <a class="upcoming-cta" href="#showtime-modal-{{ $show->id }}" data-open-modal="showtime-modal-{{ $show->id }}" role="button">
                                        <span>احجز الآن</span>
                                        <span class="cta-arrow" aria-hidden="true">←</span>
                                    </a>
                                @else
                                    <a class="upcoming-cta" href="{{ route('bookings.create', $nextTime) }}">
                                        <span>احجز الآن</span>
                                        <span class="cta-arrow" aria-hidden="true">←</span>
                                    </a>
                                @endif
                            @else
                                <a class="upcoming-cta upcoming-cta--quiet" href="{{ route('shows.show', $show) }}">
                                    <span>تفاصيل العرض</span>
                                    <span class="cta-arrow" aria-hidden="true">←</span>
                                </a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Showtime Selection Modals --}}
    @php
        $modalShows = collect([$featuredShow])->filter()->merge($shows)->unique('id');
    @endphp

    @foreach($modalShows as $modalShow)
        @if($modalShow->showTimes->count() > 0)
            <div id="showtime-modal-{{ $modalShow->id }}" class="showtime-modal" role="dialog" aria-modal="true" aria-labelledby="modal-title-{{ $modalShow->id }}" hidden>
                <div class="showtime-modal-backdrop" data-close-modal></div>
                <div class="showtime-modal-dialog">
                    <header class="showtime-modal-header">
                        <div>
                            <span class="showtime-modal-kicker">
                                <span class="kicker-dot" aria-hidden="true"></span>
                                مواعيد العرض المتاحة
                            </span>
                            <h3 id="modal-title-{{ $modalShow->id }}" class="showtime-modal-title">
                                {{ $modalShow->title }}
                            </h3>
                        </div>
                        <a href="#" class="showtime-modal-close" data-close-modal aria-label="إغلاق النافذة" role="button">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </a>
                    </header>

                    <div class="showtime-modal-body">
                        <p class="showtime-modal-instructions">
                            اختر الموعد المناسب لك للمتابعة إلى صفحة حجز التذاكر:
                        </p>
                        <ul class="showtime-slot-list" role="list">
                            @foreach($modalShow->showTimes as $slotTime)
                                @php
                                    $slotClock = $slotTime->time ? \Carbon\Carbon::parse($slotTime->time) : null;
                                    $slotRemaining = (int) ($slotTime->homepage_remaining_tickets ?? $slotTime->available_tickets);
                                    $slotSoldOut = $slotTime->is_sold_out || $slotRemaining <= 0;
                                    $slotCritical = !$slotSoldOut && $slotRemaining <= 10;
                                    $slotLow = !$slotSoldOut && !$slotCritical && ($slotRemaining <= 25 || ((int) $slotTime->total_tickets > 0 && $slotRemaining / (int) $slotTime->total_tickets <= .25));
                                @endphp
                                <li class="showtime-slot-card {{ $slotSoldOut ? 'is-soldout' : '' }}">
                                    <div class="showtime-slot-info">
                                        <div class="showtime-slot-time-block">
                                            <span class="slot-date">
                                                <span class="slot-icon" aria-hidden="true">📅</span>
                                                <span>{{ $slotTime->date?->format('d/m/Y') }}</span>
                                            </span>
                                            @if($slotClock)
                                                <span class="slot-time">
                                                    <span class="slot-icon" aria-hidden="true">⏰</span>
                                                    <span>{{ $slotClock->format('g:i') }} {{ $slotClock->format('A') === 'PM' ? 'م' : 'ص' }}</span>
                                                </span>
                                            @endif
                                            @if($slotTime->ticket_price !== null)
                                                <span class="slot-price">
                                                    <span class="slot-icon" aria-hidden="true">🎟️</span>
                                                    <span>{{ number_format((float) $slotTime->ticket_price, 0) }} ج.م</span>
                                                </span>
                                            @endif
                                        </div>
                                        <div class="showtime-slot-status">
                                            @if($slotSoldOut)
                                                <span class="ticket-state ticket-state--soldout">اكتملت التذاكر</span>
                                            @elseif($slotCritical)
                                                <span class="ticket-state ticket-state--critical">متبقي {{ $slotRemaining }} {{ $slotRemaining === 1 ? 'تذكرة' : 'تذاكر' }} فقط</span>
                                            @elseif($slotLow)
                                                <span class="ticket-state ticket-state--low">متبقي عدد محدود</span>
                                            @else
                                                <span class="ticket-state ticket-state--healthy">متاح للحجز</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="showtime-slot-action">
                                        @if(!$slotSoldOut)
                                            <a href="{{ route('bookings.create', $slotTime) }}" class="showtime-slot-btn">
                                                <span>حجز هذا الموعد</span>
                                                <span class="cta-arrow" aria-hidden="true">←</span>
                                            </a>
                                        @else
                                            <button type="button" class="showtime-slot-btn is-disabled" disabled aria-disabled="true">
                                                <span>اكتملت التذاكر</span>
                                            </button>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

    <script>
    (function () {
        document.addEventListener('click', function (e) {
            var trigger = e.target.closest('[data-open-modal]');
            if (trigger) {
                e.preventDefault();
                var modalId = trigger.getAttribute('data-open-modal');
                var modal = document.getElementById(modalId);
                if (modal) {
                    modal.removeAttribute('hidden');
                    document.body.classList.add('modal-open');
                    modal._prevFocus = trigger;
                    var closeBtn = modal.querySelector('.showtime-modal-close');
                    if (closeBtn) closeBtn.focus();
                }
                return;
            }

            var closeBtn = e.target.closest('[data-close-modal]');
            if (closeBtn) {
                e.preventDefault();
                var modal = closeBtn.closest('.showtime-modal');
                if (modal) {
                    modal.setAttribute('hidden', '');
                    document.body.classList.remove('modal-open');
                    if (modal._prevFocus && typeof modal._prevFocus.focus === 'function') {
                        modal._prevFocus.focus();
                    }
                }
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                var openModal = document.querySelector('.showtime-modal:not([hidden])');
                if (openModal) {
                    openModal.setAttribute('hidden', '');
                    document.body.classList.remove('modal-open');
                    if (openModal._prevFocus && typeof openModal._prevFocus.focus === 'function') {
                        openModal._prevFocus.focus();
                    }
                }
            }
        });
    })();
    </script>
</div>
@else
<section class="public-shows-page" dir="rtl" aria-labelledby="public-shows-title">
    <header><p>فريق الصرخة المسرحي</p><h1 id="public-shows-title">العروض المتاحة</h1></header>
    @forelse($shows as $show)
        @php
            $showPoster = $resolvePoster($show->poster_path ?? $show->poster);
            $time = $show->showTimes->first(fn ($item) => !$item->is_sold_out && (int) $item->available_tickets > 0) ?? $show->showTimes->first();
            $remaining = $time ? max(0, (int) $time->available_tickets) : 0;
            $soldOut = !$time || $time->is_sold_out || $remaining <= 0;
        @endphp
        <article class="public-show-card">
            @if($showPoster)<img src="{{ $showPoster }}" alt="ملصق {{ $show->title }}" loading="lazy">@endif
            <div><h2>{{ $show->title }}</h2><p>{{ $show->description }}</p>
                @if($time)<p>{{ $time->date?->translatedFormat('j F Y') }} @if($time->time) · {{ \Carbon\Carbon::parse($time->time)->format('g:i A') }} @endif · {{ number_format((float) $time->ticket_price, 0) }} ج.م</p>@endif
                @if($soldOut)<span class="ticket-state ticket-state--soldout">{{ $time ? 'اكتملت التذاكر' : 'لا يوجد موعد قادم' }}</span>
                @elseif($remaining <= 10)<span class="ticket-state ticket-state--critical">متبقي {{ $remaining }} {{ $remaining === 1 ? 'تذكرة' : 'تذاكر' }} فقط</span>
                @elseif($remaining <= 25)<span class="ticket-state ticket-state--low">متبقي عدد محدود من التذاكر</span>
                @else<span class="ticket-state ticket-state--healthy">متاح للحجز الآن</span>@endif
                @if($time && !$soldOut)<a class="booking-cta" href="{{ route('bookings.create', $time) }}">احجز الآن</a>@else<a href="{{ route('shows.show', $show) }}">تفاصيل العرض</a>@endif
            </div>
        </article>
    @empty
        <p>لا توجد عروض متاحة في الوقت الحالي.</p>
    @endforelse
</section>
@endif
@endsection
