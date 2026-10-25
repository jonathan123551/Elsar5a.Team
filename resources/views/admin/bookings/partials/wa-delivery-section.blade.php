@php
    $totalCount          = $booking->ticketsTotalCount();
    $deliveredCount      = $booking->ticketsDeliveredCount();
    $sendingCount        = $booking->ticketsSendingCount();
    $sentCount           = $booking->ticketsSentCount();
    $failedCount         = $booking->ticketsFailedCount();
    $claimedCount        = $booking->tickets->filter(fn($t) => !is_null($t->claimed_at))->count();
    $isTemplateFailed    = $booking->template_status === 'failed';
    $isTemplateDelivered = in_array($booking->template_status, ['delivered', 'read'], true);
    $isTemplateSent      = in_array($booking->template_status, ['sent', 'delivered', 'read'], true);
    $isAllTicketsDelivered = ($deliveredCount === $totalCount) && ($totalCount > 0);
@endphp

        {{-- Section Title & Aggregate Status Pill --}}
        <div class="flex items-center justify-between gap-3 border-b border-white/10 pb-3.5 flex-wrap">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-bold font-heading text-white">حالة تسليم التذاكر عبر واتساب</h2>
                    <p class="text-xs text-slate-400">متابعة دقيقة لمراحل الإرسال، قالب Meta، وتفاعل العميل</p>
                </div>
            </div>

            {{-- Aggregate State Pill --}}
            <div>
                @if($isAllTicketsDelivered)
                    <span id="deliverySummaryPill" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-emerald-500/15 text-emerald-300 border border-emerald-500/40">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        <span>تم تسليم جميع التذاكر بالكامل ({{ $deliveredCount }}/{{ $totalCount }})</span>
                    </span>
                @elseif($isTemplateFailed)
                    <span id="deliverySummaryPill" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-rose-500/15 text-rose-300 border border-rose-500/40">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>فشل إرسال قالب استلام التذاكر</span>
                    </span>
                @elseif($failedCount > 0)
                    <span id="deliverySummaryPill" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-rose-500/15 text-rose-300 border border-rose-500/40">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>تعذر تسليم {{ $failedCount }} تذكرة ({{ $deliveredCount }}/{{ $totalCount }})</span>
                    </span>
                @elseif($booking->isAwaitingCustomerClick())
                    <span id="deliverySummaryPill" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-sky-500/15 text-sky-300 border border-sky-500/40">
                        <span class="w-2 h-2 rounded-full bg-sky-400 animate-pulse"></span>
                        <span>أُرسل القالب · بانتظار نقر العميل على واتساب</span>
                    </span>
                @elseif($sendingCount > 0 || $booking->template_status === 'sending')
                    <span id="deliverySummaryPill" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-amber-500/15 text-amber-300 border border-amber-500/40">
                        <span class="btn-spinner"></span>
                        <span>جاري الإرسال لـ Meta</span>
                    </span>
                @elseif($sentCount > 0)
                    <span id="deliverySummaryPill" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-cyan-500/15 text-cyan-300 border border-cyan-500/40">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        <span>أُرسلت التذاكر للشبكة ({{ $sentCount }}/{{ $totalCount }})</span>
                    </span>
                @elseif($booking->status === 'approved')
                    <span id="deliverySummaryPill" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-white/5 text-slate-400 border border-white/10">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span>
                        <span>معتمد (لم يُرسل)</span>
                    </span>
                @else
                    <span id="deliverySummaryPill" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-white/5 text-slate-400 border border-white/10">
                        <span>بانتظار اعتماد الحجز أولاً</span>
                    </span>
                @endif
            </div>
        </div>

        {{-- 4-Point Clean Summary Strip (Visible by default) --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 p-3 rounded-xl bg-black/40 border border-white/10 text-xs">
            <div>
                <span class="text-slate-400 block text-[11px] mb-0.5">● حالة الإرسال:</span>
                <span id="summarySendState" class="font-bold text-white">
                    @if($isAllTicketsDelivered)
                        <span class="text-emerald-400">تم التسليم بالكامل</span>
                    @elseif($isTemplateFailed || $failedCount > 0)
                        <span class="text-rose-400">تعذر الإرسال</span>
                    @elseif($sendingCount > 0 || $booking->template_status === 'sending')
                        <span class="text-amber-300">جاري الإرسال…</span>
                    @elseif($sentCount > 0)
                        <span class="text-cyan-300">أُرسلت للشبكة</span>
                    @elseif($isTemplateSent)
                        <span class="text-sky-300">أُرسل القالب</span>
                    @else
                        <span class="text-slate-400">لم يُرسل بعد</span>
                    @endif
                </span>
            </div>
            <div>
                <span class="text-slate-400 block text-[11px] mb-0.5">● حالة Meta:</span>
                <span id="summaryMetaState" class="font-bold text-white">
                    @if($isTemplateDelivered || $isAllTicketsDelivered)
                        <span class="text-emerald-400">تم التسليم (مؤكد)</span>
                    @elseif($isTemplateFailed)
                        <span class="text-rose-400">فشل لدى Meta</span>
                    @elseif($sendingCount > 0 || $booking->template_status === 'sending')
                        <span class="text-amber-300">جاري الإرسال…</span>
                    @elseif($sentCount > 0 || $isTemplateSent)
                        <span class="text-cyan-300">أُرسلت (مقبولة)</span>
                    @else
                        <span class="text-slate-400">—</span>
                    @endif
                </span>
            </div>
            <div>
                <span class="text-slate-400 block text-[11px] mb-0.5">● حالة العميل:</span>
                <span id="summaryCustomerState" class="font-bold text-white">
                    @if($claimedCount > 0 && !$booking->isAwaitingCustomerClick())
                        <span class="text-emerald-400">تم طلب التذكرة</span>
                    @elseif($isTemplateFailed)
                        <span class="text-rose-400">تعذر النقر</span>
                    @elseif($booking->status === 'approved')
                        <span class="text-sky-300">بانتظار النقر</span>
                    @else
                        <span class="text-slate-400">—</span>
                    @endif
                </span>
            </div>
            <div>
                <span class="text-slate-400 block text-[11px] mb-0.5">● حالة التذاكر:</span>
                <span id="summaryTicketsState" class="font-bold text-white">
                    @if($isAllTicketsDelivered)
                        <span class="text-emerald-400">تم التسليم ({{ $deliveredCount }}/{{ $totalCount }})</span>
                    @elseif($failedCount > 0)
                        <span class="text-rose-400">فشل ({{ $failedCount }}/{{ $totalCount }})</span>
                    @elseif($sentCount > 0)
                        <span class="text-amber-300">أُرسلت ({{ $sentCount }}/{{ $totalCount }})</span>
                    @else
                        <span class="text-slate-400">لم تُرسل بعد</span>
                    @endif
                </span>
            </div>
        </div>

        {{-- Error Banner with Human-Readable Meta Error Hint --}}
        @if($isTemplateFailed || $booking->template_error || $failedCount > 0)
            <div id="metaErrorBanner" class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-xs text-rose-200 flex items-start gap-2.5">
                <svg class="w-4 h-4 text-rose-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div class="space-y-0.5">
                    <span class="font-bold block text-rose-300">⚠️ تنبيه خطأ واتساب (Meta):</span>
                    <span class="block text-rose-100">
                        {{ $booking->template_error ?? ($booking->tickets->where('delivery_status', 'failed')->first()?->delivery_error ?? 'تعذر وصول الرسالة إلى هذا الرقم عبر خوادم WhatsApp.') }}
                    </span>
                </div>
            </div>
        @endif

        {{-- Collapsible Dropdown Details (Closed by default to keep page compact) --}}
        <details class="group rounded-xl border border-white/10 bg-black/40 overflow-hidden" data-details-key="timeline">
            <summary class="flex items-center justify-between text-xs text-amber-400 hover:text-amber-300 font-bold cursor-pointer py-2.5 px-3.5 bg-white/5 hover:bg-white/10 transition select-none">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-400 transition-transform duration-200 group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                    <span>▼ عرض تفاصيل الإرسال والمراحل (Meta & Webhook)</span>
                </span>
                <span class="text-[11px] text-slate-400 font-normal">المراحل الثلاث بالتفصيل</span>
            </summary>

            <div class="p-3.5 space-y-3 border-t border-white/10">
                {{-- 3-Step Clear Timeline Overview --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    {{-- Step 1: Meta Template --}}
                    <div class="p-3 rounded-xl bg-black/50 border border-white/10 space-y-1.5">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-400 font-medium">1. قالب الاستلام (Meta)</span>
                            @if($booking->template_status === 'delivered')
                                <span class="text-emerald-400 font-bold flex items-center gap-1">
                                    <span>✓ تم التسليم</span>
                                    @if($booking->template_sent_at)
                                        <span class="text-[10px] text-slate-400 font-normal">({{ $booking->template_sent_at->format('g:i A') }})</span>
                                    @endif
                                </span>
                            @elseif(in_array($booking->template_status, ['sent', 'delivered', 'read'], true))
                                <span class="text-emerald-400 font-bold flex items-center gap-1">
                                    <span>✓ تم الإرسال</span>
                                    @if($booking->template_sent_at)
                                        <span class="text-[10px] text-slate-400 font-normal">({{ $booking->template_sent_at->format('g:i A') }})</span>
                                    @endif
                                </span>
                            @elseif($booking->template_status === 'failed')
                                <span class="text-rose-400 font-bold">✕ فشل الإرسال</span>
                            @elseif($booking->template_status === 'sending')
                                <span class="text-amber-400 font-bold">جاري الإرسال…</span>
                            @else
                                <span class="text-slate-500">معلق (لم يُرسل)</span>
                            @endif
                        </div>
                        <div class="text-[11px] leading-tight text-slate-300">
                            @if($booking->template_status === 'delivered')
                                <p>وصل القالب بنجاح على تطبيق واتساب لدى العميل.</p>
                            @elseif(in_array($booking->template_status, ['sent', 'delivered', 'read'], true))
                                <p>أُرسل للعميل بنجاح وبانتظار تأكيد استلامه على الهاتف.</p>
                            @elseif($booking->template_status === 'failed')
                                <div class="p-2 rounded-lg bg-rose-500/10 border border-rose-500/30 text-rose-300 space-y-0.5">
                                    <span class="font-bold block">⚠️ لم يصل قالب الاستلام</span>
                                    <span class="text-rose-200 block">{{ $booking->template_error ?? 'Meta رفضت إرسال القالب لهذا الرقم.' }}</span>
                                </div>
                            @else
                                <p class="text-slate-400">يُرسل تلقائياً لفتح نافذة واتساب الرسمية فور اعتماد الحجز.</p>
                            @endif
                        </div>
                    </div>

                    {{-- Step 2: Customer Tap --}}
                    <div class="p-3 rounded-xl bg-black/50 border border-white/10 space-y-1.5">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-400 font-medium">2. نقر العميل على الاستلام</span>
                            @if($claimedCount > 0 && !$booking->isAwaitingCustomerClick())
                                <span class="text-emerald-400 font-bold flex items-center gap-1">
                                    <span>✓ نقر العميل</span>
                                    @if($firstClaimedAt = $booking->tickets->whereNotNull('claimed_at')->first()?->claimed_at)
                                        <span class="text-[10px] text-slate-400 font-normal">({{ $firstClaimedAt->format('g:i A') }})</span>
                                    @endif
                                </span>
                            @elseif($isTemplateFailed)
                                <span class="text-rose-400 font-bold">✕ تعذر (فشل القالب)</span>
                            @elseif($booking->status === 'approved')
                                <span class="text-sky-400 font-medium">⏳ بانتظار النقر</span>
                            @else
                                <span class="text-slate-500">معلق</span>
                            @endif
                        </div>
                        <div class="text-[11px] leading-tight text-slate-300">
                            @if($claimedCount > 0 && !$booking->isAwaitingCustomerClick())
                                <p>تم استقبال تفاعل العميل بنجاح عبر Webhook وجاري معالجة إرسال التذاكر.</p>
                            @elseif($isTemplateFailed)
                                <p class="text-rose-300">لم يصل القالب للعميل، لذلك لم يتمكن من النقر على زر الاستلام.</p>
                            @else
                                <p class="text-slate-400">فور نقر العميل على زر "استلام التذكرة"، تُرسل صور التذاكر فورياً.</p>
                            @endif
                        </div>
                    </div>

                    {{-- Step 3: Direct Ticket Delivery --}}
                    <div class="p-3 rounded-xl bg-black/50 border border-white/10 space-y-1.5">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-400 font-medium">3. تسليم صور التذاكر (QR)</span>
                            <span class="font-bold tabular-nums {{ $isAllTicketsDelivered ? 'text-emerald-400' : ($failedCount > 0 ? 'text-rose-400' : ($sentCount > 0 ? 'text-cyan-300' : 'text-slate-300')) }}">
                                @if($isAllTicketsDelivered)
                                    ✓ تم التسليم ({{ $deliveredCount }}/{{ $totalCount }})
                                @elseif($failedCount > 0)
                                    ✕ فشل الإرسال ({{ $deliveredCount }}/{{ $totalCount }})
                                @elseif($sendingCount > 0)
                                    جاري الإرسال… ({{ $sentCount }}/{{ $totalCount }})
                                @elseif($sentCount > 0)
                                    أُرسلت للشبكة ({{ $sentCount }}/{{ $totalCount }})
                                @elseif($claimedCount > 0 && !$booking->isAwaitingCustomerClick())
                                    نقر العميل · بانتظار البدء (0/{{ $totalCount }})
                                @else
                                    لم تُرسل بعد (0/{{ $totalCount }})
                                @endif
                            </span>
                        </div>
                        <div class="text-[11px] leading-tight text-slate-300">
                            @if($isAllTicketsDelivered)
                                <p class="text-emerald-300 font-medium">وصلت جميع التذاكر على هاتف العميل بالكامل.</p>
                            @elseif($failedCount > 0)
                                <div class="p-2 rounded-lg bg-rose-500/10 border border-rose-500/30 text-rose-300 space-y-0.5">
                                    <span class="font-bold block">⚠️ التذكرة لم تصل للعميل</span>
                                    <span class="text-rose-200 block">{{ $booking->tickets->where('delivery_status', 'failed')->first()?->delivery_error ?? 'حدث خطأ أثناء إرسال التذكرة عبر WhatsApp' }}</span>
                                </div>
                            @elseif($sendingCount > 0)
                                <p class="text-amber-300">جاري إرسال صور الـ QR إلى واتساب العميل…</p>
                            @elseif($sentCount > 0)
                                <p class="text-cyan-300">أُرسلت صور التذاكر لخوادم Meta بنجاح، بانتظار استلام جهاز العميل.</p>
                            @elseif($claimedCount > 0 && !$booking->isAwaitingCustomerClick())
                                <p class="text-amber-300">ضغط العميل للاستلام وجاري إرسال صور الـ QR.</p>
                            @elseif($booking->isAwaitingCustomerClick())
                                <p class="text-slate-400">بانتظار نقر العميل لإرسال صور التذاكر.</p>
                            @else
                                <p class="text-slate-400">لم تُرسل التذاكر بعد.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </details>

        {{-- Collapsible Ticket Operational Breakdown --}}
        <details class="group rounded-2xl bg-black/30 border border-white/10 overflow-hidden" data-details-key="tickets" open>
            <summary class="cursor-pointer flex items-center justify-between p-3.5 sm:p-4 bg-white/[0.03] hover:bg-white/[0.06] transition text-xs sm:text-sm font-bold text-white select-none">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                    </svg>
                    <span>تفاصيل التذاكر الفردية ({{ $totalCount }})</span>
                </span>
                <span class="flex items-center gap-1.5 text-xs text-slate-400 font-normal">
                    <span>عرض / إخفاء تفاصيل التذاكر</span>
                    <svg class="w-4 h-4 transition-transform duration-200 group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </span>
            </summary>

            <div class="p-3 sm:p-4 space-y-3 border-t border-white/5">
                @forelse($booking->tickets as $ticket)
                    @php
                        $isTicketDelivered = $ticket->isTicketDeliveredOrReceived();
                        $isTicketSent      = $ticket->isPendingNetworkDelivery();
                        $isTicketFailed    = ($ticket->delivery_status === 'failed');
                    @endphp
                    <div class="adm2-stub">
                        <div class="who min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="name">{{ $ticket->name }}</span>
                                @if($ticket->is_scanned)
                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 font-bold">
                                        ✓ تم الدخول على الباب
                                    </span>
                                @endif
                            </div>

                            <p class="phone" dir="ltr">{{ $ticket->phone }}</p>

                            @if($ticket->ticket_code)
                                <span class="code" dir="ltr">{{ $ticket->ticket_code }}</span>
                            @endif

                            {{-- Detailed Specific Error Display --}}
                            @if($ticket->delivery_error)
                                <div class="mt-2.5 p-2.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-200 text-xs space-y-1">
                                    <div class="font-bold flex items-center gap-1.5 text-rose-300">
                                        <svg class="w-3.5 h-3.5 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                        <span>سبب تعذر تسليم التذكرة:</span>
                                    </div>
                                    <p class="text-rose-300 text-[11px] leading-relaxed">{{ $ticket->delivery_error }}</p>
                                </div>
                            @endif
                        </div>

                        {{-- LED Indicator & Status --}}
                        <div class="led shrink-0">
                            <div class="bulb" data-tone="{{ $isTicketDelivered ? 'ok' : ($isTicketFailed ? 'fail' : ($isTicketSent ? 'wait' : 'wait')) }}" aria-hidden="true"></div>
                            <div id="ticket-status-{{ $ticket->id }}" class="text-[11px] font-semibold {{ $isTicketDelivered ? 'text-emerald-300' : ($isTicketFailed ? 'text-rose-300' : ($isTicketSent ? 'text-amber-300' : 'text-sky-300')) }}">
                                @if($isTicketDelivered)
                                    تم التسليم
                                @elseif($isTicketFailed)
                                    تعذر التسليم
                                @elseif($isTicketSent)
                                    أُرسلت للشبكة
                                @else
                                    بانتظار الاستلام
                                @endif
                            </div>
                        </div>

                        {{-- Per-Ticket Operational Actions --}}
                        @if($booking->status === 'approved')
                            <div class="adm2-stub-acts">
                                @if($ticket->qr_image_path)
                                    <a href="{{ $ticket->qr_image_path }}"
                                       target="_blank" rel="noopener"
                                       class="admin-btn admin-btn--secondary admin-btn--sm">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <span>معاينة صورة التذكرة</span>
                                    </a>
                                @else
                                    <span class="admin-btn admin-btn--secondary admin-btn--sm opacity-50 cursor-not-allowed">
                                        <span class="btn-spinner"></span>
                                        <span>جاري إنشاء التذكرة…</span>
                                    </span>
                                @endif

                                <form action="{{ route('admin.resend.ticket', $ticket->id) }}"
                                      method="POST"
                                      data-processing-message="جاري إعادة إرسال التذكرة للرقم عبر واتساب…">
                                    @csrf
                                    @if($isTicketDelivered)
                                        <button type="submit" class="admin-btn admin-btn--secondary admin-btn--sm opacity-80" title="تم تسليم التذكرة بنجاح مسبقاً — انقر لإعادة الإرسال عند رغبة العميل">
                                            <svg class="w-3.5 h-3.5 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            <span>تم التسليم (إعادة)</span>
                                        </button>
                                    @elseif($isTicketFailed)
                                        <button type="submit" class="admin-btn admin-btn--danger admin-btn--sm" title="تعذر إرسال التذكرة — انقر لإعادة المحاولة">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                            <span>إعادة المحاولة</span>
                                        </button>
                                    @else
                                        <button type="submit" class="admin-btn admin-btn--warning admin-btn--sm" title="إرسال التذكرة للرقم">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                            <span>إرسال التذكرة</span>
                                        </button>
                                    @endif
                                </form>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-center py-6 text-xs text-slate-400">
                        لا توجد تذاكر مرتبطة بهذا الحجز.
                    </div>
                @endforelse
            </div>
        </details>
