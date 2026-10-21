@foreach($bookings as $booking)
                        @php
                            $dt = $booking->showTime
                                ? $booking->showTime->date->format('Y-m-d').' '.$booking->showTime->time
                                : '';
                            $totalTickets     = $booking->ticketsTotalCount();
                            $deliveredTickets = $booking->ticketsDeliveredCount();
                            $sentTickets      = $booking->ticketsSentCount();
                            $sendingTickets   = $booking->ticketsSendingCount();
                            $failedTickets    = $booking->ticketsFailedCount();
                            $hasFailed        = $failedTickets > 0 || $booking->template_status === 'failed';
                            $deliveryBadge    = $booking->delivery_badge;
                        @endphp
                        <tr class="booking-row hover:bg-white/[0.03] transition duration-150"
                            data-booking-id="{{ $booking->id }}"
                            data-booking-sort="{{ $booking->created_at?->timestamp ?? 0 }}"
                            data-search="{{ strtolower($booking->full_name.' '.$booking->phone.' '.$booking->reference_code) }}"
                            data-pn="{{ $booking->public_number ?? '' }}"
                            data-status="{{ $booking->status }}"
                            data-has-issues="{{ $hasFailed ? '1' : '0' }}"
                            data-datetime="{{ $dt }}">

                            {{-- Booking Number --}}
                            <td class="px-3 py-3 text-center align-middle">
                                <span class="inline-flex items-center justify-center min-w-[42px] h-8 px-2 rounded-lg font-bold tabular-nums text-xs bg-amber-400/10 text-amber-200 border border-amber-400/30">
                                    #{{ $booking->public_number ?? $booking->id }}
                                </span>
                            </td>

                            {{-- Customer details --}}
                            <td class="px-4 py-3 align-top">
                                <p class="font-bold text-white leading-snug">{{ $booking->full_name }}</p>
                                <p class="text-xs text-slate-400 font-mono mt-0.5" dir="ltr">{{ $booking->phone }}</p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="inline-flex items-center gap-1 text-[11px] text-amber-300 font-medium">
                                        <svg class="w-3.5 h-3.5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                                        </svg>
                                        <span>{{ $booking->tickets_count }} تذكرة</span>
                                    </span>
                                    @if($booking->tickets->count() > 1)
                                        <details class="group inline-block">
                                            <summary class="cursor-pointer text-[11px] text-slate-500 hover:text-slate-300 select-none">
                                                (عرض {{ $booking->tickets->count() }} أسماء)
                                            </summary>
                                            <div class="mt-1 space-y-0.5 p-2 rounded-lg bg-black/70 border border-white/10 text-xs">
                                                @foreach($booking->tickets as $ticket)
                                                    <div class="text-[11px] text-slate-300 flex items-center justify-between gap-2">
                                                        <span class="inline-flex items-center gap-1">
                                                            <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                            </svg>
                                                            <span>{{ $ticket->name }}</span>
                                                        </span>
                                                        <span class="text-slate-500 font-mono" dir="ltr">{{ $ticket->phone }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </details>
                                    @endif
                                </div>
                            </td>

                            {{-- Show and ShowTime --}}
                            <td class="px-4 py-3 align-top">
                                <p class="font-semibold text-slate-100 text-xs sm:text-sm leading-snug">
                                    {{ $booking->showTime?->show?->title ?? '-' }}
                                </p>
                                <span class="text-slate-400 text-xs mt-0.5 inline-flex items-center gap-1.5 flex-wrap">
                                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span>{{ $booking->showTime?->date?->format('d/m/Y') }}</span>
                                    <span>•</span>
                                    <span>{{ $booking->showTime?->time ? \Carbon\Carbon::parse($booking->showTime->time)->format('g:i A') : '' }}</span>
                                </span>
                            </td>

                            {{-- Price & Payment Transfer Proof --}}
                            <td class="px-4 py-3 align-top">
                                <div class="flex items-start gap-2.5">
                                    @if($booking->transfer_screenshot_path)
                                        <button type="button"
                                                class="proof-thumb-btn w-12 h-14 shrink-0 focus:outline-none focus:ring-2 focus:ring-amber-400"
                                                data-preview-img="{{ $booking->transfer_screenshot_path }}"
                                                data-preview-title="إثبات تحويل #{{ $booking->public_number ?? $booking->id }} — {{ $booking->full_name }}"
                                                title="انقر لتكبير صورة التحويل">
                                            <img src="{{ $booking->transfer_screenshot_path }}"
                                                 alt="صورة التحويل"
                                                 class="w-full h-full object-cover">
                                        </button>
                                    @else
                                        <div class="w-12 h-14 rounded-lg bg-white/5 border border-white/10 shrink-0 flex items-center justify-center text-[10px] text-slate-500 text-center p-1">
                                            لا يوجد إثبات
                                        </div>
                                    @endif

                                    <div class="min-w-0">
                                        <p class="font-extrabold text-amber-300 text-sm tabular-nums">
                                            {{ number_format((int) $booking->total_price) }} <span class="text-[11px] font-normal text-amber-200/80">ج.م</span>
                                        </p>
                                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-white/5 border border-white/10 text-slate-400 mt-1 inline-block">
                                            {{ ($booking->payment_method && $booking->payment_method !== 'manual_transfer') ? $booking->payment_method : 'محفظة / إنستاباي' }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            {{-- Booking Status Pill --}}
                            <td class="px-4 py-3 text-center align-middle">
                                @if($booking->status === 'approved')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/15 text-emerald-200 border border-emerald-500/40">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                        مقبول
                                    </span>
                                @elseif($booking->status === 'rejected')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-500/15 text-rose-200 border border-rose-500/40">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                                        مرفوض
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-sky-500/15 text-sky-200 border border-sky-500/40">
                                        <span class="w-1.5 h-1.5 rounded-full bg-sky-400 animate-pulse"></span>
                                        قيد المراجعة
                                    </span>
                                @endif
                            </td>

                            {{-- WhatsApp Multi-Stage Delivery Indicator --}}
                            <td class="px-4 py-3 text-center align-middle">
                                @if($booking->status === 'approved')
                                    <span class="adm-delivery-pill {{ $deliveryBadge['bg'] }}" title="{{ $deliveryBadge['title'] }}">
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
                                @elseif($booking->status === 'rejected')
                                    <span class="text-xs text-slate-500">—</span>
                                @else
                                    <span class="text-xs text-slate-500">بانتظار الاعتماد</span>
                                @endif
                            </td>

                            {{-- Reference Code --}}
                            <td class="px-4 py-3 align-middle font-mono text-xs text-slate-300" dir="ltr">
                                {{ $booking->reference_code }}
                            </td>

                            {{-- Action --}}
                            <td class="px-3 py-3 align-middle text-center">
                                <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                    <a href="{{ route('admin.bookings.show', $booking) }}"
                                       class="admin-btn admin-btn--primary admin-btn--sm">
                                        <span>تفاصيل</span>
                                        <svg class="w-3.5 h-3.5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
