@extends('layouts.admin')

@section('title', 'تعديل موعد - ' . $show->title)

@section('content')
<section class="max-w-2xl mx-auto space-y-5" dir="rtl">

    {{-- ------------------------------------------------------
         HEADER & PAGE CONTEXT
         ------------------------------------------------------ --}}
    <div class="flex items-center justify-between gap-3 bg-black/40 border border-white/10 rounded-2xl p-4 backdrop-blur-md">
        <div class="min-w-0">
            <h1 class="text-lg sm:text-2xl font-bold font-heading text-white">تعديل الموعد</h1>
            <p class="text-xs text-slate-400 truncate mt-0.5">
                تحديث بيانات الموعد والتسعير والتذاكر لعرض: <span class="text-amber-300 font-medium">{{ $show->title }}</span>
            </p>
        </div>

        @include('admin.partials.back-button', ['url' => route('admin.shows.times.index', $show), 'label' => 'العودة للمواعيد'])
    </div>

    {{-- Global Validation Errors --}}
    @if ($errors->any())
        <div class="bg-rose-500/10 border border-rose-500/40 text-rose-200 text-xs sm:text-sm rounded-2xl p-4 space-y-1.5 shadow-lg" role="alert">
            <div class="font-bold flex items-center gap-2 text-rose-300">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>يرجى تصحيح الأخطاء التالية قبل المتابعة:</span>
            </div>
            <ul class="list-disc list-inside space-y-1 ps-2 text-[12.5px] text-rose-200/90">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.shows.times.update', [$show, $showTime]) }}" method="POST"
          class="space-y-5 single-submit-form" novalidate>
        @csrf
        @method('PUT')

        {{-- =========================================================
             CARD 1: بيانات العرض والجدول الزمني
             ========================================================= --}}
        <div class="bg-black/40 border border-white/10 rounded-2xl p-4 sm:p-5 space-y-4 shadow-xl backdrop-blur-md">
            <div class="flex items-center gap-2.5 border-b border-white/10 pb-3">
                <div class="w-8 h-8 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm sm:text-base font-bold text-white">بيانات العرض والجدول الزمني</h2>
                    <p class="text-[11px] text-slate-400">حدد موعد ويوم إقامة العرض المسرحي</p>
                </div>
            </div>

            {{-- Show Title Read-only Reference --}}
            <div>
                <label class="block text-xs text-slate-400 mb-1.5 font-medium">اسم العرض</label>
                <div class="w-full rounded-xl bg-white/5 border border-white/10 px-3.5 py-2.5 text-sm text-white font-medium flex items-center gap-2 select-none">
                    <span class="text-amber-400">🎭</span>
                    <span>{{ $show->title }}</span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                {{-- Date --}}
                <div>
                    <label for="date" class="block text-xs text-slate-300 mb-1.5 font-medium">
                        تاريخ العرض <span class="text-amber-400">*</span>
                    </label>
                    <input type="date" name="date" id="date" required
                           value="{{ old('date', $showTime->date ? $showTime->date->format('Y-m-d') : '') }}"
                           class="w-full rounded-xl bg-black/60 border @error('date') border-rose-500 focus:border-rose-500 ring-1 ring-rose-500/40 @else border-white/15 focus:border-amber-400 focus:ring-amber-400/40 @enderror px-3.5 py-2.5 min-h-[46px] text-sm text-slate-100 focus:outline-none focus:ring-1 transition"
                           aria-invalid="{{ $errors->has('date') ? 'true' : 'false' }}">
                    @error('date')
                        <p class="text-xs text-rose-400 mt-1 flex items-center gap-1">
                            <span>⚠</span>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                {{-- Time --}}
                <div>
                    <label for="time" class="block text-xs text-slate-300 mb-1.5 font-medium">
                        وقت بدء العرض <span class="text-amber-400">*</span>
                    </label>
                    <input type="time" name="time" id="time" required
                           value="{{ old('time', $showTime->time) }}"
                           class="w-full rounded-xl bg-black/60 border @error('time') border-rose-500 focus:border-rose-500 ring-1 ring-rose-500/40 @else border-white/15 focus:border-amber-400 focus:ring-amber-400/40 @enderror px-3.5 py-2.5 min-h-[46px] text-sm text-slate-100 focus:outline-none focus:ring-1 transition"
                           aria-invalid="{{ $errors->has('time') ? 'true' : 'false' }}">
                    @error('time')
                        <p class="text-xs text-rose-400 mt-1 flex items-center gap-1">
                            <span>⚠</span>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- =========================================================
             CARD 2: التذاكر والأسعار
             ========================================================= --}}
        <div class="bg-black/40 border border-white/10 rounded-2xl p-4 sm:p-5 space-y-4 shadow-xl backdrop-blur-md">
            <div class="flex items-center gap-2.5 border-b border-white/10 pb-3">
                <div class="w-8 h-8 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm sm:text-base font-bold text-white">التذاكر والأسعار</h2>
                    <p class="text-[11px] text-slate-400">تسعير المقاعد وسعة الصالة الإجمالية لهذا الموعد</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                {{-- Ticket Price --}}
                <div>
                    <label for="ticket_price" class="block text-xs text-slate-300 mb-1 font-medium">
                        سعر التذكرة (جنيه مصري) <span class="text-amber-400">*</span>
                    </label>
                    <input type="number" step="0.5" min="0" name="ticket_price" id="ticket_price" required
                           inputmode="decimal"
                           value="{{ old('ticket_price', $showTime->ticket_price) }}"
                           placeholder="مثال: 100"
                           class="w-full rounded-xl bg-black/60 border @error('ticket_price') border-rose-500 focus:border-rose-500 ring-1 ring-rose-500/40 @else border-white/15 focus:border-amber-400 focus:ring-amber-400/40 @enderror px-3.5 py-2.5 min-h-[46px] text-sm text-amber-300 font-bold tabular-nums focus:outline-none focus:ring-1 transition"
                           aria-invalid="{{ $errors->has('ticket_price') ? 'true' : 'false' }}">
                    <p class="text-[11px] text-slate-400 mt-1">السعر المطلوب لكل تذكرة بالجنيه</p>
                    @error('ticket_price')
                        <p class="text-xs text-rose-400 mt-1 flex items-center gap-1">
                            <span>⚠</span>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                {{-- Total Tickets --}}
                <div>
                    <label for="total_tickets" class="block text-xs text-slate-300 mb-1 font-medium">
                        إجمالي عدد التذاكر <span class="text-amber-400">*</span>
                    </label>
                    <input type="number" min="1" name="total_tickets" id="total_tickets" required
                           inputmode="numeric"
                           value="{{ old('total_tickets', $showTime->total_tickets) }}"
                           placeholder="مثال: 50"
                           class="w-full rounded-xl bg-black/60 border @error('total_tickets') border-rose-500 focus:border-rose-500 ring-1 ring-rose-500/40 @else border-white/15 focus:border-amber-400 focus:ring-amber-400/40 @enderror px-3.5 py-2.5 min-h-[46px] text-sm text-slate-100 font-bold tabular-nums focus:outline-none focus:ring-1 transition"
                           aria-invalid="{{ $errors->has('total_tickets') ? 'true' : 'false' }}">
                    <p class="text-[11px] text-slate-400 mt-1">السعة الإجمالية لمقاعد الصالة</p>
                    @error('total_tickets')
                        <p class="text-xs text-rose-400 mt-1 flex items-center gap-1">
                            <span>⚠</span>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>
            </div>

            {{-- Available Tickets (Optional) --}}
            <div class="pt-1">
                <label for="available_tickets" class="block text-xs text-slate-300 mb-1 font-medium">
                    التذاكر المتاحة الآن <span class="text-slate-500 text-[11px] font-normal">(المتبقية للحجز)</span>
                </label>
                <input type="number" min="0" name="available_tickets" id="available_tickets"
                       inputmode="numeric"
                       value="{{ old('available_tickets', $showTime->available_tickets) }}"
                       placeholder="اتركه فارغاً ليطابق إجمالي التذاكر تلقائياً"
                       class="w-full rounded-xl bg-black/60 border @error('available_tickets') border-rose-500 focus:border-rose-500 ring-1 ring-rose-500/40 @else border-white/15 focus:border-amber-400 focus:ring-amber-400/40 @enderror px-3.5 py-2.5 min-h-[46px] text-sm text-slate-100 tabular-nums focus:outline-none focus:ring-1 transition"
                       aria-invalid="{{ $errors->has('available_tickets') ? 'true' : 'false' }}">
                <p class="text-[11px] text-slate-400 mt-1">
                    عدد المقاعد المتبقية للمستخدمين للحجز فوراً.
                </p>
                @error('available_tickets')
                    <p class="text-xs text-rose-400 mt-1 flex items-center gap-1">
                        <span>⚠</span>
                        <span>{{ $message }}</span>
                    </p>
                @enderror
            </div>
        </div>

        {{-- =========================================================
             CARD 3: حالة الموعد والحجز (Sold Out Toggle)
             ========================================================= --}}
        <div class="bg-black/40 border border-white/10 rounded-2xl p-4 sm:p-5 space-y-4 shadow-xl backdrop-blur-md">
            <div class="flex items-center gap-2.5 border-b border-white/10 pb-3">
                <div class="w-8 h-8 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm sm:text-base font-bold text-white">حالة الموعد وإتاحة الحجز</h2>
                    <p class="text-[11px] text-slate-400">التحكم في ظهور الموعد للجمهور كـ Sold Out</p>
                </div>
            </div>

            <label for="is_sold_out"
                   class="flex items-start justify-between gap-3 p-3.5 rounded-xl bg-white/5 border border-white/10 cursor-pointer hover:bg-white/[0.08] transition">
                <div class="min-w-0">
                    <span class="block text-sm font-bold text-white">Sold Out من البداية</span>
                    <span class="block text-xs text-slate-400 mt-0.5">
                        لن يكون الموعد متاحاً للحجز عند نشره، وسيظهر للجمهور كعرض مكتمل العدد.
                    </span>
                </div>

                <div class="relative inline-flex items-center shrink-0 mt-0.5">
                    <input type="checkbox" name="is_sold_out" id="is_sold_out" value="1"
                           {{ old('is_sold_out', $showTime->is_sold_out) ? 'checked' : '' }}
                           class="sr-only peer">
                    <div class="w-11 h-6 rounded-full bg-white/15 peer-checked:bg-amber-400 transition-colors duration-200 ring-1 ring-white/10"></div>
                    <div class="absolute start-0.5 top-0.5 w-5 h-5 rounded-full bg-white transition-transform duration-200 peer-checked:translate-x-5 rtl:peer-checked:-translate-x-5 shadow"></div>
                </div>
            </label>
        </div>

        {{-- =========================================================
             SUBMIT ACTION (WITH LOADING STATE & DOUBLE-SUBMIT GUARD)
             ========================================================= --}}
        <div class="pt-2">
            <button type="submit"
                    id="submitBtn"
                    class="w-full admin-btn admin-btn--primary min-h-[48px] py-3 text-sm sm:text-base font-bold justify-center shadow-xl">
                <span class="btn-label">حفظ التعديلات</span>
                <span class="btn-spinner hidden" aria-hidden="true"></span>
            </button>
        </div>
    </form>
</section>

<script>
document.querySelectorAll('.single-submit-form').forEach(function (f) {
    f.addEventListener('submit', function () {
        requestAnimationFrame(function () {
            f.querySelectorAll('button[type=submit]').forEach(function (b) {
                if (b.disabled) return;
                b.disabled = true;
                b.classList.add('is-loading');
                var spin = b.querySelector('.btn-spinner');
                if (spin) spin.classList.remove('hidden');
                var lbl = b.querySelector('.btn-label');
                if (lbl) lbl.textContent = 'جاري حفظ التعديلات…';
            });
        });
    });
});
</script>
@endsection
