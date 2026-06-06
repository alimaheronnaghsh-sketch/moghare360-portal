document.addEventListener('DOMContentLoaded', () => {
  const openBtn = document.querySelector('#openContractViewer');
  const closeBtn = document.querySelector('#closeContractViewer');
  const modal = document.querySelector('#contractViewerModal');

  const contractReadConfirm = document.querySelector('#contractReadConfirm');
  const closeAfterReadBtn = document.querySelector('#closeAfterReadBtn');
  const contractViewState = document.querySelector('#contractViewState');

  const contractViewedAt = document.querySelector('#contractViewedAt');
  const contractReadConfirmedAt = document.querySelector('#contractReadConfirmedAt');

  const signatureBox = document.querySelector('#signatureBox');
  const startQuestionsBtn = document.querySelector('#startQuestionsBtn');

  const typedSignature = document.querySelector('#typedSignature');
  const signedNationalCode = document.querySelector('#signedNationalCode');

  const signatureCanvas = document.querySelector('#signaturePad');
  const signatureData = document.querySelector('#signatureData');
  const clearSignatureBtn = document.querySelector('#clearSignatureBtn');

  const contractForm = document.querySelector('#contractAgreementForm');

  const hiddenFaultAccepted = document.querySelector('#hiddenFaultAccepted');
  const insuranceOption = document.querySelector('#insuranceOption');
  const insuranceWarningAccepted = document.querySelector('#insuranceWarningAccepted');
  const purchaseLimitOption = document.querySelector('#purchaseLimitOption');

  const questionModal = document.querySelector('#contractQuestionModal');
  const questionTitle = document.querySelector('#contractQuestionTitle');
  const questionBody = document.querySelector('#contractQuestionBody');
  const questionActions = document.querySelector('#contractQuestionActions');

  let contractReadDone = false;
  let signatureIsNotEmpty = false;

  function nowSqlLike() {
    const d = new Date();
    const pad = (n) => String(n).padStart(2, '0');

    return (
      d.getFullYear() + '-' +
      pad(d.getMonth() + 1) + '-' +
      pad(d.getDate()) + ' ' +
      pad(d.getHours()) + ':' +
      pad(d.getMinutes()) + ':' +
      pad(d.getSeconds())
    );
  }

  function openContractModal() {
    if (!modal) return;

    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');

    if (contractViewedAt && !contractViewedAt.value) {
      contractViewedAt.value = nowSqlLike();
    }
  }

  function closeContractModalWithoutApproval() {
    if (!modal) return;

    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');

    if (!contractReadDone && contractViewState) {
      contractViewState.textContent = 'متن قرارداد بدون تأیید مطالعه بسته شد. برای ادامه باید دوباره قرارداد را باز کرده و مطالعه را تأیید کنید.';
      contractViewState.classList.add('warning');
      contractViewState.classList.remove('success');
    }
  }

  function closeContractModalWithApproval() {
    if (!contractReadConfirm || !contractReadConfirm.checked) {
      alert('برای ادامه، ابتدا باید گزینه مطالعه کامل متن قرارداد را تأیید کنید.');
      return;
    }

    contractReadDone = true;

    if (contractReadConfirmedAt) {
      contractReadConfirmedAt.value = nowSqlLike();
    }

    if (modal) {
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden', 'true');
    }

    if (signatureBox) {
      signatureBox.classList.remove('hidden');
      signatureBox.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    if (contractViewState) {
      contractViewState.textContent = 'متن قرارداد با تأیید مطالعه بسته شد. اکنون مرحله امضا فعال است.';
      contractViewState.classList.add('success');
      contractViewState.classList.remove('warning');
    }
  }

  if (openBtn) {
    openBtn.addEventListener('click', openContractModal);
  }

  if (closeBtn) {
    closeBtn.addEventListener('click', closeContractModalWithoutApproval);
  }

  if (modal) {
    modal.addEventListener('click', (event) => {
      if (event.target === modal) {
        alert('برای ادامه، باید داخل متن قرارداد گزینه مطالعه را تأیید کنید و دکمه مخصوص بستن قرارداد را بزنید.');
      }
    });
  }

  if (contractReadConfirm && closeAfterReadBtn) {
    closeAfterReadBtn.disabled = !contractReadConfirm.checked;

    contractReadConfirm.addEventListener('change', () => {
      closeAfterReadBtn.disabled = !contractReadConfirm.checked;
    });

    closeAfterReadBtn.addEventListener('click', closeContractModalWithApproval);
  }

  function setupSignaturePad() {
    if (!signatureCanvas) return;

    const ctx = signatureCanvas.getContext('2d');
    let drawing = false;

    function fitCanvas() {
      const rect = signatureCanvas.getBoundingClientRect();
      const ratio = window.devicePixelRatio || 1;

      if (!rect.width || !rect.height) return;

      signatureCanvas.width = rect.width * ratio;
      signatureCanvas.height = rect.height * ratio;

      ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
      ctx.lineWidth = 3;
      ctx.lineCap = 'round';
      ctx.lineJoin = 'round';
      ctx.strokeStyle = '#0e3d2f';
    }

    setTimeout(fitCanvas, 200);
    window.addEventListener('resize', fitCanvas);

    function getPoint(event) {
      const rect = signatureCanvas.getBoundingClientRect();
      const touch = event.touches ? event.touches[0] : event;

      return {
        x: touch.clientX - rect.left,
        y: touch.clientY - rect.top
      };
    }

    function startDraw(event) {
      event.preventDefault();
      drawing = true;
      signatureIsNotEmpty = true;

      const point = getPoint(event);
      ctx.beginPath();
      ctx.moveTo(point.x, point.y);
    }

    function draw(event) {
      if (!drawing) return;

      event.preventDefault();
      const point = getPoint(event);
      ctx.lineTo(point.x, point.y);
      ctx.stroke();
    }

    function stopDraw() {
      if (!drawing) return;
      drawing = false;

      if (signatureData) {
        signatureData.value = signatureCanvas.toDataURL('image/png');
      }
    }

    signatureCanvas.addEventListener('mousedown', startDraw);
    signatureCanvas.addEventListener('mousemove', draw);
    signatureCanvas.addEventListener('mouseup', stopDraw);
    signatureCanvas.addEventListener('mouseleave', stopDraw);

    signatureCanvas.addEventListener('touchstart', startDraw, { passive: false });
    signatureCanvas.addEventListener('touchmove', draw, { passive: false });
    signatureCanvas.addEventListener('touchend', stopDraw);

    if (clearSignatureBtn) {
      clearSignatureBtn.addEventListener('click', () => {
        ctx.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
        signatureIsNotEmpty = false;

        if (signatureData) {
          signatureData.value = '';
        }
      });
    }
  }

  setupSignaturePad();

  function isValidNationalCode(value) {
    return /^[0-9]{10}$/.test(value);
  }

  function validateSignatureStep() {
    if (!contractReadDone && (!contractReadConfirmedAt || !contractReadConfirmedAt.value)) {
      alert('ابتدا باید متن قرارداد را مشاهده و مطالعه آن را تأیید کنید.');
      return false;
    }

    if (!typedSignature || typedSignature.value.trim() === '') {
      alert('نام و نام خانوادگی جهت امضا را وارد کنید.');
      return false;
    }

    if (!signedNationalCode || !isValidNationalCode(signedNationalCode.value.trim())) {
      alert('کد ملی امضاکننده باید دقیقاً ۱۰ رقم باشد.');
      return false;
    }

    if (!signatureIsNotEmpty || !signatureData || signatureData.value.trim() === '') {
      alert('امضای دیجیتال باید ثبت شود.');
      return false;
    }

    return true;
  }

  function openQuestionModal() {
    if (!questionModal) return;
    questionModal.classList.add('is-open');
    questionModal.setAttribute('aria-hidden', 'false');
  }

  function closeQuestionModal() {
    if (!questionModal) return;
    questionModal.classList.remove('is-open');
    questionModal.setAttribute('aria-hidden', 'true');
  }

  function setQuestion(title, bodyHtml, actionsHtml) {
    if (!questionTitle || !questionBody || !questionActions) return;

    questionTitle.textContent = title;
    questionBody.innerHTML = bodyHtml;
    questionActions.innerHTML = actionsHtml;

    openQuestionModal();
  }

  function showHiddenFaultQuestion() {
    setQuestion(
      '۱. تأیید خرابی‌های پنهان و کامپیوتری',
      `
        <p>
          آیا می‌پذیرید که مسئولیت مالی خرابی‌های پنهان، کامپیوتری، برقی، نرم‌افزاری، ECU، سنسورها
          و ایرادات غیرقابل مشاهده در لحظه پذیرش، پس از اعلام و ثبت در پرونده، بر عهده شما خواهد بود؟
        </p>
        <p class="contract-warning-text">
          بدون پذیرش این بند، امکان ادامه فرآیند پذیرش و عقد قرارداد آنلاین وجود ندارد.
        </p>
      `,
      `
        <button class="btn primary" type="button" id="acceptHiddenFaultBtn">می‌پذیرم</button>
        <button class="btn danger" type="button" id="rejectHiddenFaultBtn">نمی‌پذیرم</button>
      `
    );

    document.querySelector('#acceptHiddenFaultBtn').addEventListener('click', () => {
      if (hiddenFaultAccepted) hiddenFaultAccepted.value = '1';
      showInsuranceQuestion();
    });

    document.querySelector('#rejectHiddenFaultBtn').addEventListener('click', () => {
      if (hiddenFaultAccepted) hiddenFaultAccepted.value = '0';
      closeQuestionModal();
      alert('بدون پذیرش مسئولیت خرابی‌های پنهان و کامپیوتری، امکان ادامه فرآیند پذیرش و عقد قرارداد آنلاین وجود ندارد.');
    });
  }

  function showInsuranceQuestion() {
    setQuestion(
      '۲. تست رانندگی و بیمه بدنه',
      `
        <p>
          در صورت نیاز به تست رانندگی، جابه‌جایی خودرو یا حادثه احتمالی، وضعیت اجازه تست و استفاده از بیمه بدنه را مشخص کنید.
        </p>

        <label class="radio-line">
          <input type="radio" name="qInsurance" value="ALLOW">
          اجازه تست رانندگی و استفاده از بیمه بدنه خودرو را در صورت حادثه احتمالی می‌دهم.
        </label>

        <label class="radio-line">
          <input type="radio" name="qInsurance" value="NOT_ALLOWED">
          اجازه تست رانندگی / استفاده از بیمه بدنه را نمی‌دهم.
        </label>

        <label class="radio-line">
          <input type="radio" name="qInsurance" value="NOT_AVAILABLE">
          خودرو بیمه بدنه ندارد / از وضعیت بیمه بدنه اطلاع ندارم.
        </label>

        <div id="insuranceWarningBox" class="contract-warning-box hidden">
          <strong>هشدار مهم</strong>
          <p>
            در این حالت خودرو ممکن است بدون تست رانندگی نهایی تحویل شود یا مشتری باید پیش از اتمام کار
            در محل مجموعه حاضر شود تا تست رانندگی با حضور یا مسئولیت ایشان انجام شود.
          </p>

          <label class="checkbox-line">
            <input type="checkbox" id="insuranceWarningCheckbox">
            هشدار فوق را مطالعه کردم و تأیید می‌کنم.
          </label>
        </div>
      `,
      `
        <button class="btn primary" type="button" id="submitInsuranceBtn">ادامه</button>
      `
    );

    const radios = document.querySelectorAll('input[name="qInsurance"]');
    const warningBox = document.querySelector('#insuranceWarningBox');
    const warningCheckbox = document.querySelector('#insuranceWarningCheckbox');

    radios.forEach((radio) => {
      radio.addEventListener('change', () => {
        if (radio.value === 'NOT_ALLOWED' || radio.value === 'NOT_AVAILABLE') {
          warningBox.classList.remove('hidden');
        } else {
          warningBox.classList.add('hidden');
          warningCheckbox.checked = false;
        }
      });
    });

    document.querySelector('#submitInsuranceBtn').addEventListener('click', () => {
      const selected = document.querySelector('input[name="qInsurance"]:checked');

      if (!selected) {
        alert('لطفاً وضعیت تست رانندگی و بیمه بدنه را انتخاب کنید.');
        return;
      }

      if (
        (selected.value === 'NOT_ALLOWED' || selected.value === 'NOT_AVAILABLE') &&
        !warningCheckbox.checked
      ) {
        alert('برای ادامه، باید هشدار مربوط به تست رانندگی و بیمه بدنه را تأیید کنید.');
        return;
      }

      if (insuranceOption) insuranceOption.value = selected.value;
      if (insuranceWarningAccepted) {
        insuranceWarningAccepted.value =
          (selected.value === 'NOT_ALLOWED' || selected.value === 'NOT_AVAILABLE') ? '1' : '0';
      }

      showPurchaseQuestion();
    });
  }

  function showPurchaseQuestion() {
    setQuestion(
      '۳. سقف اختیار خرید قطعه و خدمات مرتبط',
      `
        <p>
          سقف اختیار مجموعه برای خرید قطعه، خدمات مرتبط، خدمات بیرونی و اقلام مورد نیاز خودرو را مشخص کنید.
        </p>

        <label class="radio-line">
          <input type="radio" name="qPurchase" value="under_1b_rial">
          کمتر از ۱,۰۰۰,۰۰۰,۰۰۰ ریال
        </label>

        <label class="radio-line">
          <input type="radio" name="qPurchase" value="between_1b_2_5b_rial">
          از ۱,۰۰۰,۰۰۰,۰۰۰ ریال تا ۲,۵۰۰,۰۰۰,۰۰۰ ریال
        </label>

        <label class="radio-line">
          <input type="radio" name="qPurchase" value="unlimited">
          بدون سقف، مطابق نیاز فنی خودرو و تشخیص مجموعه
        </label>
      `,
      `
        <button class="btn primary" type="button" id="submitPurchaseBtn">ادامه و ارسال کد تأیید</button>
      `
    );

    document.querySelector('#submitPurchaseBtn').addEventListener('click', () => {
      const selected = document.querySelector('input[name="qPurchase"]:checked');

      if (!selected) {
        alert('لطفاً سقف اختیار خرید قطعه و خدمات مرتبط را انتخاب کنید.');
        return;
      }

      if (purchaseLimitOption) purchaseLimitOption.value = selected.value;

      closeQuestionModal();

      if (contractForm) {
        contractForm.submit();
      }
    });
  }

  if (startQuestionsBtn) {
    startQuestionsBtn.addEventListener('click', () => {
      if (!validateSignatureStep()) return;
      showHiddenFaultQuestion();
    });
  }
});
