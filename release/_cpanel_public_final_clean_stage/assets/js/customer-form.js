/**
 * MOGHARE360 — Customer request form interactions (server-rendered dates).
 */
(function () {
  'use strict';

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
      var el = document.getElementById(id);
      if (!el) return;
      el.addEventListener('change', function () {
        if (el.value !== '' && index < ids.length - 1) {
          var next = document.getElementById(ids[index + 1]);
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

  function initServerVisitCalendar() {
    var hidden = document.getElementById('visit_date');
    var display = document.getElementById('visit_date_display');
    var calendar = document.getElementById('m360_server_calendar');
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
    var y = document.getElementById('birth_year_jalali');
    var m = document.getElementById('birth_month_jalali');
    var d = document.getElementById('birth_day_jalali');
    var hidden = document.getElementById('birth_date');
    if (!y || !m || !d || !hidden) return;
    if (y.value && m.value && d.value) {
      var mm = String(m.value).padStart(2, '0');
      var dd = String(d.value).padStart(2, '0');
      hidden.value = y.value + '/' + mm + '/' + dd;
    } else {
      hidden.value = '';
    }
  }

  function updateVisitHint() {
    var type = document.getElementById('request_type');
    var hint = document.getElementById('visit_time_hint');
    if (!type || !hint) return;
    hint.style.display = (type.value === 'diagnostic_inspection') ? 'block' : 'none';
  }

  function buildPlateDisplay() {
    var d1 = document.getElementById('plate_first_digit_1');
    var d2 = document.getElementById('plate_first_digit_2');
    var letter = document.getElementById('plate_letter');
    var m1 = document.getElementById('plate_middle_digit_1');
    var m2 = document.getElementById('plate_middle_digit_2');
    var m3 = document.getElementById('plate_middle_digit_3');
    var r1 = document.getElementById('plate_region_digit_1');
    var r2 = document.getElementById('plate_region_digit_2');
    var left = document.getElementById('plate_left_2_digits');
    var mid = document.getElementById('plate_middle_3_digits');
    var region = document.getElementById('plate_region_2_digits');
    var hidden = document.getElementById('plate_display');
    var preview = document.getElementById('plate_preview');
    if (!d1 || !d2 || !letter || !m1 || !m2 || !m3 || !r1 || !r2 || !left || !mid || !region || !hidden) return;

    if (d1.value !== '' && d2.value !== '') {
      left.value = d1.value + d2.value;
    }
    if (m1.value !== '' && m2.value !== '' && m3.value !== '') {
      mid.value = m1.value + m2.value + m3.value;
    }
    if (r1.value !== '' && r2.value !== '') {
      region.value = r1.value + r2.value;
    }

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

  document.addEventListener('DOMContentLoaded', function () {
    var province = document.getElementById('province');
    var city = document.getElementById('city');
    if (province && city && window.m360PopulateProvinces) {
      m360PopulateProvinces(province);
      city.disabled = true;
      province.addEventListener('change', function () { m360PopulateCities(province, city); });
      bindPersianValidity(province, 'لطفاً استان را انتخاب کنید.');
      bindPersianValidity(city, 'لطفاً شهر را انتخاب کنید.');
    }

    var brand = document.getElementById('vehicle_brand');
    var vclass = document.getElementById('vehicle_class');
    if (brand && vclass && window.m360PopulateVehicleBrands) {
      m360PopulateVehicleBrands(brand);
      vclass.disabled = true;
      brand.addEventListener('change', function () { m360PopulateVehicleClasses(brand, vclass); });
      bindPersianValidity(brand, 'لطفاً برند خودرو را انتخاب کنید.');
      bindPersianValidity(vclass, 'لطفاً کلاس / مدل خودرو را انتخاب کنید.');
    }

    bindPersianValidity(document.getElementById('vehicle_year_pair'), 'لطفاً سال تولید خودرو را انتخاب کنید.');
    bindPersianValidity(document.getElementById('request_type'), 'لطفاً نوع درخواست را انتخاب کنید.');

    ['birth_year_jalali', 'birth_month_jalali', 'birth_day_jalali'].forEach(function (id) {
      var el = document.getElementById(id);
      if (el) el.addEventListener('change', syncBirthDateHidden);
    });
    syncBirthDateHidden();

    var plateIds = [
      'plate_first_digit_1', 'plate_first_digit_2', 'plate_letter',
      'plate_middle_digit_1', 'plate_middle_digit_2', 'plate_middle_digit_3',
      'plate_region_digit_1', 'plate_region_digit_2'
    ];
    plateIds.forEach(function (id) {
      populateDigitSelect(document.getElementById(id));
    });
    plateIds.forEach(function (id) {
      var labels = {
        plate_first_digit_1: 'رقم اول پلاک را انتخاب کنید.',
        plate_first_digit_2: 'رقم دوم پلاک را انتخاب کنید.',
        plate_letter: 'حرف پلاک را انتخاب کنید.',
        plate_middle_digit_1: 'رقم اول بخش سه‌رقمی را انتخاب کنید.',
        plate_middle_digit_2: 'رقم دوم بخش سه‌رقمی را انتخاب کنید.',
        plate_middle_digit_3: 'رقم سوم بخش سه‌رقمی را انتخاب کنید.',
        plate_region_digit_1: 'رقم اول کد ایران را انتخاب کنید.',
        plate_region_digit_2: 'رقم دوم کد ایران را انتخاب کنید.'
      };
      bindPersianValidity(document.getElementById(id), labels[id]);
      var el = document.getElementById(id);
      if (el) el.addEventListener('change', buildPlateDisplay);
    });
    chainPlateFocus(plateIds);

    initServerVisitCalendar();

    var requestType = document.getElementById('request_type');
    if (requestType) {
      requestType.addEventListener('change', updateVisitHint);
      updateVisitHint();
    }

    var form = document.querySelector('form.m360-customer-form');
    if (form) {
      form.addEventListener('submit', function (e) {
        buildPlateDisplay();
        syncBirthDateHidden();

        var visitHidden = document.getElementById('visit_date');
        var visitDisplay = document.getElementById('visit_date_display');
        if (!visitHidden || visitHidden.value === '') {
          e.preventDefault();
          if (visitDisplay) {
            visitDisplay.value = '';
            visitDisplay.placeholder = 'لطفاً تاریخ مراجعه را از تقویم انتخاب کنید.';
            visitDisplay.classList.add('m360-date-display--error');
          }
        }
      });
    }
  });
})();
