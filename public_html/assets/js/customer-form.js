/**
 * MOGHARE360 — Customer request form (OTP-first, step-based wizard).
 */
(function () {
  'use strict';

  var RESEND_SECONDS = 60;
  var verified = false;
  var resendTimerId = null;
  var currentWizardStep = 'm360_step_mobile';
  var profileVehicles = [];

  var ONLINE_WIZARD_STEPS_AFTER_OTP = [
    'm360_section_profile',
    'm360_section_vehicle',
    'm360_section_request'
  ];
  var STAFF_WIZARD_STEPS = [
    'm360_section_customer_search',
    'm360_section_profile',
    'm360_section_vehicle',
    'm360_section_request',
    'm360_section_condition',
    'm360_section_checklist',
    'm360_section_contract',
    'm360_section_submit'
  ];
  var ALL_WIZARD_SECTIONS = ONLINE_WIZARD_STEPS_AFTER_OTP.concat(STAFF_WIZARD_STEPS).filter(function (step, index, list) {
    return list.indexOf(step) === index;
  });

  function wizardStepsForPage() {
    var boot = window.m360CustomerPageBoot || {};
    return boot.staffWalkinMode ? STAFF_WIZARD_STEPS : ONLINE_WIZARD_STEPS_AFTER_OTP;
  }

  function wizardProgressStepsForPage() {
    var boot = window.m360CustomerPageBoot || {};
    return boot.staffWalkinMode ? STAFF_WIZARD_STEPS : ['m360_step_mobile'].concat(ONLINE_WIZARD_STEPS_AFTER_OTP);
  }

  function $(id) { return document.getElementById(id); }

  var busyButtons = [];

    function isBusyBlocked(el) {
    if (!el) return true;
    if (el.closest && el.closest('.m360-public-nav')) return true;
    if (el.closest && el.closest('.m360-rw-header')) return true;
    if (el.classList && el.classList.contains('m360-rw-back')) return true;
    if (el.getAttribute && el.getAttribute('data-m360-no-busy') === '1') return true;
    if (el.getAttribute && el.getAttribute('data-m360-nav-link') === '1') return true;
    if (el.tagName === 'A') return true;
    return false;
  }

  function clearNavLoadingState() {
    document.querySelectorAll('.m360-public-nav__link').forEach(function (link) {
      link.classList.remove('m360-btn-is-loading');
      link.removeAttribute('aria-busy');
      link.removeAttribute('disabled');
    });
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
    clearNavLoadingState();
    document.body.classList.remove('m360-page-busy');
  }

  function setButtonBusy(btn, busy) {
    if (!btn || isBusyBlocked(btn)) return;
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

  function isLocalDev() {
    var host = (window.location && window.location.hostname) ? window.location.hostname.toLowerCase() : '';
    return host === 'localhost' || host === '127.0.0.1' || host.endsWith('.localhost');
  }

  function logDevError(message, detail) {
    if (!isLocalDev()) return;
    if (detail !== undefined) console.error(message, detail);
    else console.error(message);
  }

  function resolveApiUrl(path) {
    try { return new URL(path, window.location.href).href; } catch (e) { return path; }
  }

  function isExplicitNewRequestMode() {
    return /(?:^|[?&])mode=new(?:&|$)/.test(window.location.search);
  }

  function profileRedirectUrl() {
    var boot = window.m360CustomerPageBoot || {};
    if (boot.profileRedirectUrl) {
      return boot.profileRedirectUrl;
    }
    try {
      return new URL('customer-profile.php', window.location.href).href;
    } catch (e) {
      return 'customer-profile.php';
    }
  }

  function bindPersianValidity(select, message) {
    if (!select) return;
    select.addEventListener('invalid', function (e) {
      if (e.target.validity.valueMissing) {
        e.target.setCustomValidity(message || 'لطفاً یک گزینه را انتخاب کنید.');
      }
    });
    select.addEventListener('change', function (e) { e.target.setCustomValidity(''); });
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
    return fetch(resolveApiUrl(url), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify(body || {})
    }).then(function (res) {
      return res.text().then(function (text) {
        var data = null;
        var trimmed = (text || '').trim();
        if (!trimmed) {
          return { status: res.status, data: { ok: false, message: 'ارسال کد تأیید انجام نشد. لطفاً دوباره تلاش کنید.' } };
        }
        try { data = JSON.parse(trimmed); } catch (e) {
          data = { ok: false, message: 'ارسال کد تأیید انجام نشد. لطفاً دوباره تلاش کنید.' };
        }
        return { status: res.status, data: data };
      });
    });
  }

  function setStatus(el, message, type) {
    if (!el) return;
    el.textContent = message || '';
    el.classList.remove('m360-otp-status--ok', 'm360-otp-status--error', 'm360-otp-status--pending', 'm360-state-success', 'm360-state-error');
    if (type === 'ok') el.classList.add('m360-otp-status--ok', 'm360-state-success');
    if (type === 'error') el.classList.add('m360-otp-status--error', 'm360-state-error');
    if (type === 'pending') el.classList.add('m360-otp-status--pending');
  }

  function showSection(id, visible) {
    var el = $(id);
    if (!el) return;
    el.classList.toggle('m360-step--hidden', !visible);
    el.classList.toggle('m360-step-card--active', !!visible);
  }

  var STEP_ERROR_IDS = {
    m360_section_customer_search: 'm360_customer_search_step_error',
    m360_section_profile: 'm360_profile_step_error',
    m360_section_vehicle: 'm360_vehicle_step_error',
    m360_section_request: 'm360_request_step_error',
    m360_section_condition: 'm360_condition_step_error',
    m360_section_checklist: 'm360_checklist_step_error',
    m360_section_contract: 'm360_contract_step_error',
    m360_section_submit: 'm360_submit_step_error',
    m360_step_otp: 'm360_otp_status'
  };

  function clearAllStepErrors() {
    Object.keys(STEP_ERROR_IDS).forEach(function (stepId) {
      var el = $(STEP_ERROR_IDS[stepId]);
      if (!el) return;
      if (stepId === 'm360_step_otp') {
        if (!verified) setStatus(el, '', '');
        return;
      }
      el.textContent = '';
      el.classList.add('m360-step--hidden');
    });
    var top = $('m360_top_submit_alert');
    if (top) top.style.display = 'none';
  }

  function showStepError(stepId, message) {
    clearAllStepErrors();
    var targetId = STEP_ERROR_IDS[stepId] || '';
    var el = targetId ? $(targetId) : null;
    if (!el || !message) return;
    if (stepId === 'm360_step_otp') {
      setStatus(el, message, 'error');
      return;
    }
    el.textContent = message;
    el.classList.remove('m360-step--hidden');
  }

  function hideAllWizardSections() {
    ['m360_step_welcome'].concat(ALL_WIZARD_SECTIONS).forEach(function (id) {
      showSection(id, false);
    });
  }

  function updateWizardProgress(stepId) {
    var nav = $('m360_wizard_progress');
    if (!nav) return;
    var steps = wizardProgressStepsForPage();
    nav.hidden = steps.indexOf(stepId) < 0;
    nav.querySelectorAll('[data-step]').forEach(function (li) {
      var itemStep = li.getAttribute('data-step');
      li.classList.toggle('m360-customer-wizard-progress__item--active', li.getAttribute('data-step') === stepId);
      li.classList.toggle('m360-customer-wizard-progress__item--done', steps.indexOf(itemStep) >= 0 && steps.indexOf(itemStep) < steps.indexOf(stepId));
    });
  }

  function goToWizardStep(stepId) {
    currentWizardStep = stepId;
    var inPostOtp = wizardStepsForPage().indexOf(stepId) >= 0;
    showSection('m360_step_mobile', stepId === 'm360_step_mobile');
    showSection('m360_step_otp', stepId === 'm360_step_otp');
    if (inPostOtp) {
      showSection('m360_step_mobile', false);
      showSection('m360_step_otp', false);
      var progress = $('m360_wizard_progress');
      if (progress) progress.hidden = false;
    }
    hideAllWizardSections();
    if (inPostOtp) {
      showSection(stepId, true);
    }
    updateWizardProgress(stepId);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function setSubmitEnabled(enabled) {
    var btn = $('m360_submit_btn');
    var hidden = $('mobile_verified');
    if (btn) btn.disabled = !enabled;
    if (hidden) hidden.value = enabled ? '1' : '0';
  }

  function setFieldRequired(el, required) {
    if (!el) return;
    if (required) el.setAttribute('required', 'required');
    else { el.removeAttribute('required'); el.setCustomValidity(''); }
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

  function setVehicleNewFieldsRequired(required) {
    var mode = $('vehicle_mode');
    var isNew = !mode || mode.value !== 'existing';
    document.querySelectorAll('#m360_vehicle_new_fields [data-required-both="1"]').forEach(function (el) {
      setFieldRequired(el, required && isNew);
    });
  }

  function isStaffWalkinMode() {
    var boot = window.m360CustomerPageBoot || {};
    return !!boot.staffWalkinMode;
  }

  function setWalkinVisitFieldsRequired(required) {
    if (!isStaffWalkinMode()) return;
    ['odometer_km', 'fuel_level'].forEach(function (id) {
      setFieldRequired($(id), !!required);
    });
  }

  function setExistingVehicleSummary(vehicle) {
    var summary = $('m360_existing_vehicle_summary');
    var label = $('m360_existing_vehicle_summary_label');
    if (!summary) return;
    if (!vehicle) {
      summary.hidden = true;
      if (label) label.textContent = '';
      return;
    }
    summary.hidden = false;
    if (label) label.textContent = vehicle.label || [vehicle.brand, vehicle.model, vehicle.plate].filter(Boolean).join(' — ');
  }

  function applyExistingVehicleSelection(vehicle) {
    var mode = $('vehicle_mode');
    var selected = $('selected_vehicle_id');
    var newFields = $('m360_vehicle_new_fields');
    if (mode) mode.value = 'existing';
    if (selected) selected.value = String(vehicle.vehicle_id || '');
    if (newFields) newFields.hidden = true;
    setVehicleNewFieldsRequired(false);
    if (isStaffWalkinMode()) {
      setExistingVehicleSummary(vehicle);
      setWalkinVisitFieldsRequired(true);
      var visitFields = $('m360_vehicle_visit_fields');
      if (visitFields) visitFields.hidden = false;
    }
  }

  function applyNewVehicleSelection() {
    var mode = $('vehicle_mode');
    var selected = $('selected_vehicle_id');
    var newFields = $('m360_vehicle_new_fields');
    var list = $('m360_vehicle_picker_list');
    if (mode) mode.value = 'new';
    if (selected) selected.value = '';
    if (list) list.querySelectorAll('input[type="radio"]').forEach(function (r) { r.checked = false; });
    if (newFields) newFields.hidden = false;
    setVehicleNewFieldsRequired(true);
    setExistingVehicleSummary(null);
    if (isStaffWalkinMode()) {
      setWalkinVisitFieldsRequired(true);
      var visitFields = $('m360_vehicle_visit_fields');
      if (visitFields) visitFields.hidden = false;
    }
  }

  function syncFullNameHidden() {
    var first = $('first_name');
    var last = $('last_name');
    var hidden = $('full_name');
    if (!hidden) return;
    var full = ((first ? first.value.trim() : '') + ' ' + (last ? last.value.trim() : '')).trim();
    hidden.value = full;
  }

  function renderVehiclePicker(vehicles, outOfScope) {
    profileVehicles = vehicles || [];
    var picker = $('m360_vehicle_picker');
    var list = $('m360_vehicle_picker_list');
    var newFields = $('m360_vehicle_new_fields');
    var mode = $('vehicle_mode');
    var selected = $('selected_vehicle_id');
    var oosWrap = $('m360_vehicle_out_of_scope');
    var oosList = $('m360_vehicle_out_of_scope_list');
    if (!picker || !list || !newFields) return;

    list.innerHTML = '';
    if (oosList) oosList.innerHTML = '';
    var oos = outOfScope || [];
    if (oosWrap) oosWrap.hidden = oos.length === 0;
    oos.forEach(function (v) {
      if (!oosList) return;
      var li = document.createElement('li');
      li.textContent = (v.label || '') + ' — خارج از محدوده فعلی';
      oosList.appendChild(li);
    });

    if (profileVehicles.length === 0) {
      picker.hidden = oos.length === 0;
      applyNewVehicleSelection();
      return;
    }

    picker.hidden = false;
    profileVehicles.forEach(function (v) {
      var label = document.createElement('label');
      label.className = 'm360-vehicle-picker__item';
      var radio = document.createElement('input');
      radio.type = 'radio';
      radio.name = 'vehicle_pick';
      radio.value = String(v.vehicle_id);
      radio.addEventListener('change', function () {
        applyExistingVehicleSelection(v);
      });
      label.appendChild(radio);
      label.appendChild(document.createTextNode(' ' + (v.label || '')));
      list.appendChild(label);
    });

    var addBtn = $('m360_vehicle_add_new');
    if (addBtn) {
      addBtn.onclick = function () {
        applyNewVehicleSelection();
      };
    }
    applyNewVehicleSelection();
  }

  function fillProfileForm(profile) {
    if (!profile) return;
    var map = {
      first_name: profile.first_name,
      last_name: profile.last_name,
      national_id: profile.national_id,
      second_phone: profile.second_phone,
      residence_address: profile.residence_address,
      vehicle_delivery_address: profile.vehicle_delivery_address,
      authorized_receiver_name: profile.authorized_receiver_name,
      authorized_receiver_phone: profile.authorized_receiver_phone
    };
    Object.keys(map).forEach(function (key) {
      var el = $(key);
      if (el && map[key]) el.value = map[key];
    });
    if (profile.city) {
      var city = $('city');
      if (city) {
        city.disabled = false;
        if (!city.querySelector('option[value="' + profile.city + '"]')) {
          var opt = document.createElement('option');
          opt.value = profile.city;
          opt.textContent = profile.city;
          city.appendChild(opt);
        }
        city.value = profile.city;
      }
    }
    var profileMobile = $('profile_primary_mobile');
    var mobileInput = $('mobile');
    if (profileMobile) {
      profileMobile.value = profile.primary_mobile || (mobileInput ? mobileInput.value : '');
    }
    syncFullNameHidden();
  }

  function formCsrfToken() {
    var token = document.querySelector('input[name="erp_csrf_token"]');
    return token ? token.value : '';
  }

  function setWalkinSearchStatus(message, type) {
    setStatus($('m360_walkin_customer_search_status'), message, type);
  }

  function setHiddenValue(id, value) {
    var el = $(id);
    if (el) el.value = value || '';
  }

  function copyWalkinSearchToProfile() {
    var searchMobile = $('walkin_search_mobile');
    var searchNational = $('walkin_search_national_id');
    var mobile = $('mobile');
    var national = $('national_id');
    if (mobile && searchMobile && mobile.value.trim() === '') mobile.value = searchMobile.value.trim();
    if (national && searchNational && national.value.trim() === '') national.value = searchNational.value.trim();
    var profileMobile = $('profile_primary_mobile');
    if (profileMobile && mobile) profileMobile.value = mobile.value;
  }

  function clearWalkinNewCustomerProfile() {
    ['first_name', 'last_name', 'full_name', 'second_phone', 'residence_address', 'vehicle_delivery_address', 'authorized_receiver_name', 'authorized_receiver_phone', 'customer_notes'].forEach(function (id) {
      var el = $(id);
      if (el) el.value = '';
    });
    setHiddenValue('selected_existing_customer_id', '');
    copyWalkinSearchToProfile();
  }

  function beginWalkinNewCustomer() {
    var flow = $('customer_flow');
    if (flow) flow.value = 'new';
    setHiddenValue('verified_customer_name', '');
    clearWalkinNewCustomerProfile();
    renderVehiclePicker([], []);
    setNewCustomerRequired(true);
    setBothFlowRequired(true);
    setVehicleNewFieldsRequired(true);
    setSubmitEnabled(true);
    clearAllStepErrors();
    goToWizardStep('m360_section_profile');
  }

  function applyWalkinExistingCustomer(payload) {
    var profile = payload.profile || {};
    var mobile = $('mobile');
    var profileMobile = profile.primary_mobile || profile.mobile || '';
    if (mobile && profileMobile) {
      mobile.value = profileMobile;
      mobile.readOnly = false;
    }
    fillProfileForm(profile);
    setHiddenValue('customer_flow', 'returning');
    setHiddenValue('verified_customer_name', profile.full_name || '');
    setHiddenValue('selected_existing_customer_id', String(profile.customer_id || ''));
    renderVehiclePicker(payload.vehicles || [], payload.vehicles_out_of_scope || []);
    setNewCustomerRequired(false);
    setBothFlowRequired(true);
    setVehicleNewFieldsRequired(true);
    setSubmitEnabled(true);
    clearAllStepErrors();
    setWalkinSearchStatus('مشتری موجود پیدا شد. ادامه از مرحله اطلاعات خودرو انجام می‌شود.', 'ok');
    goToWizardStep('m360_section_vehicle');
  }

  function searchWalkinCustomer() {
    var searchBtn = $('m360_walkin_customer_search_btn');
    var mobile = $('walkin_search_mobile');
    var national = $('walkin_search_national_id');
    var phone = mobile ? mobile.value.trim() : '';
    var nationalId = national ? national.value.trim() : '';
    if (phone === '' && nationalId === '') {
      showStepError('m360_section_customer_search', 'شماره موبایل یا کد ملی مشتری را وارد کنید.');
      setWalkinSearchStatus('شماره موبایل یا کد ملی مشتری را وارد کنید.', 'error');
      return;
    }
    if (phone !== '' && !/^09\d{9}$/.test(phone)) {
      showStepError('m360_section_customer_search', 'شماره موبایل معتبر ۱۱ رقمی وارد کنید.');
      setWalkinSearchStatus('شماره موبایل معتبر ۱۱ رقمی وارد کنید.', 'error');
      return;
    }
    setButtonBusy(searchBtn, true);
    setWalkinSearchStatus('در حال جستجوی مشتری...', 'pending');
    fetchJson('api/staff/customer-lookup.php', {
      mobile: phone,
      national_id: nationalId,
      erp_csrf_token: formCsrfToken()
    }).then(function (result) {
      var data = result.data || {};
      var payload = data.data || data;
      if (data.ok && payload.customer_exists) {
        applyWalkinExistingCustomer(payload);
        return;
      }
      if (data.ok) {
        setWalkinSearchStatus('مشتری پیدا نشد. اطلاعات مشتری جدید را تکمیل کنید.', 'pending');
        beginWalkinNewCustomer();
        return;
      }
      showStepError('m360_section_customer_search', data.message || 'جستجوی مشتری ناموفق بود.');
      setWalkinSearchStatus(data.message || 'جستجوی مشتری ناموفق بود.', 'error');
    }).catch(function () {
      showStepError('m360_section_customer_search', 'جستجوی مشتری ناموفق بود.');
      setWalkinSearchStatus('جستجوی مشتری ناموفق بود.', 'error');
    }).finally(function () {
      setButtonBusy(searchBtn, false);
    });
  }

  function initStaffCustomerSearch() {
    var boot = window.m360CustomerPageBoot || {};
    if (!boot.staffWalkinMode) return;
    var searchBtn = $('m360_walkin_customer_search_btn');
    var newBtn = $('m360_walkin_new_customer_btn');
    if (searchBtn) {
      searchBtn.addEventListener('click', function (e) {
        e.preventDefault();
        searchWalkinCustomer();
      });
    }
    if (newBtn) {
      newBtn.addEventListener('click', function (e) {
        e.preventDefault();
        setWalkinSearchStatus('اطلاعات مشتری جدید را تکمیل کنید.', 'pending');
        beginWalkinNewCustomer();
      });
    }
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
    hideAllWizardSections();
    showSection('m360_step_otp', false);
    goToWizardStep('m360_step_mobile');
    setStatus($('m360_otp_status'), '', '');
    setStatus($('m360_mobile_status'), 'برای شروع، شماره موبایل خود را وارد کنید.', '');
    var flow = $('customer_flow');
    if (flow) flow.value = 'new';
    var vname = $('verified_customer_name');
    if (vname) vname.value = '';
    setNewCustomerRequired(false);
    setBothFlowRequired(false);
    setVehicleNewFieldsRequired(false);
    var mobile = $('mobile');
    if (mobile) mobile.readOnly = false;
    var progress = $('m360_wizard_progress');
    if (progress) progress.hidden = true;
  }

  function sendOtp() {
    var mobile = $('mobile');
    var sendBtn = $('m360_send_otp');
    var phone = mobile ? mobile.value.trim() : '';
    if (!/^09\d{9}$/.test(phone)) {
      setStatus($('m360_mobile_status'), 'شماره موبایل معتبر وارد کنید.', 'error');
      return;
    }
    setButtonBusy(sendBtn, true);
    setStatus($('m360_mobile_status'), 'در حال ارسال کد تأیید...', 'pending');
    fetchJson('api/customer/send-otp.php', { phone: phone })
      .then(function (result) {
        if (result.data && result.data.ok) {
          setStatus($('m360_mobile_status'), result.data.message || 'کد تأیید ارسال شد.', 'ok');
          goToWizardStep('m360_step_otp');
          startResendCountdown();
        } else {
          setStatus($('m360_mobile_status'), (result.data && result.data.message) || 'ارسال کد تأیید انجام نشد. لطفاً دوباره تلاش کنید.', 'error');
        }
      })
      .catch(function () {
        setStatus($('m360_mobile_status'), 'ارسال کد تأیید انجام نشد. لطفاً دوباره تلاش کنید.', 'error');
      })
      .finally(function () { setButtonBusy(sendBtn, false); });
  }

  function loadProfileAndShowForm() {
    var mobile = $('mobile');
    var verifyBtn = $('m360_verify_otp');
    var phone = mobile ? mobile.value.trim() : '';
    setButtonBusy(verifyBtn, true);
    setStatus($('m360_otp_status'), 'در حال بارگذاری پروفایل...', 'pending');
    fetchJson('api/customer/profile-status.php', { mobile: phone })
      .then(function (result) {
        var payload = (result.data && result.data.data) ? result.data.data : (result.data || {});
        var exists = !!payload.customer_exists;
        var profile = payload.profile || {};
        var flow = $('customer_flow');
        var vname = $('verified_customer_name');
        if (flow) flow.value = exists ? 'returning' : 'new';
        if (vname) vname.value = profile.full_name || (payload.customer && payload.customer.full_name) || '';

        fillProfileForm(profile);
        renderVehiclePicker(payload.vehicles || [], payload.vehicles_out_of_scope || []);
        setNewCustomerRequired(!exists);
        setBothFlowRequired(true);
        setVehicleNewFieldsRequired(true);
        setSubmitEnabled(true);
        if (mobile) mobile.readOnly = true;

        clearAllStepErrors();
        setStatus($('m360_otp_status'), '', '');
        goToWizardStep('m360_section_profile');
      })
      .catch(function () {
        setStatus($('m360_otp_status'), 'بارگذاری پروفایل ناموفق بود. لطفاً اطلاعات را تکمیل کنید.', 'error');
        setNewCustomerRequired(true);
        setBothFlowRequired(true);
        setSubmitEnabled(true);
        goToWizardStep('m360_section_profile');
      })
      .finally(function () { setButtonBusy(verifyBtn, false); });
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
    setButtonBusy(verifyBtn, true);
    setStatus($('m360_otp_status'), 'در حال تأیید کد...', 'pending');
    fetchJson('api/customer/verify-otp.php', { phone: phone, otp: code })
      .then(function (result) {
        if (result.data && result.data.ok) {
          verified = true;
          if (!isExplicitNewRequestMode()) {
            var target = (result.data.data && result.data.data.redirect_url) ? result.data.data.redirect_url : profileRedirectUrl();
            window.location.href = target;
            return;
          }
          loadProfileAndShowForm();
        } else {
          verified = false;
          setStatus($('m360_otp_status'), (result.data && result.data.message) || 'تأیید شماره موبایل ناموفق بود.', 'error');
          setButtonBusy(verifyBtn, false);
        }
      })
      .catch(function () {
        verified = false;
        setStatus($('m360_otp_status'), 'تأیید شماره موبایل ناموفق بود. لطفاً دوباره تلاش کنید.', 'error');
      })
      .finally(function () {
        if (!verified) setButtonBusy(verifyBtn, false);
      });
  }

  function initWizardNav() {
    document.querySelectorAll('.m360-wizard-next').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var target = btn.getAttribute('data-target');
        if (target === 'm360_section_vehicle') syncFullNameHidden();
        if (target) goToWizardStep(target);
      });
    });
    document.querySelectorAll('.m360-wizard-prev').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var target = btn.getAttribute('data-target');
        if (target) goToWizardStep(target);
      });
    });
  }

  function initServerVisitCalendar() {
    var hidden = $('visit_date');
    var display = $('visit_date_display');
    var calendar = $('m360_server_calendar');
    if (!hidden || !display || !calendar) return;
    calendar.querySelectorAll('.m360-calendar-day').forEach(function (btn) {
      if (btn.disabled || btn.getAttribute('data-selectable') === '0') {
        btn.addEventListener('click', function (e) { e.preventDefault(); e.stopPropagation(); });
        return;
      }
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

  function updateVisitHint() {
    var type = $('request_type');
    var hint = $('visit_time_hint');
    if (!type) return;
    var isDiagnostic = type.value === 'diagnostic_inspection';
    if (hint) hint.style.display = isDiagnostic ? 'block' : 'none';
    document.querySelectorAll('[data-diagnostic-parity]').forEach(function (panel) {
      panel.hidden = !isDiagnostic;
      panel.querySelectorAll('[data-diagnostic-input]').forEach(function (input) {
        input.disabled = !isDiagnostic;
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
    var preview = $('plate_preview') || $('m360_rw_plate_preview');
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

  function bindTrunkOtherToggle() {
    var box = $('m360_rw_trunk_other_selected');
    var text = $('m360_rw_trunk_other_text');
    if (!box || !text) return;
    var sync = function () {
      text.hidden = !box.checked;
      if (!box.checked) text.value = '';
    };
    box.addEventListener('change', sync);
    sync();
  }

  function bindDamageSelector() {
    var selector = $('m360_rw_damage_selector');
    if (!selector) return;
    var statusLabels = { HEALTHY: 'سالم', MINOR: 'آسیب جزئی', SEVERE: 'آسیب شدید' };
    var rows = Array.prototype.slice.call(selector.querySelectorAll('.m360-rw-zone-row'));
    if (!rows.length) return;

    function rowLabel(row) {
      return row.getAttribute('data-label') || row.getAttribute('data-zone') || 'ناحیه';
    }

    function paintRow(row) {
      var status = row.getAttribute('data-status') || 'HEALTHY';
      var assessed = row.getAttribute('data-assessed') === '1';
      row.querySelectorAll('.m360-rw-zone-seg button[data-status]').forEach(function (btn) {
        var active = assessed && btn.getAttribute('data-status') === status;
        btn.classList.toggle('is-active', active);
        btn.setAttribute('aria-pressed', active ? 'true' : 'false');
      });
      row.setAttribute('aria-label', rowLabel(row) + '، وضعیت فعلی ' + (assessed ? (statusLabels[status] || status) : 'بررسی‌نشده'));
    }

    function setRowStatus(row, status, assessed) {
      var zone = row.getAttribute('data-zone') || '';
      row.setAttribute('data-status', status);
      row.setAttribute('data-assessed', assessed ? '1' : '0');
      var hidden = $('m360_rw_damage_zone_' + zone);
      if (hidden) hidden.value = status;
      paintRow(row);
    }

    function renderSummary() {
      var body = $('m360_rw_damage_summary_body');
      if (!body) return;
      var damaged = [];
      var assessedCount = 0;
      rows.forEach(function (row) {
        if (row.getAttribute('data-assessed') !== '1') return;
        assessedCount += 1;
        var status = row.getAttribute('data-status') || 'HEALTHY';
        if (status !== 'HEALTHY') damaged.push({ label: rowLabel(row), status: statusLabels[status] || status });
      });
      if (damaged.length) {
        body.innerHTML = '<ul>' + damaged.map(function (item) {
          return '<li>' + item.label + ' — ' + item.status + '</li>';
        }).join('') + '</ul>';
      } else if (assessedCount === rows.length) {
        body.innerHTML = '<p class="m360-rw-muted" style="margin:0">همه نواحی بررسی‌شده سالم ثبت شدند.</p>';
      } else {
        body.innerHTML = '<p class="m360-rw-muted" style="margin:0">هنوز آسیب ظاهری ثبت نشده است. نواحی را بررسی کنید یا «ثبت همه سالم» را بزنید.</p>';
      }
    }

    rows.forEach(paintRow);
    renderSummary();

    selector.addEventListener('click', function (e) {
      var target = e.target;
      var btn = target && target.closest ? target.closest('.m360-rw-zone-seg button[data-status]') : null;
      if (!btn) return;
      var row = btn.closest('.m360-rw-zone-row');
      if (!row) return;
      setRowStatus(row, btn.getAttribute('data-status'), true);
      renderSummary();
    });

    var allHealthy = $('m360_rw_damage_all_healthy');
    if (allHealthy) {
      allHealthy.addEventListener('click', function () {
        rows.forEach(function (row) { setRowStatus(row, 'HEALTHY', true); });
        renderSummary();
      });
    }

    var clearBtn = $('m360_rw_damage_clear');
    if (clearBtn) {
      clearBtn.addEventListener('click', function () {
        rows.forEach(function (row) { setRowStatus(row, 'HEALTHY', false); });
        renderSummary();
      });
    }
  }

  function allDamageZonesAssessed() {
    var selector = $('m360_rw_damage_selector');
    if (!selector) return true;
    var rows = Array.prototype.slice.call(selector.querySelectorAll('.m360-rw-zone-row'));
    return rows.length === 0 || rows.every(function (row) {
      return row.getAttribute('data-assessed') === '1';
    });
  }

  function allTrunkItemsChecked() {
    var rows = Array.prototype.slice.call(document.querySelectorAll('.m360-rw-trunk-row[data-trunk-key]'));
    return rows.length === 0 || rows.every(function (row) {
      var key = row.getAttribute('data-trunk-key') || '';
      return !!document.querySelector('input[name="trunk_' + key + '"]:checked');
    });
  }

  function initOtpFirstFlow() {
    var mobile = $('mobile');
    var sendBtn = $('m360_send_otp');
    var verifyBtn = $('m360_verify_otp');
    var resendBtn = $('m360_resend_otp');
    if (!sendBtn || !mobile) return;
    sendBtn.addEventListener('click', function (e) { e.preventDefault(); sendOtp(); });
    if (verifyBtn) verifyBtn.addEventListener('click', function (e) { e.preventDefault(); verifyOtp(); });
    if (resendBtn) resendBtn.addEventListener('click', function (e) { e.preventDefault(); sendOtp(); });
    mobile.addEventListener('input', resetOtpFlow);
    mobile.addEventListener('change', resetOtpFlow);
    bindPersianValidity(mobile, 'لطفاً شماره موبایل معتبر وارد کنید.');
  }

  function beginFreshOtpWizard() {
    hideAllWizardSections();
    showSection('m360_step_otp', false);
    goToWizardStep('m360_step_mobile');
    setSubmitEnabled(false);
    setNewCustomerRequired(false);
    setBothFlowRequired(false);
    setStatus($('m360_mobile_status'), 'برای شروع، شماره موبایل خود را وارد کنید.', '');
  }

  function beginStaffWalkinFlow() {
    verified = true;
    var flow = $('customer_flow');
    if (flow) flow.value = 'new';
    var mode = $('vehicle_mode');
    if (mode && mode.value === '') mode.value = 'new';
    setNewCustomerRequired(false);
    setBothFlowRequired(false);
    setVehicleNewFieldsRequired(false);
    setSubmitEnabled(true);
    showSection('m360_step_mobile', false);
    showSection('m360_step_otp', false);
    goToWizardStep('m360_section_customer_search');
  }

  function validateWizardBeforeSubmit() {
    var boot = window.m360CustomerPageBoot || {};
    syncFullNameHidden();
    if (boot.staffWalkinMode) {
      var mobile = $('mobile');
      var phone = mobile ? mobile.value.trim() : '';
      if (!/^09\d{9}$/.test(phone)) {
        showStepError('m360_section_profile', 'لطفاً شماره موبایل معتبر ۱۱ رقمی وارد کنید.');
        goToWizardStep('m360_section_profile');
        if (mobile) mobile.focus();
        return false;
      }
    }
    var flow = $('customer_flow');
    var isReturning = flow && flow.value === 'returning';
    var first = $('first_name');
    var last = $('last_name');
    if (!isReturning) {
      if (!first || first.value.trim() === '') {
        showStepError('m360_section_profile', 'لطفاً نام را وارد کنید.');
        goToWizardStep('m360_section_profile');
        if (first) first.focus();
        return false;
      }
      if (!last || last.value.trim() === '') {
        showStepError('m360_section_profile', 'لطفاً نام خانوادگی را وارد کنید.');
        goToWizardStep('m360_section_profile');
        if (last) last.focus();
        return false;
      }
    }
    var mode = $('vehicle_mode');
    var selected = $('selected_vehicle_id');
    if (mode && mode.value === 'existing') {
      if (!selected || selected.value === '') {
        showStepError('m360_section_vehicle', 'لطفاً یک خودرو تأییدشده انتخاب کنید یا خودرو جدید اضافه کنید.');
        goToWizardStep('m360_section_vehicle');
        return false;
      }
    } else {
      var brand = $('vehicle_brand');
      var vclass = $('vehicle_class');
      var year = $('vehicle_year_pair');
      var plate = $('plate_display');
      if (!brand || brand.value === '') {
        showStepError('m360_section_vehicle', 'لطفاً برند خودرو را از فهرست تأییدشده انتخاب کنید.');
        goToWizardStep('m360_section_vehicle');
        return false;
      }
      if (!vclass || vclass.value === '') {
        showStepError('m360_section_vehicle', 'لطفاً مدل خودرو را انتخاب کنید.');
        goToWizardStep('m360_section_vehicle');
        return false;
      }
      if (!year || year.value === '') {
        showStepError('m360_section_vehicle', 'لطفاً سال تولید خودرو را انتخاب کنید.');
        goToWizardStep('m360_section_vehicle');
        return false;
      }
      if (!plate || plate.value === '') {
        showStepError('m360_section_vehicle', 'لطفاً پلاک خودرو را کامل وارد کنید.');
        goToWizardStep('m360_section_vehicle');
        return false;
      }
    }
    if (boot.staffWalkinMode) {
      var odometer = $('odometer_km');
      var fuel = $('fuel_level');
      if (!odometer || odometer.value.trim() === '') {
        showStepError('m360_section_vehicle', 'لطفاً کیلومتر فعلی خودرو را وارد کنید.');
        goToWizardStep('m360_section_vehicle');
        if (odometer) odometer.focus();
        return false;
      }
      if (!fuel || fuel.value === '') {
        showStepError('m360_section_vehicle', 'لطفاً سطح بنزین را انتخاب کنید.');
        goToWizardStep('m360_section_vehicle');
        if (fuel) fuel.focus();
        return false;
      }
    }
    var reqType = $('request_type');
    var reqDesc = $('request_description');
    if (!reqType || reqType.value === '') {
      showStepError('m360_section_request', 'لطفاً نوع درخواست را انتخاب کنید.');
      goToWizardStep('m360_section_request');
      return false;
    }
    if (!reqDesc || reqDesc.value.trim() === '') {
      showStepError('m360_section_request', 'لطفاً شرح درخواست را وارد کنید.');
      goToWizardStep('m360_section_request');
      if (reqDesc) reqDesc.focus();
      return false;
    }
    var visitHidden = $('visit_date');
    if (!visitHidden || visitHidden.value === '') {
      showStepError('m360_section_request', 'لطفاً تاریخ مراجعه را از تقویم انتخاب کنید.');
      goToWizardStep('m360_section_request');
      return false;
    }
    if (boot.staffWalkinMode) {
      if (!allDamageZonesAssessed()) {
        showStepError('m360_section_condition', 'برای ذخیره پذیرش حضوری، وضعیت همه نواحی خودرو را مشخص کنید.');
        goToWizardStep('m360_section_condition');
        return false;
      }
      if (!allTrunkItemsChecked()) {
        showStepError('m360_section_checklist', 'برای ذخیره پذیرش حضوری، وضعیت همه متعلقات را مشخص کنید.');
        goToWizardStep('m360_section_checklist');
        return false;
      }
    }
    clearAllStepErrors();
    return true;
  }

  function restoreFormSectionsAfterPost() {
    var boot = window.m360CustomerPageBoot || {};
    if (boot.submitSuccess) return true;
    if (!boot.restoreForm) return false;

    if (!boot.mobileVerified) {
      if (boot.submitError) showStepError('m360_step_otp', boot.submitError);
      goToWizardStep('m360_step_otp');
      return true;
    }

    verified = true;
    var mobile = $('mobile');
    if (mobile) {
      mobile.value = boot.mobile || mobile.value;
      mobile.readOnly = true;
    }
    var flow = $('customer_flow');
    if (flow && boot.customerFlow) flow.value = boot.customerFlow;
    setBothFlowRequired(true);
    setSubmitEnabled(true);

    var step = boot.submitErrorStep || (boot.staffWalkinMode ? 'm360_section_submit' : 'm360_section_request');
    goToWizardStep(step);
    if (boot.submitError) {
      if (boot.submitErrorIsOtp) {
        showStepError('m360_step_otp', boot.submitError);
        goToWizardStep('m360_step_otp');
      } else {
        showStepError(step, boot.submitError);
      }
    }
    return true;
  }

  function bindNavLinkProtection() {
    document.querySelectorAll('.m360-public-nav__link, .m360-rw-back').forEach(function (link) {
      link.setAttribute('data-m360-nav-link', '1');
      link.setAttribute('data-m360-no-busy', '1');
      link.addEventListener('click', function () {
        clearAllBusyButtons();
      }, true);
    });
  }

  function clearPr02bStaleUiState() {
    clearAllBusyButtons();
    document.querySelectorAll('.m360-public-nav__link, .m360-rw-back').forEach(function (link) {
      link.classList.remove('m360-btn-is-loading');
      link.removeAttribute('aria-busy');
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    clearPr02bStaleUiState();
    bindNavLinkProtection();
    window.addEventListener('pageshow', clearPr02bStaleUiState);
    window.addEventListener('pageshow', clearAllBusyButtons);
    document.addEventListener('visibilitychange', function () {
      if (document.visibilityState === 'visible') clearPr02bStaleUiState();
    });

    var customerForm = document.querySelector('form.m360-customer-form');
    if (!customerForm) return;
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

    ['first_name', 'last_name'].forEach(function (id) {
      var el = $(id);
      if (el) el.addEventListener('input', syncFullNameHidden);
    });

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
      var el = $(id);
      if (el) el.addEventListener('change', buildPlateDisplay);
    });
    chainPlateFocus(plateDigitIds.concat(['plate_letter']));

    initServerVisitCalendar();
    bindTrunkOtherToggle();
    bindDamageSelector();
    initOtpFirstFlow();
    initWizardNav();
    initStaffCustomerSearch();
    var boot = window.m360CustomerPageBoot || {};
    if (boot.staffWalkinMode) {
      beginStaffWalkinFlow();
    } else if (boot.skipOtpToWizard && boot.verifiedSessionMobile) {
      verified = true;
      var mobileEl = $('mobile');
      if (mobileEl) {
        mobileEl.value = boot.verifiedSessionMobile;
        mobileEl.readOnly = true;
      }
      setSubmitEnabled(true);
      loadProfileAndShowForm();
    } else if (!restoreFormSectionsAfterPost()) {
      beginFreshOtpWizard();
    }

    var requestType = $('request_type');
    if (requestType) {
      requestType.addEventListener('change', updateVisitHint);
      updateVisitHint();
    }

    var form = customerForm;
    if (form) {
      form.addEventListener('submit', function (e) {
        buildPlateDisplay();
        if (!verified) {
          e.preventDefault();
          showStepError('m360_step_otp', 'برای ثبت درخواست، ابتدا شماره موبایل را با کد پیامکی تأیید کنید.');
          goToWizardStep('m360_step_otp');
          return;
        }
        if (!validateWizardBeforeSubmit()) {
          e.preventDefault();
          clearAllBusyButtons();
          return;
        }
        var submitBtn = $('m360_submit_btn');
        setButtonBusy(submitBtn, true);
        window.setTimeout(clearAllBusyButtons, 45000);
      });
    }
  });
})();
