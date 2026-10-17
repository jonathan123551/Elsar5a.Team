@extends('layouts.app')

@section('title', 'حجز تذاكر - ' . $showTime->show->title)

@section('content')
@php
    $show = $showTime->show;
    $playTitle = $show->title;
    $playDescription = $show->description ?? 'عرض مسرحي حي من تقديم فريق الصرخة المسرحي — تجربة فنية تلامس الوعي وتفتح آفاق الفكر على خشبة المسرح.';
    $playLocation = 'مسرح فريق الصرخة';

    // Resolve Poster safely
    $posterUrl = asset('images/logo.png');
    if ($show->poster_path) {
        if (\Illuminate\Support\Str::startsWith($show->poster_path, ['http://', 'https://', 'data:image'])) {
            $posterUrl = $show->poster_path;
        } elseif (\Illuminate\Support\Str::startsWith($show->poster_path, 'storage/')) {
            $posterUrl = asset($show->poster_path);
        } else {
            $posterUrl = asset('storage/' . ltrim($show->poster_path, '/'));
        }
    }

    $unitPrice = (float) ($showTime->ticket_price ?: 100);
    $initialQty = (int) old('tickets_count', 1);
    if ($remaining > 0 && $initialQty > $remaining) {
        $initialQty = $remaining;
    }
    $initialTotal = $initialQty * $unitPrice;

    // Show date and time formatting
    $showDateCarbon = \Carbon\Carbon::parse($showTime->date);
    $showDateFormatted = $showDateCarbon->locale('ar')->translatedFormat('l j F Y');
    $showTimeFormatted = \Carbon\Carbon::parse($showTime->time)->locale('ar')->translatedFormat('g:i A');

    // Other showtimes for the same production (if multiple exist)
    $otherShowTimes = $show->showTimes()
        ->where('is_sold_out', false)
        ->where('id', '!=', $showTime->id)
        ->orderBy('date')
        ->orderBy('time')
        ->get();

    // Payment methods resolution
    $paymentMethods = collect();
    if (!empty($transferWallet)) {
        $cleanPhone = preg_replace('/[^0-9]/', '', $transferWallet);
        $isPhone = preg_match('/^01[0125][0-9]{8}$/', $cleanPhone) || preg_match('/^201[0125][0-9]{8}$/', $cleanPhone);
        $waUrl = null;
        if ($isPhone) {
            $normalizedPhone = \Illuminate\Support\Str::startsWith($cleanPhone, '20') ? $cleanPhone : ('20' . ltrim($cleanPhone, '0'));
            $waMsg = rawurlencode("مرحباً، أود الاستفسار بخصوص تحويل حجز مسرحية: {$playTitle}");
            $waUrl = "https://wa.me/{$normalizedPhone}?text={$waMsg}";
        }

        $paymentMethods->push([
            'id' => 'wallet',
            'label' => 'محفظة إلكترونية (فودافون كاش / أورانج / اتصالات / وي)',
            'number' => $transferWallet,
            'whatsapp_url' => $waUrl,
            'type' => 'wallet',
        ]);
    }

    if (!empty($transferInsta)) {
        $paymentMethods->push([
            'id' => 'instapay',
            'label' => 'تطبيق إنستاباي (InstaPay)',
            'number' => $transferInsta,
            'whatsapp_url' => null,
            'type' => 'instapay',
        ]);
    }
@endphp

<link rel="stylesheet" href="{{ asset('css/elsar5a-booking.css') }}?v={{ file_exists(public_path('css/elsar5a-booking.css')) ? filemtime(public_path('css/elsar5a-booking.css')) : '1.0' }}">

<div class="booking-experience-shell" dir="rtl">

    {{-- FIXED THEATRICAL AMBIENT BACKDROP --}}
    <div class="theatre-ambient-backdrop" aria-hidden="true">
        <div class="theatre-stage-light"></div>
        <div class="theatre-velvet-glow"></div>
        <div class="theatre-spotlight-beam"></div>
        <div class="theatre-ambient-grain"></div>
    </div>

    <div class="booking-container">

        {{-- THEATRICAL HERO BANNER --}}
        <section class="booking-hero-banner" aria-labelledby="booking-show-title">
            <div class="booking-hero-content">
                <div class="booking-hero-meta-strip">
                    <span class="booking-status-badge">شباك التذاكر الرسمي</span>
                    <span class="booking-theatre-badge">{{ $playLocation }}</span>
                    @if($remaining > 0)
                        <span class="booking-seats-badge">متبقي {{ $remaining }} مقاعد متاحة</span>
                    @endif
                </div>

                <h1 id="booking-show-title" class="booking-hero-title">{{ $playTitle }}</h1>
                <p class="booking-hero-description">{{ $playDescription }}</p>

                <div class="booking-hero-tags">
                    <div class="hero-tag-item">
                        <span class="tag-icon" aria-hidden="true">📅</span>
                        <span class="tag-text">{{ $showDateFormatted }}</span>
                    </div>
                    <div class="hero-tag-item">
                        <span class="tag-icon" aria-hidden="true">⏰</span>
                        <span class="tag-text">{{ $showTimeFormatted }}</span>
                    </div>
                    <div class="hero-tag-item is-highlight">
                        <span class="tag-icon" aria-hidden="true">🎟️</span>
                        <span class="tag-text">{{ number_format($unitPrice, 0) }} جنيه للتذكرة</span>
                    </div>
                </div>
            </div>

            <div class="booking-hero-poster-wrap">
                <div class="poster-card-frame">
                    <img src="{{ $posterUrl }}" alt="بوستر مسرحية {{ $playTitle }}" class="booking-poster-img" loading="eager">
                    <div class="poster-shine-layer" aria-hidden="true"></div>
                </div>
            </div>
        </section>

        {{-- 4-STAGE VISUAL JOURNEY PROGRESS RAIL --}}
        <nav class="booking-journey-rail" aria-label="مراحل حجز التذكرة">
            <ol class="journey-steps-list">
                <li class="journey-step-item">
                    <a href="#stage-01" data-jump-stage="stage-01" class="journey-step-link is-active" data-step-indicator="1">
                        <span class="step-indicator-pill">01</span>
                        <div class="step-meta">
                            <span class="step-milestone">موعد وتذاكر</span>
                            <span class="step-action-tag">تحديد الكمية</span>
                        </div>
                    </a>
                </li>
                <li class="journey-step-connector" aria-hidden="true"></li>

                <li class="journey-step-item">
                    <a href="#stage-02" data-jump-stage="stage-02" class="journey-step-link" data-step-indicator="2">
                        <span class="step-indicator-pill">02</span>
                        <div class="step-meta">
                            <span class="step-milestone">التحويل والدفع</span>
                            <span class="step-action-tag">أرقام التحويل</span>
                        </div>
                    </a>
                </li>
                <li class="journey-step-connector" aria-hidden="true"></li>

                <li class="journey-step-item">
                    <a href="#stage-03" data-jump-stage="stage-03" class="journey-step-link" data-step-indicator="3">
                        <span class="step-indicator-pill">03</span>
                        <div class="step-meta">
                            <span class="step-milestone">إثبات الدفع والبيانات</span>
                            <span class="step-action-tag">الإيصال والواتساب</span>
                        </div>
                    </a>
                </li>
                <li class="journey-step-connector" aria-hidden="true"></li>

                <li class="journey-step-item">
                    <a href="#stage-04" data-jump-stage="stage-04" class="journey-step-link" data-step-indicator="4">
                        <span class="step-indicator-pill">04</span>
                        <div class="step-meta">
                            <span class="step-milestone">مراجعة وإرسال</span>
                            <span class="step-action-tag">التأكيد النهائي</span>
                        </div>
                    </a>
                </li>
            </ol>
        </nav>

        {{-- BOOKING WORKSPACE (STAGES COLUMN + DESKTOP STICKY SUMMARY COLUMN) --}}
        <div class="booking-workspace-grid">

            {{-- PRIMARY FORM & STAGES --}}
            <div class="booking-stages-column">

                {{-- SERVER VALIDATION ERRORS --}}
                @if ($errors->any())
                    <div class="booking-flash-banner is-error" role="alert">
                        <div class="flash-icon" aria-hidden="true">⚠️</div>
                        <div class="flash-content">
                            <h4 class="font-bold text-sm mb-1">يرجى تصحيح الملاحظات التالية:</h4>
                            <ul class="text-xs space-y-1 list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <form
                    action="{{ route('bookings.store', $showTime) }}"
                    method="POST"
                    enctype="multipart/form-data"
                    id="elsar5a-booking-form"
                    novalidate
                    data-unit-price="{{ $unitPrice }}"
                    data-max-tickets="{{ min(20, $remaining) }}"
                    data-showtime-id="{{ $showTime->id }}"
                >
                    @csrf
                    <input type="hidden" name="idempotency_token" id="idempotencyToken" value="">
                    <input type="hidden" name="tickets_count" id="ticket-quantity-input" value="{{ $initialQty }}">

                    {{-- ===================================================
                         STAGE 01 — SELECT TICKET QUANTITY & SHOWTIME
                         =================================================== --}}
                    <section id="stage-01" class="booking-stage-card is-focused" data-stage-section="1" aria-labelledby="stage-01-heading">
                        <header class="stage-card-header">
                            <div class="stage-badge-wrap">
                                <span class="stage-number-pill">01</span>
                                <span class="stage-status-tag" id="stage-01-status">الخطوة الحالية</span>
                            </div>
                            <h2 id="stage-01-heading" class="stage-card-title">عدد التذاكر وموعد العرض</h2>
                            <p class="stage-card-subtitle">حدد عدد التذاكر المطلوبة ليتم حساب إجمالي قيمة الحجز فوراً.</p>
                        </header>

                        {{-- Multiple Showtimes Switcher (if other dates exist for this production) --}}
                        @if($otherShowTimes->isNotEmpty())
                            <div class="showtime-selector-block mb-5">
                                <label class="booking-field-label">مواعيد العرض المتاحة لهذه المسرحية:</label>
                                <div class="showtime-pills-grid" role="group" aria-label="مواعيد العرض">
                                    <div class="showtime-option-card is-selected" aria-current="true">
                                        <span class="showtime-option-date">{{ $showDateFormatted }}</span>
                                        <span class="showtime-option-time">{{ $showTimeFormatted }}</span>
                                        <span class="showtime-option-capacity">الموعد المحدد حالياً ✓</span>
                                    </div>
                                    @foreach($otherShowTimes as $otherSt)
                                        @php
                                            $otherDate = \Carbon\Carbon::parse($otherSt->date)->locale('ar')->translatedFormat('l j F');
                                            $otherTime = \Carbon\Carbon::parse($otherSt->time)->locale('ar')->translatedFormat('g:i A');
                                        @endphp
                                        <a href="{{ route('bookings.create', $otherSt) }}" class="showtime-option-card" title="التبديل إلى هذا الموعد">
                                            <span class="showtime-option-date">{{ $otherDate }}</span>
                                            <span class="showtime-option-time">{{ $otherTime }}</span>
                                            <span class="showtime-option-capacity">اضغط للتبديل</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Tactile Quick-Select Ticket Quantity Deck --}}
                        <div class="ticket-selector-vault">
                            <label class="booking-field-label mb-2 block">اختر عدد التذاكر:</label>
                            <div class="ticket-preset-grid" role="group" aria-label="اختيار سريع لعدد التذاكر">
                                @for($i = 1; $i <= min(5, max(1, $remaining)); $i++)
                                    <button
                                        type="button"
                                        class="ticket-preset-card {{ $initialQty === $i ? 'is-active' : '' }}"
                                        data-qty-preset="{{ $i }}"
                                        aria-pressed="{{ $initialQty === $i ? 'true' : 'false' }}"
                                    >
                                        <span class="preset-stub-notch" aria-hidden="true"></span>
                                        <span class="preset-count-num">{{ $i }}</span>
                                        <span class="preset-count-label">
                                            @if($i === 1) تذكرة واحدة
                                            @elseif($i === 2) تذكرتان
                                            @else {{ $i }} تذاكر
                                            @endif
                                        </span>
                                        <span class="preset-price-tag">
                                            {{ number_format($i * $unitPrice, 0) }} ج.م
                                        </span>
                                    </button>
                                @endfor
                            </div>

                            {{-- Tactile Stepper Controller (+ / -) --}}
                            <div class="ticket-stepper-row">
                                <div class="stepper-caption">
                                    <span class="stepper-caption-title">التحكم الدقيق في العدد:</span>
                                    <span class="stepper-caption-sub">الحد الأقصى لكل حجز {{ min(20, $remaining) }} تذكرة</span>
                                </div>

                                <div class="tactile-stepper" aria-label="تعديل عدد التذاكر">
                                    <button
                                        type="button"
                                        id="qty-decrease-btn"
                                        class="stepper-action-btn is-decrease"
                                        aria-label="إنقاص تذكرة واحدة"
                                        {{ $initialQty <= 1 ? 'disabled' : '' }}
                                    >
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                            <line x1="5" y1="12" x2="19" y2="12"></line>
                                        </svg>
                                    </button>

                                    <div class="stepper-readout-dial" aria-live="polite">
                                        <span class="stepper-readout-num js-sync-qty">{{ $initialQty }}</span>
                                        <span class="stepper-readout-unit">تذاكر</span>
                                    </div>

                                    <button
                                        type="button"
                                        id="qty-increase-btn"
                                        class="stepper-action-btn is-increase"
                                        aria-label="إضافة تذكرة واحدة"
                                        {{ $remaining > 0 && $initialQty >= $remaining ? 'disabled' : '' }}
                                    >
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                            <line x1="12" y1="5" x2="12" y2="19"></line>
                                            <line x1="5" y1="12" x2="19" y2="12"></line>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            {{-- Live Price Calculation Ledger --}}
                            <div class="stage-01-price-ledger" aria-live="polite">
                                <div class="ledger-equation">
                                    <span class="ledger-item">
                                        <strong id="ledger-qty-num" class="js-sync-qty">{{ $initialQty }}</strong>
                                        <small>تذاكر</small>
                                    </span>
                                    <span class="ledger-operator" aria-hidden="true">×</span>
                                    <span class="ledger-item">
                                        <strong id="ledger-unit-price">{{ number_format($unitPrice, 0) }}</strong>
                                        <small>جنيه / تذكرة</small>
                                    </span>
                                </div>

                                <div class="ledger-total-highlight">
                                    <span class="ledger-total-label">المبلغ الإجمالي المستحق:</span>
                                    <div class="ledger-total-value-wrap">
                                        <strong id="stage-01-total-amount" class="ledger-total-number js-sync-total">{{ number_format($initialTotal, 0) }}</strong>
                                        <span class="ledger-currency">جنيه مصري</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    {{-- ===================================================
                         STAGE 02 — PAYMENT & TRANSFER NUMBERS + WHATSAPP
                         =================================================== --}}
                    <section id="stage-02" class="booking-stage-card" data-stage-section="2" aria-labelledby="stage-02-heading">
                        <header class="stage-card-header">
                            <div class="stage-badge-wrap">
                                <span class="stage-number-pill">02</span>
                                <span class="stage-status-tag" id="stage-02-status">الدفع والتحويل</span>
                            </div>
                            <h2 id="stage-02-heading" class="stage-card-title">حوّل قيمة الحجز على أحد الأرقام التالية</h2>
                            <p class="stage-card-subtitle">
                                قم بتحويل مبلغ <strong class="gold-inline-amount"><span class="js-sync-total">{{ number_format($initialTotal, 0) }}</span> جنيه</strong> على رقم المحفظة أو إنستاباي أدناه. اضغط لنسخ الرقم فوراً.
                            </p>
                        </header>

                        @if($paymentMethods->isNotEmpty())
                            <div class="payment-vault-grid" role="list" aria-label="أرقام التحويل والدفع">
                                @foreach($paymentMethods as $idx => $method)
                                    <article
                                        class="payment-vault-card {{ $idx === 0 ? 'is-selected' : '' }}"
                                        data-payment-card
                                        data-payment-number="{{ $method['number'] }}"
                                        role="listitem"
                                    >
                                        <div class="payment-card-top">
                                            <div class="flex items-center gap-2">
                                                @if($method['type'] === 'wallet')
                                                    <span class="text-xl" aria-hidden="true">📱</span>
                                                @else
                                                    <span class="text-xl" aria-hidden="true">⚡</span>
                                                @endif
                                                <span class="payment-provider-title">{{ $method['label'] }}</span>
                                            </div>

                                            <span class="payment-verified-badge">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                    <path d="M9 12L11 14L15 10M12 3L4 7V11C4 16.55 7.4 21.74 12 23C16.6 21.74 20 16.55 20 11V7L12 3Z"/>
                                                </svg>
                                                رقم معتمد
                                            </span>
                                        </div>

                                        {{-- Number Display --}}
                                        <div class="payment-number-showcase">
                                            <div class="payment-number-digits" dir="ltr">{{ $method['number'] }}</div>
                                        </div>

                                        {{-- Copy & WhatsApp Actions --}}
                                        <div class="payment-card-actions">
                                            <button
                                                type="button"
                                                class="payment-action-btn is-copy js-copy-number-btn"
                                                data-copy-value="{{ $method['number'] }}"
                                                aria-label="نسخ رقم التحويل {{ $method['number'] }}"
                                            >
                                                <svg class="icon-copy" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <rect x="9" y="9" width="13" height="13" rx="2" stroke-linejoin="round"/>
                                                    <path d="M5 15H4C2.89543 15 2 14.1046 2 13V4C2 2.89543 2.89543 2 4 2H13C14.1046 2 15 2.89543 15 4V5"/>
                                                </svg>
                                                <span class="btn-copy-text">نسخ الرقم</span>
                                            </button>

                                            @if(!empty($method['whatsapp_url']))
                                                <a
                                                    href="{{ $method['whatsapp_url'] }}"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="payment-action-btn is-whatsapp"
                                                    aria-label="التواصل عبر واتساب مع رقم التحويل"
                                                >
                                                    <svg width="19" height="19" viewBox="0 0 24 24" fill="currentColor">
                                                        <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.312.045-.694.06-1.144-.085-.3-.097-.687-.234-1.189-.452-2.127-.925-3.513-3.08-3.619-3.223-.105-.14-862-1.148-.862-2.189 0-1.04.544-1.552.738-1.764.193-.211.423-.264.563-.264.14 0 .281.002.404.008.131.007.306-.05.479.366.176.423.6 1.464.653 1.57.053.107.088.232.018.374-.07.141-.106.23-.211.353-.106.124-.222.277-.317.372-.106.105-.216.219-.093.43.123.21.547.904 1.173 1.462.806.719 1.485.942 1.696 1.048.211.105.334.088.457-.053.123-.141.528-.616.669-.828.14-.21.281-.176.474-.105.193.07 1.229.58 1.44.685.211.106.351.159.404.247.053.088.053.512-.091.917z"/>
                                                    </svg>
                                                    <span>تواصل واتساب</span>
                                                </a>
                                            @endif
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @else
                            <div class="payment-vault-empty text-center p-6 border border-white/10 rounded-2xl bg-black/40">
                                <p class="text-sm text-stone-300">
                                    يرجى التواصل مع إدارة الفريق لتأكيد حساب التحويل قبل رفع الإيصال.
                                </p>
                            </div>
                        @endif

                        <div class="payment-instruction-strip">
                            <span class="instruction-icon" aria-hidden="true">💡</span>
                            <p>
                                بعد إتمام التحويل على أي من الأرقام أعلاه، التقط صورة شاشة (Screenshot) لعملية التحويل الناجحة، ثم ارفعها في الخطوة التالية.
                            </p>
                        </div>
                    </section>

                    {{-- ===================================================
                         STAGE 03 — SCREENSHOT UPLOAD + USER BOOKING DATA
                         =================================================== --}}
                    <section id="stage-03" class="booking-stage-card" data-stage-section="3" aria-labelledby="stage-03-heading">
                        <header class="stage-card-header">
                            <div class="stage-badge-wrap">
                                <span class="stage-number-pill">03</span>
                                <span class="stage-status-tag" id="stage-03-status">إثبات الدفع والبيانات</span>
                            </div>
                            <h2 id="stage-03-heading" class="stage-card-title">إيصال التحويل وبيانات استلام التذكرة</h2>
                            <p class="stage-card-subtitle">ارفع صورة التحويل وأدخل اسمك ورقم الواتساب الذي ستصل عليه التذكرة برمز QR.</p>
                        </header>

                        {{-- Interactive Screenshot Upload Zone --}}
                        <div class="upload-section-wrapper">
                            <label class="booking-field-label">صورة إيصال التحويل (Screenshot) <span class="required-indicator">*</span></label>

                            <input
                                type="file"
                                name="payment_screenshot"
                                id="payment-screenshot-input"
                                accept="image/*"
                                class="sr-only"
                                required
                            >

                            <div
                                id="screenshot-dropzone"
                                class="upload-dropzone"
                                role="button"
                                tabindex="0"
                                aria-label="منطقة رفع صورة إيصال التحويل"
                                onclick="document.getElementById('payment-screenshot-input').click()"
                            >
                                {{-- Empty State --}}
                                <div id="upload-empty-state" class="upload-empty-state">
                                    <div class="upload-emblem-ring" aria-hidden="true">
                                        <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15"/>
                                            <polyline points="17 8 12 3 7 8"/>
                                            <line x1="12" y1="3" x2="12" y2="15"/>
                                        </svg>
                                    </div>
                                    <p class="upload-primary-prompt">اضغط لاختيار صورة التحويل أو اسحبها هنا</p>
                                    <p class="upload-secondary-prompt">صور PNG، JPG، أو لقطات iPhone حتى 20 ميجابايت</p>
                                    <span class="upload-formats-badge">معاينة فورية للإيصال</span>
                                </div>

                                {{-- Preview State --}}
                                <div id="upload-preview-state" class="upload-preview-state" hidden onclick="event.stopPropagation()">
                                    <div class="preview-thumbnail-frame">
                                        <img id="upload-preview-img" src="" alt="معاينة إيصال التحويل" class="preview-image-element">
                                    </div>
                                    <div class="preview-meta-details">
                                        <div class="file-name-wrap">
                                            <span class="file-success-icon" aria-hidden="true">✓</span>
                                            <strong id="upload-filename" class="file-name-text">receipt.jpg</strong>
                                        </div>
                                        <span id="upload-filesize" class="file-size-text">0 كيلوبايت</span>
                                        <div class="preview-control-buttons">
                                            <button type="button" id="change-screenshot-btn" class="preview-btn-modify">
                                                استبدال الصورة
                                            </button>
                                            <button type="button" id="remove-screenshot-btn" class="preview-btn-remove">
                                                حذف
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <p id="error-screenshot" class="field-error-msg" role="alert"></p>
                        </div>

                        {{-- Primary Customer Information Fields --}}
                        <div class="customer-fields-grid mt-6">
                            <div class="booking-field-group">
                                <label for="customer-name-input" class="booking-field-label">
                                    <span>الاسم بالكامل <span class="required-indicator">*</span></span>
                                    <small class="field-label-hint">(الاسم الأساسي لحاجز التذاكر)</small>
                                </label>
                                <div class="booking-input-shell">
                                    <span class="input-leading-icon" aria-hidden="true">👤</span>
                                    <input
                                        type="text"
                                        id="customer-name-input"
                                        name="names[]"
                                        value="{{ old('names.0') }}"
                                        placeholder="الاسم الثلاثي أو الرباعي"
                                        autocomplete="name"
                                        inputmode="text"
                                        class="booking-text-input"
                                        required
                                    >
                                </div>
                                <p id="error-name" class="field-error-msg" role="alert"></p>
                            </div>

                            <div class="booking-field-group">
                                <div class="whatsapp-field-header">
                                    <label for="customer-whatsapp-input" class="booking-field-label">
                                        <span>رقم الواتساب <span class="required-indicator">*</span></span>
                                        <small class="field-label-hint">(تصلك التذكرة عليه)</small>
                                    </label>
                                    <span class="whatsapp-secure-pill">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771z"/>
                                        </svg>
                                        إرسال آلي للـ QR
                                    </span>
                                </div>
                                <div class="booking-input-shell">
                                    <span class="input-leading-icon" aria-hidden="true">📱</span>
                                    <input
                                        type="tel"
                                        id="customer-whatsapp-input"
                                        name="phones[]"
                                        value="{{ old('phones.0') }}"
                                        placeholder="010XXXXXXXX"
                                        autocomplete="tel"
                                        inputmode="tel"
                                        dir="ltr"
                                        class="booking-text-input text-left"
                                        required
                                    >
                                </div>
                                <p id="error-whatsapp" class="field-error-msg" role="alert"></p>
                            </div>
                        </div>

                        {{-- Additional Attendees Container (rendered dynamically if tickets > 1) --}}
                        <div id="attendees-container" class="space-y-3" hidden></div>

                        {{-- Trust / WhatsApp Note --}}
                        <div class="whatsapp-delivery-guarantee mt-4">
                            <span class="guarantee-icon" aria-hidden="true">🎫</span>
                            <p>
                                <strong>تنبيه هام:</strong> بعد مراجعة إيصال التحويل من قِبل إدارة المسرح، ستصلك رسالة واتساب رسمية تحتوي على بطاقة الدخول الذكية ورمز الـ QR Code الخاص بحجزك.
                            </p>
                        </div>
                    </section>

                    {{-- ===================================================
                         STAGE 04 — SEND BOOKING & FINAL READINESS CHECK
                         =================================================== --}}
                    <section id="stage-04" class="booking-stage-card is-final-stage" data-stage-section="4" aria-labelledby="stage-04-heading">
                        <header class="stage-card-header">
                            <div class="stage-badge-wrap">
                                <span class="stage-number-pill">04</span>
                                <span class="stage-status-tag" id="stage-04-status">التأكيد النهائي</span>
                            </div>
                            <h2 id="stage-04-heading" class="stage-card-title">مراجعة جاهزية الحجز وإرسال الطلب</h2>
                            <p class="stage-card-subtitle">تأكد من اكتمال عناصر حجزك قبل الضغط على زر إرسال الحجز.</p>
                        </header>

                        {{-- Live Readiness Checklist --}}
                        <ul class="readiness-checklist" aria-label="حالة اكتمال بيانات الحجز">
                            <li class="readiness-item is-done" id="ready-check-tickets">
                                <span class="readiness-icon" aria-hidden="true">✓</span>
                                <span>تم تحديد عدد التذاكر (<strong class="js-sync-qty">{{ $initialQty }}</strong> تذكرة — <strong class="js-sync-total">{{ number_format($initialTotal, 0) }}</strong> ج.م)</span>
                            </li>
                            <li class="readiness-item" id="ready-check-screenshot">
                                <span class="readiness-icon" aria-hidden="true">○</span>
                                <span>إرفاق صورة إيصال التحويل (Screenshot)</span>
                            </li>
                            <li class="readiness-item" id="ready-check-user">
                                <span class="readiness-icon" aria-hidden="true">○</span>
                                <span>كتابة الاسم بالكامل ورقم الواتساب لاستلام التذكرة</span>
                            </li>
                        </ul>

                        {{-- 3D Tactile Master Submit CTA --}}
                        <div class="final-cta-wrapper">
                            <button
                                type="submit"
                                id="submit-booking-cta"
                                class="booking-master-cta"
                                disabled
                            >
                                <span class="cta-content-default">
                                    <span class="cta-main-label">إرسال طلب الحجز 🎟️</span>
                                    <span class="cta-sub-pill">
                                        <span class="js-sync-qty">{{ $initialQty }}</span> تذكرة • <span class="js-sync-total">{{ number_format($initialTotal, 0) }}</span> ج.م
                                    </span>
                                </span>

                                <span class="cta-content-loading" hidden aria-hidden="true">
                                    <span class="cta-spinner"></span>
                                    <span>جاري تسجيل الحجز ورفع الإيصال...</span>
                                </span>
                            </button>

                            <p class="cta-reassurance-foot">
                                🔒 بمجرد مراجعة التحويل، تصلك تذكرتك الرسمية المزودة برمز الدخول QR على رقم الواتساب المسجل.
                            </p>
                        </div>
                    </section>
                </form>
            </div>

            {{-- =======================================================
                 DESKTOP STICKY SUMMARY COLUMN (THEATRICAL TICKET STUB)
                 ======================================================= --}}
            <aside class="booking-summary-column" aria-label="ملخص الحجز المباشر">
                <div class="booking-ticket-summary-card">
                    <div class="ticket-summary-header">
                        <span class="summary-kicker">ملخص التذكرة</span>
                        <h3 class="summary-play-title">{{ $playTitle }}</h3>
                        <span class="summary-play-venue">{{ $playLocation }}</span>
                    </div>

                    <div class="ticket-perforation-divider" aria-hidden="true">
                        <span class="notch-right"></span>
                        <span class="perforation-line"></span>
                        <span class="notch-left"></span>
                    </div>

                    <dl class="ticket-summary-list">
                        <div class="summary-row">
                            <dt>العرض</dt>
                            <dd>{{ $playTitle }}</dd>
                        </div>
                        <div class="summary-row">
                            <dt>الموعد</dt>
                            <dd id="summary-date-display">{{ $showDateFormatted }}</dd>
                        </div>
                        <div class="summary-row">
                            <dt>الساعة</dt>
                            <dd>{{ $showTimeFormatted }}</dd>
                        </div>
                        <div class="summary-row">
                            <dt>عدد التذاكر</dt>
                            <dd><strong id="summary-qty-display" class="js-sync-qty">{{ $initialQty }}</strong></dd>
                        </div>
                        <div class="summary-row">
                            <dt>سعر التذكرة</dt>
                            <dd><span id="summary-unit-display">{{ number_format($unitPrice, 0) }}</span> ج.م</dd>
                        </div>
                        <div class="summary-row">
                            <dt>حالة الإيصال</dt>
                            <dd id="summary-receipt-status" class="status-pending">بانتظار الإرفاق</dd>
                        </div>
                    </dl>

                    <div class="ticket-summary-total-box">
                        <span class="total-box-label">المجموع المستحق</span>
                        <div class="total-box-amount">
                            <strong id="summary-total-display" class="js-sync-total">{{ number_format($initialTotal, 0) }}</strong>
                            <span class="currency">جنيه مصري</span>
                        </div>
                    </div>

                    <a href="#stage-04" data-jump-stage="stage-04" class="summary-jump-cta">
                        <span>متابعة إلى الإرسال</span>
                        <span class="jump-arrow" aria-hidden="true">↓</span>
                    </a>

                    <div class="summary-trust-strip">
                        <div class="trust-badge-item">
                            <span class="trust-icon" aria-hidden="true">🔒</span>
                            <span>مراجعة يدوية آمنة</span>
                        </div>
                        <div class="trust-badge-item">
                            <span class="trust-icon" aria-hidden="true">⚡</span>
                            <span>تأكيد عبر واتساب</span>
                        </div>
                        <div class="trust-badge-item">
                            <span class="trust-icon" aria-hidden="true">🎟️</span>
                            <span>تذكرة QR ذكية</span>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        {{-- MOBILE STICKY BOTTOM SUMMARY BAR --}}
        <div id="mobile-sticky-summary" class="mobile-sticky-summary" role="region" aria-label="شريط ملخص الحجز السريع">
            <div class="mobile-sticky-content">
                <div class="mobile-sticky-figures">
                    <span class="mobile-sticky-label">الإجمالي:</span>
                    <strong id="mobile-total-display" class="mobile-sticky-total js-sync-total">{{ number_format($initialTotal, 0) }} ج.م</strong>
                    <span id="mobile-qty-count" class="mobile-sticky-count js-sync-qty">{{ $initialQty }} تذاكر</span>
                </div>
                <button type="button" id="mobile-sticky-action-btn" class="mobile-sticky-cta-btn">
                    متابعة الحجز ↓
                </button>
            </div>
        </div>

        {{-- FLOATING TOAST NOTIFICATION --}}
        <div id="booking-toast" class="booking-toast" role="status" aria-live="polite" hidden>
            <span class="toast-check">✓</span>
            <span id="booking-toast-text">تم نسخ رقم التحويل بنجاح</span>
        </div>

    </div>
</div>

<script>
    window.__oldNames = @json(old('names', []));
    window.__oldPhones = @json(old('phones', []));
</script>
<script src="{{ asset('js/elsar5a-booking.js') }}?v={{ file_exists(public_path('js/elsar5a-booking.js')) ? filemtime(public_path('js/elsar5a-booking.js')) : '1.0' }}" defer></script>
@endsection
