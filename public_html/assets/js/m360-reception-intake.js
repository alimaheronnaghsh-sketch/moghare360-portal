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
        var panel = document.querySelector('[data-service-diag-panel]');
        if (!route || !panel) return;
        var sync = function () {
            var show = route.value === 'diag' || route.value === '';
            panel.hidden = !show;
        };
        route.addEventListener('change', sync);
        sync();
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
            var brand = $('m360_rw_vehicle_brand');
            var cls = $('m360_rw_vehicle_class');
            var year = $('m360_rw_vehicle_year');
            var visit = $('m360_rw_visit_date');
            var mileage = form.querySelector('input[name="mileage"]');
            var fuel = form.querySelector('select[name="fuel_level"]');
            var plateHidden = $('plate_display');

            if (cls) {
                cls.disabled = false;
            }

            if (!brand || !brand.value || brand.value === 'انتخاب برند') {
                msgs.push('برند خودرو را انتخاب کنید.');
            } else if (window.m360IsTopLevelOtherBrand && window.m360IsTopLevelOtherBrand(brand.value)) {
                var brandOther = form.querySelector('[name="brand_other_explanation"]');
                if (!brandOther || !String(brandOther.value || '').trim()) {
                    msgs.push('برای برند «سایر»، توضیح الزامی است.');
                }
            } else if (cls && (!cls.value || cls.value === 'انتخاب کلاس / مدل')) {
                msgs.push('کلاس / مدل خودرو را انتخاب کنید.');
            } else if (cls && window.m360IsModelListGap && window.m360IsModelListGap(brand.value, cls.value)) {
                var modelOther = form.querySelector('[name="model_other_explanation"]');
                if (!modelOther || !String(modelOther.value || '').trim()) {
                    msgs.push('برای مدل «سایر»، توضیح الزامی است.');
                }
            }

            if (!year || !year.value || year.value === 'انتخاب سال') {
                msgs.push('سال ساخت خودرو را انتخاب کنید.');
            }
            if (!visit || !String(visit.value || '').trim()) {
                msgs.push('تاریخ مراجعه را از تقویم انتخاب کنید.');
            }
            if (!plateHidden || !String(plateHidden.value || '').trim()) {
                msgs.push('پلاک خودرو را کامل وارد کنید.');
            }
            if (!mileage || String(mileage.value || '').trim() === '') {
                msgs.push('کیلومتر ورود الزامی است.');
            }
            if (!fuel || !fuel.value) {
                msgs.push('سطح سوخت را انتخاب کنید.');
            }

            if (msgs.length) {
                e.preventDefault();
                clearAllBusyButtons();
                if (errBox) {
                    errBox.textContent = msgs.join(' ');
                    errBox.style.display = 'block';
                    errBox.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                } else {
                    alert(msgs.join('\n'));
                }
                return;
            }
            if (errBox) {
                errBox.textContent = '';
                errBox.style.display = 'none';
            }
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
        var stream = null;

        function enableCamera() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                if (startBtn) startBtn.disabled = true;
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

        if (startBtn && video) {
            startBtn.addEventListener('click', function () {
                enableCamera().catch(function () {
                    alert('دسترسی به دوربین ممکن نیست. لطفاً مجوز دوربین را بررسی کنید.');
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
                    if (!data) return;
                    var form = btn.closest('.m360-rw-photo-slot-form');
                    if (!form) return;
                    var hidden = form.querySelector('.m360-rw-slot-base64');
                    var saveBtn = form.querySelector('.m360-rw-slot-save');
                    if (hidden) hidden.value = data;
                    if (saveBtn) saveBtn.disabled = false;
                    var preview = document.getElementById('m360_rw_preview_' + slot);
                    if (preview) {
                        preview.src = data;
                        preview.style.display = 'block';
                    }
                }).catch(function () {
                    alert('دسترسی به دوربین ممکن نیست. لطفاً مجوز دوربین را بررسی کنید.');
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

    document.addEventListener('DOMContentLoaded', function () {
        clearAllBusyButtons();
        bindRouteLinkProtection();
        window.addEventListener('pageshow', clearAllBusyButtons);
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') clearAllBusyButtons();
        });
        bindReceptionFormLoading();
        bindServiceRoutePanel();
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
    });
})();
