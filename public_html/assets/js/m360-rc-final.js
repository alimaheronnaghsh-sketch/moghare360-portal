(function () {
    'use strict';
    document.querySelectorAll('[data-m360-rcf-refresh]').forEach(function (el) {
        el.addEventListener('click', function () { window.location.reload(); });
    });
})();
