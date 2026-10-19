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
       'class' => 'adm-back-btn inline-flex items-center gap-2 text-xs sm:text-sm font-medium px-3.5 py-2 rounded-xl
                   bg-white/5 hover:bg-white/10 active:bg-white/15 border border-white/10
                   text-gray-300 hover:text-white transition duration-150 min-h-[44px]
                   focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400/60 shadow-sm'
   ]) }}
   aria-label="{{ $label }}">
    <svg class="w-4 h-4 rtl:rotate-0 ltr:rotate-180 text-amber-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 5l7 7-7 7" />
    </svg>
    <span>{{ $label }}</span>
</a>
