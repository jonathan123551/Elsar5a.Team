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
    $additionalShows = $shows ?? collect();
    if ($featuredShow) {
        $additionalShows = $additionalShows->reject(fn ($show) => $show->id === $featuredShow->id)->values();
    }
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
    $rawDesc = $featuredShow?->description ?? '';
    $locationUrl = null;
    $cleanDesc = $rawDesc;
    if ($rawDesc && preg_match('/(https?:\/\/[^\s<]+)/i', $rawDesc, $urlMatches)) {
        $locationUrl = $urlMatches[1];
        $cleanDesc = trim(preg_replace('/https?:\/\/[^\s<]+/i', '', $rawDesc));
        $cleanDesc = rtrim($cleanDesc, " -:—،,\t\n\r");
    }
    $locationLabel = 'موقع المسرح على الخريطة';
    if ($locationUrl && (str_contains($locationUrl, 'maps') || str_contains($locationUrl, 'goo.gl'))) {
        $locationLabel = 'موقع المسرح على الخريطة (Google Maps)';
    }
@endphp

<style>
/* ============================================================
   SHOWTIME CARDS — SCREENSHOT 1 GLASSMORPHISM REDESIGN
   ------------------------------------------------------------
   Recreates the exact visual language of Screenshot 1:
   - Deep midnight translucent glass surface with ambient blue/slate sheen
   - Smooth generous rounded corners (26px desktop, 20px mobile)
   - Delicate specular perimeter border (rgba(255,255,255,0.10)) + top edge highlight
   - Warm, luminous golden-yellow gradient pill CTA button (rounded-full)
   - Obsidian dark bold typography (#0b0f19) on CTA button
   - Warm golden drop shadow & radiant ambient glow underneath CTA
   - High-contrast hierarchy: gold day name, crisp white time,
     sleek translucent pill availability chip with glowing indicator dot.
   ============================================================ */

.cinematic-showtimes {
  order: 3;
  width: min(100%, 580px);
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
  margin-top: 18px;
  will-change: transform, opacity;
}

.cinematic-showtime-card {
  position: relative;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 18px 18px 16px !important;
  min-height: 172px !important;
  border-radius: 26px !important;
  text-decoration: none;
  text-align: right;
  color: #f1f5fb;

  /* Translucent deep midnight glass with ambient sheen */
  background:
    radial-gradient(ellipse at 50% 0%, rgba(30, 58, 138, 0.16) 0%, transparent 75%),
    linear-gradient(155deg, rgba(15, 23, 42, 0.80) 0%, rgba(10, 16, 31, 0.88) 55%, rgba(3, 7, 18, 0.94) 100%) !important;
  backdrop-filter: blur(20px) saturate(160%) !important;
  -webkit-backdrop-filter: blur(20px) saturate(160%) !important;

  /* Thin elegant perimeter border with top specular edge light */
  border: 1px solid rgba(255, 255, 255, 0.10) !important;
  box-shadow:
    0 22px 48px -12px rgba(0, 0, 0, 0.78),
    0 4px 16px -2px rgba(0, 0, 0, 0.45),
    inset 0 1px 0 0 rgba(255, 255, 255, 0.16),
    inset 0 -1px 0 0 rgba(0, 0, 0, 0.35) !important;

  /* Compositor transitions */
  transition:
    transform 0.26s cubic-bezier(0.16, 1, 0.3, 1),
    box-shadow 0.26s ease,
    border-color 0.26s ease,
    background 0.26s ease;

  touch-action: manipulation;
  overflow: hidden;
  isolation: isolate;
}

/* Specular light wash on top edge */
.cinematic-showtime-card::before {
  content: "";
  position: absolute;
  inset: 0;
  border-radius: inherit;
  background: linear-gradient(
    180deg,
    rgba(255, 255, 255, 0.08) 0%,
    rgba(255, 255, 255, 0.02) 22%,
    transparent 55%
  ) !important;
  pointer-events: none;
  z-index: 1;
}

/* Subtle inner ambient glow */
.cinematic-showtime-card::after {
  content: "";
  position: absolute;
  top: -40px;
  right: -40px;
  width: 120px;
  height: 120px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(250, 204, 21, 0.08) 0%, transparent 70%);
  pointer-events: none;
  z-index: 0;
  opacity: 0.6;
  transition: opacity 0.3s ease;
}

.cinematic-showtime-card:hover,
.cinematic-showtime-card:focus-visible {
  transform: translateY(-3px) !important;
  border-color: rgba(251, 191, 36, 0.38) !important;
  box-shadow:
    0 28px 56px -12px rgba(0, 0, 0, 0.88),
    0 0 28px rgba(245, 158, 11, 0.15),
    inset 0 1px 0 0 rgba(255, 255, 255, 0.25) !important;
}

.cinematic-showtime-card:hover::after {
  opacity: 1;
}

.cinematic-showtime-card:active {
  transform: scale(0.985) !important;
  transition-duration: 0.1s;
}

/* 1. Date Row */
.cinematic-showtime-card .st-date-row {
  position: relative;
  z-index: 2;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.cinematic-showtime-card .st-day-name {
  font-size: 13.5px;
  font-weight: 700;
  color: #fde68a !important;
  letter-spacing: 0.01em;
  text-shadow: 0 1px 4px rgba(0, 0, 0, 0.4);
}

.cinematic-showtime-card .st-date-val {
  font-size: 12px;
  font-weight: 600;
  color: rgba(226, 232, 240, 0.72) !important;
  font-variant-numeric: tabular-nums;
  direction: ltr;
}

/* 2. Time Row */
.cinematic-showtime-card .st-time-row {
  position: relative;
  z-index: 2;
  display: flex;
  align-items: baseline;
  gap: 6px;
  margin-top: 5px;
}

.cinematic-showtime-card .st-time-num {
  font-size: 27px !important;
  font-weight: 800;
  color: #ffffff !important;
  font-variant-numeric: tabular-nums;
  letter-spacing: -0.02em;
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.6);
  line-height: 1.1;
}

.cinematic-showtime-card .st-time-ampm {
  font-size: 12.5px;
  font-weight: 700;
  color: #cbd5e1 !important;
}

/* Delicate Hairline Glass Seam */
.cinematic-showtime-card .st-seam {
  position: relative;
  z-index: 2;
  height: 1px;
  width: 100%;
  margin: 11px 0 10px !important;
  background: linear-gradient(
    90deg,
    transparent 0%,
    rgba(255, 255, 255, 0.14) 25%,
    rgba(250, 204, 21, 0.28) 50%,
    rgba(255, 255, 255, 0.14) 75%,
    transparent 100%
  ) !important;
}

/* 3. Meta Row (Availability Chip & Price) */
.cinematic-showtime-card .st-meta-row {
  position: relative;
  z-index: 2;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 12px;
}

/* Pill Chip matching Screenshot 1's sd-pill */
.cinematic-showtime-card .st-avail-chip {
  display: inline-flex;
  align-items: center;
  gap: 5.5px;
  padding: 3.5px 9px !important;
  border-radius: 9999px !important;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.12);
  font-size: 11px;
  font-weight: 600;
  line-height: 1;
}

.cinematic-showtime-card .st-dot {
  width: 5.5px;
  height: 5.5px;
  border-radius: 50%;
  flex-shrink: 0;
}

.cinematic-showtime-card .st-avail-chip.is-healthy {
  color: #a7f3d0 !important;
  border-color: rgba(52, 211, 153, 0.28) !important;
  background: rgba(52, 211, 153, 0.08) !important;
}
.cinematic-showtime-card .st-avail-chip.is-healthy .st-dot {
  background: #34d399 !important;
  box-shadow: 0 0 8px rgba(52, 211, 153, 0.75) !important;
}

.cinematic-showtime-card .st-avail-chip.is-low {
  color: #fde68a !important;
  border-color: rgba(251, 191, 36, 0.32) !important;
  background: rgba(251, 191, 36, 0.08) !important;
}
.cinematic-showtime-card .st-avail-chip.is-low .st-dot {
  background: #fbbf24 !important;
  box-shadow: 0 0 8px rgba(251, 191, 36, 0.75) !important;
}

.cinematic-showtime-card .st-avail-chip.is-critical {
  color: #fca5a5 !important;
  border-color: rgba(248, 113, 113, 0.32) !important;
  background: rgba(248, 113, 113, 0.08) !important;
}
.cinematic-showtime-card .st-avail-chip.is-critical .st-dot {
  background: #f87171 !important;
  box-shadow: 0 0 8px rgba(248, 113, 113, 0.75) !important;
}

.cinematic-showtime-card .st-price-block {
  display: inline-flex;
  align-items: baseline;
  gap: 3.5px;
  direction: ltr;
}

.cinematic-showtime-card .st-price-amount {
  font-size: 15px;
  font-weight: 800;
  color: #ffffff !important;
  font-variant-numeric: tabular-nums;
  text-shadow: 0 1px 4px rgba(0, 0, 0, 0.5);
}

.cinematic-showtime-card .st-price-label {
  font-size: 11px;
  font-weight: 700;
  color: #fde68a !important;
}

/* ============================================================
   4. THE HERO CTA BUTTON — LUMINOUS GOLD PILL FROM SCREENSHOT 1
   ============================================================ */
.cinematic-showtime-card .st-cta-btn {
  position: relative;
  z-index: 2;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  width: 100%;
  min-height: 42px !important;
  padding: 0 16px !important;
  border-radius: 9999px !important;

  /* Screenshot 1 warm radiant gold gradient */
  background: linear-gradient(180deg, #fde68a 0%, #f59e0b 60%, #d97706 100%) !important;
  border: 1px solid rgba(255, 255, 255, 0.35) !important;

  /* Typography: obsidian bold */
  color: #0b0f19 !important;
  font-size: 13.5px !important;
  font-weight: 800 !important;
  letter-spacing: normal;
  text-shadow: 0 1px 1px rgba(255, 255, 255, 0.3);

  /* Radiant golden drop shadow + top specular light */
  box-shadow:
    0 8px 22px -4px rgba(245, 158, 11, 0.52),
    0 3px 8px -2px rgba(0, 0, 0, 0.35),
    inset 0 1px 0 0 rgba(255, 255, 255, 0.70) !important;

  transition:
    transform 0.24s cubic-bezier(0.16, 1, 0.3, 1),
    box-shadow 0.28s ease,
    background 0.24s ease,
    filter 0.24s ease;
}

.cinematic-showtime-card:hover .st-cta-btn,
.cinematic-showtime-card:focus-visible .st-cta-btn {
  background: linear-gradient(180deg, #fef08a 0%, #fbbf24 55%, #d97706 100%) !important;
  transform: translateY(-1.5px) !important;
  box-shadow:
    0 14px 30px -4px rgba(245, 158, 11, 0.75),
    0 0 22px rgba(251, 191, 36, 0.45),
    0 4px 10px rgba(0, 0, 0, 0.4),
    inset 0 1px 0 0 rgba(255, 255, 255, 0.85) !important;
}

.cinematic-showtime-card:active .st-cta-btn {
  transform: scale(0.975) !important;
  box-shadow:
    0 4px 14px -2px rgba(245, 158, 11, 0.45),
    inset 0 1px 0 0 rgba(255, 255, 255, 0.5) !important;
  transition-duration: 0.08s;
}

.cinematic-showtime-card .st-cta-icon {
  font-size: 14px;
  line-height: 1;
  display: inline-block;
  vertical-align: middle;
  filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.25));
  transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1);
}

.cinematic-showtime-card:hover .st-cta-icon,
.cinematic-showtime-card:focus-visible .st-cta-icon {
  transform: scale(1.15) rotate(-6deg);
}

/* ============================================================
   RESPONSIVE REFINEMENT ACROSS VIEWPORTS
   ============================================================ */
@media (max-width: 639.98px) {
  .cinematic-showtimes {
    width: 100%;
    max-width: 100%;
    gap: 9px !important;
    margin-top: 12px;
  }
  .cinematic-showtime-card {
    min-height: 148px !important;
    padding: 13px 12px 11px !important;
    border-radius: 20px !important;
  }
  .cinematic-showtime-card .st-day-name {
    font-size: 12px;
  }
  .cinematic-showtime-card .st-date-val {
    font-size: 10.5px;
  }
  .cinematic-showtime-card .st-time-row {
    gap: 4px;
    margin-top: 3px;
  }
  .cinematic-showtime-card .st-time-num {
    font-size: 21px !important;
  }
  .cinematic-showtime-card .st-time-ampm {
    font-size: 11px;
  }
  .cinematic-showtime-card .st-seam {
    margin: 8px 0 7px !important;
  }
  .cinematic-showtime-card .st-meta-row {
    margin-bottom: 9px !important;
    gap: 4px;
  }
  .cinematic-showtime-card .st-avail-chip {
    font-size: 9.5px;
    padding: 2.5px 7px !important;
    gap: 4px;
  }
  .cinematic-showtime-card .st-dot {
    width: 4.5px;
    height: 4.5px;
  }
  .cinematic-showtime-card .st-price-amount {
    font-size: 13.5px !important;
  }
  .cinematic-showtime-card .st-price-label {
    font-size: 9.5px;
  }
  .cinematic-showtime-card .st-cta-btn {
    min-height: 36px !important;
    padding: 0 10px !important;
    font-size: 12px !important;
    gap: 5px;
  }
  .cinematic-showtime-card .st-cta-icon {
    font-size: 11px;
  }
}

@media (max-width: 375px) {
  .cinematic-showtimes {
    gap: 7px !important;
  }
  .cinematic-showtime-card {
    min-height: 140px !important;
    padding: 10px 9px 9px !important;
    border-radius: 18px !important;
  }
  .cinematic-showtime-card .st-day-name {
    font-size: 11.5px;
  }
  .cinematic-showtime-card .st-date-val {
    font-size: 9.5px;
  }
  .cinematic-showtime-card .st-time-num {
    font-size: 19px !important;
  }
  .cinematic-showtime-card .st-time-ampm {
    font-size: 10px;
  }
  .cinematic-showtime-card .st-seam {
    margin: 6px 0 5px !important;
  }
  .cinematic-showtime-card .st-meta-row {
    margin-bottom: 7px !important;
    gap: 3px;
  }
  .cinematic-showtime-card .st-avail-chip {
    font-size: 8.5px;
    padding: 2px 5px !important;
    gap: 3px;
  }
  .cinematic-showtime-card .st-price-amount {
    font-size: 12px !important;
  }
  .cinematic-showtime-card .st-price-label {
    font-size: 9px;
  }
  .cinematic-showtime-card .st-cta-btn {
    min-height: 33px !important;
    font-size: 11px !important;
    padding: 0 8px !important;
  }
}

@media (max-width: 330px) {
  .cinematic-showtimes {
    gap: 5px !important;
  }
  .cinematic-showtime-card {
    padding: 9px 7px 7px !important;
    border-radius: 16px !important;
  }
  .cinematic-showtime-card .st-day-name {
    font-size: 10.5px;
  }
  .cinematic-showtime-card .st-date-val {
    font-size: 8.5px;
  }
  .cinematic-showtime-card .st-time-num {
    font-size: 17px !important;
  }
  .cinematic-showtime-card .st-avail-chip {
    font-size: 8px;
    padding: 2px 4px !important;
  }
  .cinematic-showtime-card .st-cta-btn {
    min-height: 30px !important;
    font-size: 10px !important;
  }
}
</style>

@if(request()->routeIs('home'))
<div class="cinema-home" dir="rtl">
    <div class="curtain-sequence" id="cinema-curtain" aria-hidden="true" data-state="closed">
        <div class="curtain-leaf curtain-leaf--left"></div>
        <div class="curtain-leaf curtain-leaf--right"></div>
        <div class="curtain-seam"></div>
    </div>
    <section class="cinema-opening" id="opening" aria-labelledby="team-title">
        <div class="cinema-plate cinema-plate--opening" aria-hidden="true">
            <img src="{{ asset('images/cinematic/theatre/empty-stage.webp') }}" alt="" width="960" height="1440" fetchpriority="high" decoding="async">
        </div>
        <div class="opening-scrim" aria-hidden="true"></div>
        <div class="opening-blackout" id="opening-blackout" aria-hidden="true"></div>
        <div class="opening-copy">
            <h1 id="team-title" class="sr-only">فريق الصرخة المسرحي</h1>
            <div class="logo-anchor"><div class="logo-reveal"><img src="{{ asset('images/sarkha-logo.png') }}" alt="شعار فريق الصرخة المسرحي" width="112" height="112" fetchpriority="high"></div></div>
            <div class="opening-identity" aria-label="من بيان الفريق">
                <p class="opening-phrase opening-phrase--first" data-opening-phrase="1">لا نريد سوا حواسك</p>
                <p class="opening-phrase" data-opening-phrase="2">نجول</p>
                <p class="opening-phrase" data-opening-phrase="3">نصرخ</p>
                <p class="opening-phrase opening-phrase--final" data-opening-phrase="4">فيزداد العقل وعيًا</p>
            </div>
        </div>
        <a class="opening-archive" href="{{ route('archive') }}">الأرشيف <span aria-hidden="true">↗</span></a>
    </section>

    <section class="camera-track {{ $featuredShow && $featuredTime ? 'has-featured-production' : 'has-no-featured-production' }}" id="camera-journey" aria-label="العرض القادم" inert>
        <div class="camera-viewport">
            <div class="camera-world" id="camera-world" aria-hidden="true">
                <img src="{{ asset('images/cinematic/theatre/empty-stage.webp') }}" alt="" width="960" height="1440" decoding="async">
                <div class="camera-grade"></div>
            </div>
            <div class="website-world" id="website-world" aria-hidden="true"></div>
            <div class="camera-plane camera-auditorium" id="camera-auditorium" aria-hidden="true"></div>
            <div class="camera-plane camera-aisle" id="camera-aisle" aria-hidden="true"></div>
            <div class="camera-plane camera-spotlight" id="camera-spotlight" aria-hidden="true"></div>
            <div class="camera-plane camera-proscenium" id="camera-proscenium" aria-hidden="true"></div>
            @if($featuredShow && $featuredTime)
                <article class="stage-production" id="stage-production" aria-labelledby="featured-title" inert>
                    @if($featuredPoster)
                        <div class="production-poster-wrap">
                            <span class="production-light" aria-hidden="true"></span>
                            <div class="production-poster-frame">
                                <img src="{{ $featuredPoster }}" alt="ملصق العرض {{ $featuredShow->title }}" width="640" height="900" loading="eager" decoding="sync" fetchpriority="high">
                            </div>
                        </div>
                    @else
                        <div class="production-poster-missing" role="img" aria-label="لم ينشر الفريق ملصق هذا العرض بعد">
                            <span>ملصق العرض لم يُنشر بعد</span>
                        </div>
                    @endif
                    <div class="production-information">
                        <h2 id="featured-title" data-production-step="title">{{ $featuredShow->title }}</h2>
                        @if($cleanDesc)<p class="production-description" data-production-step="description">{{ $cleanDesc }}</p>@endif
                        @if($locationUrl)
                            <div class="production-location-wrap" data-production-step="location">
                                <a href="{{ $locationUrl }}" target="_blank" rel="noopener noreferrer" class="theatre-location-btn">
                                    <span class="location-pin" aria-hidden="true">📍</span>
                                    <span>{{ $locationLabel }}</span>
                                    <span class="location-external" aria-hidden="true">↗</span>
                                </a>
                            </div>
                        @endif
                        <div class="production-status-wrap sr-only" data-production-step="status">
                            <span class="ticket-state {{ $featuredSoldOut ? 'ticket-state--soldout' : ($featuredCritical ? 'ticket-state--critical' : ($featuredLow ? 'ticket-state--low' : 'ticket-state--healthy')) }}" role="status">
                                @if($featuredSoldOut) اكتملت التذاكر
                                @elseif($featuredCritical) متبقي {{ $featuredRemaining }} {{ $featuredRemaining === 1 ? 'تذكرة' : 'تذاكر' }} فقط
                                @elseif($featuredLow) متبقي عدد محدود من التذاكر
                                @else متاح للحجز الآن @endif
                            </span>
                            @if($featuredBookableTimes->count() > 1)
                                <a href="#showtime-modal-{{ $featuredShow->id }}" data-open-modal="showtime-modal-{{ $featuredShow->id }}" class="sr-only">{{ $featuredBookableTimes->count() }} مواعيد متاحة</a>
                            @endif
                        </div>
                    </div>
                    @if($featuredBookableTimes->isNotEmpty() && !$featuredSoldOut)
                        <div class="cinematic-showtimes" data-production-step="showtimes" aria-label="مواعيد الحجز المتاحة">
                            @foreach($featuredBookableTimes->take(2) as $showtime)
                                @php
                                    $showtimeClock = $showtime->time ? \Carbon\Carbon::parse($showtime->time) : null;
                                    $showtimeRemaining = (int) $showtime->homepage_remaining_tickets;
                                    $showtimeCritical = $showtimeRemaining > 0 && $showtimeRemaining <= 10;
                                    $showtimeLow = !$showtimeCritical && ($showtimeRemaining <= 25 || ((int) $showtime->total_tickets > 0 && $showtimeRemaining / (int) $showtime->total_tickets <= .25));
                                    $arabicDays = [
                                        'Sunday' => 'الأحد',
                                        'Monday' => 'الإثنين',
                                        'Tuesday' => 'الثلاثاء',
                                        'Wednesday' => 'الأربعاء',
                                        'Thursday' => 'الخميس',
                                        'Friday' => 'الجمعة',
                                        'Saturday' => 'السبت',
                                    ];
                                    $rawDay = $showtime->date ? $showtime->date->format('l') : '';
                                    $arabicDayName = $arabicDays[$rawDay] ?? ($showtime->date?->translatedFormat('l') ?? '');
                                @endphp
                                <a class="cinematic-showtime-card"
                                   data-showtime-card
                                   href="{{ route('bookings.create', $showtime) }}"
                                   aria-label="احجز عرض {{ $featuredShow->title }} يوم {{ $showtime->date?->format('d/m/Y') }}">
                                    
                                    {{-- 1. Date --}}
                                    <div class="st-date-row">
                                        <span class="st-day-name">{{ $arabicDayName }}</span>
                                        <span class="st-date-val">{{ $showtime->date?->format('d/m/Y') }}</span>
                                    </div>

                                    {{-- 2. Time --}}
                                    <div class="st-time-row">
                                        <span class="st-time-num">{{ $showtimeClock ? $showtimeClock->format('g:i') : '' }}</span>
                                        <span class="st-time-ampm">{{ $showtimeClock ? ($showtimeClock->format('A') === 'PM' ? 'مساءً' : 'صباحاً') : '' }}</span>
                                    </div>

                                    {{-- Glass Seam Divider --}}
                                    <div class="st-seam" aria-hidden="true"></div>

                                    {{-- 3 & 4. Meta Row: Availability & Price --}}
                                    <div class="st-meta-row">
                                        <div class="st-avail-chip {{ $showtimeCritical ? 'is-critical' : ($showtimeLow ? 'is-low' : 'is-healthy') }}">
                                            <span class="st-dot" aria-hidden="true"></span>
                                            <span>
                                                @if($showtimeCritical) متبقي {{ $showtimeRemaining }} فقط
                                                @elseif($showtimeLow) متبقي عدد محدود
                                                @else متاح للحجز @endif
                                            </span>
                                        </div>

                                        @if($showtime->ticket_price !== null)
                                            <div class="st-price-block">
                                                <span class="st-price-amount">{{ number_format((float) $showtime->ticket_price, 0) }}</span>
                                                <span class="st-price-label">ج.م</span>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- 5. Booking CTA inside Card --}}
                                    <div class="st-cta-btn" data-production-step="action">
                                        <span>احجز الآن</span>
                                        <span class="st-cta-icon" aria-hidden="true">🎟️</span>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                        @if($featuredBookableTimes->count() > 2)
                            <div class="showtimes-more-wrap">
                                <a href="#showtime-modal-{{ $featuredShow->id }}" data-open-modal="showtime-modal-{{ $featuredShow->id }}" class="showtimes-more-link" role="button">
                                    <span>+ {{ $featuredBookableTimes->count() - 2 }} مواعيد أخرى متاحة (عرض الكل)</span>
                                    <span aria-hidden="true">↗</span>
                                </a>
                            </div>
                        @endif
                    @elseif($featuredSoldOut)
                        <div class="production-soldout-action">
                            <a class="booking-cta booking-cta--quiet" data-production-step="action" href="{{ route('shows.show', $featuredShow) }}">تفاصيل العرض</a>
                        </div>
                    @endif
                </article>
            @else
                <div class="stage-intermission" id="stage-intermission">
                    <p class="intermission-kicker">الخشبة جاهزة</p>
                    <p class="intermission-title">خشبة المسرح تستعد للعرض القادم</p>
                    <p class="intermission-copy">لا يوجد عرض حقيقي مُعلن ومتاح للحجز في الوقت الحالي.</p>
                    <a href="{{ route('archive') }}">اكتشف العروض السابقة <span aria-hidden="true">↗</span></a>
                </div>
            @endif
        </div>
    </section>

    @if($additionalShows->isNotEmpty())
    <section class="upcoming-section" id="shows" aria-labelledby="upcoming-heading">
        <header class="section-heading">
            <div>
                <div class="upcoming-kicker-badge" aria-hidden="true"><span class="kicker-dot"></span> على خشبة المسرح</div>
                <p class="sr-only">على خشبة المسرح</p>
                <h2 id="upcoming-heading">العروض القادمة</h2>
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
                                <p class="upcoming-date upcoming-date--pending">لم تُحدد مواعيد بعد · سيتم الإعلان عنها قريباً</p>
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
        $modalShows = collect([$featuredShow])->filter()->merge($additionalShows)->unique('id');
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
