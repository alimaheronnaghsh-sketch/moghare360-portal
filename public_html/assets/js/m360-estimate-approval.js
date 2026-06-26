(function () {
    var btn = document.getElementById('m360-est-send-otp');
    if (!btn) return;
    btn.addEventListener('click', function () {
        var token = btn.getAttribute('data-token') || '';
        btn.disabled = true;
        fetch('api/customer/estimate-send-otp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ token: token })
        })
            .then(function (r) { return r.json(); })
            .then(function (j) {
                alert(j.message || (j.ok ? 'ارسال شد' : 'خطا'));
                btn.disabled = false;
            })
            .catch(function () {
                alert('خطا در ارسال کد');
                btn.disabled = false;
            });
    });
})();
