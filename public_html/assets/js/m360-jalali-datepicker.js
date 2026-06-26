/**
 * MOGHARE360 — Inline Jalali calendar (customer visit date).
 * Renders immediately on DOMContentLoaded — no popup, always visible.
 */
(function (global) {
  'use strict';

  var monthNames = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
  var weekdayNames = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];

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

  function formatDisplay(jy, jm, jd) {
    return jy + '/' + pad2(jm) + '/' + pad2(jd) + ' — ' + weekdayNames[irWeekday(jy, jm, jd)];
  }

  function setDisplayValue(displayEl, text, filled) {
    if (!displayEl) return;
    if (displayEl.tagName === 'INPUT' || displayEl.tagName === 'TEXTAREA') {
      displayEl.value = text;
    } else {
      displayEl.textContent = text;
    }
    if (filled) {
      displayEl.classList.add('m360-jalali-datepicker__selected--filled');
    } else {
      displayEl.classList.remove('m360-jalali-datepicker__selected--filled');
    }
  }

  function ensureCalendarShell(container) {
    container.innerHTML = '';
    container.classList.add('m360-jalali-calendar-inline--ready');

    var toolbar = document.createElement('div');
    toolbar.className = 'm360-cal-toolbar';
    toolbar.setAttribute('aria-label', 'ناوبری تقویم');

    var weekdays = document.createElement('div');
    weekdays.className = 'm360-cal-weekdays';
    weekdays.setAttribute('aria-hidden', 'true');

    var grid = document.createElement('div');
    grid.className = 'm360-cal-grid';
    grid.setAttribute('role', 'grid');
    grid.setAttribute('aria-label', 'روزهای ماه');

    container.appendChild(toolbar);
    container.appendChild(weekdays);
    container.appendChild(grid);

    return { toolbar: toolbar, weekdays: weekdays, grid: grid };
  }

  function initInlineJalaliCalendar(options) {
    options = options || {};
    var container = document.getElementById(options.containerId || 'm360_jalali_calendar');
    var hidden = document.getElementById(options.hiddenId || 'visit_date');
    var display = document.getElementById(options.displayId || 'visit_date_display');

    if (!container) {
      console.error('[MOGHARE360] Jalali calendar container #m360_jalali_calendar not found.');
      return null;
    }
    if (!hidden) {
      console.error('[MOGHARE360] Hidden visit_date input not found.');
      return null;
    }
    if (!display) {
      console.error('[MOGHARE360] visit_date_display field not found.');
      return null;
    }

    var parts = ensureCalendarShell(container);
    var toolbar = parts.toolbar;
    var weekdays = parts.weekdays;
    var grid = parts.grid;

    var now = new Date();
    var today = toJalali(now.getFullYear(), now.getMonth() + 1, now.getDate());
    var todayKey = dateKey(today.jy, today.jm, today.jd);
    var maxAhead = typeof options.maxDaysAhead === 'number' ? options.maxDaysAhead : 90;
    var maxDate = addJalaliDays(today.jy, today.jm, today.jd, maxAhead);
    var maxKey = dateKey(maxDate.jy, maxDate.jm, maxDate.jd);
    var view = { jy: today.jy, jm: today.jm };

    weekdays.innerHTML = '';
    weekdayNames.forEach(function (name) {
      var cell = document.createElement('div');
      cell.className = 'm360-cal-weekday';
      cell.textContent = name;
      weekdays.appendChild(cell);
    });

    function isPast(jy, jm, jd) {
      return dateKey(jy, jm, jd) < todayKey;
    }

    function isSelectable(jy, jm, jd) {
      var key = dateKey(jy, jm, jd);
      return key >= todayKey && key <= maxKey;
    }

    function isToday(jy, jm, jd) {
      return dateKey(jy, jm, jd) === todayKey;
    }

    function updateDisplayText() {
      if (!hidden.value) {
        setDisplayValue(display, '', false);
        return;
      }
      var p = hidden.value.split('/');
      if (p.length === 3) {
        setDisplayValue(
          display,
          formatDisplay(parseInt(p[0], 10), parseInt(p[1], 10), parseInt(p[2], 10)),
          true
        );
      }
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
      prevYear.className = 'm360-cal-nav';
      prevYear.textContent = '«';
      prevYear.setAttribute('aria-label', 'سال قبل');
      prevYear.addEventListener('click', function () { shiftYear(-1); });

      var prevMonth = document.createElement('button');
      prevMonth.type = 'button';
      prevMonth.className = 'm360-cal-nav';
      prevMonth.textContent = '‹';
      prevMonth.setAttribute('aria-label', 'ماه قبل');
      prevMonth.addEventListener('click', function () { shiftMonth(-1); });

      var title = document.createElement('span');
      title.className = 'm360-cal-title-label';
      title.textContent = monthNames[view.jm - 1] + ' ' + view.jy;

      var nextMonth = document.createElement('button');
      nextMonth.type = 'button';
      nextMonth.className = 'm360-cal-nav';
      nextMonth.textContent = '›';
      nextMonth.setAttribute('aria-label', 'ماه بعد');
      nextMonth.addEventListener('click', function () { shiftMonth(1); });

      var nextYear = document.createElement('button');
      nextYear.type = 'button';
      nextYear.className = 'm360-cal-nav';
      nextYear.textContent = '»';
      nextYear.setAttribute('aria-label', 'سال بعد');
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
        if (isPast(cellJy, cellJm, dayNum)) {
          btn.classList.add('m360-cal-day--past');
          btn.disabled = true;
        } else if (isToday(cellJy, cellJm, dayNum)) {
          btn.classList.add('m360-cal-day--today');
        }

        var selectable = isSelectable(cellJy, cellJm, dayNum);
        if (selectable) {
          btn.classList.add('available');
        } else if (!isPast(cellJy, cellJm, dayNum)) {
          btn.classList.add('m360-cal-day--future-disabled');
          btn.disabled = true;
        }

        var selectedParts = hidden.value ? hidden.value.split('/') : [];
        if (selectedParts.length === 3 &&
            parseInt(selectedParts[0], 10) === cellJy &&
            parseInt(selectedParts[1], 10) === cellJm &&
            parseInt(selectedParts[2], 10) === dayNum) {
          btn.classList.add('selected');
        }

        if (selectable) {
          (function (jy, jm, jd, button) {
            button.addEventListener('click', function () {
              hidden.value = jy + '/' + pad2(jm) + '/' + pad2(jd);
              updateDisplayText();
              grid.querySelectorAll('.m360-cal-day').forEach(function (el) {
                el.classList.remove('selected');
              });
              button.classList.add('selected');
            });
          })(cellJy, cellJm, dayNum, btn);
        }

        grid.appendChild(btn);
      }
    }

    function validateSelected() {
      if (!hidden.value) {
        return false;
      }
      var p = hidden.value.split('/');
      if (p.length !== 3) {
        return false;
      }
      return isSelectable(parseInt(p[0], 10), parseInt(p[1], 10), parseInt(p[2], 10));
    }

    if (hidden.value && !validateSelected()) {
      hidden.value = '';
    }

    render();
    updateDisplayText();

    var api = {
      validate: validateSelected,
      refresh: render
    };

    global.m360VisitPicker = api;
    return api;
  }

  function boot() {
    if (!document.getElementById('m360_jalali_calendar')) {
      return;
    }
    initInlineJalaliCalendar({ maxDaysAhead: 90 });
  }

  global.m360Jalali = {
    toJalali: toJalali,
    addJalaliDays: addJalaliDays,
    dateKey: dateKey,
    initInlineJalaliCalendar: initInlineJalaliCalendar,
    initJalaliDatepicker: initInlineJalaliCalendar
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})(window);
