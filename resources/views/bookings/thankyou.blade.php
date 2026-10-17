@extends('layouts.app')

@section('title', 'حالة طلب الحجز')

@section('content')
<style>
    .booking-confirmation { max-width: 42rem; margin: 0 auto; padding: .5rem 0 1.5rem; }
    .confirmation-panel { background: rgba(14,10,10,.78); border: 1px solid rgba(245,230,210,.14); border-radius: 1rem; padding: clamp(1.1rem,4vw,2rem); }
    .confirmation-status { border-inline-start: 3px solid #d8b474; background: rgba(216,180,116,.08); padding: .9rem 1rem; border-radius: .35rem .75rem .75rem .35rem; }
    .confirmation-data { display: grid; grid-template-columns: 1fr 1fr; gap: .8rem 1rem; }
    .confirmation-data dt { color: #baa996; font-size: .8rem; }
    .confirmation-data dd { margin-top: .2rem; color: #f6eee5; font-weight: 600; overflow-wrap: anywhere; }
    @media (max-width: 480px) { .confirmation-data { grid-template-columns: 1fr; } }
</style>

@php
    $time = $booking->showTime;
    $statusLabel = match ($booking->status) {
        'approved' => 'تم تأكيد الحجز',
        'rejected' => 'تعذّر تأكيد الحجز',
        default => 'طلبك قيد المراجعة',
    };
    $phoneTail = substr((string) $booking->phone, -4);
@endphp

<main class="booking-confirmation" aria-labelledby="confirmation-title">
    <section class="confirmation-panel space-y-6">
        <header class="space-y-2">
            <p class="text-xs font-semibold tracking-wider text-amber-200/80">فريق الصرخة المسرحي · طلب حجز</p>
            <h1 id="confirmation-title" class="text-2xl sm:text-3xl font-bold text-stone-100">تم استلام طلبك يا {{ $booking->full_name }}</h1>
            <p class="text-sm leading-7 text-stone-300">احتفظ برقم الحجز للرجوع إلى الطلب.</p>
        </header>

        <div class="confirmation-status" role="status">
            <p class="font-semibold text-amber-100">{{ $statusLabel }}</p>
            @if($booking->status === 'pending')
                <p class="mt-1 text-sm leading-6 text-stone-300">لم يصبح الحجز مؤكدًا بعد. يراجع الفريق بيانات الحجز وإثبات التحويل، وبعد الموافقة تُرسل التذاكر إلى رقم واتساب المسجّل.</p>
                <p class="mt-2 text-xs text-stone-400">المراجعة تستغرق حتى 24 ساعة.</p>
            @elseif($booking->status === 'approved')
                <p class="mt-1 text-sm leading-6 text-stone-300">تمت الموافقة على الحجز. تُرسل التذكرة إلى رقم واتساب المسجّل وفق إجراءات الفريق.</p>
            @else
                <p class="mt-1 text-sm leading-6 text-stone-300">راجع حالة الطلب أو تواصل مع الفريق عبر قنواته الرسمية إذا احتجت مساعدة.</p>
            @endif
        </div>

        <dl class="confirmation-data border-y border-white/10 py-4">
            <div>
                <dt>رقم الحجز</dt>
                <dd dir="ltr" class="text-amber-200">{{ $booking->reference_code }}</dd>
            </div>
            <div>
                <dt>إجمالي المبلغ</dt>
                <dd>{{ $booking->total_price }} جنيه</dd>
            </div>
            <div>
                <dt>العرض</dt>
                <dd>{{ $time?->show?->title ?? '—' }}</dd>
            </div>
            <div>
                <dt>الموعد</dt>
                <dd>
                    @if($time)
                        {{ $time->date->format('d/m/Y') }} · {{ \Carbon\Carbon::parse($time->time)->format('g:i A') }}
                    @else
                        —
                    @endif
                </dd>
            </div>
            <div>
                <dt>عدد التذاكر</dt>
                <dd>{{ $booking->tickets_count }}</dd>
            </div>
            <div>
                <dt>رقم واتساب المسجّل</dt>
                <dd dir="ltr">••••••{{ $phoneTail }}</dd>
            </div>
        </dl>

        <a href="{{ route('shows.index') }}"
           class="inline-flex min-h-12 w-full items-center justify-center rounded-lg bg-amber-200 px-5 py-3 text-sm font-bold text-stone-950 transition hover:bg-amber-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-200">
            استكشف العروض
        </a>
    </section>
</main>
@endsection
