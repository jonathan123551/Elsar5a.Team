@extends('layouts.admin')

@section('title', 'إدارة الحجوزات والتحويلات')

@section('content')
<style>
    /* Tap target baseline */
    .adm-tap {
        min-height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    /* Status-colored right accent border on mobile cards in RTL */
    .adm-card {
        position: relative;
        overflow: hidden;
    }
    .adm-card::before {
        content: "";
        position: absolute;
        inset-inline-start: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: var(--adm-accent, rgba(125,211,252,0.7));
        opacity: 0.85;
    }
    .adm-card[data-status="approved"] { --adm-accent: rgb(52,211,153); }
    .adm-card[data-status="rejected"] { --adm-accent: rgb(248,113,113); }
    .adm-card[data-status="pending"]  { --adm-accent: rgb(125,211,252); }

    /* Segmented status filter chips */
    .adm-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        min-height: 44px;
        padding: 0 14px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 500;
        color: rgb(209,213,219);
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08);
        transition: all .15s ease-in-out;
        white-space: nowrap;
        flex: 1 1 auto;
    }
    .adm-chip:active { transform: scale(.97); }
    .adm-chip.is-active {
        background: rgb(245, 158, 11);
        color: rgb(2, 6, 23);
        border-color: rgb(245, 158, 11);
        font-weight: 700;
    }
    .adm-chip[data-tone="pending"].is-active  { background: rgb(56, 189, 248); border-color: rgb(56, 189, 248); color: #020617; }
    .adm-chip[data-tone="approved"].is-active { background: rgb(52, 211, 153); border-color: rgb(52, 211, 153); color: #020617; }
    .adm-chip[data-tone="rejected"].is-active { background: rgb(248, 113, 113); border-color: rgb(248, 113, 113); color: #ffffff; }

    @media (max-width: 639px) {
        #statusFilter { display: none; }
    }

    /* Sticky filter bar */
    .adm-filter-bar {
        position: sticky;
        top: 68px;
        z-index: 30;
    }

    /* Thumbnail preview trigger */
    .proof-thumb-btn {
        position: relative;
        display: inline-block;
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.15);
        background: #020617;
        transition: all 0.2s ease;
    }
    .proof-thumb-btn:hover {
        border-color: rgba(245, 158, 11, 0.7);
        transform: scale(1.05);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.6);
    }

    /* Multi-stage delivery badge */
    .adm-delivery-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 9px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.02em;
        white-space: nowrap;
    }
</style>

<section class="space-y-4 sm:space-y-6">

    {{-- ------------------------------------------------------
         HEADER & PAGE CONTEXT
         ------------------------------------------------------ --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-black/40 border border-white/10 rounded-2xl p-4 backdrop-blur-md">
        <div class="min-w-0">
            <div class="flex items-center gap-2.5 mb-1">
                <svg class="w-6 h-6 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                </svg>
                <h1 class="text-lg sm:text-2xl font-bold font-heading text-white">إدارة الحجوزات والتحويلات</h1>
            </div>
            <p id="resultCount" class="text-xs text-slate-400 tabular-nums">
                متابعة طلبات الحجز، مطابقة التحويلات، وتتبع تسليم التذاكر عبر واتساب
            </p>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap self-start sm:self-auto">
            <button type="button"
                    id="openGlobalResendModal"
                    class="admin-btn admin-btn--indigo"
                    title="إرسال التذاكر غير المستلمة لكافة الحجوزات المعتمدة">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
                <span>إرسال التذاكر غير المستلمة (<span data-live-count="unreceived">{{ $unreceivedTicketsCount ?? 0 }}</span>)</span>
            </button>

            @include('admin.partials.back-button', ['url' => route('admin.dashboard'), 'label' => 'العودة للوحة التحكم'])
        </div>
    </div>

    {{-- ------------------------------------------------------
         STATUS COUNT SUMMARY
         ------------------------------------------------------ --}}
    @php
        $whatsappIssuesCount = $bookings->filter(fn($b) => $b->template_status === 'failed' || $b->tickets->where('delivery_status', 'failed')->isNotEmpty())->count();
        $counts = [
            'all'      => $bookings->count(),
            'pending'  => $bookings->where('status', 'pending')->count(),
            'approved' => $bookings->where('status', 'approved')->count(),
            'rejected' => $bookings->where('status', 'rejected')->count(),
            'issues'   => $whatsappIssuesCount,
        ];
    @endphp

    <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 sm:gap-2.5" role="tablist" aria-label="أقسام حالات الحجز">
            <button type="button" class="adm-chip is-active" data-status-jump="" data-tone="">
                <span class="opacity-80">الكل</span>
                <span class="font-bold tabular-nums" data-live-count="total">({{ $counts['all'] }})</span>
            </button>
            <button type="button" class="adm-chip" data-status-jump="pending" data-tone="pending">
                <span class="opacity-80">قيد المراجعة</span>
                <span class="font-bold tabular-nums" data-live-count="pending">({{ $counts['pending'] }})</span>
            </button>
            <button type="button" class="adm-chip" data-status-jump="approved" data-tone="approved">
                <span class="opacity-80">مقبول</span>
                <span class="font-bold tabular-nums" data-live-count="approved">({{ $counts['approved'] }})</span>
            </button>
            <button type="button" class="adm-chip" data-status-jump="rejected" data-tone="rejected">
                <span class="opacity-80">مرفوض</span>
                <span class="font-bold tabular-nums" data-live-count="rejected">({{ $counts['rejected'] }})</span>
            </button>
            <button type="button" class="adm-chip" data-status-jump="issues" data-tone="rejected">
                <span class="opacity-80">مشاكل واتساب</span>
                <span class="font-bold tabular-nums" data-live-count="issues">({{ $counts['issues'] }})</span>
            </button>
        </div>

    {{-- ------------------------------------------------------
         STICKY FILTER & SEARCH BAR
         ------------------------------------------------------ --}}
    <div class="adm-filter-bar bg-[#0b101b]/90 backdrop-blur-md border border-white/10 rounded-2xl p-3 sm:p-4 shadow-xl">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5 text-sm">
            {{-- Search Input --}}
            <div class="relative sm:col-span-1 lg:col-span-2">
                <div class="absolute inset-y-0 start-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input id="searchInput" type="search"
                       autocomplete="off"
                       enterkeyhint="search"
                       inputmode="search"
                       placeholder="بحث بالاسم / رقم الهاتف / الكود المرجعي / رقم الحجز (#3)"
                       class="w-full rounded-xl bg-black/60 border border-white/15 ps-10 pe-10
                              py-2.5 min-h-[44px] text-sm text-slate-100 placeholder:text-slate-500
                              focus:outline-none focus:border-amber-400/70 transition">
                <button id="searchClear" type="button"
                        aria-label="مسح نص البحث"
                        class="hidden absolute inset-y-0 end-2 my-auto h-8 w-8 rounded-full
                               bg-white/10 hover:bg-white/20 text-xs text-slate-300 items-center justify-center">
                    <svg class="w-3.5 h-3.5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Show / Showtime Dropdown --}}
            <div>
                <select id="dateTimeFilter"
                        aria-label="تصفية حسب موعد العرض"
                        class="w-full rounded-xl bg-black/60 border border-white/15 px-3
                               py-2.5 min-h-[44px] text-sm text-slate-200
                               focus:outline-none focus:border-amber-400/70 transition">
                    <option value="">جميع المواعيد والعروض</option>
                    @foreach(
                        $bookings->map(fn($b) => $b->showTime
                            ? $b->showTime->date->format('Y-m-d').' '.$b->showTime->time
                            : null)->filter()->unique()->sort()
                        as $dt
                    )
                        <option value="{{ $dt }}">
                            {{ \Carbon\Carbon::parse($dt)->format('d/m/Y • g:i A') }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Hidden status select for JS filter consistency --}}
        <select id="statusFilter" class="hidden" aria-hidden="true">
            <option value="">كل الحالات</option>
            <option value="pending">قيد المراجعة</option>
            <option value="approved">مقبول</option>
            <option value="rejected">مرفوض</option>
            <option value="issues">مشاكل واتساب</option>
        </select>
    </div>

    @if($bookings->isEmpty())
        {{-- Empty State --}}
        <div class="bg-black/40 border border-white/10 rounded-2xl p-10 text-center space-y-3">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center text-slate-400">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
            </div>
            <h2 class="text-base font-semibold text-gray-200">لا توجد حجوزات مسجلة بعد</h2>
            <p class="text-xs text-gray-400 max-w-md mx-auto leading-relaxed">
                بمجرد إتمام الحجز والدفع من قبل الجمهور، ستظهر طلبات الحجز هنا تلقائياً لمراجعة صورة التحويل واعتماد التذاكر.
            </p>
        </div>
    @else

    {{-- ------------------------------------------------------
         💻 DESKTOP TABLE
         ------------------------------------------------------ --}}
    <div class="hidden md:block">
        <div class="bg-black/40 border border-white/10 rounded-2xl overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right text-slate-200">
                    <thead class="bg-white/5 text-xs text-slate-400 uppercase tracking-wider border-b border-white/10">
                        <tr>
                            <th class="px-3 py-3 font-semibold text-center w-16">#</th>
                            <th class="px-4 py-3 font-semibold">بيانات العميل</th>
                            <th class="px-4 py-3 font-semibold">العرض والموعد</th>
                            <th class="px-4 py-3 font-semibold">المبلغ والتحويل</th>
                            <th class="px-4 py-3 font-semibold text-center">حالة الحجز</th>
                            <th class="px-4 py-3 font-semibold text-center">واتساب والتسليم</th>
                            <th class="px-4 py-3 font-semibold">الكود</th>
                            <th class="px-3 py-3 font-semibold text-center w-24">الإجراء</th>
                        </tr>
                    </thead>
                    <tbody id="bookingTableRows" class="divide-y divide-white/5">
                    @include('admin.bookings.partials.booking-table-rows')
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ------------------------------------------------------
         📱 MOBILE CARDS
         ------------------------------------------------------ --}}
    <div id="bookingMobileCards" class="md:hidden space-y-3">
        @include('admin.bookings.partials.booking-mobile-cards')
    </div>

    {{-- "No matches" empty state --}}
    <div id="noMatchesEmpty" class="hidden bg-black/40 border border-white/10 rounded-2xl p-8 text-center space-y-3">
        <div class="w-12 h-12 mx-auto rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-slate-400">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
        <p class="text-sm text-gray-200">لا توجد حجوزات تطابق معايير البحث الحالية.</p>
        <button type="button" id="clearFilters"
                class="admin-btn admin-btn--secondary admin-btn--sm mt-2">
            مسح جميع الفلاتر
        </button>
    </div>

    @endif

</section>

{{-- =========================================================
     INTERACTIVE SCREENSHOT PREVIEW MODAL
     ========================================================= --}}
<div id="adm-screenshot-modal"
     class="fixed inset-0 z-50 hidden items-center justify-center p-3 sm:p-6 bg-black/85 backdrop-blur-md transition-opacity duration-200"
     role="dialog"
     aria-modal="true"
     aria-labelledby="adm-modal-title">
    <div class="relative max-w-xl w-full bg-[#0b0f19] border border-white/15 rounded-2xl overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
        <div class="flex items-center justify-between p-3.5 border-b border-white/10 bg-white/5">
            <h3 id="adm-modal-title" class="text-sm sm:text-base font-bold text-white truncate pe-2">
                إثبات التحويل
            </h3>
            <button type="button"
                    id="adm-modal-close"
                    class="w-9 h-9 rounded-xl bg-white/10 hover:bg-white/20 text-slate-200 flex items-center justify-center transition focus:outline-none"
                    aria-label="إغلاق المعاينة">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="p-3 overflow-auto flex-1 flex items-center justify-center bg-black/50">
            <img id="adm-modal-img"
                 src=""
                 alt="لقطة شاشة التحويل بالحجم الكامل"
                 class="max-w-full max-h-[70vh] object-contain rounded-lg border border-white/10">
        </div>

        <div class="p-3 border-t border-white/10 bg-white/5 flex items-center justify-between">
            <span class="text-xs text-slate-400">انقر خارج النافذة أو زر الإغلاق للخروج</span>
            <a id="adm-modal-link"
               href=""
               target="_blank"
               rel="noopener"
               class="admin-btn admin-btn--primary admin-btn--sm">
                <span>فتح الرابط الأصلي</span>
                <svg class="w-3.5 h-3.5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
            </a>
        </div>
    </div>
</div>

{{-- =========================================================
     GLOBAL SEND UNRECEIVED TICKETS CONFIRMATION MODAL
     ========================================================= --}}
<div id="adm-global-resend-modal"
     class="fixed inset-0 z-50 hidden items-center justify-center p-3 sm:p-4 bg-black/85 backdrop-blur-md"
     role="dialog"
     aria-modal="true"
     aria-labelledby="adm-global-resend-title">
    <div class="relative w-full max-w-md bg-[#0f172a] border border-white/20 rounded-2xl shadow-2xl p-5 sm:p-6 text-right">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-white/10">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-indigo-500/20 text-indigo-400 flex items-center justify-center">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </div>
                <h3 id="adm-global-resend-title" class="text-base font-bold text-white">تأكيد الإرسال الشامل للتذاكر</h3>
            </div>
            <button type="button"
                    id="adm-global-resend-close"
                    class="w-8 h-8 rounded-lg bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white flex items-center justify-center transition"
                    aria-label="إغلاق">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="space-y-3 mb-5">
            <p class="text-sm text-slate-100 font-semibold leading-relaxed">
                سيتم إرسال التذاكر غير المستلمة إلى أصحابها — العدد: <span class="text-amber-400 font-bold tabular-nums text-base">{{ $unreceivedTicketsCount ?? 0 }}</span>
            </p>
            <p class="text-xs text-slate-400 leading-normal">
                سيتم إرسال التذاكر مباشرة عبر واتساب للعملاء المتاحين، أو إرسال قوالب الاستلام لتجديد نافذة الـ 24 ساعة، مع تخطي التذاكر المستلمة مسبقاً تلقائياً لمنع التكرار.
            </p>
        </div>

        <form action="{{ route('admin.bookings.send-all-unreceived') }}"
              method="POST"
              data-processing-message="جاري معالجة وإرسال التذاكر الشامل عبر واتساب…">
            @csrf
            <div class="flex items-center justify-end gap-2.5">
                <button type="button"
                        id="adm-global-resend-cancel"
                        class="admin-btn admin-btn--secondary admin-btn--sm">
                    إلغاء
                </button>
                <button type="submit"
                        class="admin-btn admin-btn--indigo admin-btn--sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    <span>تأكيد وإرسال الآن</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var search       = document.getElementById('searchInput');
    var status       = document.getElementById('statusFilter');
    var dt           = document.getElementById('dateTimeFilter');
    var clearBtn     = document.getElementById('searchClear');
    var clearAllBtn  = document.getElementById('clearFilters');
    var resultCount  = document.getElementById('resultCount');
    var noMatches    = document.getElementById('noMatchesEmpty');
    var statusChips  = document.querySelectorAll('[data-status-jump]');
    var totalItems   = document.querySelectorAll('.booking-row').length;

    function filter() {
        if (!search) return;
        var s = (search.value || '').toLowerCase().trim();
        var st = status.value;
        var when = dt.value;

        var hashMatch = s.match(/^#?\s*(\d+)$/);
        var pnQuery = hashMatch ? hashMatch[1] : null;
        var searchQuery = pnQuery ? '' : s;

        function matches(el) {
            if (pnQuery !== null && el.dataset.pn !== pnQuery) return false;
            if (searchQuery && !el.dataset.search.includes(searchQuery)) return false;
            if (st === 'issues') {
                if (el.dataset.hasIssues !== '1') return false;
            } else if (st && el.dataset.status !== st) {
                return false;
            }
            if (when && el.dataset.datetime !== when) return false;
            return true;
        }

        var visibleRows = 0;
        var rows = document.querySelectorAll('.booking-row');
        var cards = document.querySelectorAll('.booking-card');
        totalItems = rows.length;
        rows.forEach(function (el) {
            var ok = matches(el);
            el.style.display = ok ? '' : 'none';
            if (ok) visibleRows++;
        });
        cards.forEach(function (el) {
            el.style.display = matches(el) ? '' : 'none';
        });

        if (clearBtn) clearBtn.classList.toggle('hidden', !s);
        if (noMatches) {
            noMatches.classList.toggle('hidden', visibleRows > 0 || totalItems === 0);
        }

        if (resultCount) {
            resultCount.innerText = (s || st || when)
                ? 'نتائج البحث: ' + visibleRows + ' من أصل ' + totalItems + ' حجز'
                : 'إجمالي الحجوزات: ' + totalItems + ' حجز';
        }
    }
    window.adminBookingRefilter = filter;

    function syncChips() {
        statusChips.forEach(function (c) {
            c.classList.toggle('is-active', (c.dataset.statusJump || '') === (status.value || ''));
        });
    }

    if (search && status && dt) {
        [search, status, dt].forEach(function (i) {
            i.addEventListener('input', function () { filter(); syncChips(); });
            i.addEventListener('change', function () { filter(); syncChips(); });
        });

        statusChips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                status.value = chip.dataset.statusJump || '';
                filter();
                syncChips();
            });
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                search.value = '';
                filter();
                search.focus();
            });
        }
        if (clearAllBtn) {
            clearAllBtn.addEventListener('click', function () {
                search.value = '';
                status.value = '';
                dt.value = '';
                filter();
                syncChips();
            });
        }

        filter();
        syncChips();
    }

    // Modal Image Preview
    var modal = document.getElementById('adm-screenshot-modal');
    var modalImg = document.getElementById('adm-modal-img');
    var modalTitle = document.getElementById('adm-modal-title');
    var modalLink = document.getElementById('adm-modal-link');
    var modalClose = document.getElementById('adm-modal-close');

    function openModal(src, title) {
        if (!modal || !modalImg) return;
        modalImg.src = src;
        modalTitle.textContent = title || 'إثبات التحويل';
        modalLink.href = src;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal() {
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        if (modalImg) modalImg.src = '';
    }

    document.addEventListener('click', function (e) {
        var btn = e.target && e.target.closest ? e.target.closest('[data-preview-img]') : null;
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        openModal(btn.getAttribute('data-preview-img'), btn.getAttribute('data-preview-title'));
    });

    if (modalClose) modalClose.addEventListener('click', closeModal);
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModal();
        });
    }

    // Global Send Unreceived Tickets Modal
    var globalResendModal = document.getElementById('adm-global-resend-modal');
    var openGlobalResendBtn = document.getElementById('openGlobalResendModal');
    var closeGlobalResendBtn = document.getElementById('adm-global-resend-close');
    var cancelGlobalResendBtn = document.getElementById('adm-global-resend-cancel');

    function openGlobalResend() {
        if (!globalResendModal) return;
        globalResendModal.classList.remove('hidden');
        globalResendModal.classList.add('flex');
    }

    function closeGlobalResend() {
        if (!globalResendModal) return;
        globalResendModal.classList.add('hidden');
        globalResendModal.classList.remove('flex');
    }

    if (openGlobalResendBtn) openGlobalResendBtn.addEventListener('click', openGlobalResend);
    if (closeGlobalResendBtn) closeGlobalResendBtn.addEventListener('click', closeGlobalResend);
    if (cancelGlobalResendBtn) cancelGlobalResendBtn.addEventListener('click', closeGlobalResend);
    if (globalResendModal) {
        globalResendModal.addEventListener('click', function (e) {
            if (e.target === globalResendModal) closeGlobalResend();
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            if (modal && !modal.classList.contains('hidden')) closeModal();
            if (globalResendModal && !globalResendModal.classList.contains('hidden')) closeGlobalResend();
        }
    });

    // -------------------------------------------------------------
    // LIVE BOOKING MANAGEMENT STATUS POLLING (3s incremental updates)
    // -------------------------------------------------------------
    var pollInterval = 3000;
    var pollTimer = null;
    var abortCtrl = null;
    var latestKnownId = {{ (int) $bookings->max('id') }};
    var lastPollCursor = @json($pollCursor);

    function mergeBookingMarkup(container, markup, selector, completeSnapshot) {
        if (!container || typeof markup !== 'string') return;
        var template = document.createElement('template');
        template.innerHTML = markup.trim();
        var incoming = Array.prototype.slice.call(template.content.querySelectorAll(selector));
        var desiredIds = {};

        incoming.forEach(function (fresh) {
            var id = fresh.dataset.bookingId;
            if (!id) return;
            desiredIds[id] = true;
            var current = container.querySelector(selector + '[data-booking-id="' + id + '"]');
            var comparableCurrent = current && current.cloneNode(true);
            if (comparableCurrent) comparableCurrent.removeAttribute('style');
            var comparableFresh = fresh.cloneNode(true);
            comparableFresh.removeAttribute('style');
            if (current && comparableCurrent.outerHTML !== comparableFresh.outerHTML) {
                var display = current.style.display;
                fresh.style.display = display;
                current.replaceWith(fresh);
                current = fresh;
            } else if (!current) {
                current = fresh;
                var before = Array.prototype.slice.call(container.querySelectorAll(selector)).find(function (item) {
                    var itemSort = Number(item.dataset.bookingSort || 0);
                    var freshSort = Number(current.dataset.bookingSort || 0);
                    if (itemSort !== freshSort) return itemSort < freshSort;
                    return Number(item.dataset.bookingId) < Number(current.dataset.bookingId);
                });
                container.insertBefore(current, before || null);
            }
        });

        if (completeSnapshot) {
            Array.prototype.slice.call(container.querySelectorAll(selector)).forEach(function (item) {
                if (!desiredIds[item.dataset.bookingId]) item.remove();
            });
        }
    }

    function pollIndex() {
        if (document.hidden) return;
        if (abortCtrl) {
            try { abortCtrl.abort(); } catch (e) {}
        }
        if (window.AbortController) {
            abortCtrl = new AbortController();
        }

        var pollUrl = new URL("{{ route('admin.bookings.poll-status') }}", window.location.origin);
        pollUrl.searchParams.set('index', '1');
        if (lastPollCursor) pollUrl.searchParams.set('since', lastPollCursor);
        pollUrl.searchParams.set('after_id', String(latestKnownId));
        fetch(pollUrl.toString(), {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' },
            signal: abortCtrl ? abortCtrl.signal : undefined
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!data || !data.ok || !data.summary) return;
            if (data.cursor) lastPollCursor = data.cursor;
            var s = data.summary;

            // 1. Update Global Send Unreceived Button count live
            var liveCounts = {
                total: s.total_bookings,
                pending: s.pending_bookings,
                approved: s.approved_bookings,
                rejected: s.rejected_bookings,
                issues: s.issue_bookings,
                unreceived: s.unreceived_tickets_count
            };
            Object.keys(liveCounts).forEach(function (key) {
                var countNode = document.querySelector('[data-live-count="' + key + '"]');
                if (countNode && Number.isFinite(Number(liveCounts[key]))) {
                    countNode.textContent = key === 'unreceived' ? String(liveCounts[key]) : '(' + liveCounts[key] + ')';
                }
            });
            if (data.index_rows) {
                mergeBookingMarkup(document.getElementById('bookingTableRows'), data.index_rows.table, '.booking-row', data.index_rows.complete);
                mergeBookingMarkup(document.getElementById('bookingMobileCards'), data.index_rows.mobile, '.booking-card', data.index_rows.complete);
                latestKnownId = Math.max(latestKnownId, s.latest_booking_id || 0);
                totalItems = Number(s.total_bookings || 0);
                if (window.adminBookingRefilter) window.adminBookingRefilter();
            }
        })
        .catch(function () {});
    }

    function startIndexPoll() {
        if (!pollTimer) pollTimer = setInterval(pollIndex, pollInterval);
    }
    function stopIndexPoll() {
        if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
    }
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) stopIndexPoll();
        else { pollIndex(); startIndexPoll(); }
    });
    startIndexPoll();
});
</script>
@endsection
