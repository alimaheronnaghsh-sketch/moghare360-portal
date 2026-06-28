/**
 * MOGHARE360 P11.4.2 — Department-dependent position dropdown (Access Management UI).
 */
(function () {
    'use strict';

    var mapEl = document.getElementById('m360-access-positions-by-dept');
    var deptEl = document.getElementById('department_id');
    var posEl = document.getElementById('position_id');
    var deptLabelEl = document.getElementById('m360-access-dept-label');

    if (!mapEl || !deptEl || !posEl) {
        return;
    }

    var map = {};
    var deptNames = {};

    try {
        map = JSON.parse(mapEl.textContent || '{}');
    } catch (e) {
        map = {};
    }

    var namesEl = document.getElementById('m360-access-dept-names');
    if (namesEl) {
        try {
            deptNames = JSON.parse(namesEl.textContent || '{}');
        } catch (e2) {
            deptNames = {};
        }
    }

    var initialPos = posEl.getAttribute('data-selected-position') || '';

    function rebuildPositions() {
        var deptId = deptEl.value;
        var selectedBefore = posEl.value;

        posEl.innerHTML = '';
        var empty = document.createElement('option');
        empty.value = '';
        empty.textContent = '—';
        posEl.appendChild(empty);

        if (!deptId) {
            posEl.disabled = true;
            if (deptLabelEl) {
                deptLabelEl.textContent = '';
            }
            return;
        }

        posEl.disabled = false;
        var list = map[deptId] || [];

        list.forEach(function (p) {
            var opt = document.createElement('option');
            opt.value = p.position_id;
            opt.textContent = p.position_name;
            posEl.appendChild(opt);
        });

        var restore = selectedBefore || initialPos;
        if (restore) {
            for (var i = 0; i < list.length; i++) {
                if (list[i].position_id === restore) {
                    posEl.value = restore;
                    break;
                }
            }
        }

        if (deptLabelEl && deptNames[deptId]) {
            deptLabelEl.textContent = 'واحد انتخاب‌شده: ' + deptNames[deptId];
        }
    }

    deptEl.addEventListener('change', rebuildPositions);
    rebuildPositions();
})();
