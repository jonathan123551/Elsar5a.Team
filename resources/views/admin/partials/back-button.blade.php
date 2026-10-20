@props([
    'url' => null,
    'label' => 'رجوع',
    'fallback' => route('admin.dashboard'),
])

@php
    $targetUrl = $url ?: (url()->previous() !== url()->current() ? url()->previous() : $fallback);
@endphp

<a href="{{ $targetUrl }}"
   {{ $attributes->merge([
       'class' => 'admin-btn admin-btn--secondary adm-back-btn'
   ]) }}
   aria-label="{{ $label }}">
    <svg class="w-4 h-4 rtl:rotate-0 ltr:rotate-180 text-amber-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 5l7 7-7 7" />
    </svg>
    <span>{{ $label }}</span>
</a>
