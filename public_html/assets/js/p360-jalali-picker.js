(function () {
  'use strict';
  var MONTHS = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
  var DOW = ['ش','ی','د','س','چ','پ','ج'];

  function g2j(gy, gm, gd) {
    var g_d_m = [0,31,59,90,120,151,181,212,243,273,304,334];
    var gy2 = (gm > 2) ? (gy + 1) : gy;
    var days = 355666 + (365 * gy) + Math.floor((gy2 + 3) / 4) - Math.floor((gy2 + 99) / 100) + Math.floor((gy2 + 399) / 400) + gd + g_d_m[gm - 1];
    var jy = -1595 + (33 * Math.floor(days / 12053));
    days %= 12053;
    jy += 4 * Math.floor(days / 1461);
    days %= 1461;
    if (days > 365) { jy += Math.floor((days - 1) / 365); days = (days - 1) % 365; }
    var jm = (days < 186) ? (1 + Math.floor(days / 31)) : (7 + Math.floor((days - 186) / 30));
    var jd = 1 + ((days < 186) ? (days % 31) : ((days - 186) % 30));
    return [jy, jm, jd];
  }
  function j2g(jy, jm, jd) {
    jy += 1595;
    var days = -355668 + (365 * jy) + Math.floor(jy / 33) * 8 + Math.floor(((jy % 33) + 3) / 4) + jd + ((jm < 7) ? ((jm - 1) * 31) : (((jm - 7) * 30) + 186));
    var gy = 400 * Math.floor(days / 146097);
    days %= 146097;
    if (days > 36524) {
      gy += 100 * Math.floor(--days / 36524);
      days %= 36524;
      if (days >= 365) days++;
    }
    gy += 4 * Math.floor(days / 1461);
    days %= 1461;
    if (days > 365) { gy += Math.floor((days - 1) / 365); days = (days - 1) % 365; }
    var gd = days + 1;
    var leap = ((gy % 4 === 0 && gy % 100 !== 0) || (gy % 400 === 0));
    var sal = [0,31,leap?29:28,31,30,31,30,31,31,30,31,30,31];
    var gm = 1;
    for (; gm <= 12 && gd > sal[gm]; gm++) gd -= sal[gm];
    return [gy, gm, gd];
  }
  function isLeap(jy) {
    var a = ((jy - 474) % 2820 + 2820) % 2820 + 474;
    return ((((a + 38) * 682) % 2816) < 682);
  }
  function monthDays(jy, jm) {
    if (jm <= 6) return 31;
    if (jm <= 11) return 30;
    return isLeap(jy) ? 30 : 29;
  }
  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function fmtJ(jy, jm, jd) { return jy + '/' + pad(jm) + '/' + pad(jd); }
  function fmtG(gy, gm, gd) { return gy + '-' + pad(gm) + '-' + pad(gd); }

  var openPop = null;

  function closePop() {
    if (openPop && openPop.parentNode) openPop.parentNode.removeChild(openPop);
    openPop = null;
  }

  function parseSql(sql) {
    var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(sql || '');
    if (!m) return null;
    return g2j(+m[1], +m[2], +m[3]);
  }

  function render(wrap) {
    var display = wrap.querySelector('[data-jdate-display]');
    var hidden = wrap.querySelector('[data-jdate-sql]');
    var cur = parseSql(hidden.value) || g2j(new Date().getFullYear(), new Date().getMonth() + 1, new Date().getDate());
    var jy = cur[0], jm = cur[1];

    closePop();
    var pop = document.createElement('div');
    pop.className = 'p360-jdate-pop';
    pop.setAttribute('dir', 'rtl');

    function draw() {
      var days = monthDays(jy, jm);
      var firstG = j2g(jy, jm, 1);
      var first = new Date(firstG[0], firstG[1] - 1, firstG[2]);
      // JS getDay: 0=Sun ... convert to Sat-start: (getDay+1)%7
      var start = (first.getDay() + 1) % 7;
      var html = '<div class="p360-jdate-head">'
        + '<button type="button" data-prev>‹</button>'
        + '<strong>' + MONTHS[jm - 1] + ' ' + jy + '</strong>'
        + '<button type="button" data-next>›</button></div>';
      html += '<div class="p360-jdate-years"><select data-year>';
      for (var y = jy - 80; y <= jy + 20; y++) {
        html += '<option value="' + y + '"' + (y === jy ? ' selected' : '') + '>' + y + '</option>';
      }
      html += '</select><select data-month>';
      for (var mi = 1; mi <= 12; mi++) {
        html += '<option value="' + mi + '"' + (mi === jm ? ' selected' : '') + '>' + MONTHS[mi - 1] + '</option>';
      }
      html += '</select></div>';
      html += '<div class="p360-jdate-grid">';
      for (var d = 0; d < 7; d++) html += '<button type="button" class="dow" tabindex="-1">' + DOW[d] + '</button>';
      for (var e = 0; e < start; e++) html += '<button type="button" class="empty" tabindex="-1">&nbsp;</button>';
      var sel = parseSql(hidden.value);
      for (var day = 1; day <= days; day++) {
        var cls = (sel && sel[0] === jy && sel[1] === jm && sel[2] === day) ? ' sel' : '';
        html += '<button type="button" data-day="' + day + '" class="' + cls + '">' + day + '</button>';
      }
      html += '</div>';
      pop.innerHTML = html;
      pop.querySelector('[data-prev]').onclick = function () {
        jm--; if (jm < 1) { jm = 12; jy--; } draw();
      };
      pop.querySelector('[data-next]').onclick = function () {
        jm++; if (jm > 12) { jm = 1; jy++; } draw();
      };
      pop.querySelector('[data-year]').onchange = function () { jy = +this.value; draw(); };
      pop.querySelector('[data-month]').onchange = function () { jm = +this.value; draw(); };
      pop.querySelectorAll('[data-day]').forEach(function (btn) {
        btn.onclick = function () {
          var jd = +btn.getAttribute('data-day');
          var g = j2g(jy, jm, jd);
          hidden.value = fmtG(g[0], g[1], g[2]);
          display.value = fmtJ(jy, jm, jd);
          wrap.dispatchEvent(new CustomEvent('p360-jdate-change', { detail: { sql: hidden.value, jalali: display.value } }));
          closePop();
        };
      });
    }
    draw();
    wrap.style.position = 'relative';
    wrap.appendChild(pop);
    openPop = pop;
  }

  function bind(wrap) {
    var openBtn = wrap.querySelector('[data-jdate-open]');
    var clearBtn = wrap.querySelector('[data-jdate-clear]');
    var display = wrap.querySelector('[data-jdate-display]');
    var hidden = wrap.querySelector('[data-jdate-sql]');
    if (display) {
      display.addEventListener('keydown', function (e) { e.preventDefault(); });
      display.addEventListener('paste', function (e) { e.preventDefault(); });
      display.addEventListener('beforeinput', function (e) { e.preventDefault(); });
    }
    if (openBtn) openBtn.addEventListener('click', function (e) { e.preventDefault(); render(wrap); });
    if (display) display.addEventListener('click', function () { if (!display.disabled) render(wrap); });
    if (clearBtn) clearBtn.addEventListener('click', function (e) {
      e.preventDefault();
      hidden.value = '';
      display.value = '';
      wrap.dispatchEvent(new CustomEvent('p360-jdate-change', { detail: { sql: '', jalali: '' } }));
      closePop();
    });
  }

  function init() {
    document.querySelectorAll('[data-p360-jdate]').forEach(bind);
    document.addEventListener('click', function (e) {
      if (openPop && !e.target.closest('[data-p360-jdate]')) closePop();
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
  window.P360JalaliPicker = { init: init, monthDays: monthDays, j2g: j2g, g2j: g2j };
})();
