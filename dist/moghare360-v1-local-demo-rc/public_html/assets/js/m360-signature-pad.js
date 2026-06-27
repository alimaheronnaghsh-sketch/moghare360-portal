(function () {
  'use strict';

  function initSignaturePad(canvasId, clearBtnId, hiddenInputId) {
    var canvas = document.getElementById(canvasId);
    if (!canvas || !canvas.getContext) {
      return;
    }
    var ctx = canvas.getContext('2d');
    var drawing = false;
    var hidden = document.getElementById(hiddenInputId);

    function resize() {
      var rect = canvas.getBoundingClientRect();
      canvas.width = Math.floor(rect.width);
      canvas.height = Math.floor(rect.height);
      ctx.lineWidth = 2.5;
      ctx.lineCap = 'round';
      ctx.strokeStyle = '#0f172a';
    }

    function pos(e) {
      var rect = canvas.getBoundingClientRect();
      var clientX = e.touches ? e.touches[0].clientX : e.clientX;
      var clientY = e.touches ? e.touches[0].clientY : e.clientY;
      return { x: clientX - rect.left, y: clientY - rect.top };
    }

    function syncHidden() {
      if (hidden) {
        hidden.value = canvas.toDataURL('image/png');
      }
    }

    function start(e) {
      drawing = true;
      var p = pos(e);
      ctx.beginPath();
      ctx.moveTo(p.x, p.y);
      e.preventDefault();
    }

    function move(e) {
      if (!drawing) return;
      var p = pos(e);
      ctx.lineTo(p.x, p.y);
      ctx.stroke();
      e.preventDefault();
    }

    function end() {
      if (!drawing) return;
      drawing = false;
      syncHidden();
    }

    resize();
    window.addEventListener('resize', resize);
    canvas.addEventListener('mousedown', start);
    canvas.addEventListener('mousemove', move);
    window.addEventListener('mouseup', end);
    canvas.addEventListener('touchstart', start, { passive: false });
    canvas.addEventListener('touchmove', move, { passive: false });
    canvas.addEventListener('touchend', end);

    var clearBtn = document.getElementById(clearBtnId);
    if (clearBtn) {
      clearBtn.addEventListener('click', function () {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        if (hidden) hidden.value = '';
      });
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    initSignaturePad('m360_signature_canvas', 'm360_signature_clear', 'm360_signature_data');
  });
})();
