(function () {
    'use strict';

    var busyButtons = [];

    function $(id) {
        return document.getElementById(id);
    }

    function clearButtonBusy(btn) {
        if (!btn) return;
        btn.classList.remove('m360-btn-is-loading');
        btn.removeAttribute('aria-busy');
        if (btn.dataset.m360BusyPrevDisabled === '1') {
            btn.disabled = true;
        } else {
            btn.disabled = false;
        }
        delete btn.dataset.m360BusyPrevDisabled;
        var idx = busyButtons.indexOf(btn);
        if (idx >= 0) busyButtons.splice(idx, 1);
    }

    function clearAllBusyButtons() {
        busyButtons.slice().forEach(clearButtonBusy);
        document.querySelectorAll('.m360-rw-form-saving').forEach(function (el) {
            el.textContent = '';
            el.hidden = true;
        });
    }

    function isRouteLink(el) {
        if (!el) return false;
        if (el.classList && el.classList.contains('m360-rw-back')) return true;
        if (el.closest && el.closest('.m360-rw-header')) return true;
        return false;
    }

    function setButtonBusy(btn, busy) {
        if (!btn || isRouteLink(btn)) return;
        if (!busy) {
            clearButtonBusy(btn);
            return;
        }
        if (!btn.classList.contains('m360-btn-is-loading')) {
            btn.dataset.m360BusyPrevDisabled = btn.disabled ? '1' : '0';
            busyButtons.push(btn);
        }
        btn.disabled = true;
        btn.classList.add('m360-btn-is-loading');
        btn.setAttribute('aria-busy', 'true');
    }

    function bindReceptionFormLoading() {
        document.querySelectorAll('form.m360-rw-form').forEach(function (form) {
            if (form.dataset.m360RwSubmitBound === '1') return;
            form.dataset.m360RwSubmitBound = '1';
            form.addEventListener('invalid', function () {
                clearAllBusyButtons();
            }, true);
            form.addEventListener('submit', function (e) {
                if (e.defaultPrevented) return;
                if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
                    clearAllBusyButtons();
                    if (typeof form.reportValidity === 'function') {
                        form.reportValidity();
                    }
                    e.preventDefault();
                    return;
                }
                var submitter = e.submitter || form.querySelector('button[type="submit"], input[type="submit"]');
                if (!submitter) return;
                setButtonBusy(submitter, true);
                var status = form.querySelector('.m360-rw-form-saving');
                if (!status) {
                    status = document.createElement('p');
                    status.className = 'm360-rw-form-saving m360-rw-muted';
                    status.setAttribute('role', 'status');
                    status.hidden = true;
                    form.appendChild(status);
                }
                status.textContent = 'در حال ذخیره...';
                status.hidden = false;
                window.setTimeout(clearAllBusyButtons, 45000);
            });
        });
    }

    function bindServiceRoutePanel() {
        var route = $('service_route');
        var diagPanel = document.querySelector('[data-service-diag-panel]');
        var tradePanel = document.querySelector('[data-service-trade-panel]');
        if (!route) return;
        var sync = function () {
            if (diagPanel) {
                diagPanel.hidden = !(route.value === 'diag' || route.value === '');
            }
            if (tradePanel) {
                tradePanel.hidden = route.value !== 'trade';
            }
        };
        route.addEventListener('change', sync);
        sync();
    }

    function bindTrunkOtherToggle() {
        var box = $('m360_rw_trunk_other_selected');
        var text = $('m360_rw_trunk_other_text');
        if (!box || !text) return;
        var sync = function () {
            text.hidden = !box.checked;
            if (!box.checked) {
                text.value = '';
            }
        };
        box.addEventListener('change', sync);
        sync();
    }

    function bindDamageSelector() {
        // C11 — structured zone selector (interactive hotspots removed).
        var selector = $('m360_rw_damage_selector');
        if (!selector) {
            return;
        }
        var statusLabels = { HEALTHY: 'سالم', MINOR: 'آسیب جزئی', SEVERE: 'آسیب شدید' };
        var rows = Array.prototype.slice.call(selector.querySelectorAll('.m360-rw-zone-row'));
        if (!rows.length) {
            return;
        }

        function rowLabel(row) {
            return row.getAttribute('data-label') || row.getAttribute('data-zone') || 'ناحیه';
        }

        function paintRow(row) {
            var status = row.getAttribute('data-status') || 'HEALTHY';
            var assessed = row.getAttribute('data-assessed') === '1';
            var segButtons = row.querySelectorAll('.m360-rw-zone-seg button[data-status]');
            Array.prototype.forEach.call(segButtons, function (b) {
                var active = assessed && b.getAttribute('data-status') === status;
                b.classList.toggle('is-active', active);
                b.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
            var stateText = assessed ? (statusLabels[status] || status) : 'بررسی‌نشده';
            row.setAttribute('aria-label', rowLabel(row) + '، وضعیت فعلی ' + stateText);
        }

        function setRowStatus(row, status, assessed) {
            var zone = row.getAttribute('data-zone') || '';
            row.setAttribute('data-status', status);
            row.setAttribute('data-assessed', assessed ? '1' : '0');
            var hidden = $('m360_rw_damage_zone_' + zone);
            if (hidden) {
                hidden.value = status;
            }
            paintRow(row);
        }

        function renderSummary() {
            var body = $('m360_rw_damage_summary_body');
            if (!body) {
                return;
            }
            var damaged = [];
            var assessedCount = 0;
            rows.forEach(function (row) {
                if (row.getAttribute('data-assessed') !== '1') {
                    return;
                }
                assessedCount += 1;
                var status = row.getAttribute('data-status') || 'HEALTHY';
                if (status === 'HEALTHY') {
                    return;
                }
                damaged.push({ label: rowLabel(row), status: statusLabels[status] || status });
            });
            if (damaged.length) {
                body.innerHTML = '<ul>' + damaged.map(function (row) {
                    return '<li>' + row.label + ' — ' + row.status + '</li>';
                }).join('') + '</ul>';
                return;
            }
            if (assessedCount === rows.length && rows.length > 0) {
                body.innerHTML = '<p class="m360-rw-muted" style="margin:0">همه نواحی بررسی‌شده سالم ثبت شدند.</p>';
                return;
            }
            body.innerHTML = '<p class="m360-rw-muted" style="margin:0">هنوز آسیب ظاهری ثبت نشده است. نواحی را بررسی کنید یا «ثبت همه سالم» را بزنید.</p>';
        }

        function allAssessed() {
            return rows.every(function (row) {
                return row.getAttribute('data-assessed') === '1';
            });
        }

        rows.forEach(paintRow);
        renderSummary();

        selector.addEventListener('click', function (e) {
            var target = e.target;
            var btn = target && target.closest ? target.closest('.m360-rw-zone-seg button[data-status]') : null;
            if (!btn) {
                return;
            }
            var row = btn.closest('.m360-rw-zone-row');
            if (!row) {
                return;
            }
            setRowStatus(row, btn.getAttribute('data-status'), true);
            renderSummary();
        });

        var allHealthy = $('m360_rw_damage_all_healthy');
        if (allHealthy) {
            allHealthy.addEventListener('click', function () {
                rows.forEach(function (row) {
                    setRowStatus(row, 'HEALTHY', true);
                });
                renderSummary();
            });
        }

        var clearBtn = $('m360_rw_damage_clear');
        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                rows.forEach(function (row) {
                    // Pre-save UI neutral only; hidden stays HEALTHY for payload compatibility until assessed.
                    setRowStatus(row, 'HEALTHY', false);
                });
                renderSummary();
            });
        }

        var form = $('m360_rw_condition_form');
        if (form) {
            form.addEventListener('submit', function (e) {
                if (allAssessed()) {
                    return;
                }
                e.preventDefault();
                clearAllBusyButtons();
                var summary = $('m360_rw_damage_summary_body');
                if (summary) {
                    summary.innerHTML = '<p class="m360-rw-flash is-err" style="margin:0">برای ذخیره، همه نواحی را بررسی کنید یا «ثبت همه نواحی به‌عنوان سالم» را انتخاب کنید.</p>';
                    summary.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                }
            });
        }
    }

    function chainPlateFocus(ids) {
        ids.forEach(function (id, index) {
            var el = $(id);
            if (!el) return;
            el.addEventListener('change', function () {
                if (el.value !== '' && index < ids.length - 1) {
                    var next = $(ids[index + 1]);
                    if (next) next.focus();
                }
            });
        });
    }

    function buildPlateDisplay() {
        var d1 = $('plate_first_digit_1');
        var d2 = $('plate_first_digit_2');
        var letter = $('plate_letter');
        var m1 = $('plate_middle_digit_1');
        var m2 = $('plate_middle_digit_2');
        var m3 = $('plate_middle_digit_3');
        var r1 = $('plate_region_digit_1');
        var r2 = $('plate_region_digit_2');
        var left = $('plate_left_2_digits');
        var mid = $('plate_middle_3_digits');
        var region = $('plate_region_2_digits');
        var hidden = $('plate_display');
        var preview = $('m360_rw_plate_preview');
        if (!d1 || !d2 || !letter || !m1 || !m2 || !m3 || !r1 || !r2 || !left || !mid || !region || !hidden) return;
        if (d1.value !== '' && d2.value !== '') left.value = d1.value + d2.value;
        if (m1.value !== '' && m2.value !== '' && m3.value !== '') mid.value = m1.value + m2.value + m3.value;
        if (r1.value !== '' && r2.value !== '') region.value = r1.value + r2.value;
        if (left.value && letter.value && mid.value && region.value) {
            hidden.value = left.value + ' ' + letter.value + ' ' + mid.value + ' ایران ' + region.value;
            if (preview) {
                preview.textContent = hidden.value;
                preview.classList.add('iran-plate-preview--filled');
            }
        } else if (preview) {
            preview.textContent = 'پس از تکمیل، پلاک اینجا نمایش داده می‌شود';
            preview.classList.remove('iran-plate-preview--filled');
        }
    }

    function bindVehicleStepForm() {
        var form = $('m360_rw_vehicle_form');
        if (!form) {
            return;
        }
        var errBox = $('m360_rw_vehicle_form_error');
        form.addEventListener('submit', function (e) {
            buildPlateDisplay();
            var msgs = [];
            var firstInvalid = null;
            var brand = $('m360_rw_vehicle_brand');
            var cls = $('m360_rw_vehicle_class');
            var year = $('m360_rw_vehicle_year');
            var visit = $('m360_rw_visit_date');
            var mileage = form.querySelector('input[name="mileage"]');
            var fuel = form.querySelector('select[name="fuel_level"]');
            var plateHidden = $('plate_display');
            var boundMode = form.getAttribute('data-vehicle-bound') === '1'
                || (!!plateHidden && String(plateHidden.value || '').trim() !== ''
                    && brand && brand.type === 'hidden'
                    && String(brand.value || '').trim() !== '');

            if (cls) {
                cls.disabled = false;
            }

            if (!boundMode) {
                if (!brand || !brand.value || brand.value === 'انتخاب برند') {
                    msgs.push('برند خودرو را انتخاب کنید.');
                    firstInvalid = firstInvalid || brand;
                } else if (window.m360IsTopLevelOtherBrand && window.m360IsTopLevelOtherBrand(brand.value)) {
                    var brandOther = form.querySelector('[name="brand_other_explanation"]');
                    if (!brandOther || !String(brandOther.value || '').trim()) {
                        msgs.push('برای برند «سایر»، توضیح الزامی است.');
                        firstInvalid = firstInvalid || brandOther;
                    }
                } else if (cls && (!cls.value || cls.value === 'انتخاب کلاس / مدل')) {
                    msgs.push('کلاس / مدل خودرو را انتخاب کنید.');
                    firstInvalid = firstInvalid || cls;
                } else if (cls && window.m360IsModelListGap && window.m360IsModelListGap(brand.value, cls.value)) {
                    var modelOther = form.querySelector('[name="model_other_explanation"]');
                    if (!modelOther || !String(modelOther.value || '').trim()) {
                        msgs.push('برای مدل «سایر»، توضیح الزامی است.');
                        firstInvalid = firstInvalid || modelOther;
                    }
                }

                if (!year || !year.value || year.value === 'انتخاب سال') {
                    msgs.push('سال ساخت خودرو را انتخاب کنید.');
                    firstInvalid = firstInvalid || year;
                }
                if (!plateHidden || !String(plateHidden.value || '').trim()) {
                    msgs.push('پلاک خودرو را کامل وارد کنید.');
                    firstInvalid = firstInvalid || plateHidden || $('plate_first_digit_1');
                }
            }

            if (!visit || !String(visit.value || '').trim()) {
                msgs.push('تاریخ مراجعه را از تقویم انتخاب کنید.');
                firstInvalid = firstInvalid || visit || $('m360_server_calendar');
            }
            if (!mileage || String(mileage.value || '').trim() === '') {
                msgs.push('کیلومتر ورود الزامی است.');
                firstInvalid = firstInvalid || mileage;
            }
            if (!fuel || !fuel.value) {
                msgs.push('سطح سوخت را انتخاب کنید.');
                firstInvalid = firstInvalid || fuel;
            }

            if (msgs.length) {
                e.preventDefault();
                clearAllBusyButtons();
                if (errBox) {
                    errBox.textContent = msgs.join(' ');
                    errBox.style.display = 'block';
                    errBox.scrollIntoView({ block: 'center', behavior: 'smooth' });
                } else {
                    alert(msgs.join('\n'));
                }
                if (firstInvalid && typeof firstInvalid.focus === 'function') {
                    try { firstInvalid.focus({ preventScroll: false }); } catch (err) { firstInvalid.focus(); }
                }
                return;
            }
            if (errBox) {
                errBox.textContent = '';
                errBox.style.display = 'none';
            }
        });
    }

    function showStepNavError(message, focusEl) {
        var box = $('m360_rw_step_nav_error');
        if (box) {
            box.textContent = message || 'این مرحله هنوز کامل نیست.';
            box.style.display = 'block';
            box.scrollIntoView({ block: 'center', behavior: 'smooth' });
        } else {
            alert(message || 'این مرحله هنوز کامل نیست.');
        }
        if (focusEl && typeof focusEl.focus === 'function') {
            try { focusEl.focus({ preventScroll: false }); } catch (err) { focusEl.focus(); }
            if (focusEl.scrollIntoView) {
                focusEl.scrollIntoView({ block: 'center', behavior: 'smooth' });
            }
        }
    }

    function validateCurrentStepBeforeNext(stepKey) {
        if (stepKey === 'vehicle') {
            var form = $('m360_rw_vehicle_form');
            if (!form) {
                return { ok: true };
            }
            var fuel = form.querySelector('select[name="fuel_level"]');
            var mileage = form.querySelector('input[name="mileage"]');
            if (fuel && !fuel.value) {
                return { ok: false, message: 'سطح سوخت را انتخاب کنید.', focus: fuel };
            }
            if (mileage && String(mileage.value || '').trim() === '') {
                return { ok: false, message: 'کیلومتر ورود الزامی است.', focus: mileage };
            }
            return { ok: true };
        }
        if (stepKey === 'condition') {
            var pending = document.querySelectorAll('.m360-rw-photo-card.is-pending');
            if (pending && pending.length) {
                var focusTarget = document.getElementById('m360_rw_camera_panel')
                    || document.getElementById('section-condition-photos')
                    || pending[0];
                return {
                    ok: false,
                    message: 'ثبت عکس‌های شش‌گانه پذیرش لازم است. اسلات‌های باقی‌مانده: ' + pending.length + ' — فقط از دوربین ثبت کنید.',
                    focus: focusTarget
                };
            }
            var confirmForm = document.querySelector('.m360-rw-photo-final-confirm');
            if (confirmForm) {
                return {
                    ok: false,
                    message: 'ابتدا هر ۶ عکس پذیرش را ثبت و تأیید نهایی کنید.',
                    focus: confirmForm.querySelector('button[type="submit"]') || confirmForm
                };
            }
            return { ok: true };
        }
        if (stepKey === 'documents') {
            var cost = document.querySelector('input[name="cost_agreement"], textarea[name="cost_agreement"]');
            if (cost && String(cost.value || '').trim() === '') {
                return { ok: false, message: 'توافق هزینه را وارد کنید.', focus: cost };
            }
            return { ok: true };
        }
        return { ok: true };
    }

    function bindStepNextValidation() {
        document.querySelectorAll('[data-m360-validate-before-next="1"]').forEach(function (link) {
            link.addEventListener('click', function (e) {
                var step = link.getAttribute('data-current-step') || '';
                var result = validateCurrentStepBeforeNext(step);
                if (!result.ok) {
                    e.preventDefault();
                    showStepNavError(result.message, result.focus || null);
                }
            });
        });
    }

    function bindVehicleSelector() {
        var brandSelect = $('m360_rw_vehicle_brand');
        var classSelect = $('m360_rw_vehicle_class');
        if (!brandSelect || !classSelect) {
            return;
        }
        if (!window.m360BindVehicleSelector) {
            console.error('MOGHARE360: vehicle-brand-classes.js not loaded; brand dropdown PHP fallback remains active.');
            return;
        }
        window.m360BindVehicleSelector({
            brandSelect: brandSelect,
            classSelect: classSelect,
            topOtherPanel: $('m360_rw_top_other_panel'),
            modelGapPanel: $('m360_rw_model_gap_panel'),
            initialBrand: window.m360RwVehicleInit ? window.m360RwVehicleInit.brand : '',
            initialModel: window.m360RwVehicleInit ? window.m360RwVehicleInit.model : ''
        });
    }

    function bindVisitCalendar() {
        var calendar = $('m360_rw_server_calendar');
        var visitHidden = $('m360_rw_visit_date');
        var visitDisplay = $('m360_rw_visit_date_display');
        if (!calendar || !visitHidden || !visitDisplay) return;
        calendar.querySelectorAll('.m360-calendar-day').forEach(function (btn) {
            if (btn.disabled || btn.getAttribute('data-selectable') === '0') {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                });
                return;
            }
            btn.addEventListener('click', function () {
                calendar.querySelectorAll('.m360-calendar-day--selected').forEach(function (el) {
                    el.classList.remove('m360-calendar-day--selected');
                });
                btn.classList.add('m360-calendar-day--selected');
                visitHidden.value = btn.getAttribute('data-gregorian') || '';
                visitDisplay.value = btn.getAttribute('data-label') || '';
                visitDisplay.classList.add('m360-date-display--filled');
            });
        });
    }

    function initCameraCapture() {
        var video = $('m360_rw_camera_video');
        var canvas = $('m360_rw_camera_canvas');
        var startBtn = $('m360_rw_camera_start');
        var fallbackNote = $('m360_rw_camera_fallback_note');
        var stream = null;
        var cameraDeniedMsg = 'دسترسی دوربین فعال نشد. برای ثبت عکس پذیرش باید مجوز دوربین را بدهید.';

        function showCameraFallbackNote() {
            if (fallbackNote) fallbackNote.hidden = false;
            if (startBtn) startBtn.disabled = true;
        }

        function applySlotImage(slot, data, readyText) {
            var form = document.querySelector('.m360-rw-photo-slot-form input[name="photo_slot"][value="' + slot + '"]');
            form = form ? form.closest('.m360-rw-photo-slot-form') : null;
            if (!form && slot) {
                var card = document.getElementById('photo-slot-' + slot);
                form = card ? card.querySelector('.m360-rw-photo-slot-form') : null;
            }
            if (!form) return;
            var hidden = form.querySelector('.m360-rw-slot-base64');
            var saveBtn = form.querySelector('.m360-rw-slot-save');
            if (hidden) hidden.value = data;
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.classList.add('is-ready');
                saveBtn.removeAttribute('aria-disabled');
            }
            var preview = document.getElementById('m360_rw_preview_' + slot);
            if (preview) {
                preview.src = data;
                preview.style.display = 'block';
            }
            var hint = document.getElementById('m360_rw_hint_' + slot);
            if (hint) {
                hint.hidden = false;
                hint.textContent = readyText || 'عکس آماده است؛ برای ذخیره روی «ذخیره عکس» بزنید.';
                hint.classList.add('is-ok');
            }
            var statusEl = form.closest('.m360-rw-photo-card');
            if (statusEl) {
                var statusSpan = statusEl.querySelector('.m360-rw-photo-card__status');
                if (statusSpan && statusSpan.textContent.indexOf('ثبت شده') === -1) {
                    statusSpan.textContent = 'آماده ذخیره';
                }
            }
        }

        function enableCamera() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                showCameraFallbackNote();
                return Promise.reject(new Error('no camera'));
            }
            if (stream) {
                return Promise.resolve(stream);
            }
            return navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false })
                .then(function (s) {
                    stream = s;
                    if (video) {
                        video.srcObject = s;
                        video.play();
                    }
                    return s;
                });
        }

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showCameraFallbackNote();
        }

        if (startBtn && video) {
            startBtn.addEventListener('click', function () {
                enableCamera().catch(function () {
                    showCameraFallbackNote();
                    alert(cameraDeniedMsg);
                });
            });
        }

        function captureForSlot() {
            if (!canvas || !video) return null;
            canvas.width = video.videoWidth || 640;
            canvas.height = video.videoHeight || 480;
            var ctx = canvas.getContext('2d');
            if (!ctx) return null;
            ctx.drawImage(video, 0, 0);
            return canvas.toDataURL('image/jpeg', 0.85);
        }

        document.querySelectorAll('.m360-rw-slot-capture').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var slot = btn.getAttribute('data-slot') || '';
                enableCamera().then(function () {
                    var data = captureForSlot(slot);
                    if (!data) {
                        alert('ثبت تصویر از دوربین ناموفق بود. دوباره تلاش کنید.');
                        return;
                    }
                    applySlotImage(slot, data, 'عکس گرفته شد؛ برای ذخیره روی «ذخیره عکس» بزنید.');
                }).catch(function () {
                    showCameraFallbackNote();
                    alert(cameraDeniedMsg);
                });
            });
        });

    }

    function initStepScroll() {
        if (window.location.hash) {
            var target = document.querySelector(window.location.hash);
            if (target) {
                window.requestAnimationFrame(function () {
                    target.scrollIntoView({ block: 'start', behavior: 'instant' in window ? 'instant' : 'auto' });
                });
            }
        } else {
            var activePanel = document.querySelector('.m360-rw-step-panel.is-active');
            if (activePanel) {
                window.requestAnimationFrame(function () {
                    activePanel.scrollIntoView({ block: 'start', behavior: 'instant' in window ? 'instant' : 'auto' });
                });
            }
        }

        document.querySelectorAll('.m360-rw-stepper-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var href = btn.getAttribute('href') || '';
                if (href.indexOf('#') > -1) {
                    sessionStorage.setItem('m360_rw_step_scroll', '1');
                }
            });
        });
    }

    function bindRouteLinkProtection() {
        document.querySelectorAll('.m360-rw-back, .m360-rw-header a[href]').forEach(function (link) {
            link.addEventListener('click', function () {
                clearAllBusyButtons();
            }, true);
        });
    }

    function initDocumentUploadAccept() {
        var typeSel = document.getElementById('document_type');
        var fileInput = document.getElementById('intake_document');
        if (!typeSel || !fileInput) return;
        function syncAccept() {
            var isVideo = String(typeSel.value || '') === 'vehicle_video';
            var accept = isVideo
                ? (typeSel.getAttribute('data-video-accept') || 'video/mp4,video/webm,.mp4,.webm,.mov,.3gp')
                : (typeSel.getAttribute('data-pdf-accept') || 'application/pdf,.pdf');
            fileInput.setAttribute('accept', accept);
            fileInput.value = '';
        }
        typeSel.addEventListener('change', syncAccept);
        syncAccept();
    }

    function initMoneyThousandSeparators() {
        function toAsciiDigits(v) {
            return String(v || '')
                .replace(/[۰-۹]/g, function (d) { return String('۰۱۲۳۴۵۶۷۸۹'.indexOf(d)); })
                .replace(/[٠-٩]/g, function (d) { return String('٠١٢٣٤٥٦٧٨٩'.indexOf(d)); });
        }
        function digitsOnly(v) {
            return toAsciiDigits(v).replace(/[^\d]/g, '');
        }
        function formatThousands(v) {
            var d = digitsOnly(v);
            if (!d) return '';
            return d.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }
        function bindMoneyInput(el) {
            if (!el || el.getAttribute('data-m360-money-bound') === '1') return;
            el.setAttribute('data-m360-money-bound', '1');
            // Ensure reload/display always shows separators even if PHP left raw digits.
            if (el.value) {
                el.value = formatThousands(el.value);
            }
            el.addEventListener('input', function () {
                var start = el.selectionStart;
                var before = el.value;
                var formatted = formatThousands(el.value);
                el.value = formatted;
                if (typeof start === 'number') {
                    var diff = formatted.length - before.length;
                    var pos = Math.max(0, start + diff);
                    try { el.setSelectionRange(pos, pos); } catch (e) {}
                }
            });
            el.addEventListener('blur', function () {
                el.value = formatThousands(el.value);
            });
        }
        document.querySelectorAll('input[data-m360-money="1"], input.m360-rw-money-input').forEach(bindMoneyInput);
        document.querySelectorAll('form.m360-rw-agreements-form').forEach(function (form) {
            form.addEventListener('submit', function () {
                form.querySelectorAll('input[data-m360-money="1"], input.m360-rw-money-input').forEach(function (el) {
                    el.value = digitsOnly(el.value);
                });
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        clearAllBusyButtons();
        bindRouteLinkProtection();
        window.addEventListener('pageshow', clearAllBusyButtons);
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') clearAllBusyButtons();
        });
        bindReceptionFormLoading();
        bindServiceRoutePanel();
        bindTrunkOtherToggle();
        bindDamageSelector();
        var plateDigitIds = [
            'plate_first_digit_1', 'plate_first_digit_2',
            'plate_middle_digit_1', 'plate_middle_digit_2', 'plate_middle_digit_3',
            'plate_region_digit_1', 'plate_region_digit_2'
        ];
        plateDigitIds.forEach(function (id) {
            var el = $(id);
            if (el) el.addEventListener('change', buildPlateDisplay);
        });
        var plateLetter = $('plate_letter');
        if (plateLetter) plateLetter.addEventListener('change', buildPlateDisplay);
        chainPlateFocus(plateDigitIds.concat(['plate_letter']));
        buildPlateDisplay();

        bindVehicleSelector();
        bindVehicleStepForm();
        bindVisitCalendar();
        initCameraCapture();
        initStepScroll();
        bindStepNextValidation();
        initDocumentUploadAccept();
        initMoneyThousandSeparators();
    });
})();
