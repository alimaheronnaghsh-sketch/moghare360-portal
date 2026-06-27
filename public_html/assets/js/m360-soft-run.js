(function () {
    'use strict';
    document.querySelectorAll('[data-m360-sr-refresh]').forEach(function (el) {
        el.addEventListener('click', function () { window.location.reload(); });
    });
})();
