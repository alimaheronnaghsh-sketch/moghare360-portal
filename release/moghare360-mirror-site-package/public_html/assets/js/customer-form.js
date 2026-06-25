/**
 * MOGHARE360 — Customer request form interactions.
 */
(function () {
  'use strict';

  function toJalali(gy, gm, gd) {
    var gdm = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    var jy, jm, jd, gy2, days;
    gy2 = (gm > 2) ? (gy + 1) : gy;
    days = 355666 + (365 * gy) + Math.floor((gy2 + 3) / 4) - Math.floor((gy2 + 99) / 100) + Math.floor((gy2 + 399) / 400) + gd + gdm[gm - 1];
    jy = -1595 + (33 * Math.floor(days / 12053));
    days %= 12053;
    jy += 4 * Math.floor(days / 1461);
    days %= 1461;
    if (days > 365) {
      jy += Math.floor((days - 1) / 365);
      days = (days - 1) % 365;
    }
    if (days < 186) {
      jm = 1 + Math.floor(days / 31);
      jd = 1 + (days % 31);
    } else {
      jm = 7 + Math.floor((days - 186) / 30);
      jd = 1 + ((days - 186) % 30);
    }
    return { jy: jy, jm: jm, jd: jd };
  }

  function pad2(n) {
    return n < 10 ? '0' + n : String(n);
  }

  function populateYearPairs(select) {
    if (!select) return;
    var now = new Date();
    var j = toJalali(now.getFullYear(), now.getMonth() + 1, now.getDate());
    select.innerHTML = '<option value="">انتخاب سال</option>';
    for (var i = 0; i < 20; i++) {
      var jy = j.jy - i;
      var gy = now.getFullYear() - i;
      var opt = document.createElement('option');
      opt.value = jy + ' - ' + gy;
      opt.textContent = jy + ' - ' + gy;
      select.appendChild(opt);
    }
  }

  function populateNumericSelect(select, from, to, placeholder) {
    if (!select) return;
    select.innerHTML = '<option value="">' + placeholder + '</option>';
    for (var n = from; n <= to; n++) {
      var opt = document.createElement('option');
      opt.value = String(n);
      opt.textContent = String(n);
      select.appendChild(opt);
    }
  }

  function initJalaliPicker() {
    var hidden = document.getElementById('visit_date');
    var display = document.getElementById('visit_date_display');
    var grid = document.getElementById('visit_date_grid');
    if (!hidden || !display || !grid) return;

    var now = new Date();
    var view = toJalali(now.getFullYear(), now.getMonth() + 1, now.getDate());
    var monthNames = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];

    function render() {
      grid.innerHTML = '';
      var title = document.createElement('div');
      title.className = 'm360-cal-title';
      title.textContent = monthNames[view.jm - 1] + ' ' + view.jy;
      grid.appendChild(title);

      var daysInMonth = (view.jm <= 6) ? 31 : (view.jm <= 11 ? 30 : 29);
      for (var d = 1; d <= daysInMonth; d++) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'm360-cal-day';
        btn.textContent = String(d);
        (function (day) {
          btn.addEventListener('click', function () {
            hidden.value = view.jy + '/' + pad2(view.jm) + '/' + pad2(day);
            display.textContent = 'تاریخ انتخاب‌شده: ' + hidden.value;
            grid.querySelectorAll('.m360-cal-day').forEach(function (el) { el.classList.remove('selected'); });
            btn.classList.add('selected');
          });
        })(d);
        grid.appendChild(btn);
      }
    }

    render();
  }

  function updateVisitHint() {
    var type = document.getElementById('request_type');
    var hint = document.getElementById('visit_time_hint');
    if (!type || !hint) return;
    hint.style.display = (type.value === 'diagnostic_inspection') ? 'block' : 'none';
  }

  function buildPlateDisplay() {
    var l = document.getElementById('plate_left_2_digits');
    var letter = document.getElementById('plate_letter');
    var mid = document.getElementById('plate_middle_3_digits');
    var region = document.getElementById('plate_region_2_digits');
    var hidden = document.getElementById('plate_display');
    if (!l || !letter || !mid || !region || !hidden) return;
    if (l.value && letter.value && mid.value && region.value) {
      hidden.value = l.value + ' ' + letter.value + ' ' + mid.value + ' ایران ' + region.value;
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    var province = document.getElementById('province');
    var city = document.getElementById('city');
    if (province && city && window.m360PopulateProvinces) {
      m360PopulateProvinces(province);
      city.disabled = true;
      province.addEventListener('change', function () { m360PopulateCities(province, city); });
    }

    var brand = document.getElementById('vehicle_brand');
    var vclass = document.getElementById('vehicle_class');
    if (brand && vclass && window.m360PopulateVehicleBrands) {
      m360PopulateVehicleBrands(brand);
      vclass.disabled = true;
      brand.addEventListener('change', function () { m360PopulateVehicleClasses(brand, vclass); });
    }

    populateYearPairs(document.getElementById('vehicle_year_pair'));
    populateNumericSelect(document.getElementById('plate_left_2_digits'), 10, 99, 'دو رقم');
    populateNumericSelect(document.getElementById('plate_region_2_digits'), 10, 99, 'ایران');
    populateNumericSelect(document.getElementById('plate_middle_3_digits'), 100, 999, 'سه رقم');

    initJalaliPicker();

    var requestType = document.getElementById('request_type');
    if (requestType) {
      requestType.addEventListener('change', updateVisitHint);
      updateVisitHint();
    }

    ['plate_left_2_digits', 'plate_letter', 'plate_middle_3_digits', 'plate_region_2_digits'].forEach(function (id) {
      var el = document.getElementById(id);
      if (el) el.addEventListener('change', buildPlateDisplay);
    });

    var form = document.querySelector('form.m360-customer-form');
    if (form) {
      form.addEventListener('submit', function () {
        buildPlateDisplay();
      });
    }
  });
})();
