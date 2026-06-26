/**
 * MOGHARE360 — Customer request form (OTP-first entry flow).
 */
(function () {
  'use strict';

  var RESEND_SECONDS = 60;
  var verified = false;
  var resendTimerId = null;

  function $(id) { return document.getElementById(id); }

  function bindPersianValidity(select, message) {
    if (!select) return;
    select.addEventListener('invalid', function (e) {
      if (e.target.validity.valueMissing) {
        e.target.setCustomValidity(message || 'لطفاً یک گزینه را انتخاب کنید.');
      }
    });
    select.addEventListener('change', function (e) {
      e.target.setCustomValidity('');
    });
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

  function populateDigitSelect(select) {
    if (!select) return;
    var current = select.value;
    select.innerHTML = '<option value="">-</option>';
    for (var n = 0; n <= 9; n++) {
      var opt = document.createElement('option');
      opt.value = String(n);
      opt.textContent = String(n);
      select.appendChild(opt);
    }
    if (current !== '') select.value = current;
  }

  function fetchJson(url, body) {
    return fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify(body || {})
    }).then(function (res) {
      return res.text().then(function (text) {
        var data = null;
        try {
          data = text ? JSON.parse(text) : null;
        } catch (e) {
          data = { ok: false, message: 'پاسخ سرور نامعتبر است. لطفاً دوباره تلاش کنید.' };
        }
        return { status: res.status, data: data };
      });
    });
  }

  function setStatus(el, message, type) {
    if (!el) return;
    el.textContent = message || '';
    el.classList.remove('m360-otp-status--ok', 'm360-otp-status--error', 'm360-otp-status--pending');
    if (type === 'ok') el.classList.add('m360-otp-status--ok');
    if (type === 'error') el.classList.add('m360-otp-status--error');
    if (type === 'pending') el.classList.add('m360-otp-status--pending');
  }

  function showSection(id, visible) {
    var el = $(id);
    if (!el) return;
    el.classList.toggle('m360-step--hidden', !visible);
    el.classList.toggle('m360-step-card--active', !!visible);
  }

  function hideFormSections() {
    ['m360_step_welcome', 'm360_section_profile', 'm360_section_vehicle', 'm360_section_request'].forEach(function (id) {
      showSection(id, false);
    });
    setSubmitEnabled(false);
  }

  function setSubmitEnabled(enabled) {
    var btn = $('m360_submit_btn');
    var hidden = $('mobile_verified');
    if (btn) btn.disabled = !enabled;
    if (hidden) hidden.value = enabled ? '1' : '0';
  }

  function setFieldRequired(el, required) {
    if (!el) return;
    if (required) {
      el.setAttribute('required', 'required');
    } else {
      el.removeAttribute('required');
      el.setCustomValidity('');
    }
  }

  function setNewCustomerRequired(enabled) {
    document.querySelectorAll('[data-required-new="1"]').forEach(function (el) {
      setFieldRequired(el, enabled);
    });
  }

  function setBothFlowRequired(enabled) {
    document.querySelectorAll('[data-required-both="1"]').forEach(function (el) {
      setFieldRequired(el, enabled);
    });
  }

  function startResendCountdown() {
    var resendBtn = $('m360_resend_otp');
    var timerEl = $('m360_resend_timer');
    var remaining = RESEND_SECONDS;
    if (resendBtn) resendBtn.disabled = true;
    if (resendTimerId) clearInterval(resendTimerId);
    if (timerEl) timerEl.textContent = remaining + ' ثانیه تا ارسال مجدد';
    resendTimerId = setInterval(function () {
      remaining -= 1;
      if (remaining <= 0) {
        clearInterval(resendTimerId);
        resendTimerId = null;
        if (resendBtn) resendBtn.disabled = false;
        if (timerEl) timerEl.textContent = '';
        return;
      }
      if (timerEl) timerEl.textContent = remaining + ' ثانیه تا ارسال مجدد';
    }, 1000);
  }

  function resetOtpFlow() {
    verified = false;
    setSubmitEnabled(false);
    hideFormSections();
    showSection('m360_step_otp', false);
    setStatus($('m360_otp_status'), '', '');
    setStatus($('m360_mobile_status'), '', '');
    var flow = $('customer_flow');
    if (flow) flow.value = 'new';
    var vname = $('verified_customer_name');
    if (vname) vname.value = '';
    setNewCustomerRequired(false);
    setBothFlowRequired(false);
    var mobile = $('mobile');
    if (mobile) mobile.readOnly = false;
  }

  function sendOtp() {
    var mobile = $('mobile');
    var sendBtn = $('m360_send_otp');
    var phone = mobile ? mobile.value.trim() : '';
    if (!/^09\d{9}$/.test(phone)) {
      setStatus($('m360_mobile_status'), 'شماره موبایل معتبر نیست. فرمت صحیح: 09xxxxxxxxx', 'error');
      return;
    }
    if (sendBtn) sendBtn.disabled = true;
    setStatus($('m360_mobile_status'), 'در حال ارسال کد تأیید...', 'pending');
    fetchJson('api/customer/send-otp.php', { phone: phone })
      .then(function (result) {
        if (result.data && result.data.ok) {
          setStatus($('m360_mobile_status'), result.data.message || 'کد تأیید ارسال شد.', 'ok');
          showSection('m360_step_otp', true);
          startResendCountdown();
        } else {
          setStatus($('m360_mobile_status'), (result.data && result.data.message) || 'ارسال کد تأیید ناموفق بود.', 'error');
        }
      })
      .catch(function () {
        setStatus($('m360_mobile_status'), 'ارسال کد تأیید ناموفق بود. لطفاً دوباره تلاش کنید.', 'error');
      })
      .finally(function () {
        if (sendBtn) sendBtn.disabled = false;
      });
  }

  function loadProfileAndShowForm() {
    var mobile = $('mobile');
    var phone = mobile ? mobile.value.trim() : '';
    fetchJson('api/customer/profile-status.php', { mobile: phone })
      .then(function (result) {
        var payload = (result.data && result.data.data) ? result.data.data : (result.data || {});
        var exists = !!payload.customer_exists;
        var customer = payload.customer || {};
        var flow = $('customer_flow');
        var vname = $('verified_customer_name');
        if (flow) flow.value = exists ? 'returning' : 'new';
        if (vname && customer.full_name) vname.value = customer.full_name;

        showSection('m360_step_welcome', true);
        var welcome = $('m360_welcome_message');
        if (welcome) {
          welcome.textContent = exists
            ? 'مشتری گرامی، شماره شما تأیید شد.'
            : 'خوش آمدید. لطفاً اطلاعات خود را تکمیل کنید.';
        }
        var vehicleHint = $('m360_last_vehicle_hint');
        if (vehicleHint) {
          vehicleHint.textContent = exists && customer.last_vehicle
            ? 'آخرین خودرو: ' + customer.last_vehicle
            : '';
        }

        if (exists) {
          showSection('m360_section_profile', false);
          setNewCustomerRequired(false);
        } else {
          showSection('m360_section_profile', true);
          setNewCustomerRequired(true);
        }
        showSection('m360_section_vehicle', true);
        showSection('m360_section_request', true);
        setBothFlowRequired(true);
        setSubmitEnabled(true);
        if (mobile) mobile.readOnly = true;
      })
      .catch(function () {
        setStatus($('m360_otp_status'), 'بارگذاری وضعیت مشتری ناموفق بود. لطفاً دوباره تلاش کنید.', 'error');
        showSection('m360_section_profile', true);
        showSection('m360_section_vehicle', true);
        showSection('m360_section_request', true);
        setNewCustomerRequired(true);
        setBothFlowRequired(true);
        setSubmitEnabled(true);
        var flow = $('customer_flow');
        if (flow) flow.value = 'new';
      });
  }

  function verifyOtp() {
    var mobile = $('mobile');
    var otpInput = $('m360_otp_code');
    var verifyBtn = $('m360_verify_otp');
    var phone = mobile ? mobile.value.trim() : '';
    var code = otpInput ? otpInput.value.trim() : '';
    if (!/^09\d{9}$/.test(phone)) {
      setStatus($('m360_otp_status'), 'شماره موبایل معتبر نیست.', 'error');
      return;
    }
    if (!/^\d{6}$/.test(code)) {
      setStatus($('m360_otp_status'), 'کد تأیید باید ۶ رقم باشد.', 'error');
      return;
    }
    if (verifyBtn) verifyBtn.disabled = true;
    fetchJson('api/customer/verify-otp.php', { phone: phone, otp: code })
      .then(function (result) {
        if (result.data && result.data.ok) {
          verified = true;
          setStatus($('m360_otp_status'), 'شماره موبایل تأیید شد', 'ok');
          loadProfileAndShowForm();
        } else {
          verified = false;
          setStatus($('m360_otp_status'), (result.data && result.data.message) || 'تأیید شماره موبایل ناموفق بود.', 'error');
        }
      })
      .catch(function () {
        verified = false;
        setStatus($('m360_otp_status'), 'تأیید شماره موبایل ناموفق بود. لطفاً دوباره تلاش کنید.', 'error');
      })
      .finally(function () {
        if (verifyBtn) verifyBtn.disabled = false;
      });
  }

  function initServerVisitCalendar() {
    var hidden = $('visit_date');
    var display = $('visit_date_display');
    var calendar = $('m360_server_calendar');
    if (!hidden || !display || !calendar) return;
    calendar.querySelectorAll('.m360-calendar-day').forEach(function (btn) {
      btn.addEventListener('click', function () {
        hidden.value = btn.getAttribute('data-gregorian') || '';
        display.value = btn.getAttribute('data-label') || '';
        display.classList.add('m360-date-display--filled');
        display.classList.remove('m360-date-display--error');
        calendar.querySelectorAll('.m360-calendar-day').forEach(function (el) {
          el.classList.remove('m360-calendar-day--selected');
        });
        btn.classList.add('m360-calendar-day--selected');
      });
    });
  }

  function syncBirthDateHidden() {
    var y = $('birth_year_jalali');
    var m = $('birth_month_jalali');
    var d = $('birth_day_jalali');
    var hidden = $('birth_date');
    if (!y || !m || !d || !hidden) return;
    if (y.value && m.value && d.value) {
      hidden.value = y.value + '/' + String(m.value).padStart(2, '0') + '/' + String(d.value).padStart(2, '0');
    } else {
      hidden.value = '';
    }
  }

  function updateVisitHint() {
    var type = $('request_type');
    var hint = $('visit_time_hint');
    if (!type || !hint) return;
    hint.style.display = (type.value === 'diagnostic_inspection') ? 'block' : 'none';
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
    var preview = $('plate_preview');
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

  function initOtpFirstFlow() {
    var mobile = $('mobile');
    var sendBtn = $('m360_send_otp');
    var verifyBtn = $('m360_verify_otp');
    var resendBtn = $('m360_resend_otp');
    if (!mobile || !sendBtn || !verifyBtn) return;

    hideFormSections();
    showSection('m360_step_otp', false);
    setSubmitEnabled(false);
    setNewCustomerRequired(false);
    setBothFlowRequired(false);

    sendBtn.addEventListener('click', function (e) {
      e.preventDefault();
      sendOtp();
    });
    verifyBtn.addEventListener('click', function (e) {
      e.preventDefault();
      verifyOtp();
    });
    if (resendBtn) {
      resendBtn.addEventListener('click', function (e) {
        e.preventDefault();
        sendOtp();
      });
    }
    mobile.addEventListener('input', resetOtpFlow);
    mobile.addEventListener('change', resetOtpFlow);
  }

  document.addEventListener('DOMContentLoaded', function () {
    var province = $('province');
    var city = $('city');
    if (province && city && window.m360PopulateProvinces) {
      m360PopulateProvinces(province);
      city.disabled = true;
      province.addEventListener('change', function () { m360PopulateCities(province, city); });
      bindPersianValidity(province, 'لطفاً استان را انتخاب کنید.');
      bindPersianValidity(city, 'لطفاً شهر را انتخاب کنید.');
    }

    var brand = $('vehicle_brand');
    var vclass = $('vehicle_class');
    if (brand && vclass && window.m360PopulateVehicleBrands) {
      m360PopulateVehicleBrands(brand);
      vclass.disabled = true;
      brand.addEventListener('change', function () { m360PopulateVehicleClasses(brand, vclass); });
      bindPersianValidity(brand, 'لطفاً برند خودرو را انتخاب کنید.');
      bindPersianValidity(vclass, 'لطفاً کلاس / مدل خودرو را انتخاب کنید.');
    }

    bindPersianValidity($('vehicle_year_pair'), 'لطفاً سال تولید خودرو را انتخاب کنید.');
    bindPersianValidity($('request_type'), 'لطفاً نوع درخواست را انتخاب کنید.');

    ['birth_year_jalali', 'birth_month_jalali', 'birth_day_jalali'].forEach(function (id) {
      var el = $(id);
      if (el) el.addEventListener('change', syncBirthDateHidden);
    });
    syncBirthDateHidden();

    var plateDigitIds = [
      'plate_first_digit_1', 'plate_first_digit_2',
      'plate_middle_digit_1', 'plate_middle_digit_2', 'plate_middle_digit_3',
      'plate_region_digit_1', 'plate_region_digit_2'
    ];
    plateDigitIds.forEach(function (id) { populateDigitSelect($(id)); });
    bindPersianValidity($('plate_letter'), 'لطفاً حرف پلاک را انتخاب کنید.');
    var plateLetter = $('plate_letter');
    if (plateLetter) plateLetter.addEventListener('change', buildPlateDisplay);
    plateDigitIds.forEach(function (id) {
      var labels = {
        plate_first_digit_1: 'رقم اول پلاک را انتخاب کنید.',
        plate_first_digit_2: 'رقم دوم پلاک را انتخاب کنید.',
        plate_middle_digit_1: 'رقم اول بخش سه‌رقمی را انتخاب کنید.',
        plate_middle_digit_2: 'رقم دوم بخش سه‌رقمی را انتخاب کنید.',
        plate_middle_digit_3: 'رقم سوم بخش سه‌رقمی را انتخاب کنید.',
        plate_region_digit_1: 'رقم اول کد ایران را انتخاب کنید.',
        plate_region_digit_2: 'رقم دوم کد ایران را انتخاب کنید.'
      };
      bindPersianValidity($(id), labels[id]);
      var el = $(id);
      if (el) el.addEventListener('change', buildPlateDisplay);
    });
    chainPlateFocus(plateDigitIds.concat(['plate_letter']));

    initServerVisitCalendar();
    initOtpFirstFlow();

    var requestType = $('request_type');
    if (requestType) {
      requestType.addEventListener('change', updateVisitHint);
      updateVisitHint();
    }

    var form = document.querySelector('form.m360-customer-form');
    if (form) {
      form.addEventListener('submit', function (e) {
        buildPlateDisplay();
        syncBirthDateHidden();
        if (!$('mobile_verified') || $('mobile_verified').value !== '1') {
          e.preventDefault();
          setStatus($('m360_otp_status'), 'برای ثبت درخواست، ابتدا شماره موبایل خود را با کد پیامکی تأیید کنید.', 'error');
          return;
        }
        var visitHidden = $('visit_date');
        var visitDisplay = $('visit_date_display');
        if (!visitHidden || visitHidden.value === '') {
          e.preventDefault();
          if (visitDisplay) {
            visitDisplay.placeholder = 'لطفاً تاریخ مراجعه را از تقویم انتخاب کنید.';
            visitDisplay.classList.add('m360-date-display--error');
          }
        }
      });
    }
  });
})();
