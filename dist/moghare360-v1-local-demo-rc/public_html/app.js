document.addEventListener('DOMContentLoaded', () => {
  const brand = document.querySelector('#vehicleBrand');
  const model = document.querySelector('#vehicleModel');

  if (brand && model) {
    const allOptions = Array.from(model.querySelectorAll('option'));
    const defaultOption = allOptions.find((option) => !option.dataset.brand) || allOptions[0] || null;

    const refreshModels = () => {
      const selectedBrand = (brand.value || '').trim();
      model.value = '';
      model.disabled = selectedBrand === '';

      allOptions.forEach((option) => {
        if (!option.dataset.brand) {
          option.hidden = false;
          return;
        }
        option.hidden = selectedBrand !== '' && option.dataset.brand !== selectedBrand;
      });

      if (defaultOption) {
        defaultOption.textContent = selectedBrand === '' ? 'ابتدا برند را انتخاب کنید' : 'مدل را انتخاب کنید';
      }
    };

    brand.addEventListener('change', refreshModels);
    refreshModels();
  }

  document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!confirm(form.dataset.confirm || 'آیا مطمئن هستید؟')) {
        event.preventDefault();
      }
    });
  });

  const shouldShowPhotoReminder = window.MOGHARE360_PHOTO_REMINDER === true;
  const reminderModal = document.querySelector('#photoReminderModal');
  if (shouldShowPhotoReminder && reminderModal) {
    const storageKey = 'moghare360_photo_reminder_dismissed_at';
    const sessionKey = 'moghare360_photo_reminder_shown';
    const now = Date.now();
    const lastDismissed = parseInt(localStorage.getItem(storageKey) || '0', 10);
    const seenThisSession = sessionStorage.getItem(sessionKey) === '1';
    const canShowByTime = !lastDismissed || (now - lastDismissed) > (24 * 60 * 60 * 1000);

    if (!seenThisSession && canShowByTime) {
      reminderModal.classList.add('is-open');
      reminderModal.setAttribute('aria-hidden', 'false');
      sessionStorage.setItem(sessionKey, '1');
    }

    const closeModal = () => {
      reminderModal.classList.remove('is-open');
      reminderModal.setAttribute('aria-hidden', 'true');
      localStorage.setItem(storageKey, String(Date.now()));
    };

    reminderModal.querySelectorAll('[data-photo-reminder-dismiss]').forEach((el) => {
      el.addEventListener('click', closeModal);
    });

    reminderModal.querySelectorAll('[data-photo-reminder-upload]').forEach((el) => {
      el.addEventListener('click', closeModal);
    });
  }
});
