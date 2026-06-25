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

  function jalaliToGregorian(jy, jm, jd) {
    jy = parseInt(jy, 10);
    jm = parseInt(jm, 10);
    jd = parseInt(jd, 10);
    var jy2 = jy + 1595;
    var days = -355668 + (365 * jy2) + Math.floor(jy2 / 33) * 8 + Math.floor(((jy2 % 33) + 3) / 4) + jd + (jm < 7 ? (jm - 1) * 31 : (jm - 7) * 30 + 186);
    var gy = 400 * Math.floor(days / 146097);
    days %= 146097;
    if (days > 36524) {
      gy += 100 * Math.floor(--days / 36524);
      days %= 36524;
      if (days >= 365) {
        days++;
      }
    }
    gy += 4 * Math.floor(days / 1461);
    days %= 1461;
    if (days > 365) {
      gy += Math.floor((days - 1) / 365);
      days = (days - 1) % 365;
    }
    var gd = days + 1;
    var salA = [0, 31, ((gy % 4 === 0 && gy % 100 !== 0) || (gy % 400 === 0)) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    var gm = 0;
    for (gm = 1; gm <= 12 && gd > salA[gm]; gm++) {
      gd -= salA[gm];
    }
    return { gy: gy, gm: gm, gd: gd };
  }

  function pad2(n) {
    return n < 10 ? '0' + n : String(n);
  }

  function dateKey(jy, jm, jd) {
    return jy * 10000 + jm * 100 + jd;
  }

  function addJalaliDays(jy, jm, jd, days) {
    var g = jalaliToGregorian(jy, jm, jd);
    var dt = new Date(g.gy, g.gm - 1, g.gd);
    dt.setDate(dt.getDate() + days);
    return toJalali(dt.getFullYear(), dt.getMonth() + 1, dt.getDate());
  }

  function jalaliMonthLength(jy, jm) {
    if (jm <= 6) return 31;
    if (jm <= 11) return 30;
    var r = jy % 33;
    var leap = (r === 1 || r === 5 || r === 9 || r === 13 || r === 17 || r === 22 || r === 26 || r === 30);
    return leap ? 30 : 29;
  }

  function irWeekday(jy, jm, jd) {
    var g = jalaliToGregorian(jy, jm, jd);
    var d = new Date(g.gy, g.gm - 1, g.gd).getDay();
    return (d + 1) % 7;
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

  function populateDigitSelect(select) {
    if (!select) return;
    var current = select.value;
    select.innerHTML = '<option value="">-</option>';
    for (var n = 1; n <= 9; n++) {
      var opt = document.createElement('option');
      opt.value = String(n);
      opt.textContent = String(n);
      select.appendChild(opt);
    }
    if (current) select.value = current;
  }

  function initJalaliPicker() {
    var hidden = document.getElementById('visit_date');
    var display = document.getElementById('visit_date_display');
    var grid = document.getElementById('visit_date_grid');
    var toolbar = document.getElementById('visit_cal_toolbar');
    var weekdays = document.getElementById('visit_cal_weekdays');
    if (!hidden || !display || !grid || !toolbar || !weekdays) return;

    var now = new Date();
    var today = toJalali(now.getFullYear(), now.getMonth() + 1, now.getDate());
    var minDate = addJalaliDays(today.jy, today.jm, today.jd, 1);
    var maxDate = addJalaliDays(today.jy, today.jm, today.jd, 7);
    var minKey = dateKey(minDate.jy, minDate.jm, minDate.jd);
    var maxKey = dateKey(maxDate.jy, maxDate.jm, maxDate.jd);
    var view = { jy: today.jy, jm: today.jm };
    var monthNames = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
    var weekdayNames = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];

    weekdays.innerHTML = '';
    weekdayNames.forEach(function (name) {
      var cell = document.createElement('div');
      cell.textContent = name;
      weekdays.appendChild(cell);
    });

    function isSelectable(jy, jm, jd) {
      var key = dateKey(jy, jm, jd);
      return key >= minKey && key <= maxKey;
    }

    function shiftMonth(delta) {
      view.jm += delta;
      if (view.jm > 12) {
        view.jm = 1;
        view.jy++;
      } else if (view.jm < 1) {
        view.jm = 12;
        view.jy--;
      }
      render();
    }

    function shiftYear(delta) {
      view.jy += delta;
      render();
    }

    function renderToolbar() {
      toolbar.innerHTML = '';
      var prevYear = document.createElement('button');
      prevYear.type = 'button';
      prevYear.textContent = '« سال قبل';
      prevYear.addEventListener('click', function () { shiftYear(-1); });

      var prevMonth = document.createElement('button');
      prevMonth.type = 'button';
      prevMonth.textContent = '‹ ماه قبل';
      prevMonth.addEventListener('click', function () { shiftMonth(-1); });

      var title = document.createElement('span');
      title.textContent = monthNames[view.jm - 1] + ' ' + view.jy;

      var nextMonth = document.createElement('button');
      nextMonth.type = 'button';
      nextMonth.textContent = 'ماه بعد ›';
      nextMonth.addEventListener('click', function () { shiftMonth(1); });

      var nextYear = document.createElement('button');
      nextYear.type = 'button';
      nextYear.textContent = 'سال بعد »';
      nextYear.addEventListener('click', function () { shiftYear(1); });

      toolbar.appendChild(prevYear);
      toolbar.appendChild(prevMonth);
      toolbar.appendChild(title);
      toolbar.appendChild(nextMonth);
      toolbar.appendChild(nextYear);
    }

    function render() {
      renderToolbar();
      grid.innerHTML = '';

      var daysInMonth = jalaliMonthLength(view.jy, view.jm);
      var startOffset = irWeekday(view.jy, view.jm, 1);
      var prevMonth = view.jm === 1 ? 12 : view.jm - 1;
      var prevYear = view.jm === 1 ? view.jy - 1 : view.jy;
      var prevDays = jalaliMonthLength(prevYear, prevMonth);

      var totalCells = Math.ceil((startOffset + daysInMonth) / 7) * 7;
      for (var cell = 0; cell < totalCells; cell++) {
        var dayNum;
        var cellJy = view.jy;
        var cellJm = view.jm;
        var outside = false;

        if (cell < startOffset) {
          dayNum = prevDays - startOffset + cell + 1;
          cellJm = prevMonth;
          cellJy = prevYear;
          outside = true;
        } else if (cell >= startOffset + daysInMonth) {
          dayNum = cell - startOffset - daysInMonth + 1;
          cellJm = view.jm === 12 ? 1 : view.jm + 1;
          cellJy = view.jm === 12 ? view.jy + 1 : view.jy;
          outside = true;
        } else {
          dayNum = cell - startOffset + 1;
        }

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'm360-cal-day';
        btn.textContent = String(dayNum);
        if (outside) {
          btn.classList.add('outside-month');
        }

        var selectable = isSelectable(cellJy, cellJm, dayNum);
        if (selectable) {
          btn.classList.add('available');
        } else {
          btn.disabled = true;
        }

        var selectedVal = hidden.value;
        if (selectedVal) {
          var parts = selectedVal.split('/');
          if (parts.length === 3 && parseInt(parts[0], 10) === cellJy && parseInt(parts[1], 10) === cellJm && parseInt(parts[2], 10) === dayNum) {
            btn.classList.add('selected');
          }
        }

        if (selectable) {
          (function (jy, jm, jd, button) {
            button.addEventListener('click', function () {
              hidden.value = jy + '/' + pad2(jm) + '/' + pad2(jd);
              display.textContent = 'تاریخ انتخاب‌شده: ' + hidden.value;
              grid.querySelectorAll('.m360-cal-day').forEach(function (el) { el.classList.remove('selected'); });
              button.classList.add('selected');
            });
          })(cellJy, cellJm, dayNum, btn);
        }

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
    if (!d1 || !d2 || !letter || !m1 || !m2 || !m3 || !r1 || !r2 || !left || !mid || !region || !hidden) return;

    if (d1.value && d2.value) {
      left.value = d1.value + d2.value;
    }
    if (m1.value && m2.value && m3.value) {
      mid.value = m1.value + m2.value + m3.value;
    }
    if (r1.value && r2.value) {
      region.value = r1.value + r2.value;
    }

    if (left.value && letter.value && mid.value && region.value) {
      hidden.value = left.value + ' ' + letter.value + ' ' + mid.value + ' ایران ' + region.value;
    }
  }

  function validateVisitDate() {
    var hidden = document.getElementById('visit_date');
    if (!hidden || hidden.value === '') return false;
    var now = new Date();
    var today = toJalali(now.getFullYear(), now.getMonth() + 1, now.getDate());
    var minDate = addJalaliDays(today.jy, today.jm, today.jd, 1);
    var maxDate = addJalaliDays(today.jy, today.jm, today.jd, 7);
    var minKey = dateKey(minDate.jy, minDate.jm, minDate.jd);
    var maxKey = dateKey(maxDate.jy, maxDate.jm, maxDate.jd);
    var parts = hidden.value.split('/');
    if (parts.length !== 3) return false;
    var key = dateKey(parseInt(parts[0], 10), parseInt(parts[1], 10), parseInt(parts[2], 10));
    return key >= minKey && key <= maxKey;
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

    [
      'plate_first_digit_1', 'plate_first_digit_2',
      'plate_middle_digit_1', 'plate_middle_digit_2', 'plate_middle_digit_3',
      'plate_region_digit_1', 'plate_region_digit_2'
    ].forEach(function (id) {
      populateDigitSelect(document.getElementById(id));
    });

    initJalaliPicker();

    var requestType = document.getElementById('request_type');
    if (requestType) {
      requestType.addEventListener('change', updateVisitHint);
      updateVisitHint();
    }

    [
      'plate_first_digit_1', 'plate_first_digit_2', 'plate_letter',
      'plate_middle_digit_1', 'plate_middle_digit_2', 'plate_middle_digit_3',
      'plate_region_digit_1', 'plate_region_digit_2'
    ].forEach(function (id) {
      var el = document.getElementById(id);
      if (el) el.addEventListener('change', buildPlateDisplay);
    });

    var form = document.querySelector('form.m360-customer-form');
    if (form) {
      form.addEventListener('submit', function (e) {
        buildPlateDisplay();
        if (!validateVisitDate()) {
          e.preventDefault();
          var display = document.getElementById('visit_date_display');
          if (display) {
            display.textContent = 'لطفاً یک تاریخ مجاز از تقویم انتخاب کنید (فردا تا ۷ روز آینده).';
          }
        }
      });
    }
  });
})();
