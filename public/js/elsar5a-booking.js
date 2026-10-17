/**
 * Elsar5a Theatre — 4-Stage Luxury Box-Office Booking Experience
 * Integrates Gemini's 4-stage mobile-first UI with Elsar5a Laravel Backend.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('elsar5a-booking-form') || document.getElementById('bookingForm');
        if (!form) return;

        // --- Configuration & Data Attributes ---
        var unitPrice = parseFloat(form.getAttribute('data-unit-price') || '100');
        var maxTickets = parseInt(form.getAttribute('data-max-tickets') || '10', 10);
        var showTimeId = form.getAttribute('data-showtime-id') || '1';

        // --- DOM Elements: Stage 01 ---
        var qtyInput = document.getElementById('ticket-quantity-input') || document.getElementById('tickets_count');
        var btnDecrease = document.getElementById('qty-decrease-btn');
        var btnIncrease = document.getElementById('qty-increase-btn');
        var presetCards = document.querySelectorAll('.ticket-preset-card');
        var ledgerQtyNum = document.getElementById('ledger-qty-num');
        var ledgerUnitPrice = document.getElementById('ledger-unit-price');
        var stage01TotalEl = document.getElementById('stage-01-total-amount');

        // --- DOM Elements: Summary Sidebar & Mobile Sticky ---
        var summaryQty = document.getElementById('summary-qty-display');
        var summaryUnit = document.getElementById('summary-unit-display');
        var summaryTotal = document.getElementById('summary-total-display');
        var summaryReceipt = document.getElementById('summary-receipt-status');
        var mobileQty = document.getElementById('mobile-qty-count');
        var mobileTotal = document.getElementById('mobile-total-display');
        var mobileStickyBar = document.getElementById('mobile-sticky-summary');
        var mobileStickyBtn = document.getElementById('mobile-sticky-action-btn');

        // --- DOM Elements: Stage 02 Payment ---
        var paymentCards = document.querySelectorAll('[data-payment-card]');
        var copyButtons = document.querySelectorAll('.js-copy-number-btn');
        var toastEl = document.getElementById('booking-toast');
        var toastText = document.getElementById('booking-toast-text');
        var toastTimer = null;

        // --- DOM Elements: Stage 03 Upload & Customer Info ---
        var fileInput = document.getElementById('payment-screenshot-input') || document.getElementById('screenshot');
        var dropzone = document.getElementById('screenshot-dropzone');
        var uploadEmptyState = document.getElementById('upload-empty-state');
        var uploadPreviewState = document.getElementById('upload-preview-state');
        var uploadPreviewImg = document.getElementById('upload-preview-img');
        var uploadFilename = document.getElementById('upload-filename');
        var uploadFilesize = document.getElementById('upload-filesize');
        var removeScreenshotBtn = document.getElementById('remove-screenshot-btn');
        var changeScreenshotBtn = document.getElementById('change-screenshot-btn');
        var errorScreenshot = document.getElementById('error-screenshot');

        var nameInput = document.getElementById('customer-name-input');
        var waInput = document.getElementById('customer-whatsapp-input');
        var attendeesContainer = document.getElementById('attendees-container');
        var errorName = document.getElementById('error-name');
        var errorWhatsapp = document.getElementById('error-whatsapp');

        // --- DOM Elements: Stage 04 & Readiness ---
        var readyCheckTickets = document.getElementById('ready-check-tickets');
        var readyCheckScreenshot = document.getElementById('ready-check-screenshot');
        var readyCheckUser = document.getElementById('ready-check-user');
        var submitBtn = document.getElementById('submit-booking-cta') || document.getElementById('submitBtn');
        var ctaDefaultContent = submitBtn ? submitBtn.querySelector('.cta-content-default') : null;
        var ctaLoadingContent = submitBtn ? submitBtn.querySelector('.cta-content-loading') : null;
        var idempotencyInput = document.getElementById('idempotencyToken');

        // Memory for attendee inputs
        var oldNames = window.__oldNames || [];
        var oldPhones = window.__oldPhones || [];
        var isSubmitting = false;

        // ---------------------------------------------------------------------
        // 1. FORMATTING HELPERS
        // ---------------------------------------------------------------------
        function formatMoney(amount) {
            return Math.round(amount).toLocaleString('ar-EG');
        }

        function formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' بايت';
            if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' كيلوبايت';
            return (bytes / 1048576).toFixed(2) + ' ميجابايت';
        }

        function showToast(message) {
            if (!toastEl) return;
            if (toastText) toastText.textContent = message || 'تم نسخ رقم التحويل بنجاح';
            toastEl.hidden = false;
            toastEl.classList.remove('is-visible');
            void toastEl.offsetWidth;
            toastEl.classList.add('is-visible');

            if (toastTimer) clearTimeout(toastTimer);
            toastTimer = setTimeout(function () {
                toastEl.classList.remove('is-visible');
                setTimeout(function () { toastEl.hidden = true; }, 300);
            }, 2500);
        }

        // ---------------------------------------------------------------------
        // 2. STAGE 01 — TICKET QUANTITY & DYNAMIC TOTALS
        // ---------------------------------------------------------------------
        function getCurrentQuantity() {
            var val = parseInt(qtyInput ? qtyInput.value : '1', 10);
            if (isNaN(val) || val < 1) val = 1;
            if (maxTickets > 0 && val > maxTickets) val = maxTickets;
            return val;
        }

        function updateQuantityAndTotal(newQty) {
            var qty = parseInt(newQty, 10);
            if (isNaN(qty) || qty < 1) qty = 1;
            if (maxTickets > 0 && qty > maxTickets) qty = maxTickets;

            if (qtyInput) qtyInput.value = qty;

            var total = qty * unitPrice;
            var formattedTotal = formatMoney(total);

            // Update live ledger
            if (ledgerQtyNum) ledgerQtyNum.textContent = qty;
            if (ledgerUnitPrice) ledgerUnitPrice.textContent = formatMoney(unitPrice);
            if (stage01TotalEl) {
                stage01TotalEl.textContent = formattedTotal;
                stage01TotalEl.classList.remove('is-bumping');
                void stage01TotalEl.offsetWidth;
                stage01TotalEl.classList.add('is-bumping');
            }

            // Sync all .js-sync-qty and .js-sync-total elements
            document.querySelectorAll('.js-sync-qty').forEach(function (el) {
                el.textContent = qty;
            });
            document.querySelectorAll('.js-sync-total').forEach(function (el) {
                el.textContent = formattedTotal;
            });

            // Update summary sidebar
            if (summaryQty) summaryQty.textContent = qty;
            if (summaryUnit) summaryUnit.textContent = formatMoney(unitPrice);
            if (summaryTotal) summaryTotal.textContent = formattedTotal;

            // Update mobile bar
            if (mobileQty) mobileQty.textContent = qty + (qty === 1 ? ' تذكرة' : (qty === 2 ? ' تذكرتان' : ' تذاكر'));
            if (mobileTotal) mobileTotal.textContent = formattedTotal + ' ج.م';

            // Update preset buttons state
            presetCards.forEach(function (card) {
                var pVal = parseInt(card.getAttribute('data-qty-preset'), 10);
                var isActive = (pVal === qty);
                card.classList.toggle('is-active', isActive);
                card.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });

            // Stepper disabled states
            if (btnDecrease) btnDecrease.disabled = (qty <= 1);
            if (btnIncrease) btnIncrease.disabled = (maxTickets > 0 && qty >= maxTickets);

            // Render attendee inputs if count > 1
            renderAttendeeInputs(qty);

            // Update readiness checklist item 1
            if (readyCheckTickets) {
                readyCheckTickets.classList.add('is-done');
                var rIcon = readyCheckTickets.querySelector('.readiness-icon');
                if (rIcon) rIcon.textContent = '✓';
            }

            updateReadinessState();
        }

        if (btnDecrease) {
            btnDecrease.addEventListener('click', function () {
                updateQuantityAndTotal(getCurrentQuantity() - 1);
            });
        }

        if (btnIncrease) {
            btnIncrease.addEventListener('click', function () {
                updateQuantityAndTotal(getCurrentQuantity() + 1);
            });
        }

        presetCards.forEach(function (card) {
            card.addEventListener('click', function () {
                var val = parseInt(this.getAttribute('data-qty-preset'), 10);
                if (!isNaN(val) && val >= 1) {
                    updateQuantityAndTotal(val);
                }
            });
        });

        // ---------------------------------------------------------------------
        // 3. STAGE 02 — PAYMENT COPY ACTIONS & SELECTION
        // ---------------------------------------------------------------------
        function copyTextToClipboard(text, onSuccess) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(onSuccess, function () {
                    fallbackCopy(text, onSuccess);
                });
            } else {
                fallbackCopy(text, onSuccess);
            }
        }

        function fallbackCopy(text, onSuccess) {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try {
                document.execCommand('copy');
                if (onSuccess) onSuccess();
            } catch (err) {}
            document.body.removeChild(ta);
        }

        copyButtons.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var val = btn.getAttribute('data-copy-value') || '';
                if (!val) return;

                copyTextToClipboard(val, function () {
                    var textEl = btn.querySelector('.btn-copy-text');
                    var prevText = textEl ? textEl.textContent : 'نسخ الرقم';
                    btn.classList.add('is-copied');
                    if (textEl) textEl.textContent = 'تم النسخ ✓';
                    showToast('تم نسخ الرقم بنجاح: ' + val);

                    setTimeout(function () {
                        btn.classList.remove('is-copied');
                        if (textEl) textEl.textContent = prevText;
                    }, 1800);
                });
            });
        });

        paymentCards.forEach(function (card) {
            card.addEventListener('click', function () {
                paymentCards.forEach(function (c) { c.classList.remove('is-selected'); });
                card.classList.add('is-selected');
                var radio = card.querySelector('.payment-radio-input');
                if (radio) radio.checked = true;
            });
        });

        // ---------------------------------------------------------------------
        // 4. STAGE 03 — SCREENSHOT UPLOAD & ATTENDEE FIELDS
        // ---------------------------------------------------------------------
        function handleFileSelection(file) {
            if (!file) {
                clearScreenshot();
                return;
            }

            if (file.size > 20 * 1024 * 1024) {
                if (errorScreenshot) {
                    errorScreenshot.textContent = 'حجم الصورة أكبر من 20MB — يرجى اختيار صورة أصغر.';
                    errorScreenshot.classList.add('is-visible');
                }
                clearScreenshot();
                return;
            }

            if (errorScreenshot) {
                errorScreenshot.textContent = '';
                errorScreenshot.classList.remove('is-visible');
            }

            if (uploadFilename) uploadFilename.textContent = file.name;
            if (uploadFilesize) uploadFilesize.textContent = formatFileSize(file.size);

            var reader = new FileReader();
            reader.onload = function (e) {
                if (uploadPreviewImg) uploadPreviewImg.src = e.target.result;
                if (uploadEmptyState) uploadEmptyState.hidden = true;
                if (uploadPreviewState) uploadPreviewState.hidden = false;
                if (dropzone) dropzone.classList.add('has-file');

                if (summaryReceipt) {
                    summaryReceipt.textContent = 'تم إرفاق الإيصال ✓';
                    summaryReceipt.className = 'status-confirmed';
                }

                updateReadinessState();
            };
            reader.readAsDataURL(file);
        }

        function clearScreenshot() {
            if (fileInput) fileInput.value = '';
            if (uploadEmptyState) uploadEmptyState.hidden = false;
            if (uploadPreviewState) uploadPreviewState.hidden = true;
            if (uploadPreviewImg) uploadPreviewImg.src = '';
            if (dropzone) dropzone.classList.remove('has-file');

            if (summaryReceipt) {
                summaryReceipt.textContent = 'بانتظار الإرفاق';
                summaryReceipt.className = 'status-pending';
            }

            updateReadinessState();
        }

        if (fileInput) {
            fileInput.addEventListener('change', function () {
                var file = fileInput.files && fileInput.files[0];
                handleFileSelection(file);
            });
        }

        if (removeScreenshotBtn) {
            removeScreenshotBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                clearScreenshot();
            });
        }

        if (changeScreenshotBtn) {
            changeScreenshotBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                if (fileInput) fileInput.click();
            });
        }

        // Drag and drop events
        if (dropzone) {
            ['dragenter', 'dragover'].forEach(function (evt) {
                dropzone.addEventListener(evt, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.add('is-drag-over');
                });
            });

            ['dragleave', 'drop'].forEach(function (evt) {
                dropzone.addEventListener(evt, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.remove('is-drag-over');
                });
            });

            dropzone.addEventListener('drop', function (e) {
                if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                    if (fileInput) fileInput.files = e.dataTransfer.files;
                    handleFileSelection(e.dataTransfer.files[0]);
                }
            });
        }

        // --- Attendee Fields Rendering ---
        function renderAttendeeInputs(count) {
            if (!attendeesContainer) return;

            // Stash current input values so we preserve user typing
            var existingNames = attendeesContainer.querySelectorAll('input[name="names[]"]');
            var existingPhones = attendeesContainer.querySelectorAll('input[name="phones[]"]');

            existingNames.forEach(function (inp, idx) {
                oldNames[idx + 1] = inp.value;
            });
            existingPhones.forEach(function (inp, idx) {
                oldPhones[idx + 1] = inp.value;
            });

            if (count <= 1) {
                attendeesContainer.innerHTML = '';
                attendeesContainer.hidden = true;
                return;
            }

            attendeesContainer.hidden = false;
            var html = '<div class="attendees-section">' +
                '<h3 class="attendees-section-title">👥 بيانات باقي الحاضرين</h3>' +
                '<p class="attendees-section-subtitle">أدخل أسماء وأرقام باقي التذاكر لإصدار بطاقات الدخول بأسمائهم.</p>';

            for (var i = 2; i <= count; i++) {
                var valName = (oldNames[i - 1] || '').replace(/"/g, '&quot;');
                var valPhone = (oldPhones[i - 1] || '').replace(/"/g, '&quot;');

                html += '<div class="attendee-card">' +
                    '<div class="attendee-card-header">' +
                        '<span class="attendee-badge">تذكرة رقم ' + i + '</span>' +
                        '<span class="text-xs text-stone-400">مرافق ' + (i - 1) + '</span>' +
                    '</div>' +
                    '<div class="attendee-inputs-grid">' +
                        '<div class="booking-field-group">' +
                            '<label class="booking-field-label">الاسم بالكامل</label>' +
                            '<div class="booking-input-shell">' +
                                '<input type="text" name="names[]" value="' + valName + '" ' +
                                    'placeholder="اسم الحاضر بالكامل" class="booking-text-input" required autocomplete="name">' +
                            '</div>' +
                        '</div>' +
                        '<div class="booking-field-group">' +
                            '<label class="booking-field-label">رقم الهاتف / واتساب</label>' +
                            '<div class="booking-input-shell">' +
                                '<input type="tel" name="phones[]" value="' + valPhone + '" dir="ltr" ' +
                                    'placeholder="010XXXXXXXX" class="booking-text-input text-left" required autocomplete="tel">' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>';
            }

            html += '</div>';
            attendeesContainer.innerHTML = html;

            // Wire input events
            attendeesContainer.querySelectorAll('input').forEach(function (inp) {
                inp.addEventListener('input', updateReadinessState);
                inp.addEventListener('blur', updateReadinessState);
            });
        }

        // Bind Primary Booker inputs
        if (nameInput) {
            nameInput.addEventListener('input', updateReadinessState);
            nameInput.addEventListener('blur', updateReadinessState);
        }
        if (waInput) {
            waInput.addEventListener('input', updateReadinessState);
            waInput.addEventListener('blur', updateReadinessState);
        }

        // ---------------------------------------------------------------------
        // 5. STAGE 04 — READINESS CHECKLIST & SUBMIT GATE
        // ---------------------------------------------------------------------
        function isPhoneValid(val) {
            var digits = (val || '').replace(/\D+/g, '');
            return digits.length >= 8 && digits.length <= 15;
        }

        function areAllFieldsFilled() {
            var primaryName = nameInput ? nameInput.value.trim() : '';
            var primaryPhone = waInput ? waInput.value.trim() : '';
            if (primaryName.length < 2 || !isPhoneValid(primaryPhone)) return false;

            if (attendeesContainer && !attendeesContainer.hidden) {
                var otherNames = attendeesContainer.querySelectorAll('input[name="names[]"]');
                var otherPhones = attendeesContainer.querySelectorAll('input[name="phones[]"]');

                for (var i = 0; i < otherNames.length; i++) {
                    if (otherNames[i].value.trim().length < 2) return false;
                }
                for (var j = 0; j < otherPhones.length; j++) {
                    if (!isPhoneValid(otherPhones[j].value.trim())) return false;
                }
            }
            return true;
        }

        function hasValidScreenshot() {
            return !!(fileInput && fileInput.files && fileInput.files.length > 0 && fileInput.files[0].size <= 20 * 1024 * 1024);
        }

        function updateReadinessState() {
            var qty = getCurrentQuantity();
            var ticketsReady = (qty >= 1 && (maxTickets === 0 || qty <= maxTickets));
            var screenshotReady = hasValidScreenshot();
            var fieldsReady = areAllFieldsFilled();

            // Checklist 1: Tickets
            if (readyCheckTickets) {
                readyCheckTickets.classList.toggle('is-done', ticketsReady);
                var icon1 = readyCheckTickets.querySelector('.readiness-icon');
                if (icon1) icon1.textContent = ticketsReady ? '✓' : '○';
            }

            // Checklist 2: Screenshot
            if (readyCheckScreenshot) {
                readyCheckScreenshot.classList.toggle('is-done', screenshotReady);
                var icon2 = readyCheckScreenshot.querySelector('.readiness-icon');
                if (icon2) icon2.textContent = screenshotReady ? '✓' : '○';
            }

            // Checklist 3: User Details
            if (readyCheckUser) {
                readyCheckUser.classList.toggle('is-done', fieldsReady);
                var icon3 = readyCheckUser.querySelector('.readiness-icon');
                if (icon3) icon3.textContent = fieldsReady ? '✓' : '○';
            }

            var allReady = ticketsReady && screenshotReady && fieldsReady;
            if (submitBtn) {
                submitBtn.disabled = !allReady;
                submitBtn.classList.toggle('is-ready', allReady);
            }

            // Update mobile sticky button text based on readiness
            if (mobileStickyBtn) {
                if (allReady) {
                    mobileStickyBtn.innerHTML = 'إرسال الحجز الآن 🎟️';
                    mobileStickyBtn.classList.add('is-ready');
                } else if (!screenshotReady) {
                    mobileStickyBtn.innerHTML = 'إرفاق إيصال الدفع ↓';
                    mobileStickyBtn.classList.remove('is-ready');
                } else if (!fieldsReady) {
                    mobileStickyBtn.innerHTML = 'إكمال بيانات الحجز ↓';
                    mobileStickyBtn.classList.remove('is-ready');
                } else {
                    mobileStickyBtn.innerHTML = 'متابعة الحجز ↓';
                    mobileStickyBtn.classList.remove('is-ready');
                }
            }
        }

        // ---------------------------------------------------------------------
        // 6. IDEMPOTENCY TOKEN
        // ---------------------------------------------------------------------
        function setupIdempotency() {
            if (!idempotencyInput) return;
            var tokenKey = 'elsar5a_booking_token_show_' + showTimeId;
            var token = null;
            try {
                token = sessionStorage.getItem(tokenKey);
            } catch (e) {}

            if (!token) {
                try {
                    var buf = new Uint8Array(16);
                    (window.crypto || window.msCrypto).getRandomValues(buf);
                    token = Array.prototype.map.call(buf, function (b) {
                        return ('0' + b.toString(16)).slice(-2);
                    }).join('');
                } catch (err) {
                    token = Date.now() + '-' + Math.random().toString(36).slice(2);
                }
                try {
                    sessionStorage.setItem(tokenKey, token);
                } catch (e) {}
            }
            idempotencyInput.value = token;
        }

        // ---------------------------------------------------------------------
        // 7. FORM SUBMISSION
        // ---------------------------------------------------------------------
        form.addEventListener('submit', function (e) {
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }

            var hasFile = hasValidScreenshot();
            var fieldsOk = areAllFieldsFilled();

            if (!hasFile || !fieldsOk) {
                e.preventDefault();
                if (!hasFile) {
                    if (errorScreenshot) {
                        errorScreenshot.textContent = 'يرجى إرفاق صورة إيصال التحويل (Screenshot) للمتابعة.';
                        errorScreenshot.classList.add('is-visible');
                    }
                    var s2 = document.getElementById('stage-03');
                    if (s2) s2.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return false;
                }
                if (!fieldsOk) {
                    if (nameInput && nameInput.value.trim().length < 2) {
                        if (errorName) {
                            errorName.textContent = 'يرجى كتابة الاسم بالكامل.';
                            errorName.classList.add('is-visible');
                        }
                        nameInput.focus();
                        return false;
                    }
                    if (waInput && !isPhoneValid(waInput.value.trim())) {
                        if (errorWhatsapp) {
                            errorWhatsapp.textContent = 'يرجى إدخال رقم هاتف صحيح.';
                            errorWhatsapp.classList.add('is-visible');
                        }
                        waInput.focus();
                        return false;
                    }
                }
                return false;
            }

            isSubmitting = true;
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.classList.add('is-submitting');
            }
            if (ctaDefaultContent) ctaDefaultContent.hidden = true;
            if (ctaLoadingContent) ctaLoadingContent.hidden = false;
        });

        // iOS Safari / bfcache handling
        window.addEventListener('pageshow', function (e) {
            if (e.persisted) {
                isSubmitting = false;
                if (submitBtn) {
                    submitBtn.classList.remove('is-submitting');
                    submitBtn.disabled = false;
                }
                if (ctaDefaultContent) ctaDefaultContent.hidden = false;
                if (ctaLoadingContent) ctaLoadingContent.hidden = true;
                updateReadinessState();
            }
        });

        // ---------------------------------------------------------------------
        // 8. MOBILE STICKY BUTTON ACTION
        // ---------------------------------------------------------------------
        if (mobileStickyBtn) {
            mobileStickyBtn.addEventListener('click', function (e) {
                e.preventDefault();
                var screenshotReady = hasValidScreenshot();
                var fieldsReady = areAllFieldsFilled();

                if (!screenshotReady) {
                    var s3 = document.getElementById('stage-03');
                    if (s3) s3.scrollIntoView({ behavior: 'smooth', block: 'start' });
                } else if (!fieldsReady) {
                    var nameInp = document.getElementById('customer-name-input');
                    if (nameInp) nameInp.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    var s4 = document.getElementById('stage-04');
                    if (s4) s4.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    if (submitBtn && !submitBtn.disabled) {
                        submitBtn.click();
                    }
                }
            });
        }

        // ---------------------------------------------------------------------
        // 9. SCROLL OBSERVER FOR PROGRESS RAIL & MOBILE BAR VISIBILITY
        // ---------------------------------------------------------------------
        if ('IntersectionObserver' in window) {
            var stageSections = document.querySelectorAll('[data-stage-section]');
            var stepIndicators = document.querySelectorAll('[data-step-indicator]');

            var stageObserver = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        var stageNum = entry.target.getAttribute('data-stage-section');
                        stageSections.forEach(function (s) {
                            s.classList.toggle('is-focused', s === entry.target);
                        });
                        stepIndicators.forEach(function (ind) {
                            ind.classList.toggle('is-active', ind.getAttribute('data-step-indicator') === stageNum);
                        });
                    }
                });
            }, { threshold: 0.3 });

            stageSections.forEach(function (sec) {
                stageObserver.observe(sec);
            });

            // Hide mobile sticky bar when near Stage 04 to avoid CTA clutter
            var stage04 = document.getElementById('stage-04');
            if (stage04 && mobileStickyBar) {
                var ctaObserver = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        mobileStickyBar.classList.toggle('is-hidden-near-cta', entry.isIntersecting);
                    });
                }, { threshold: 0.2 });
                ctaObserver.observe(stage04);
            }
        }

        // Smooth scroll for progress rail links
        document.querySelectorAll('[data-jump-stage]').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                var targetId = this.getAttribute('data-jump-stage');
                var targetEl = document.getElementById(targetId);
                if (targetEl) {
                    targetEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        // ---------------------------------------------------------------------
        // 10. INITIALIZATION
        // ---------------------------------------------------------------------
        setupIdempotency();
        var initialQty = getCurrentQuantity();
        updateQuantityAndTotal(initialQty);
    });
})();
