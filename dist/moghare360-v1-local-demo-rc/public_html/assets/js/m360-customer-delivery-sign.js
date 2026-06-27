(function () {
    'use strict';

    var otpBtn = document.getElementById('m360-delivery-send-otp');
    var otpStatus = document.getElementById('m360-delivery-otp-status');
    var form = document.getElementById('m360-delivery-sign-form');

    if (otpBtn) {
        otpBtn.addEventListener('click', function () {
            var token = otpBtn.getAttribute('data-token') || '';
            otpBtn.disabled = true;
            if (otpStatus) {
                otpStatus.textContent = 'در حال ارسال...';
            }
            fetch('api/customer/delivery-send-otp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ token: token })
            })
                .then(function (r) { return r.json(); })
                .then(function (j) {
                    if (otpStatus) {
                        otpStatus.textContent = j.message || (j.ok ? 'کد ارسال شد.' : 'خطا');
                    } else {
                        alert(j.message || (j.ok ? 'ارسال شد' : 'خطا'));
                    }
                    otpBtn.disabled = false;
                })
                .catch(function () {
                    if (otpStatus) {
                        otpStatus.textContent = 'خطا در ارتباط با سرور.';
                    } else {
                        alert('خطا در ارسال کد');
                    }
                    otpBtn.disabled = false;
                });
        });
    }

    if (!form) {
        return;
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        var tokenEl = document.getElementById('m360-delivery-token');
        var sigEl = document.getElementById('m360_signature_data');
        var otpEl = document.getElementById('m360-delivery-otp');
        var token = tokenEl ? tokenEl.value : '';
        var signatureData = sigEl ? sigEl.value : '';
        var otpCode = otpEl ? otpEl.value : '';

        if (!signatureData || signatureData.length < 100) {
            alert('لطفاً امضای دیجیتال را در کادر وارد کنید.');
            return;
        }

        var payload = {
            token: token,
            otp_code: otpCode,
            signature_data: signatureData,
            confirm_vehicle: document.getElementById('m360-del-c1') && document.getElementById('m360-del-c1').checked,
            confirm_services: document.getElementById('m360-del-c2') && document.getElementById('m360-del-c2').checked,
            confirm_finance: document.getElementById('m360-del-c3') && document.getElementById('m360-del-c3').checked,
            confirm_terms: document.getElementById('m360-del-c4') && document.getElementById('m360-del-c4').checked
        };

        fetch('api/customer/delivery-confirm.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(payload)
        })
            .then(function (r) { return r.json(); })
            .then(function (j) {
                var qs = '?token=' + encodeURIComponent(token) + '&msg=' + encodeURIComponent(j.message || '') + '&ok=' + (j.ok ? '1' : '0');
                window.location.href = 'customer-delivery-sign.php' + qs;
            })
            .catch(function () {
                alert('خطا در ثبت تحویل');
            });
    });
})();
