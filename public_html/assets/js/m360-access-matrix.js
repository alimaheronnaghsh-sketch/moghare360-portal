(function () {
  'use strict';

  var state = {
    module: 'hr_self',
    personnel: [],
    permissions: [],
    recommendations: {},
    packages: [],
    selectedUserId: 0,
    dirty: [],
    drawerDirty: false,
    lastFocusEl: null,
    listenersBound: false,
    recFilter: '',
    pkgFilter: ''
  };

  function $(sel) { return document.querySelector(sel); }
  function csrf() { return ($('#amCsrf') || {}).value || ''; }

  function showUnsaved() {
    var el = $('#amUnsaved');
    if (!el) return;
    if (state.dirty.length) el.classList.add('show'); else el.classList.remove('show');
  }

  function sourceBadge(src, isBase) {
    if (isBase) return '<span class="m360-am-badge base">پایه</span>';
    if (src === 'role') return '<span class="m360-am-badge role">نقش</span>';
    if (src === 'direct_allow') return '<span class="m360-am-badge allow">مستقیم</span>';
    if (src === 'direct_deny') return '<span class="m360-am-badge deny">ممنوع</span>';
    if (src === 'owner') return '<span class="m360-am-badge allow">مالک</span>';
    return '';
  }

  /** Presentation-only map — never show raw EMPLOYEE in Owner UI. */
  function mapAccountCodeFa(code) {
    var c = String(code || '').toUpperCase().trim();
    if (!c || c === 'EMPLOYEE') return 'سرمایه انسانی';
    if (c === 'OWNER' || c === 'SYSTEM_OWNER') return 'مالک سیستم';
    if (c === 'SYSTEM_ADMIN') return 'مدیر سیستم';
    return c;
  }

  function personTypeHtml(person) {
    var isOwner = parseInt(person.is_system_owner, 10) === 1;
    var emp = String(person.employment_label_fa || '').trim();
    var roles = String(person.roles_line_fa || person.role_labels || '').trim();
    var typeFa = String(person.personnel_type_fa || '').trim();
    var h = '';
    if (isOwner) {
      h += '<span class="m360-am-type-line">' + (roles || typeFa || 'مالک سیستم') + '</span>';
      return h;
    }
    if (emp === 'سرمایه انسانی' || (!emp && mapAccountCodeFa(person.role_code) === 'سرمایه انسانی' && !roles)) {
      h += '<span class="m360-am-type-line m360-am-emp-type">سرمایه انسانی</span>';
      if (roles) {
        h += '<span class="m360-am-type-line m360-am-roles">نقش‌ها: ' + roles + '</span>';
      }
      return h;
    }
    if (emp) {
      h += '<span class="m360-am-type-line">' + emp + '</span>';
      if (roles) h += '<span class="m360-am-type-line m360-am-roles">نقش‌ها: ' + roles + '</span>';
      return h;
    }
    if (roles || typeFa) {
      h += '<span class="m360-am-type-line">' + (roles || typeFa) + '</span>';
      return h;
    }
    h += '<span class="m360-am-type-line">' + mapAccountCodeFa(person.role_code) + '</span>';
    return h;
  }

  function recForPerson(person) {
    var code = String(person.employee_code || '');
    return state.recommendations[code] || null;
  }

  function flagClass(flag) {
    if (flag.indexOf('تعارض') >= 0) return 'conflict';
    if (flag.indexOf('حساس') >= 0) return 'sensitive';
    if (flag.indexOf('انتظار') >= 0) return 'pending';
    if (flag.indexOf('آماده') >= 0 || flag.indexOf('مالک سامانه') >= 0) return 'ready';
    return '';
  }

  function personPassesRecFilter(person) {
    var rec = recForPerson(person);
    var f = state.recFilter;
    var pkg = state.pkgFilter;
    if (pkg) {
      if (!rec) return false;
      var all = (rec.primary_packages || []).concat(rec.secondary_packages || []);
      var hit = all.some(function (p) { return String(p.package_key || '') === pkg; });
      if (!hit) return false;
    }
    if (!f) return true;
    if (!rec) return false;
    var flags = rec.ui_flags || [];
    var counts = rec.counts || {};
    if (f === 'ready') return flags.indexOf('پیشنهاد آماده') >= 0 && (counts.conflict || 0) === 0;
    if (f === 'conflict') return (counts.conflict || 0) > 0 || flags.indexOf('تعارض وظایف') >= 0;
    if (f === 'sensitive') return (counts.sensitive || 0) > 0 || flags.indexOf('دسترسی‌های حساس') >= 0;
    if (f === 'pending') return (counts.pending || 0) > 0 || flags.indexOf('دسترسی‌های در انتظار تکمیل') >= 0;
    return true;
  }

  function recFlagsHtml(person) {
    var rec = recForPerson(person);
    if (!rec) return '<span class="m360-am-rec-flag">بدون پیشنهاد</span>';
    var h = '<div class="m360-am-rec-flags">';
    (rec.ui_flags || []).forEach(function (fl) {
      h += '<span class="m360-am-rec-flag ' + flagClass(fl) + '">' + fl + '</span>';
    });
    h += '</div>';
    return h;
  }

  function syncStickyHeaderOffsets() {
    var wrap = document.querySelector('.m360-am-table-wrap');
    var groupRow = document.querySelector('.m360-am-table thead tr.m360-am-group-row');
    if (!wrap) return;
    var h = 0;
    if (groupRow) {
      h = Math.ceil(groupRow.getBoundingClientRect().height) || 0;
    }
    if (h < 1) h = 0;
    wrap.style.setProperty('--m360-am-group-h', h + 'px');
  }

  function isDrawerOpen() {
    var d = $('#amDrawer');
    return !!(d && d.classList.contains('is-open'));
  }

  /**
   * Canonical close — single path for button / Escape / backdrop.
   * @param {{force?: boolean}} opts
   * @returns {boolean} true if closed
   */
  function closeAccessMatrixDrawer(opts) {
    opts = opts || {};
    var drawer = $('#amDrawer');
    var backdrop = $('#amDrawerBackdrop');
    if (!drawer) return false;

    if (!opts.force && state.drawerDirty) {
      var leave = window.confirm('تغییرات ذخیره‌نشده است. آیا پنل بسته شود؟\n\nادامه و بازگشت: انصراف\nبستن بدون ذخیره: تأیید');
      if (!leave) return false;
      state.drawerDirty = false;
    }

    drawer.classList.remove('is-open', 'open');
    drawer.setAttribute('aria-hidden', 'true');
    drawer.removeAttribute('aria-modal');
    if (backdrop) {
      backdrop.classList.remove('is-open');
      backdrop.setAttribute('aria-hidden', 'true');
    }
    document.body.classList.remove('m360-am-drawer-open');
    document.body.style.overflow = '';

    var restore = state.lastFocusEl;
    state.lastFocusEl = null;
    state.selectedUserId = 0;
    if (restore && typeof restore.focus === 'function') {
      try { restore.focus(); } catch (e) { /* ignore */ }
    }
    return true;
  }

  function openAccessMatrixDrawer(title, bodyHtml, openerEl) {
    var drawer = $('#amDrawer');
    var backdrop = $('#amDrawerBackdrop');
    var titleEl = $('#amDrawerTitle');
    var bodyEl = $('#amDrawerBody');
    if (!drawer || !bodyEl) return;

    state.lastFocusEl = openerEl || document.activeElement;
    if (titleEl) titleEl.textContent = title || 'جزئیات';
    bodyEl.innerHTML = bodyHtml || '';
    state.drawerDirty = false;

    drawer.classList.add('is-open');
    drawer.classList.remove('open');
    drawer.setAttribute('aria-hidden', 'false');
    drawer.setAttribute('aria-modal', 'true');
    if (backdrop) {
      backdrop.classList.add('is-open');
      backdrop.setAttribute('aria-hidden', 'false');
    }
    document.body.classList.add('m360-am-drawer-open');
    document.body.style.overflow = 'hidden';

    var closeBtn = $('#amDrawerClose');
    if (closeBtn && typeof closeBtn.focus === 'function') {
      closeBtn.focus();
    }
  }

  window.closeAccessMatrixDrawer = closeAccessMatrixDrawer;

  function renderTable() {
    var head = $('#amHead');
    var body = $('#amBody');
    if (!head || !body) return;
    var perms = state.permissions.filter(function (p) {
      return (p.module_key === state.module || state.module === '') &&
        String(p.enforcement_state || '').toUpperCase() !== 'LEGACY_QUARANTINED';
    });
    var cols = perms;
    var groupedModules = { workshop: 1, management: 1, hr_self: 1, customer_reception: 1, hr_admin: 1 };
    if (!groupedModules[state.module]) {
      cols = perms.filter(function (p) {
        return p.action_key === 'VIEW' || p.is_base ||
          ['PERMISSION_MANAGE', 'USER_MANAGE', 'AUDIT_VIEW', 'CREATE', 'EDIT', 'APPROVE', 'ASSIGN', 'SUBMIT', 'RETURN', 'CLOSE', 'FINANCIAL_VIEW', 'REVIEW', 'DOCUMENT_MANAGE'].indexOf(p.action_key) >= 0;
      });
      if (cols.length > 40) cols = cols.slice(0, 40);
    }
    /* Grouped modules (workshop/management/profile/customer): keep full set — never truncate. */

    var groups = {};
    var groupOrder = [];
    cols.forEach(function (c) {
      var gk = c.group_key || '_';
      if (!groups[gk]) {
        groups[gk] = { key: gk, title: c.group_title_fa || '', cols: [] };
        groupOrder.push(gk);
      }
      groups[gk].cols.push(c);
    });

    var h = '';
    var showGroups = !!groupedModules[state.module] && groupOrder.length >= 1;
    if (showGroups) {
      h += '<tr class="m360-am-group-row"><th class="sticky-col m360-am-corner m360-am-group-corner">پرسنل</th>';
      groupOrder.forEach(function (gk) {
        var g = groups[gk];
        if (!g.cols.length) return;
        var title = g.title || gk;
        var countFa = String(g.cols.length).replace(/\d/g, function (d) {
          return '۰۱۲۳۴۵۶۷۸۹'[parseInt(d, 10)];
        });
        var extra = gk === 'ws_consumable' ? ' m360-am-group-head--consumable' : '';
        h += '<th class="m360-am-group-head' + extra + '" data-group="' + gk + '" colspan="' + g.cols.length + '" title="' +
          String(title).replace(/"/g, '&quot;') + '">' +
          '<span class="m360-am-group-title">' + title + '</span>' +
          '<span class="m360-am-group-count">' + countFa + ' دسترسی</span>' +
          '</th>';
      });
      h += '</tr>';
    }
    h += '<tr class="m360-am-perm-row"><th class="sticky-col m360-am-corner">پرسنل</th>';
    cols.forEach(function (c) {
      var tip = String(c.description_fa || c.title_fa || '').replace(/"/g, '&quot;');
      var enf = String(c.enforcement_state || '').toUpperCase();
      var cls = 'm360-am-col-head';
      if (!c.is_assignable && !c.is_base) cls += ' m360-am-col-disabled';
      if (String(c.group_key || '') === 'ws_consumable') cls += ' m360-am-col-head--consumable';
      h += '<th class="' + cls + '" data-group="' + (c.group_key || '') + '" title="' + tip + '">' +
        (c.title_fa || c.permission_key) +
        (enf === 'NOT_YET_ENFORCED' ? '<span class="m360-am-enf-tag">هنوز فعال نشده</span>' : '') +
        '</th>';
    });
    h += '</tr>';
    head.innerHTML = h;

    var b = '';
    state.personnel.forEach(function (person) {
      if (!personPassesRecFilter(person)) return;
      b += '<tr data-user="' + person.user_id + '" data-code="' + (person.employee_code || '') + '"><td class="sticky-col"><button type="button" class="m360-am-id m360-am-person-open" data-user="' + person.user_id + '" style="all:unset;cursor:pointer;display:flex;flex-direction:column;gap:.15rem;text-align:right;width:100%">';
      b += '<strong>' + (person.full_name || '') + '</strong>';
      b += '<span>' + (person.employee_code || '') + ' — ' + (person.job_title || '') + '</span>';
      b += personTypeHtml(person);
      b += recFlagsHtml(person);
      b += '</button></td>';
      cols.forEach(function (c) {
        var cell = (person.matrix || []).find(function (m) { return m.permission_key === c.permission_key; }) || c;
        var locked = !!cell.is_base;
        var assignable = !!(cell.is_assignable);
        var checked = !!cell.allowed;
        var disabled = locked || !assignable;
        var tip = String(cell.description_fa || c.description_fa || c.title_fa || '').replace(/"/g, '&quot;');
        b += '<td class="m360-am-cell' + (locked ? ' locked' : '') + (!assignable && !locked ? ' not-assignable' : '') + '" data-key="' + c.permission_key + '" data-user="' + person.user_id + '" data-assignable="' + (assignable ? '1' : '0') + '" title="' + tip + '">';
        b += '<input type="checkbox"' + (checked ? ' checked' : '') + (disabled ? ' disabled' : '') + ' aria-label="' + (c.title_fa || c.permission_key) + '">';
        b += sourceBadge(cell.source, locked);
        b += '</td>';
      });
      b += '</tr>';
    });
    body.innerHTML = b;
    syncStickyHeaderOffsets();
    window.requestAnimationFrame(syncStickyHeaderOffsets);
  }

  function openPermissionDrawer(userId, key, checked, openerEl) {
    state.selectedUserId = parseInt(userId, 10) || 0;
    var meta = (state.permissions || []).find(function (p) { return p.permission_key === key; }) || {};
    var titleFa = meta.title_fa || key;
    var descFa = meta.description_fa || '';
    var enf = String(meta.enforcement_state || '').toUpperCase();
    var assignable = !!meta.is_assignable;
    var html =
      '<p><strong>' + titleFa + '</strong></p>' +
      (descFa ? '<p>' + descFa + '</p>' : '') +
      '<p>وضعیت اجرایی: ' + (enf || '—') + '</p>' +
      '<p>وضعیت پیشنهادی: ' + (checked ? 'ALLOW' : 'CLEAR/DENY') + '</p>';
    if (!assignable) {
      html += '<p class="m360-am-disabled-note">این مجوز هنوز قابل تخصیص نیست. فقط مجوزهای فعال‌شده (ENFORCED) قابل تخصیص هستند.</p>';
      if (String(key).indexOf('attendance.correction') >= 0) {
        html += '<p class="m360-am-disabled-note">فرایند اصلاح تردد هنوز به گردش تأیید متصل نشده است.</p>';
      } else if (String(meta.group_key || '') === 'ws_consumable' && enf !== 'ENFORCED') {
        html += '<p class="m360-am-disabled-note">فرایند ثبت مواد مصرفی داخلی هنوز تکمیل نشده است</p>';
      }
      openAccessMatrixDrawer(titleFa, html, openerEl);
      return;
    }
    html +=
      '<label>علت تغییر<br><textarea id="amReason" rows="3" style="width:100%"></textarea></label>' +
      '<div class="m360-am-actions">' +
      '<button type="button" class="m360-btn m360-btn-primary" id="amApplyAllow">اعمال ALLOW</button>' +
      '<button type="button" class="m360-btn m360-btn-secondary" id="amApplyDeny">DENY مستقیم</button>' +
      '<button type="button" class="m360-btn m360-btn-secondary" id="amApplyClear">حذف override</button>' +
      '<button type="button" class="m360-btn m360-btn-secondary" id="amPropose">پیشنهاد برای تأیید</button>' +
      '</div>';
    openAccessMatrixDrawer(titleFa, html, openerEl);

    var reasonEl = $('#amReason');
    if (reasonEl) {
      reasonEl.addEventListener('input', function () { state.drawerDirty = true; });
    }

    function post(effect, propose) {
      var reason = ($('#amReason') || {}).value || '';
      fetch('api/access/personnel-matrix.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          erp_csrf_token: csrf(),
          action: propose ? 'propose' : 'apply',
          user_id: parseInt(userId, 10),
          permission_key: key,
          effect: effect,
          reason: reason
        })
      }).then(function (r) { return r.json(); }).then(function (j) {
        alert(j.message || (j.ok ? 'انجام شد' : 'خطا'));
        if (j.ok) {
          state.dirty = [];
          state.drawerDirty = false;
          showUnsaved();
          closeAccessMatrixDrawer({ force: true });
          load();
        }
      }).catch(function () { alert('خطای شبکه'); });
    }

    var allowBtn = $('#amApplyAllow');
    var denyBtn = $('#amApplyDeny');
    var clearBtn = $('#amApplyClear');
    var proposeBtn = $('#amPropose');
    if (allowBtn) allowBtn.onclick = function () { post('ALLOW', false); };
    if (denyBtn) denyBtn.onclick = function () { post('DENY', false); };
    if (clearBtn) clearBtn.onclick = function () { post('CLEAR', false); };
    if (proposeBtn) proposeBtn.onclick = function () { post(checked ? 'ALLOW' : 'DENY', true); };
  }

  function openPersonDrawer(userId, openerEl) {
    var p = state.personnel.find(function (x) { return parseInt(x.user_id, 10) === userId; });
    if (!p) return;
    state.selectedUserId = userId;
    var empLine = String(p.employment_label_fa || '').trim();
    var rolesLine = String(p.roles_line_fa || p.role_labels || '').trim();
    var typeLine = String(p.personnel_type_fa || '').trim();
    if (!empLine && !rolesLine && !typeLine) {
      typeLine = mapAccountCodeFa(p.role_code);
    }
    var rec = recForPerson(p);
    var html =
      '<p>کد پرسنلی: ' + (p.employee_code || '') + '</p>' +
      '<p>واحد: ' + (p.unit_name || '—') + '</p>' +
      '<p>شغل: ' + (p.job_title || '—') + '</p>' +
      (empLine ? '<p>وضعیت سرمایه انسانی: ' + empLine + '</p>' : '') +
      '<p>نقش‌ها: ' + (rolesLine || typeLine || '—') + '</p>' +
      '<p>حساب: ' + (p.username || '') + ' / ' + ((parseInt(p.is_login_enabled, 10) === 1) ? 'فعال' : 'غیرفعال') + '</p>';

    if (rec) {
      html += '<div class="m360-am-rec-section"><h4>پیشنهاد دسترسی (غیرقابل اعمال در این مرحله)</h4>';
      html += '<p>وضعیت: ' + (rec.status || '—') + '</p>';
      html += '<p>فرمول: پروفایل قفل + بسته اصلی + مسئولیت فرعی + محدوده + اختیار + دروازه حساس + تفکیک وظایف</p>';
      html += '<p>' + (rec.reason_fa || '') + '</p>';
      html += '<p><strong>بسته اصلی:</strong> ' + ((rec.primary_packages || []).map(function (x) { return x.title_fa || x.package_key; }).join('، ') || '—') + '</p>';
      html += '<p><strong>بسته فرعی:</strong> ' + ((rec.secondary_packages || []).map(function (x) { return x.title_fa || x.package_key; }).join('، ') || '—') + '</p>';
      html += '<p>پروفایل پرسنلی: قفل / فقط رکورد خود</p>';
      var c = rec.counts || {};
      html += '<p>قابل اعمال اکنون: ' + (c.enforceable || 0) +
        ' | در انتظار نرم‌افزار: ' + (c.pending || 0) +
        ' | حساس: ' + (c.sensitive || 0) +
        ' | تعارض: ' + (c.conflict || 0) + '</p>';

      var enf = (rec.items || []).filter(function (i) { return i.bucket === 'ENFORCEABLE_NOW'; }).slice(0, 12);
      var pend = (rec.items || []).filter(function (i) { return i.bucket === 'PENDING_SOFTWARE'; }).slice(0, 12);
      var sens = (rec.items || []).filter(function (i) { return i.bucket === 'SENSITIVE_OWNER_GATE' || parseInt(i.is_sensitive, 10) === 1; }).slice(0, 12);
      html += '<p><strong>مجوزهای قابل اعمال اکنون</strong></p><ul class="m360-am-rec-list">';
      if (!enf.length) html += '<li>—</li>';
      enf.forEach(function (i) { html += '<li>' + (i.title_fa || i.permission_key) + ' <span class="m360-am-badge base">ENFORCED</span></li>'; });
      html += '</ul><p><strong>در انتظار تکمیل نرم‌افزار</strong></p><ul class="m360-am-rec-list">';
      if (!pend.length) html += '<li>—</li>';
      pend.forEach(function (i) { html += '<li>' + (i.title_fa || i.permission_key) + ' <span class="m360-am-badge pending-soft">در انتظار</span></li>'; });
      html += '</ul><p><strong>دسترسی‌های حساس (تصمیم مالک)</strong></p><ul class="m360-am-rec-list">';
      if (!sens.length) html += '<li>—</li>';
      sens.forEach(function (i) { html += '<li>' + (i.title_fa || i.permission_key) + ' <span class="m360-am-badge sens">حساس</span></li>'; });
      html += '</ul>';

      if ((rec.conflicts || []).length) {
        html += '<p><strong>هشدار تفکیک وظایف</strong></p><ul class="m360-am-rec-list">';
        rec.conflicts.forEach(function (w) {
          html += '<li><strong>' + (w.title_fa || w.code) + '</strong> — ' + (w.detail_fa || '') +
            ' <em>' + (w.safeguard_fa || '') + '</em>' +
            (w.owner_exception_required ? ' (استثنای مالک لازم است)' : '') + '</li>';
        });
        html += '</ul>';
      }
      html += '<p>دسترسی مؤثر فعلی از نقش/override جدا است؛ این پیشنهاد هیچ دسترسی جدیدی ایجاد نمی‌کند.</p>';
      html += '<div class="m360-am-actions">' +
        '<button type="button" class="m360-btn m360-btn-secondary" id="amRecMarkReview">علامت‌گذاری برای بررسی مالک</button>' +
        '</div></div>';
    }

    openAccessMatrixDrawer(p.full_name || 'پرسنل', html, openerEl);
    var markBtn = $('#amRecMarkReview');
    if (markBtn && rec) {
      markBtn.onclick = function () {
        fetch('api/access/personnel-recommendations.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            erp_csrf_token: csrf(),
            action: 'mark_owner_review',
            employee_code: p.employee_code || '',
            note: 'علامت از ماتریس دسترسی'
          })
        }).then(function (r) { return r.json(); }).then(function (j) {
          alert(j.message || (j.ok ? 'انجام شد' : 'خطا'));
          if (j.ok) loadRecommendations();
        }).catch(function () { alert('خطای شبکه'); });
      };
    }
  }

  function fillPackageFilter() {
    var sel = $('#amPkgFilter');
    if (!sel) return;
    var cur = sel.value || '';
    var opts = '<option value="">همه بسته‌ها</option>';
    (state.packages || []).forEach(function (p) {
      opts += '<option value="' + (p.package_key || '') + '">' + (p.title_fa || p.package_key) + '</option>';
    });
    sel.innerHTML = opts;
    sel.value = cur;
  }

  function loadRecommendations() {
    return fetch('api/access/personnel-recommendations.php', { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) return;
        state.packages = j.packages || [];
        var map = {};
        (j.recommendations || []).forEach(function (rec) {
          map[String(rec.employee_code || '')] = rec;
        });
        state.recommendations = map;
        fillPackageFilter();
      })
      .catch(function () { /* advisory optional */ });
  }

  function load() {
    var q = ($('#amQ') || {}).value || '';
    var unit = ($('#amUnit') || {}).value || '';
    state.recFilter = (($('#amRecFilter') || {}).value || '');
    state.pkgFilter = (($('#amPkgFilter') || {}).value || '');
    var url = 'api/access/personnel-matrix.php?module=' + encodeURIComponent(state.module) +
      '&q=' + encodeURIComponent(q) + '&unit=' + encodeURIComponent(unit);
    Promise.all([
      fetch(url, { credentials: 'same-origin' }).then(function (r) { return r.json(); }),
      loadRecommendations()
    ]).then(function (pair) {
      var j = pair[0];
      if (!j.ok) { alert(j.message || 'خطا'); return; }
      state.personnel = j.personnel || [];
      state.permissions = j.permissions || [];
      renderTable();
    }).catch(function () { alert('خطای شبکه'); });
  }

  function bindStaticListeners() {
    if (state.listenersBound) return;
    state.listenersBound = true;

    document.querySelectorAll('.m360-am-tabs button').forEach(function (btn) {
      btn.addEventListener('click', function () {
        document.querySelectorAll('.m360-am-tabs button').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        state.module = btn.getAttribute('data-module') || '';
        load();
      });
    });

    var searchBtn = $('#amSearch');
    if (searchBtn) searchBtn.onclick = load;

    var rebuildBtn = $('#amRecRebuild');
    if (rebuildBtn) {
      rebuildBtn.addEventListener('click', function () {
        fetch('api/access/personnel-recommendations.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ erp_csrf_token: csrf(), action: 'rebuild' })
        }).then(function (r) { return r.json(); }).then(function (j) {
          alert(j.message || (j.ok ? 'بازسازی شد' : 'خطا'));
          if (j.ok) {
            var map = {};
            (j.recommendations || []).forEach(function (rec) {
              map[String(rec.employee_code || '')] = rec;
            });
            state.recommendations = map;
            renderTable();
          }
        }).catch(function () { alert('خطای شبکه'); });
      });
    }

    var previewBtn = $('#amRecPreview');
    if (previewBtn) {
      previewBtn.addEventListener('click', function (e) {
        e.preventDefault();
        var lines = ['personnel_code\tname\tunit\ttitle\tprimary\tsecondary\tenforceable\tpending\tsensitive\tconflicts\tstatus\treason'];
        state.personnel.forEach(function (p) {
          var rec = recForPerson(p);
          if (!rec) return;
          var prim = (rec.primary_packages || []).map(function (x) { return x.title_fa || x.package_key; }).join('|');
          var sec = (rec.secondary_packages || []).map(function (x) { return x.title_fa || x.package_key; }).join('|');
          var c = rec.counts || {};
          lines.push([
            p.employee_code || '', p.full_name || '', p.unit_name || '', p.job_title || '',
            prim, sec, c.enforceable || 0, c.pending || 0, c.sensitive || 0, c.conflict || 0,
            rec.status || '', String(rec.reason_fa || '').replace(/\t|\n|\r/g, ' ')
          ].join('\t'));
        });
        var blob = new Blob([lines.join('\n')], { type: 'text/tab-separated-values;charset=utf-8' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'personnel_access_recommendations_preview.tsv';
        a.click();
        URL.revokeObjectURL(url);
      });
    }

    var recFilter = $('#amRecFilter');
    if (recFilter) recFilter.addEventListener('change', function () { state.recFilter = recFilter.value || ''; renderTable(); });
    var pkgFilter = $('#amPkgFilter');
    if (pkgFilter) pkgFilter.addEventListener('change', function () { state.pkgFilter = pkgFilter.value || ''; renderTable(); });

    var closeBtn = $('#amDrawerClose');
    if (closeBtn) {
      closeBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        closeAccessMatrixDrawer();
      });
    }

    var backdrop = $('#amDrawerBackdrop');
    if (backdrop) {
      backdrop.addEventListener('click', function () {
        closeAccessMatrixDrawer();
      });
    }

    var drawer = $('#amDrawer');
    if (drawer) {
      drawer.addEventListener('click', function (e) {
        e.stopPropagation();
      });
    }

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && isDrawerOpen()) {
        e.preventDefault();
        closeAccessMatrixDrawer();
      }
    });

    /* Delegated matrix interactions — survive table re-renders */
    var tableWrap = document.querySelector('.m360-am-table-wrap');
    if (tableWrap) {
      tableWrap.addEventListener('change', function (e) {
        var inp = e.target;
        if (!inp || inp.tagName !== 'INPUT' || inp.type !== 'checkbox') return;
        var td = inp.closest('.m360-am-cell');
        if (!td || td.classList.contains('locked') || td.getAttribute('data-assignable') !== '1') return;
        state.dirty.push({
          user_id: parseInt(td.getAttribute('data-user'), 10),
          permission_key: td.getAttribute('data-key'),
          effect: inp.checked ? 'ALLOW' : 'CLEAR'
        });
        state.drawerDirty = true;
        showUnsaved();
        openPermissionDrawer(
          td.getAttribute('data-user'),
          td.getAttribute('data-key'),
          inp.checked,
          inp
        );
      });

      tableWrap.addEventListener('click', function (e) {
        var td = e.target.closest('.m360-am-cell.not-assignable');
        if (td && !e.target.closest('input')) {
          openPermissionDrawer(td.getAttribute('data-user'), td.getAttribute('data-key'), false, td);
          return;
        }
        var btn = e.target.closest('.m360-am-person-open');
        if (!btn) return;
        e.preventDefault();
        openPersonDrawer(parseInt(btn.getAttribute('data-user'), 10), btn);
      });
    }
  }

  function boot() {
    /* Ensure initial closed state even if stale class leaked */
    closeAccessMatrixDrawer({ force: true });
    bindStaticListeners();
    window.addEventListener('resize', syncStickyHeaderOffsets);
    load();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
