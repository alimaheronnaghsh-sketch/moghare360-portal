(function () {
    'use strict';

    function updatePlatePreview() {
        var left = document.querySelector('[name="plate_left_2_digits"]');
        var letter = document.querySelector('[name="plate_letter"]');
        var mid = document.querySelector('[name="plate_middle_3_digits"]');
        var iran = document.querySelector('[name="plate_iran_2_digits"]');
        var out = document.getElementById('m360_rw_plate_preview');
        if (!left || !letter || !mid || !iran || !out) return;
        var l = (left.value || '').trim();
        var c = (letter.value || '').trim();
        var m = (mid.value || '').trim();
        var r = (iran.value || '').trim();
        if (l && c && m && r) {
            out.textContent = l + c + m + '-' + r;
        } else {
            out.textContent = 'پس از تکمیل، پلاک اینجا نمایش داده می‌شود';
        }
    }

    document.querySelectorAll('[name="plate_left_2_digits"], [name="plate_letter"], [name="plate_middle_3_digits"], [name="plate_iran_2_digits"]').forEach(function (el) {
        el.addEventListener('input', updatePlatePreview);
        el.addEventListener('change', updatePlatePreview);
    });
    updatePlatePreview();

    var video = document.getElementById('m360_rw_camera_video');
    var canvas = document.getElementById('m360_rw_camera_canvas');
    var startBtn = document.getElementById('m360_rw_camera_start');
    var stream = null;
    var activeSlot = '';

    function enableCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            if (startBtn) startBtn.disabled = true;
            return Promise.reject(new Error('no camera'));
        }
        if (stream) {
            return Promise.resolve(stream);
        }
        return navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false })
            .then(function (s) {
                stream = s;
                if (video) {
                    video.srcObject = s;
                    video.play();
                }
                return s;
            });
    }

    if (startBtn && video) {
        startBtn.addEventListener('click', function () {
            enableCamera().catch(function () {
                alert('دسترسی به دوربین ممکن نیست. لطفاً مجوز دوربین را بررسی کنید.');
            });
        });
    }

    function captureForSlot(slot) {
        if (!canvas || !video) return null;
        canvas.width = video.videoWidth || 640;
        canvas.height = video.videoHeight || 480;
        var ctx = canvas.getContext('2d');
        if (!ctx) return null;
        ctx.drawImage(video, 0, 0);
        return canvas.toDataURL('image/jpeg', 0.85);
    }

    document.querySelectorAll('.m360-rw-slot-capture').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var slot = btn.getAttribute('data-slot') || '';
            activeSlot = slot;
            enableCamera().then(function () {
                var data = captureForSlot(slot);
                if (!data) return;
                var form = btn.closest('.m360-rw-photo-slot-form');
                if (!form) return;
                var hidden = form.querySelector('.m360-rw-slot-base64');
                var saveBtn = form.querySelector('.m360-rw-slot-save');
                if (hidden) hidden.value = data;
                if (saveBtn) saveBtn.disabled = false;
                var preview = document.getElementById('m360_rw_preview_' + slot);
                if (preview) {
                    preview.src = data;
                    preview.style.display = 'block';
                }
            }).catch(function () {
                alert('دسترسی به دوربین ممکن نیست. لطفاً مجوز دوربین را بررسی کنید.');
            });
        });
    });

    if (window.location.hash) {
        var target = document.querySelector(window.location.hash);
        if (target) {
            window.requestAnimationFrame(function () {
                target.scrollIntoView({ block: 'start' });
            });
        }
    }
})();
