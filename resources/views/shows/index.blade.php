@extends('layouts.app')

@section('title', 'فريق الصرخة المسرحي — العروض المتاحة')

@section('content')

{{-- ============================================================
     PHASE 2A: GLOBAL STAGE ARCHITECTURE, 3D CURTAIN OVERTURE & SCROLL STORYBOARD
     ------------------------------------------------------------
     Principle: "Cinematic When Exploring. Effortless When Booking."
     Scene 01: Proscenium Overture & Heroic Slogan Choreography
     Scene 02: Featured Show Spotlight Monolith
     Scene 03: Active Shows Grid with 3D Playbill Cards & Progressive Scarcity
============================================================ --}}

{{-- 3D Pleated Theatre Curtain Overture (#elsar5a-curtain-overlay) --}}
@if(request()->routeIs('home') || request()->is('/'))
    <div id="elsar5a-curtain-overlay" class="elsar5a-curtain-overlay" data-curtain-state="closed" data-curtain-enabled="true" aria-hidden="true">
        <div class="elsar5a-curtain-seam"></div>
        <div class="elsar5a-curtain-wing elsar5a-curtain-wing--left"></div>
        <div class="elsar5a-curtain-wing elsar5a-curtain-wing--right"></div>
    </div>
    <button id="elsar5a-curtain-skip" class="elsar5a-curtain-skip" type="button" aria-label="تخطي الافتتاحية">
        تخطي الافتتاحية ↓
    </button>
@endif

<style>
    /* Supporting styles for ghost CTAs and empty states */
    .hp-cta-ghost {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: .7rem 1.35rem;
        min-height: 48px;
        border-radius: 9999px;
        font-weight: 600;
        font-size: .95rem;
        color: #f1f5fb;
        background: rgba(255,255,255,.05);
        border: 1px solid rgba(255,255,255,.18);
        transition: background .25s, border-color .25s, transform .25s;
    }
    .hp-cta-ghost:hover {
        background: rgba(255,255,255,.10);
        border-color: rgba(251,191,36,.45);
        transform: translateY(-2px);
    }
    .hp-empty {
        position: relative;
        text-align: center;
        padding: 3.5rem 1.5rem;
        border-radius: 1.5rem;
        overflow: hidden;
        background:
            radial-gradient(ellipse at top, rgba(251,191,36,.09), transparent 60%),
            radial-gradient(ellipse at bottom, rgba(220,38,38,.08), transparent 60%),
            rgba(2,6,23,.75);
        border: 1px solid rgba(255,255,255,.10);
    }
    .hp-empty::after {
        content: "";
        position: absolute;
        inset-inline: 0;
        bottom: 0;
        height: 1px;
        background: linear-gradient(to left, transparent, rgba(251,191,36,.4), transparent);
    }
    .hp-section-eyebrow {
        font-size: 11px;
        letter-spacing: .24em;
        text-transform: uppercase;
        color: rgba(252,211,77,.8);
    }
    .hp-section-title {
        font-family: var(--font-display);
        font-size: clamp(1.4rem, 4vw, 2.2rem);
        font-weight: 800;
        letter-spacing: 0;
        background: linear-gradient(135deg, #fde68a 0%, #fbbf24 50%, #f59e0b 100%);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        text-shadow: 0 0 24px rgba(250,204,21,.18);
    }
</style>

@php
    $now = now();
    $nextCurtainIso = null;
    $allShowTimes = $shows->flatMap->showTimes;
    $upcomingShowTimes = $allShowTimes
        ->filter(function ($st) use ($now) {
            if (!$st->date) return false;
            $timeStr = $st->time ?? '00:00:00';
            try {
                $dt = \Carbon\Carbon::parse($st->date->format('Y-m-d') . ' ' . $timeStr);
                return $dt->gt($now) && !$st->is_sold_out && $st->available_tickets > 0;
            } catch (\Exception $e) {
                return false;
            }
        })
        ->sortBy(function ($st) {
            $timeStr = $st->time ?? '00:00:00';
            return \Carbon\Carbon::parse($st->date->format('Y-m-d') . ' ' . $timeStr)->timestamp;
        });

    $nextShowTime = $upcomingShowTimes->first();
    if ($nextShowTime) {
        $nextCurtainIso = \Carbon\Carbon::parse(
            $nextShowTime->date->format('Y-m-d') . ' ' . ($nextShowTime->time ?? '00:00:00')
        )->toIso8601String();
    }
@endphp

{{-- ===================== SCENE 01: PROSCENIUM OVERTURE ===================== --}}
<section class="stage-overture-section">

    {{-- Hero Logo Pedestal --}}
    <div class="stage-logo-pedestal">
        <img src="{{ asset('images/sarkha-logo.png') }}"
             alt="فريق الصرخة المسرحي"
             class="stage-hero-logo"
             loading="eager">
    </div>

    {{-- Eyebrow Pill --}}
    <div class="stage-eyebrow-pill">
        <span class="inline-block w-2 h-2 rounded-full bg-amber-400 animate-pulse" aria-hidden="true"></span>
        <span>عرض مسرحي حقيقي · تجربة لا تُنسى</span>
    </div>

    {{-- Main Display Title --}}
    <h1 class="stage-main-title">
        فرقة الصرخة المسرحية
    </h1>

    {{-- Choreographed Primary Slogan --}}
    <p class="stage-slogan-primary">
        عروض مسرحية حية تجمع بين <span class="stage-slogan-highlight">الفن، الشغف، والأثر الحقيقي</span>
    </p>

    {{-- Whispered Brand Manifesto --}}
    <p class="stage-slogan-whisper">
        « مسرح يصنع الأثر، وصوت ينبض بالحياة... »
    </p>

    {{-- Primary CTA Row --}}
    <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
        @if(!$shows->isEmpty())
            <a href="#shows" class="stage-cta-primary" aria-label="احجز تذكرتك الآن">
                <span aria-hidden="true">🎟</span>
                <span>احجز تذكرتك الآن</span>
            </a>
        @endif
        <a href="{{ route('archive') }}" class="hp-cta-ghost" aria-label="أرشيف العروض السابقة">
            <span aria-hidden="true">🎭</span>
            <span>أرشيف العروض</span>
        </a>
    </div>

    {{-- Honest Curtain Countdown Strip (Only renders when real future showtime exists) --}}
    @if($nextCurtainIso)
        <div class="mt-6 inline-flex items-center gap-2.5 sm:gap-3 px-4 py-2 rounded-full bg-slate-900/90 border border-amber-400/40 text-xs sm:text-sm text-gray-200 shadow-[0_0_24px_rgba(251,191,36,0.18)]"
             data-next-curtain-iso="{{ $nextCurtainIso }}"
             aria-label="العد التنازلي للعرض القادم">
            <span class="inline-block w-2 h-2 rounded-full bg-amber-400 animate-ping" aria-hidden="true"></span>
            <span class="font-bold text-amber-300">يرفع الستار للعرض القادم خلال:</span>
            <span class="tabular-nums-ltr font-mono font-bold text-white bg-black/60 px-2 py-0.5 rounded border border-amber-400/30 flex items-center gap-1">
                <span data-cd-days>00d</span>
                <span class="text-amber-400/60">:</span>
                <span data-cd-hours>00h</span>
                <span class="text-amber-400/60">:</span>
                <span data-cd-mins>00m</span>
            </span>
        </div>
    @endif

    {{-- Golden Thread Cue --}}
    <div class="stage-scroll-thread" aria-hidden="true"></div>
</section>


{{-- ===================== SCENE 02: FEATURED SHOW SPOTLIGHT MONOLITH ===================== --}}
@if($shows->isNotEmpty())
    @php
        $featuredShow = $shows->first();
        $ftTimes = $featuredShow->showTimes;
        $ftHasTimes = $ftTimes->isNotEmpty();
        $ftTotalAvail = $ftTimes->sum('available_tickets');
        $ftTotalCap = $ftTimes->sum('total_tickets');
        $ftAllSoldOut = $ftHasTimes && $ftTimes->every(fn($st) => $st->is_sold_out || $st->available_tickets <= 0);

        if (!$ftHasTimes || $ftAllSoldOut || $ftTotalAvail <= 0) {
            $ftBadgeClass = 'scarcity-badge--soldout';
            $ftBadgeText = 'اكتملت التذاكر';
        } elseif ($ftTotalAvail <= 10) {
            $ftBadgeClass = 'scarcity-badge--critical';
            $ftBadgeText = 'متبقي ' . $ftTotalAvail . ' ' . ($ftTotalAvail == 1 ? 'تذكرة' : ($ftTotalAvail <= 10 ? 'تذاكر' : 'تذكرة')) . ' فقط';
        } elseif ($ftTotalAvail <= 25 || ($ftTotalCap > 0 && ($ftTotalAvail / $ftTotalCap) <= 0.25)) {
            $ftBadgeClass = 'scarcity-badge--low';
            $ftBadgeText = 'متبقي عدد محدود من التذاكر';
        } else {
            $ftBadgeClass = 'scarcity-badge--healthy';
            $ftBadgeText = 'متاح للحجز الآن';
        }
    @endphp

    <div class="stage-scene-reveal stage-featured-monolith mb-12">
        <div class="grid md:grid-cols-12 gap-0 items-stretch">
            {{-- Poster Column --}}
            @if($featuredShow->poster_path)
                <div class="md:col-span-5 relative min-h-[280px] md:min-h-[420px] bg-black/60 overflow-hidden flex items-center justify-center border-b md:border-b-0 md:border-l border-white/10">
                    <img src="{{ $featuredShow->poster_path }}"
                         alt="{{ $featuredShow->title }}"
                         loading="eager"
                         class="w-full h-full object-cover md:object-contain max-h-[480px]">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent md:hidden"></div>
                </div>
            @endif

            {{-- Details Column --}}
            <div class="{{ $featuredShow->poster_path ? 'md:col-span-7' : 'md:col-span-12' }} p-6 sm:p-8 md:p-10 flex flex-col justify-between space-y-6">
                <div class="space-y-4">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-400/15 border border-amber-400/60 text-xs font-bold text-amber-300">
                            <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse" aria-hidden="true"></span>
                            عرض الافتتاح المميز · FEATURED
                        </span>
                        {{-- Progressive Scarcity Badge --}}
                        <span class="scarcity-badge {{ $ftBadgeClass }}">
                            {{ $ftBadgeText }}
                        </span>
                    </div>

                    <h2 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-white leading-tight font-display">
                        {{ $featuredShow->title }}
                    </h2>

                    <p class="text-sm sm:text-base text-gray-300 leading-relaxed whitespace-pre-line max-w-2xl">
                        {{ $featuredShow->description }}
                    </p>

                    {{-- Next upcoming showtimes preview --}}
                    @if($featuredShow->showTimes->isNotEmpty())
                        <div class="space-y-2 pt-2">
                            <p class="text-xs font-semibold text-gray-400 flex items-center gap-1.5">
                                <span aria-hidden="true">🕒</span>
                                المواعيد القادمة للعرض:
                            </p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($featuredShow->showTimes->take(3) as $st)
                                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white/5 border border-white/10 text-xs text-gray-200">
                                        <span class="font-medium">
                                            {{ $st->date ? $st->date->format('d/m/Y') : '' }}
                                            @if($st->time)
                                                • {{ \Carbon\Carbon::parse($st->time)->format('g:i A') }}
                                            @endif
                                        </span>
                                        <span class="text-amber-400 font-bold">
                                            {{ $st->ticket_price }} ج
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="pt-4 border-t border-white/10 flex items-center justify-between flex-wrap gap-3">
                    <div class="text-xs text-gray-400">
                        @if($featuredShow->showTimes->isNotEmpty())
                            <span>يبدأ من <strong class="text-amber-300 text-base font-bold">{{ $featuredShow->showTimes->min('ticket_price') }} ج</strong> للتذكرة</span>
                        @endif
                    </div>
                    <a href="{{ route('shows.show', $featuredShow) }}"
                       class="stage-cta-primary text-sm sm:text-base">
                        <span>احجز مقعدك في العرض 🎟</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
@endif


{{-- ===================== SCENE 03: ACTIVE SHOWS GRID ===================== --}}
<section id="shows" class="space-y-6 scroll-mt-24">

    <header class="space-y-1 mb-2">
        <p class="hp-section-eyebrow text-center md:text-right">
            SHOWS · العروض الحالية
        </p>
        <div class="flex items-center justify-between flex-wrap gap-3">
            <h2 class="hp-section-title">العروض المتاحة</h2>
            @if(!$shows->isEmpty())
                <span class="text-xs px-3 py-1 rounded-full bg-white/5 border border-white/10 text-gray-300">
                    <span aria-hidden="true">🎭</span>
                    {{ $shows->count() }} {{ $shows->count() === 1 ? 'عرض متاح' : 'عروض متاحة' }}
                </span>
            @endif
        </div>
    </header>

    @if($shows->isEmpty())

        {{-- Cinematic Empty State --}}
        <div class="hp-empty stage-scene-reveal">
            <div class="text-5xl mb-3" aria-hidden="true">🎭</div>
            <h3 class="text-lg font-semibold text-gray-200">المسرح في استراحة قصيرة</h3>
            <p class="mt-2 text-sm text-gray-400 max-w-sm mx-auto leading-relaxed">
                لسه مفيش عروض متاحة حاليًا — انتظرنا قريبًا، الستارة سترتفع من جديد ❤️
            </p>
            <div class="mt-5">
                <a href="{{ route('archive') }}" class="hp-cta-ghost">
                    <span aria-hidden="true">🎭</span>
                    <span>تصفّح الأرشيف</span>
                </a>
            </div>
        </div>

    @else

        <div class="grid md:grid-cols-2 gap-6">
            @foreach($shows as $show)
                @php
                    $sTimes = $show->showTimes;
                    $sHasTimes = $sTimes->isNotEmpty();
                    $sTotalAvail = $sTimes->sum('available_tickets');
                    $sTotalCap = $sTimes->sum('total_tickets');
                    $sAllSoldOut = $sHasTimes && $sTimes->every(fn($st) => $st->is_sold_out || $st->available_tickets <= 0);

                    if (!$sHasTimes || $sAllSoldOut || $sTotalAvail <= 0) {
                        $sBadgeClass = 'scarcity-badge--soldout';
                        $sBadgeText = 'اكتملت التذاكر';
                    } elseif ($sTotalAvail <= 10) {
                        $sBadgeClass = 'scarcity-badge--critical';
                        $sBadgeText = 'متبقي ' . $sTotalAvail . ' ' . ($sTotalAvail == 1 ? 'تذكرة' : ($sTotalAvail <= 10 ? 'تذاكر' : 'تذكرة')) . ' فقط';
                    } elseif ($sTotalAvail <= 25 || ($sTotalCap > 0 && ($sTotalAvail / $sTotalCap) <= 0.25)) {
                        $sBadgeClass = 'scarcity-badge--low';
                        $sBadgeText = 'متبقي عدد محدود من التذاكر';
                    } else {
                        $sBadgeClass = 'scarcity-badge--healthy';
                        $sBadgeText = 'متاح للحجز الآن';
                    }
                @endphp

                <article class="scream-card stage-scene-reveal p-5 flex flex-col justify-between group">
                    <div>
                        {{-- Poster --}}
                        @if($show->poster_path)
                            <div class="relative mb-4 rounded-xl overflow-hidden border border-white/10 bg-black/50 aspect-[16/10] sm:aspect-[16/9]">
                                <img src="{{ $show->poster_path }}"
                                     alt="{{ $show->title }}"
                                     loading="lazy"
                                     decoding="async"
                                     class="w-full h-full object-cover transition-transform duration-700 ease-out group-hover:scale-105">

                                {{-- Progressive Scarcity Badge on Poster --}}
                                <div class="absolute top-2 right-2">
                                    <span class="scarcity-badge {{ $sBadgeClass }} shadow-md">
                                        {{ $sBadgeText }}
                                    </span>
                                </div>
                            </div>
                        @else
                            <div class="flex justify-end mb-3">
                                <span class="scarcity-badge {{ $sBadgeClass }}">
                                    {{ $sBadgeText }}
                                </span>
                            </div>
                        @endif

                        <h3 class="text-lg md:text-xl font-bold text-white leading-snug">
                            {{ $show->title }}
                        </h3>

                        <p class="mt-2 text-sm text-gray-300/90 leading-relaxed line-clamp-3">
                            {{ $show->description }}
                        </p>

                        {{-- Upcoming Showtimes --}}
                        <div class="mt-4 space-y-2 text-xs text-gray-400">
                            <p class="font-semibold text-gray-300 flex items-center gap-1.5">
                                <span aria-hidden="true">🕒</span>
                                المواعيد القادمة:
                            </p>
                            <ul class="space-y-1.5">
                                @forelse($show->showTimes->take(2) as $time)
                                    <li class="flex items-center justify-between gap-2 bg-white/5 hover:bg-white/10 rounded-lg px-3 py-2 border border-white/5 transition">
                                        <span class="font-medium text-gray-200">
                                            {{ $time->date ? $time->date->format('d/m/Y') : '' }}
                                            @if($time->time)
                                                <span class="text-gray-500 mx-1">•</span>
                                                {{ \Carbon\Carbon::parse($time->time)->format('g:i A') }}
                                            @endif
                                        </span>
                                        <span class="text-amber-300 font-bold whitespace-nowrap">
                                            {{ $time->ticket_price }}
                                            <span class="text-[10px] text-amber-200/70">ج</span>
                                        </span>
                                    </li>
                                @empty
                                    <li class="text-[11px] text-gray-500 px-3 py-1">
                                        لا توجد مواعيد متاحة حاليًا لهذا العرض.
                                    </li>
                                @endforelse
                            </ul>
                        </div>
                    </div>

                    {{-- Card Footer & CTA --}}
                    <div class="pt-4 mt-4 border-t border-white/10 flex justify-between items-center">
                        <span class="text-xs text-gray-400 flex items-center gap-1.5">
                            <span class="inline-block w-2 h-2 rounded-full bg-emerald-400/80 animate-pulse" aria-hidden="true"></span>
                            {{ $show->showTimes->count() }} {{ $show->showTimes->count() === 1 ? 'موعد' : 'مواعيد' }}
                        </span>
                        <a href="{{ route('shows.show', $show) }}"
                           class="inline-flex items-center gap-1.5 text-sm font-bold bg-amber-400 text-black px-4 py-2 rounded-full hover:bg-amber-300 hover:shadow-[0_8px_24px_-8px_rgba(250,204,21,0.7)] transition">
                            تفاصيل &amp; حجز
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="w-3.5 h-3.5" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    </div>
                </article>
            @endforeach
        </div>

    @endif

</section>

@endsection
