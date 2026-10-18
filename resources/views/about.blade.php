@extends('layouts.app')

@section('title', 'عن فريق الصرخة المسرحي — هويتنا ورسالتنا')
@section('meta_description', 'تعرّف على قصة فريق الصرخة المسرحي منذ عام 2009 — رسالتنا، أهدافنا، وفلسفتنا الفنية: نجول نصرخ فيزداد العقل وعيًا.')

@section('content')
@php
    $foundedYear = $about->founded_year ?? '2009';
    $description = $about->description ?? '';

    // Parse description lines if present
    $rawLines = array_values(array_filter(
        array_map('trim', preg_split('/\r\n|\r|\n/', $description)),
        fn ($l) => $l !== ''
    ));

    // Social links
    $yt = $about->youtube ?? 'https://youtube.com/@sar5ateam611?si=ioONwMrKfYJx-Bks';
    $fb = $about->facebook ?? 'https://www.facebook.com/share/17WQUehRZG/?mibextid=wwXIfr';
    $ig = $about->instagram ?? 'https://www.instagram.com/elsar5ateam?igsh=cjc0aG94Nmk1YTBt';
@endphp

<article class="about-cinematic-page" dir="rtl">

    {{-- Atmospheric Stage Lighting Background (Visual only, scoped) --}}
    <div class="about-stage-ambience" aria-hidden="true">
        <div class="about-glow about-glow-top"></div>
        <div class="about-glow about-glow-burgundy"></div>
        <div class="about-glow about-glow-amber"></div>
        <div class="about-stage-dust"></div>
    </div>

    {{-- Main Container --}}
    <div class="about-stage-container">

        {{-- ========================================================
             CHAPTER 01 — THE IDENTITY (الهوية)
             ======================================================== --}}
        <header class="about-chapter about-chapter-identity about-reveal" id="identity">
            {{-- Eyebrow badge --}}
            <div class="about-eyebrow">
                <span class="about-pulse-dot" aria-hidden="true"></span>
                <span class="about-eyebrow-text">فريق الصرخة المسرحي</span>
                <span class="about-eyebrow-sep" aria-hidden="true">•</span>
                <span class="about-eyebrow-year">تأسس عام {{ $foundedYear }}</span>
            </div>

            {{-- Chapter numeral & title --}}
            <div class="about-chapter-tag">
                <span class="about-tag-num">٠١</span>
                <span class="about-tag-line"></span>
                <span class="about-tag-label">الهوية المسرحية</span>
            </div>

            <h1 class="about-hero-title">
                <span class="about-title-lead">عـن</span>
                <span class="about-title-hero">الصَّرْخَة</span>
            </h1>

            <p class="about-hero-manifesto">
                صوتٌ مسرحيٌّ حر، ينطلق من خشبة المسرح ليخاطب الوجدان والفكر،<br class="hidden sm:inline">
                ويعيد الاعتبار للفن كرسالة بناء ووعي إنساني.
            </p>

            <div class="about-affiliation-chip">
                <svg class="about-chip-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                </svg>
                <span>كنيسة الشهيد العظيم مارجرجس بأبي طاقية — شبرا</span>
            </div>

            <div class="about-hero-divider" aria-hidden="true">
                <span class="about-divider-line"></span>
                <span class="about-divider-star">✦</span>
                <span class="about-divider-line"></span>
            </div>
        </header>

        {{-- ========================================================
             CHAPTER 02 — WHO WE ARE (من نحن؟)
             ======================================================== --}}
        <section class="about-chapter about-chapter-who about-reveal" id="who-we-are" aria-labelledby="heading-who">
            <div class="about-chapter-tag">
                <span class="about-tag-num">٠٢</span>
                <span class="about-tag-line"></span>
                <span class="about-tag-label" id="heading-who">من نحن؟</span>
            </div>

            <div class="about-editorial-grid">
                {{-- Prominent Pull Quote --}}
                <div class="about-pullquote-card">
                    <span class="about-quote-glyph" aria-hidden="true">“</span>
                    <blockquote class="about-quote-text">
                        انطلقنا منذ البداية إيمانًا بدور المسرح كوسيلة فنية تُسخَّر للخدمة، وصوتًا واعيًا يسهم في تنمية الفكر وبناء الوعي الحقيقي.
                    </blockquote>
                    <cite class="about-quote-author">
                        <span class="about-author-name">ميثاق فريق الصرخة</span>
                        <span class="about-author-sub">منذ عام {{ $foundedYear }}</span>
                    </cite>
                </div>

                {{-- Editorial Narrative Block --}}
                <div class="about-narrative-card">
                    <div class="about-card-badge">
                        <span class="about-badge-dot"></span>
                        <span>فكر ورسالة</span>
                    </div>

                    <p class="about-narrative-paragraph">
                        <strong>فريق الصرخة المسرحي</strong> هو فريق مسرحي تابع لكنيسة الشهيد العظيم جؤرجيوس بأبي طاقية - شبرا، كُرّست جميع جهوده الفنية لخدمة المجتمع وبناء الشخصية المتوازنة فكرياً وروحياً.
                    </p>

                    <p class="about-narrative-paragraph">
                        على مدار سنوات طويلة من العمل المسرحي المتواصل، التزم الفريق بتقديم أعمال فنية هادفة تتسم بالصدق والعمق، وتخاطب الإنسان في مختلف مراحله العمرية بمصداقية تُحاكي همومه وتطلعاته.
                    </p>

                    <div class="about-stats-row">
                        <div class="about-stat-item">
                            <span class="about-stat-value" dir="ltr">+{{ max(15, (int)date('Y') - (int)$foundedYear) }}</span>
                            <span class="about-stat-label">عاماً من العطاء المسرحي</span>
                        </div>
                        <div class="about-stat-divider" aria-hidden="true"></div>
                        <div class="about-stat-item">
                            <span class="about-stat-value" dir="ltr">100%</span>
                            <span class="about-stat-label">خدمة فنية غير ربحية</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ========================================================
             CHAPTER 03 — OUR MESSAGE (ثلاثية الرسالة والعمل)
             ======================================================== --}}
        <section class="about-chapter about-chapter-message about-reveal" id="our-message" aria-labelledby="heading-message">
            <div class="about-chapter-header-center">
                <div class="about-chapter-tag justify-center">
                    <span class="about-tag-num">٠٣</span>
                    <span class="about-tag-line"></span>
                    <span class="about-tag-label" id="heading-message">فلسفتنا ورسالتنا</span>
                </div>
                <h2 class="about-section-heading">ثلاثية الصرخة: المجتمع • العقول • التغيير</h2>
                <p class="about-section-sub">كيف نترجم إيماننا بالمسرح إلى أثر ملموس في حياة الناس والخشبة</p>
            </div>

            <div class="about-pillars-stack">
                {{-- Pillar 1: المجتمع --}}
                <div class="about-pillar-card about-pillar-community">
                    <div class="about-pillar-header">
                        <div class="about-pillar-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                            </svg>
                        </div>
                        <div class="about-pillar-titles">
                            <span class="about-pillar-kicker">المجتمع والخدمة</span>
                            <h3 class="about-pillar-title">الخدمة بوصفها رسالة حب غير ربحية</h3>
                        </div>
                    </div>
                    <div class="about-pillar-body">
                        <p>
                            يقوم فريق الصرخة المسرحي على مفهوم الخدمة بوصفها رسالة قبل أن تكون نشاطًا فنيًا؛ حيث تُقدَّم جميع أنشطته وعروضه دون السعي للربح المادي، إيمانًا راسخًا بأن الفن الواعي هو خدمة تُمنَح بمحبة لكل إنسان بلا قيد أو شرط.
                        </p>
                    </div>
                </div>

                {{-- Pillar 2: العقول --}}
                <div class="about-pillar-card about-pillar-minds">
                    <div class="about-pillar-header">
                        <div class="about-pillar-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.516 0c.85.493 1.508 1.333 1.508 2.316V18" />
                            </svg>
                        </div>
                        <div class="about-pillar-titles">
                            <span class="about-pillar-kicker">العقول والإبداع</span>
                            <h3 class="about-pillar-title">احتضان المواهب وصقل الفكر لجميع الأعمار</h3>
                        </div>
                    </div>
                    <div class="about-pillar-body">
                        <p>
                            يخدم الفريق جميع الأعمار، بدءًا من المرحلة الابتدائية والإعدادية والثانوية وحتى المرحلة الجامعية؛ من خلال اكتشاف المواهب، وتنمية القدرات الإبداعية، وصقل الحس الفني والفكري، في إطار أصيل من الانضباط، والعمل الجماعي، والالتزام.
                        </p>
                    </div>
                </div>

                {{-- Pillar 3: التغيير (Majestic Full-Width Feature Card) --}}
                <div class="about-pillar-card about-pillar-change">
                    <div class="about-pillar-header">
                        <div class="about-pillar-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                            </svg>
                        </div>
                        <div class="about-pillar-titles">
                            <span class="about-pillar-kicker">أثر التغيير</span>
                            <h3 class="about-pillar-title">المسرح تجربة تُعاش.. وصوتٌ يوقظ العقل</h3>
                        </div>
                    </div>
                    <div class="about-pillar-body">
                        <p class="about-change-lead">
                            يؤمن الفريق بأن المسرح ليس مجرد عرض عابر يُقدَّم وينتهي، بل تجربة متكاملة تُعاش بكل الحواس، ورسالة تُحمَل في القلب، وصوت فكري واعٍ يوقظ العقل ويدعو إلى التأمل، الفهم، والتغيير الحقيقي.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ========================================================
             CHAPTER 04 — OUR JOURNEY (مسيرتنا المسرحية)
             ======================================================== --}}
        <section class="about-chapter about-chapter-journey about-reveal" id="our-journey" aria-labelledby="heading-journey">
            <div class="about-chapter-tag">
                <span class="about-tag-num">٠٤</span>
                <span class="about-tag-line"></span>
                <span class="about-tag-label" id="heading-journey">مسيرتنا المسرحية</span>
            </div>

            <h2 class="about-section-heading">محطات على درب الفن والوعي</h2>
            <p class="about-section-sub">مسيرة متصلة من التأسيس إلى الحاضر، ركيزتها الصدق والإخلاص للخشبة</p>

            <div class="about-timeline-stream">
                {{-- Milestone 1: 2009 --}}
                <div class="about-timeline-item">
                    <div class="about-timeline-marker">
                        <span class="about-marker-dot"></span>
                        <span class="about-marker-line"></span>
                    </div>
                    <div class="about-timeline-content">
                        <div class="about-timeline-badge">
                            <span>عام {{ $foundedYear }}</span>
                        </div>
                        <h3 class="about-timeline-title">شعلة البداية والتأسيس</h3>
                        <p class="about-timeline-desc">
                            انطلاق فريق الصرخة المسرحي بكنيسة الشهيد العظيم جؤرجيوس بأبي طاقية - شبرا، انطلاقًا من الرؤية الأولى: أن يكون المسرح منبراً للخدمة الهادفة وصوتاً يُسهم في تنمية الفكر والوعي.
                        </p>
                    </div>
                </div>

                {{-- Milestone 2: سنوات العمل المتواصل --}}
                <div class="about-timeline-item">
                    <div class="about-timeline-marker">
                        <span class="about-marker-dot"></span>
                        <span class="about-marker-line"></span>
                    </div>
                    <div class="about-timeline-content">
                        <div class="about-timeline-badge">
                            <span>مسيرة متواصلة</span>
                        </div>
                        <h3 class="about-timeline-title">أعمال هادفة وصقل للأجيال</h3>
                        <p class="about-timeline-desc">
                            سنوات من العمل الفني الجاد؛ إنتاج عروض مسرحية نوعية تتسم بالصدق والعمق، واحتضان أجيال متعاقبة من الأطفال والشباب وصقل مواهبهم في بيئة تسودها المحبة والانضباط.
                        </p>
                    </div>
                </div>

                {{-- Milestone 3: اليوم والمستقبل --}}
                <div class="about-timeline-item">
                    <div class="about-timeline-marker">
                        <span class="about-marker-dot"></span>
                    </div>
                    <div class="about-timeline-content">
                        <div class="about-timeline-badge about-badge-active">
                            <span>الحاضر والغد</span>
                        </div>
                        <h3 class="about-timeline-title">رسالة تتجدد مع كل صرخة</h3>
                        <p class="about-timeline-desc">
                            نواصل رحلتنا الفنية بعروض متجددة وتجارب حية على خشبة المسرح، واضعين نصب أعيننا أن يظل المسرح دوماً منارة وعي، تجربة تُعاش، وصرخة توقظ العقول.
                        </p>
                    </div>
                </div>
            </div>
        </section>

    </div>
</article>

<style>
/* ==========================================================================
   ABOUT CINEMATIC EXPERIENCE — THEATRICAL EDITORIAL REDESIGN
   Strictly scoped to .about-cinematic-page so no global styles leak out.
   ========================================================================== */

.about-cinematic-page {
    --ab-gold: #fbbf24;
    --ab-gold-bright: #fde68a;
    --ab-gold-deep: #d97706;
    --ab-gold-muted: rgba(214, 164, 91, 0.22);
    --ab-gold-soft: rgba(251, 191, 36, 0.08);
    --ab-gold-border: rgba(214, 164, 91, 0.32);

    --ab-ink-1: #ffffff;
    --ab-ink-2: #fff7e6;
    --ab-ink-3: #cbd5e1;
    --ab-ink-muted: #94a3b8;

    --ab-glass-bg: rgba(10, 16, 30, 0.65);
    --ab-glass-border: rgba(255, 255, 255, 0.07);
    --ab-glass-highlight: rgba(255, 255, 255, 0.03);

    --ab-ease: cubic-bezier(0.16, 1, 0.3, 1);

    position: relative;
    width: 100%;
    min-height: 100dvh;
    padding-top: max(20px, env(safe-area-inset-top));
    padding-bottom: max(60px, env(safe-area-inset-bottom));
    color: var(--ab-ink-2);
    font-family: 'Readex Pro', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    overflow-x: clip;
    isolation: isolate;
}

/* Stage Ambience & Lighting */
.about-stage-ambience {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: -1;
    overflow: hidden;
}

.about-glow {
    position: absolute;
    border-radius: 50%;
    filter: blur(90px);
    opacity: 0.45;
    will-change: transform;
}

.about-glow-top {
    top: -150px;
    left: 50%;
    transform: translateX(-50%);
    width: min(100vw, 850px);
    height: 480px;
    background: radial-gradient(ellipse at center, rgba(214, 164, 91, 0.18) 0%, rgba(136, 19, 55, 0.12) 45%, transparent 70%);
}

.about-glow-burgundy {
    top: 35%;
    right: -15%;
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(136, 19, 55, 0.18) 0%, transparent 65%);
}

.about-glow-amber {
    bottom: 15%;
    left: -15%;
    width: 550px;
    height: 550px;
    background: radial-gradient(circle, rgba(214, 164, 91, 0.14) 0%, transparent 70%);
}

.about-stage-dust {
    position: absolute;
    inset: 0;
    background-image: radial-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px);
    background-size: 24px 24px;
    opacity: 0.6;
}

/* Stage Container */
.about-stage-container {
    max-width: 68rem;
    margin: 0 auto;
    padding: 0 16px;
    display: flex;
    flex-direction: column;
    gap: 72px;
}

@media (min-width: 768px) {
    .about-stage-container {
        padding: 0 28px;
        gap: 96px;
    }
}

/* Chapter Base */
.about-chapter {
    position: relative;
    width: 100%;
}

/* Chapter Tag Numeral & Label */
.about-chapter-tag {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
}

.about-tag-num {
    font-family: 'Alexandria', sans-serif;
    font-size: 13px;
    font-weight: 700;
    color: var(--ab-gold);
    letter-spacing: 0.05em;
}

.about-tag-line {
    width: 22px;
    height: 1.5px;
    background: var(--ab-gold-border);
}

.about-tag-label {
    font-size: 12.5px;
    font-weight: 600;
    letter-spacing: 0.12em;
    color: var(--ab-gold-bright);
    text-transform: uppercase;
}

/* Section Headings */
.about-section-heading {
    font-family: 'Alexandria', sans-serif;
    font-size: clamp(1.4rem, 4vw, 2.15rem);
    font-weight: 800;
    line-height: 1.3;
    color: var(--ab-ink-1);
    margin: 0 0 10px;
    letter-spacing: -0.01em;
}

.about-section-sub {
    font-size: clamp(0.9rem, 2.2vw, 1.05rem);
    color: var(--ab-ink-3);
    line-height: 1.6;
    margin: 0 0 28px;
    max-width: 42rem;
}

.about-chapter-header-center {
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.about-chapter-header-center .about-section-sub {
    margin-left: auto;
    margin-right: auto;
}

/* ==========================================================================
   CHAPTER 01: IDENTITY HERO
   ========================================================================== */
.about-chapter-identity {
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding-top: 20px;
}

.about-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    border-radius: 999px;
    background: rgba(214, 164, 91, 0.08);
    border: 1px solid var(--ab-gold-border);
    color: var(--ab-gold-bright);
    font-size: 12px;
    font-weight: 500;
    margin-bottom: 20px;
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}

.about-pulse-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--ab-gold);
    box-shadow: 0 0 10px var(--ab-gold);
    animation: abPulse 2.4s var(--ab-ease) infinite;
}

@keyframes abPulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.45); opacity: 0.55; }
}

.about-eyebrow-sep {
    color: var(--ab-gold-muted);
    font-size: 10px;
}

.about-eyebrow-year {
    color: var(--ab-ink-1);
    font-weight: 600;
}

.about-hero-title {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    margin: 8px 0 18px;
    line-height: 1.05;
}

.about-title-lead {
    font-family: 'Alexandria', sans-serif;
    font-size: clamp(1.2rem, 3.5vw, 1.85rem);
    font-weight: 600;
    color: var(--ab-ink-3);
    letter-spacing: 0.08em;
}

.about-title-hero {
    font-family: 'Alexandria', sans-serif;
    font-size: clamp(2.75rem, 9.5vw, 5.25rem);
    font-weight: 900;
    letter-spacing: -0.02em;
    background: linear-gradient(180deg, #ffffff 0%, #fff1d6 35%, #d6a45b 100%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    filter: drop-shadow(0 12px 30px rgba(214, 164, 91, 0.3));
}

.about-hero-manifesto {
    font-size: clamp(1.02rem, 2.5vw, 1.28rem);
    line-height: 1.75;
    color: var(--ab-ink-2);
    max-width: 44rem;
    margin: 0 auto 24px;
    font-weight: 400;
}

.about-affiliation-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 18px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.035);
    border: 1px solid var(--ab-glass-border);
    color: var(--ab-ink-muted);
    font-size: 12.5px;
    margin-bottom: 28px;
}

.about-chip-icon {
    width: 15px;
    height: 15px;
    color: var(--ab-gold);
    flex-shrink: 0;
}

.about-hero-divider {
    display: flex;
    align-items: center;
    gap: 14px;
    width: min(100%, 260px);
    margin: 6px auto 0;
    color: var(--ab-gold);
    opacity: 0.85;
}

.about-divider-line {
    flex: 1;
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--ab-gold-border), transparent);
}

.about-divider-star {
    font-size: 13px;
    color: var(--ab-gold);
    text-shadow: 0 0 10px var(--ab-gold);
}

/* ==========================================================================
   CHAPTER 02: WHO WE ARE (EDITORIAL DUAL CARD)
   ========================================================================== */
.about-editorial-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
}

@media (min-width: 900px) {
    .about-editorial-grid {
        grid-template-columns: 1.1fr 1.3fr;
        gap: 28px;
        align-items: stretch;
    }
}

.about-pullquote-card {
    position: relative;
    padding: 34px 28px;
    border-radius: 22px;
    background: linear-gradient(145deg, rgba(136, 19, 55, 0.16) 0%, rgba(10, 16, 30, 0.75) 100%);
    border: 1px solid rgba(214, 164, 91, 0.28);
    box-shadow: 0 20px 45px -15px rgba(0, 0, 0, 0.65), inset 0 1px 0 rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    overflow: hidden;
}

.about-quote-glyph {
    position: absolute;
    top: 8px;
    left: 18px;
    font-family: Georgia, serif;
    font-size: 88px;
    line-height: 1;
    color: var(--ab-gold);
    opacity: 0.12;
    pointer-events: none;
    user-select: none;
}

.about-quote-text {
    position: relative;
    margin: 0;
    font-family: 'Alexandria', sans-serif;
    font-size: clamp(1.1rem, 2.2vw, 1.35rem);
    font-weight: 700;
    line-height: 1.7;
    color: #fff6e5;
    letter-spacing: -0.01em;
}

.about-quote-author {
    margin-top: 24px;
    display: flex;
    flex-direction: column;
    gap: 3px;
    font-style: normal;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    padding-top: 16px;
}

.about-author-name {
    font-size: 13.5px;
    font-weight: 600;
    color: var(--ab-gold-bright);
}

.about-author-sub {
    font-size: 12px;
    color: var(--ab-ink-muted);
}

.about-narrative-card {
    position: relative;
    padding: 34px 28px;
    border-radius: 22px;
    background: var(--ab-glass-bg);
    border: 1px solid var(--ab-glass-border);
    box-shadow: 0 20px 45px -15px rgba(0, 0, 0, 0.55), inset 0 1px 0 rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.about-card-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    width: fit-content;
    padding: 4px 12px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid var(--ab-glass-border);
    color: var(--ab-gold);
    font-size: 11.5px;
    font-weight: 600;
}

.about-badge-dot {
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: var(--ab-gold);
}

.about-narrative-paragraph {
    margin: 0;
    font-size: clamp(0.96rem, 1.8vw, 1.05rem);
    line-height: 1.85;
    color: var(--ab-ink-2);
}

.about-narrative-paragraph strong {
    color: #fff6e5;
    font-weight: 600;
}

.about-stats-row {
    margin-top: auto;
    padding-top: 20px;
    border-top: 1px solid var(--ab-glass-border);
    display: flex;
    align-items: center;
    gap: 20px;
}

.about-stat-item {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.about-stat-value {
    font-family: 'Alexandria', sans-serif;
    font-size: 1.65rem;
    font-weight: 800;
    color: var(--ab-gold-bright);
    letter-spacing: -0.02em;
    line-height: 1;
}

.about-stat-label {
    font-size: 12px;
    color: var(--ab-ink-muted);
}

.about-stat-divider {
    width: 1px;
    height: 36px;
    background: var(--ab-glass-border);
}

/* ==========================================================================
   CHAPTER 03: OUR MESSAGE (THE 3 PILLARS)
   ========================================================================== */
.about-pillars-stack {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
}

@media (min-width: 860px) {
    .about-pillars-stack {
        grid-template-columns: 1fr 1fr;
    }
    .about-pillar-change {
        grid-column: span 2;
    }
}

.about-pillar-card {
    position: relative;
    padding: 30px 24px;
    border-radius: 20px;
    background: var(--ab-glass-bg);
    border: 1px solid var(--ab-glass-border);
    box-shadow: 0 16px 36px -12px rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    display: flex;
    flex-direction: column;
    gap: 16px;
    transition: transform 0.3s var(--ab-ease), border-color 0.3s var(--ab-ease);
}

.about-pillar-card:hover {
    transform: translateY(-2px);
    border-color: rgba(214, 164, 91, 0.38);
}

.about-pillar-header {
    display: flex;
    align-items: flex-start;
    gap: 16px;
}

.about-pillar-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.about-pillar-icon svg {
    width: 22px;
    height: 22px;
}

.about-pillar-community .about-pillar-icon {
    background: rgba(214, 164, 91, 0.12);
    border: 1px solid rgba(214, 164, 91, 0.3);
    color: var(--ab-gold-bright);
}

.about-pillar-minds .about-pillar-icon {
    background: rgba(136, 19, 55, 0.22);
    border: 1px solid rgba(136, 19, 55, 0.45);
    color: #fca5a5;
}

.about-pillar-change .about-pillar-icon {
    background: rgba(245, 158, 11, 0.16);
    border: 1px solid rgba(245, 158, 11, 0.38);
    color: #fef08a;
}

.about-pillar-titles {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.about-pillar-kicker {
    font-size: 11.5px;
    font-weight: 600;
    color: var(--ab-gold);
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.about-pillar-title {
    font-family: 'Alexandria', sans-serif;
    font-size: 1.15rem;
    font-weight: 700;
    line-height: 1.35;
    color: #ffffff;
    margin: 0;
}

.about-pillar-body p {
    margin: 0;
    font-size: 0.96rem;
    line-height: 1.8;
    color: var(--ab-ink-3);
}

.about-change-lead {
    font-size: 1.05rem !important;
    line-height: 1.85 !important;
    color: #fef3c7 !important;
}

.about-pillar-change {
    background: linear-gradient(160deg, rgba(136, 19, 55, 0.14) 0%, rgba(10, 16, 30, 0.85) 100%);
    border: 1px solid rgba(214, 164, 91, 0.35);
}

/* ==========================================================================
   CHAPTER 04: OUR JOURNEY (CINEMATIC TIMELINE STREAM)
   ========================================================================== */
.about-timeline-stream {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 24px;
    margin-top: 12px;
}

.about-timeline-item {
    position: relative;
    display: grid;
    grid-template-columns: 28px 1fr;
    gap: 18px;
}

@media (min-width: 640px) {
    .about-timeline-item {
        grid-template-columns: 36px 1fr;
        gap: 24px;
    }
}

.about-timeline-marker {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
}

.about-marker-dot {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: var(--ab-gold);
    border: 3px solid #020617;
    box-shadow: 0 0 10px var(--ab-gold);
    flex-shrink: 0;
    margin-top: 8px;
    z-index: 2;
}

.about-marker-line {
    width: 2px;
    flex: 1;
    background: linear-gradient(180deg, var(--ab-gold) 0%, rgba(214, 164, 91, 0.15) 100%);
    margin-top: 6px;
    margin-bottom: -16px;
}

.about-timeline-content {
    padding: 24px 22px;
    border-radius: 18px;
    background: var(--ab-glass-bg);
    border: 1px solid var(--ab-glass-border);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.about-timeline-badge {
    display: inline-flex;
    align-items: center;
    width: fit-content;
    padding: 3px 10px;
    border-radius: 6px;
    background: rgba(214, 164, 91, 0.12);
    border: 1px solid rgba(214, 164, 91, 0.28);
    color: var(--ab-gold-bright);
    font-size: 11.5px;
    font-weight: 700;
    letter-spacing: 0.04em;
}

.about-timeline-badge.about-badge-active {
    background: rgba(136, 19, 55, 0.24);
    border-color: rgba(244, 63, 94, 0.38);
    color: #fecdd3;
}

.about-timeline-title {
    font-family: 'Alexandria', sans-serif;
    font-size: 1.12rem;
    font-weight: 700;
    color: #ffffff;
    margin: 2px 0 0;
}

.about-timeline-desc {
    margin: 0;
    font-size: 0.94rem;
    line-height: 1.75;
    color: var(--ab-ink-3);
}

/* ==========================================================================
   SCROLL REVEAL & ACCESSIBILITY ANIMATION
   ========================================================================== */
.about-reveal {
    opacity: 0;
    transform: translateY(24px);
    transition: opacity 0.75s var(--ab-ease), transform 0.75s var(--ab-ease);
    will-change: transform, opacity;
}

.about-reveal.is-revealed {
    opacity: 1;
    transform: translateY(0);
}

/* Reduced Motion Override */
@media (prefers-reduced-motion: reduce) {
    .about-pulse-dot,
    .about-stage-ambience * {
        animation: none !important;
    }
    .about-reveal {
        opacity: 1 !important;
        transform: none !important;
        transition: none !important;
    }
    .about-action-btn:hover,
    .about-social-card:hover,
    .about-pillar-card:hover {
        transform: none !important;
    }
}
</style>

<script>
/**
 * Scoped Scroll-Reveal Observer for Elsar5a Cinematic About Page.
 * Respects prefers-reduced-motion, uses generous rootMargin for smooth anticipation,
 * and includes a graceful safety timer so content is never hidden if scroll events lag.
 */
(function() {
    'use strict';

    function initReveal() {
        var items = document.querySelectorAll('.about-reveal');
        if (!items.length) return;

        // Immediate reveal for reduced motion or missing IntersectionObserver
        var prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (prefersReduced || !('IntersectionObserver' in window)) {
            items.forEach(function(el) { el.classList.add('is-revealed'); });
            return;
        }

        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-revealed');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            root: null,
            rootMargin: '150px 0px 50px 0px',
            threshold: 0.05
        });

        items.forEach(function(el) {
            observer.observe(el);
        });

        // Safety fallback: ensure every element is revealed within 1.5s regardless of scroll state
        setTimeout(function() {
            items.forEach(function(el) {
                el.classList.add('is-revealed');
            });
        }, 1500);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initReveal);
    } else {
        initReveal();
    }
})();
</script>
@endsection
