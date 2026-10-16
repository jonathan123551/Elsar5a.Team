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
    $featuredShow = $featuredShow ?? null;
    $featuredPoster = $featuredShow?->poster_path
        ? \App\Support\CloudinaryUrl::forDisplay($featuredShow->poster_path)
        : null;
    $featuredTime = $featuredShow?->showTimes->first(fn ($time) => !$time->is_sold_out && (int) $time->available_tickets > 0)
        ?? $featuredShow?->showTimes->first();
    $featuredRemaining = $featuredTime ? max(0, (int) $featuredTime->available_tickets) : 0;
    $featuredDate = $featuredTime?->date?->format('d/m/Y');
    $featuredClock = $featuredTime?->time ? \Carbon\Carbon::parse($featuredTime->time) : null;
    $featuredSoldOut = !$featuredTime || $featuredTime->is_sold_out || $featuredRemaining <= 0;
    $featuredCritical = !$featuredSoldOut && $featuredRemaining <= 10;
    $featuredLow = !$featuredSoldOut && !$featuredCritical && (
        $featuredRemaining <= 25 || ((int) $featuredTime->total_tickets > 0 && $featuredRemaining / (int) $featuredTime->total_tickets <= .25)
    );
@endphp

@if(request()->routeIs('home'))
<div class="home-theatre" dir="rtl">
    <div id="elsar5a-curtain-overlay" class="elsar5a-curtain-overlay" data-curtain-state="closed" aria-hidden="true">
        <div class="curtain-valance"></div>
        <div class="elsar5a-curtain-seam"></div>
        <div class="elsar5a-curtain-wing elsar5a-curtain-wing--left"><span></span></div>
        <div class="elsar5a-curtain-wing elsar5a-curtain-wing--right"><span></span></div>
    </div>
    <button id="elsar5a-curtain-skip" class="elsar5a-curtain-skip" type="button" aria-label="تخطّي الافتتاحية ومتابعة التصفح">
        تخطّي الافتتاحية <span aria-hidden="true">↓</span>
    </button>

    <section class="stage-overture-section" aria-labelledby="stage-identity-title">
        <div class="theatre-proscenium" aria-hidden="true">
            <div class="theatre-box theatre-box--left"></div><div class="theatre-box theatre-box--right"></div>
            <div class="theatre-ceiling"></div><div class="theatre-floor"></div>
            <div class="theatre-seats" aria-hidden="true"></div>
            <div class="theatre-stage-light"></div>
        </div>
        <div class="stage-identity">
            <div class="stage-logo-pedestal">
                <img src="{{ asset('images/sarkha-logo.png') }}" alt="شعار فريق الصرخة المسرحي" class="stage-hero-logo" width="144" height="144" fetchpriority="high">
            </div>
            <p class="stage-eyebrow">فريق الصرخة المسرحي</p>
            <h1 id="stage-identity-title" class="stage-main-title">فريق الصرخة المسرحي</h1>
            <p class="stage-manifesto-line">❤ نجول نصرخ فيزداد العقل وعيا ❤</p>
            <div class="stage-scroll-hint" aria-hidden="true"><span></span><small>تابع إلى الداخل</small></div>
        </div>
        <a class="overture-archive-link" href="{{ route('archive') }}">الأرشيف <span aria-hidden="true">↗</span></a>
    </section>

    <section class="camera-journey" id="journey" aria-label="رحلة إلى داخل المسرح">
        <div class="camera-viewport">
            <div class="journey-world" aria-hidden="true">
                <div class="journey-proscenium">
                    <div class="journey-curtain journey-curtain--left"></div><div class="journey-curtain journey-curtain--right"></div>
                    <div class="journey-stage"><div class="journey-backdrop"></div><div class="journey-footlights"></div></div>
                </div>
                <div class="journey-auditorium"><div class="journey-seat-row journey-seat-row--one"></div><div class="journey-seat-row journey-seat-row--two"></div><div class="journey-seat-row journey-seat-row--three"></div></div>
            </div>
            <div class="journey-identity" aria-hidden="true">
                <img src="{{ asset('images/sarkha-logo.png') }}" alt="" width="80" height="80">
                <p>فريق الصرخة المسرحي</p><span>❤ نجول نصرخ فيزداد العقل وعيا ❤</span>
            </div>
            @if($featuredShow && $featuredTime)
                <article class="journey-playbill" aria-labelledby="featured-title">
                    <div class="playbill-poster">
                        @if($featuredPoster)
                            <img src="{{ $featuredPoster }}" alt="ملصق {{ $featuredShow->title }}" width="640" height="900" fetchpriority="high">
                        @else
                            <div class="poster-unavailable" role="img" aria-label="لا يتوفر ملصق لهذا العرض">
                                <img src="{{ asset('images/sarkha-logo.png') }}" alt="" width="78" height="78">
                                <span>فريق الصرخة المسرحي</span>
                            </div>
                        @endif
                    </div>
                    <div class="playbill-details">
                        <p class="playbill-kicker">العرض القادم</p>
                        <h2 id="featured-title">{{ $featuredShow->title }}</h2>
                        @if($featuredShow->description)
                            <p class="playbill-description">{{ $featuredShow->description }}</p>
                        @endif
                        <dl class="playbill-meta">
                            <div><dt>التاريخ</dt><dd>{{ $featuredDate }}</dd></div>
                            @if($featuredClock)<div><dt>الوقت</dt><dd>{{ $featuredClock->format('g:i') }} {{ $featuredClock->format('A') === 'PM' ? 'م' : 'ص' }}</dd></div>@endif
                            <div><dt>التذكرة</dt><dd>{{ number_format((float) $featuredTime->ticket_price, 0) }} ج.م</dd></div>
                        </dl>
                        @if($featuredSoldOut)
                            <p class="scarcity-badge scarcity-badge--soldout" role="status">اكتملت التذاكر</p>
                            <a class="stage-cta-primary" href="{{ route('shows.show', $featuredShow) }}">تفاصيل العرض</a>
                        @else
                            <p class="scarcity-badge {{ $featuredCritical ? 'scarcity-badge--critical' : ($featuredLow ? 'scarcity-badge--low' : 'scarcity-badge--healthy') }}" role="status">
                                @if($featuredCritical) متبقي {{ $featuredRemaining }} {{ $featuredRemaining === 1 ? 'تذكرة' : 'تذاكر' }} فقط
                                @elseif($featuredLow) متبقي عدد محدود من التذاكر
                                @else متاح للحجز الآن @endif
                            </p>
                            <a class="stage-cta-primary" href="{{ route('bookings.create', $featuredTime) }}">احجز تذكرتك الآن <span aria-hidden="true">🎟</span></a>
                        @endif
                    </div>
                </article>
            @else
                <div class="journey-no-show"><p>المسرح في استراحة قصيرة</p><a href="{{ route('archive') }}">تصفّح أرشيف العروض</a></div>
            @endif
            <p class="journey-instruction" aria-hidden="true">واصل التقدّم إلى المسرح</p>
        </div>
    </section>

    <section class="manifesto-section" aria-labelledby="manifesto-title">
        <div class="manifesto-heading"><p>رسالتنا</p><h2 id="manifesto-title">لماذا نصرخ؟</h2></div>
        <div class="manifesto-copy">
            @foreach($manifesto as $beat)
                <p class="manifesto-beat{{ $loop->last ? ' manifesto-signoff' : '' }}">{{ $beat }}</p>
            @endforeach
        </div>
    </section>

    <section id="shows" class="active-shows-section" aria-labelledby="shows-title">
        <header class="shows-heading"><div><p>على خشبة المسرح</p><h2 id="shows-title">العروض القادمة</h2></div>
            <a href="{{ route('archive') }}">العروض السابقة <span aria-hidden="true">↗</span></a>
        </header>
        @if($shows->isEmpty())
            <div class="shows-empty"><p>لا توجد عروض معلنة في الوقت الحالي.</p><a href="{{ route('archive') }}">تصفّح أرشيف العروض</a></div>
        @else
            <div class="shows-list">
                @foreach($shows as $show)
                    @php
                        $showPoster = $show->poster_path ? \App\Support\CloudinaryUrl::forDisplay($show->poster_path) : null;
                        $nextTime = $show->showTimes->first(fn ($time) => !$time->is_sold_out && (int) $time->available_tickets > 0) ?? $show->showTimes->first();
                        $remaining = $nextTime ? max(0, (int) $nextTime->available_tickets) : 0;
                        $nextClock = $nextTime?->time ? \Carbon\Carbon::parse($nextTime->time) : null;
                        $soldOut = !$nextTime || $nextTime->is_sold_out || $remaining <= 0;
                        $critical = !$soldOut && $remaining <= 10;
                        $low = !$soldOut && !$critical && ($remaining <= 25 || ((int) $nextTime->total_tickets > 0 && $remaining / (int) $nextTime->total_tickets <= .25));
                    @endphp
                    <article class="show-row">
                        <a class="show-row-poster" href="{{ route('shows.show', $show) }}" tabindex="-1" aria-hidden="true">
                            @if($showPoster)<img src="{{ $showPoster }}" alt="" loading="lazy" decoding="async">@else <span class="poster-fallback">الصرخة</span>@endif
                        </a>
                        <div class="show-row-copy"><p class="show-row-kicker">عرض مسرحي</p><h3>{{ $show->title }}</h3>
                            @if($show->description)<p>{{ $show->description }}</p>@endif
                            @if($nextTime)<p class="show-row-date">{{ $nextTime->date?->format('d/m/Y') }} @if($nextClock) · {{ $nextClock->format('g:i') }} {{ $nextClock->format('A') === 'PM' ? 'م' : 'ص' }} @endif · {{ number_format((float) $nextTime->ticket_price, 0) }} ج.م</p>@endif
                        </div>
                        <div class="show-row-action">
                            @if($soldOut)<span class="scarcity-badge scarcity-badge--soldout">{{ $nextTime ? 'اكتملت التذاكر' : 'لا يوجد موعد قادم' }}</span>
                            @elseif($critical)<span class="scarcity-badge scarcity-badge--critical">متبقي {{ $remaining }} {{ $remaining === 1 ? 'تذكرة' : 'تذاكر' }} فقط</span>
                            @elseif($low)<span class="scarcity-badge scarcity-badge--low">متبقي عدد محدود من التذاكر</span>
                            @else<span class="scarcity-badge scarcity-badge--healthy">متاح للحجز الآن</span>@endif
                            @if($nextTime && !$soldOut)<a class="show-row-link" href="{{ route('bookings.create', $nextTime) }}">احجز الآن</a>@else<a class="show-row-link show-row-link--quiet" href="{{ route('shows.show', $show) }}">تفاصيل العرض</a>@endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="team-note" aria-label="عن الفريق">
        <img src="{{ asset('images/sarkha-logo.png') }}" alt="" width="64" height="64" loading="lazy">
        <div><p>فريق الصرخة المسرحي</p><span>❤ نجول نصرخ فيزداد العقل وعيا ❤</span></div>
        <a href="{{ route('about') }}">عن الفريق <span aria-hidden="true">↗</span></a>
    </section>
</div>
@else
<section class="public-shows-page" dir="rtl" aria-labelledby="public-shows-title">
    <header><p>فريق الصرخة المسرحي</p><h1 id="public-shows-title">العروض المتاحة</h1></header>
    @forelse($shows as $show)
        @php
            $showPoster = $show->poster_path ? \App\Support\CloudinaryUrl::forDisplay($show->poster_path) : null;
            $time = $show->showTimes->first(fn ($item) => !$item->is_sold_out && (int) $item->available_tickets > 0) ?? $show->showTimes->first();
            $remaining = $time ? max(0, (int) $time->available_tickets) : 0;
            $soldOut = !$time || $time->is_sold_out || $remaining <= 0;
        @endphp
        <article class="public-show-card">
            @if($showPoster)<img src="{{ $showPoster }}" alt="ملصق {{ $show->title }}" loading="lazy">@endif
            <div><h2>{{ $show->title }}</h2><p>{{ $show->description }}</p>
                @if($time)<p>{{ $time->date?->translatedFormat('j F Y') }} @if($time->time) · {{ \Carbon\Carbon::parse($time->time)->format('g:i A') }} @endif · {{ number_format((float) $time->ticket_price, 0) }} ج.م</p>@endif
                @if($soldOut)<span class="scarcity-badge scarcity-badge--soldout">{{ $time ? 'اكتملت التذاكر' : 'لا يوجد موعد قادم' }}</span>
                @elseif($remaining <= 10)<span class="scarcity-badge scarcity-badge--critical">متبقي {{ $remaining }} {{ $remaining === 1 ? 'تذكرة' : 'تذاكر' }} فقط</span>
                @elseif($remaining <= 25)<span class="scarcity-badge scarcity-badge--low">متبقي عدد محدود من التذاكر</span>
                @else<span class="scarcity-badge scarcity-badge--healthy">متاح للحجز الآن</span>@endif
                @if($time && !$soldOut)<a class="stage-cta-primary" href="{{ route('bookings.create', $time) }}">احجز الآن</a>@else<a href="{{ route('shows.show', $show) }}">تفاصيل العرض</a>@endif
            </div>
        </article>
    @empty
        <p>لا توجد عروض متاحة في الوقت الحالي.</p>
    @endforelse
</section>
@endif
@endsection
