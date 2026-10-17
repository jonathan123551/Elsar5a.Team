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
    $featuredTime = $featuredShow?->showTimes->first(fn ($time) => !$time->is_sold_out && (int) $time->homepage_remaining_tickets > 0);
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
            <a class="opening-scroll" href="#camera-journey" data-reveal="scroll">
                <span class="opening-scroll-mark" aria-hidden="true"></span>
                <span>ادخل إلى المسرح</span>
            </a>
        </div>
        <a class="opening-archive" href="{{ route('archive') }}">الأرشيف <span aria-hidden="true">↗</span></a>
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
            <div class="camera-identity" id="camera-identity" aria-hidden="true">
                <p class="identity-overline">من بيان الفريق</p>
                <div class="identity-manifesto">
                    <p>{{ $manifesto[2] }}</p>
                    <p>{{ $manifesto[3] }}</p>
                    <p>{{ $manifesto[4] }}</p>
                    <p>{{ $manifesto[5] }}</p>
                </div>
                <p class="identity-signoff">{{ $manifesto[6] }}</p>
            </div>

            @if($featuredShow && $featuredTime)
                <article class="stage-production" id="stage-production" aria-labelledby="featured-title">
                    @if($featuredPoster)
                        <div class="production-poster-wrap">
                            <span class="production-light" aria-hidden="true"></span>
                            <div class="production-poster-frame">
                                <img src="{{ $featuredPoster }}" alt="ملصق العرض {{ $featuredShow->title }}" width="640" height="900" loading="eager" decoding="async" fetchpriority="high">
                            </div>
                        </div>
                    @else
                        <div class="production-poster-missing" role="img" aria-label="لم ينشر الفريق ملصق هذا العرض بعد">
                            <span>ملصق العرض لم يُنشر بعد</span>
                        </div>
                    @endif
                    <div class="production-information">
                        <p class="production-kicker">العرض القادم على خشبة المسرح</p>
                        <h2 id="featured-title">{{ $featuredShow->title }}</h2>
                        @if($featuredShow->description)<p class="production-description">{{ $featuredShow->description }}</p>@endif
                        <dl class="production-meta">
                            <div><dt>التاريخ</dt><dd>{{ $featuredDate }}</dd></div>
                            @if($featuredClock)<div><dt>الوقت</dt><dd>{{ $featuredClock->format('g:i') }} {{ $featuredClock->format('A') === 'PM' ? 'م' : 'ص' }}</dd></div>@endif
                            @if($featuredTime->ticket_price !== null)<div><dt>التذكرة</dt><dd>{{ number_format((float) $featuredTime->ticket_price, 0) }} ج.م</dd></div>@endif
                        </dl>
                        @if($featuredSoldOut)
                            <p class="ticket-state ticket-state--soldout" role="status">اكتملت التذاكر</p>
                            <a class="booking-cta booking-cta--quiet" href="{{ route('shows.show', $featuredShow) }}">تفاصيل العرض</a>
                        @else
                            <p class="ticket-state {{ $featuredCritical ? 'ticket-state--critical' : ($featuredLow ? 'ticket-state--low' : 'ticket-state--healthy') }}" role="status">
                                @if($featuredCritical) متبقي {{ $featuredRemaining }} {{ $featuredRemaining === 1 ? 'تذكرة' : 'تذاكر' }} فقط
                                @elseif($featuredLow) متبقي عدد محدود من التذاكر
                                @else متاح للحجز الآن @endif
                            </p>
                            <a class="booking-cta" href="{{ route('bookings.create', $featuredTime) }}">احجز تذكرتك الآن <span aria-hidden="true">↗</span></a>
                        @endif
                    </div>
                </article>
            @else
                <div class="stage-intermission" id="stage-intermission">
                    <p class="intermission-kicker">الخشبة جاهزة</p>
                    <p class="intermission-title">خشبة المسرح تستعد للعرض القادم</p>
                    <p class="intermission-copy">لا يوجد عرض حقيقي مُعلن ومتاح للحجز في الوقت الحالي.</p>
                    <a href="{{ route('archive') }}">اكتشف العروض السابقة <span aria-hidden="true">↗</span></a>
                </div>
            @endif

            <p class="camera-cue" aria-hidden="true">تقدّم نحو الخشبة</p>
        </div>
    </section>

    <section class="manifesto-section" aria-labelledby="manifesto-heading">
        <header class="section-intro"><p>صوت الفريق</p><h2 id="manifesto-heading">رسالتنا</h2></header>
        <div class="manifesto-lines">
            @foreach($manifesto as $beat)
                <p class="manifesto-line{{ $loop->last ? ' manifesto-line--final' : '' }}" data-manifesto-line>{{ $beat }}</p>
            @endforeach
        </div>
        <img class="manifesto-mark" src="{{ asset('images/sarkha-logo.png') }}" alt="" width="72" height="72" loading="lazy">
    </section>

    <section class="upcoming-section" id="shows" aria-labelledby="upcoming-heading">
        <header class="section-heading"><div><p>على خشبة المسرح</p><h2 id="upcoming-heading">العروض القادمة</h2></div><a href="{{ route('archive') }}">العروض السابقة <span aria-hidden="true">↗</span></a></header>
        @if($shows->isEmpty())
            <div class="upcoming-empty"><span class="empty-rule" aria-hidden="true"></span><p>لا توجد عروض معلنة في الوقت الحالي.</p><a href="{{ route('archive') }}">تصفّح الأرشيف</a></div>
        @else
            <div class="upcoming-list">
                @foreach($shows as $show)
                    @php
                        $showPoster = $resolvePoster($show->poster_path ?? $show->poster);
                        $nextTime = $show->showTimes->first(fn ($item) => !$item->is_sold_out && (int) $item->homepage_remaining_tickets > 0) ?? $show->showTimes->first();
                        $remaining = $nextTime ? (int) $nextTime->homepage_remaining_tickets : 0;
                        $clock = $nextTime?->time ? \Carbon\Carbon::parse($nextTime->time) : null;
                        $soldOut = !$nextTime || $nextTime->is_sold_out || $remaining <= 0;
                        $critical = !$soldOut && $remaining <= 10;
                        $low = !$soldOut && !$critical && ($remaining <= 25 || ((int) $nextTime->total_tickets > 0 && $remaining / (int) $nextTime->total_tickets <= .25));
                    @endphp
                    <article class="upcoming-production">
                        @if($showPoster)<img class="upcoming-poster" src="{{ $showPoster }}" alt="ملصق {{ $show->title }}" width="120" height="168" loading="lazy" decoding="async">@endif
                        <div class="upcoming-copy"><p>عرض مسرحي</p><h3>{{ $show->title }}</h3>@if($show->description)<p>{{ $show->description }}</p>@endif
                            @if($nextTime)<p class="upcoming-date">{{ $nextTime->date?->format('d/m/Y') }} @if($clock) · {{ $clock->format('g:i') }} {{ $clock->format('A') === 'PM' ? 'م' : 'ص' }} @endif · {{ number_format((float) $nextTime->ticket_price, 0) }} ج.م</p>@endif
                        </div>
                        <div class="upcoming-action">
                            @if($soldOut)<span class="ticket-state ticket-state--soldout">{{ $nextTime ? 'اكتملت التذاكر' : 'لا يوجد موعد قادم' }}</span>
                            @elseif($critical)<span class="ticket-state ticket-state--critical">متبقي {{ $remaining }} {{ $remaining === 1 ? 'تذكرة' : 'تذاكر' }} فقط</span>
                            @elseif($low)<span class="ticket-state ticket-state--low">متبقي عدد محدود من التذاكر</span>
                            @else<span class="ticket-state ticket-state--healthy">متاح للحجز الآن</span>@endif
                            @if($nextTime && !$soldOut)<a href="{{ route('bookings.create', $nextTime) }}">احجز الآن</a>@else<a href="{{ route('shows.show', $show) }}">تفاصيل العرض</a>@endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="team-signoff" aria-label="عن الفريق">
        <img src="{{ asset('images/sarkha-logo.png') }}" alt="" width="56" height="56" loading="lazy">
        <div><p>فريق الصرخة المسرحي</p><span>{{ $manifesto[6] }}</span></div>
        <a href="{{ route('about') }}">عن الفريق <span aria-hidden="true">↗</span></a>
    </section>
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
