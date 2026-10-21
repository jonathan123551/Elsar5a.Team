@foreach($bookings as $booking)
            @php
                $dt = $booking->showTime
                    ? $booking->showTime->date->format('Y-m-d').' '.$booking->showTime->time
                    : '';
                $totalTickets     = $booking->ticketsTotalCount();
                $deliveredTickets = $booking->ticketsDeliveredCount();
                $sentTickets      = $booking->ticketsSentCount();
                $failedTickets    = $booking->ticketsFailedCount();
                $hasFailed        = $failedTickets > 0 || $booking->template_status === 'failed';
                $deliveryBadge    = $booking->delivery_badge;
            @endphp

            <div class="adm-card bg-[#0e131f]/90 border border-white/10 rounded-2xl p-4 space-y-3 shadow-md booking-card"
                 data-booking-id="{{ $booking->id }}"
                 data-booking-sort="{{ $booking->created_at?->timestamp ?? 0 }}"
                 data-search="{{ strtolower($booking->full_name.' '.$booking->phone.' '.$booking->reference_code) }}"
                 data-pn="{{ $booking->public_number ?? '' }}"
                 data-status="{{ $booking->status }}"
                 data-has-issues="{{ $hasFailed ? '1' : '0' }}"
                 data-datetime="{{ $dt }}">

                {{-- Row 1: #N + Status Pill + Price --}}
                <div class="flex items-center justify-between gap-2 border-b border-white/5 pb-2.5">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center justify-center min-w-[38px] h-7 px-2 rounded-lg font-bold tabular-nums text-xs bg-amber-400/15 text-amber-200 border border-amber-400/30">
                            #{{ $booking->public_number ?? $booking->id }}
                        </span>

                        @if($booking->status === 'approved')
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/15 text-emerald-200 border border-emerald-500/40">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                مقبول
                            </span>
                        @elseif($booking->status === 'rejected')
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-500/15 text-rose-200 border border-rose-500/40">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                                مرفوض
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-500/15 text-sky-200 border border-sky-500/40">
                                <span class="w-1.5 h-1.5 rounded-full bg-sky-400 animate-pulse"></span>
                                قيد المراجعة
                            </span>
                        @endif
                    </div>

                    <div class="font-extrabold text-amber-300 text-sm tabular-nums">
                        {{ number_format((int) $booking->total_price) }} <span class="text-[11px] font-normal text-amber-200/80">ج.م</span>
                    </div>
                </div>

                {{-- Row 2: Customer Name & Phone & Screenshot Preview --}}
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <h2 class="font-bold text-base text-white truncate">{{ $booking->full_name }}</h2>
                        <p class="text-xs text-slate-400 font-mono mt-0.5" dir="ltr">{{ $booking->phone }}</p>
                        <p class="text-xs text-slate-300 mt-1.5 flex items-center gap-1.5 flex-wrap">
                            <svg class="w-3.5 h-3.5 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>
                            </svg>
                            <span class="font-medium text-slate-200">{{ $booking->showTime?->show?->title ?? '—' }}</span>
                            <span class="text-slate-500 mx-0.5">·</span>
                            <span class="text-slate-400 tabular-nums">
                                {{ $booking->showTime?->date?->format('d/m') }}
                                • {{ $booking->showTime?->time ? \Carbon\Carbon::parse($booking->showTime->time)->format('g:i A') : '' }}
                            </span>
                        </p>
                    </div>

                    @if($booking->transfer_screenshot_path)
                        <button type="button"
                                class="proof-thumb-btn w-12 h-16 shrink-0"
                                data-preview-img="{{ $booking->transfer_screenshot_path }}"
                                data-preview-title="إثبات تحويل #{{ $booking->public_number ?? $booking->id }} — {{ $booking->full_name }}"
                                title="عرض لقطة شاشة التحويل">
                            <img src="{{ $booking->transfer_screenshot_path }}"
                                 alt="صورة التحويل"
                                 class="w-full h-full object-cover">
                        </button>
                    @endif
                </div>

                {{-- Row 3: Tickets & WhatsApp State --}}
                <div class="flex items-center justify-between gap-2 pt-2 border-t border-white/5 text-xs flex-wrap">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-300 border border-amber-500/25 font-semibold text-[11px]">
                            <svg class="w-3 h-3 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                            </svg>
                            <span>{{ $booking->tickets_count }} تذكرة</span>
                        </span>

                        @if($booking->status === 'approved')
                            <span class="adm-delivery-pill {{ $deliveryBadge['bg'] }} text-[10.5px]" title="{{ $deliveryBadge['title'] }}">
                                @if($deliveryBadge['has_spinner'])
                                    <span class="btn-spinner"></span>
                                @elseif($deliveryBadge['type'] === 'emerald')
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                @elseif($deliveryBadge['type'] === 'rose')
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                                @elseif($deliveryBadge['type'] === 'teal')
                                    <span class="w-1.5 h-1.5 rounded-full bg-cyan-400"></span>
                                @elseif($deliveryBadge['type'] === 'sky')
                                    <span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span>
                                @else
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span>
                                @endif
                                <span>{{ $deliveryBadge['text'] }}</span>
                            </span>
                        @endif
                    </div>

                    <div class="flex items-center gap-1.5 ms-auto">
                        <a href="{{ route('admin.bookings.show', $booking) }}"
                           class="admin-btn admin-btn--primary admin-btn--sm">
                            <span>عرض التفاصيل</span>
                            <svg class="w-3.5 h-3.5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
