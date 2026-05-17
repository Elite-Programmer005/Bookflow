document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
      const targetSelector = button.getAttribute('data-password-toggle') || '';
      const input = targetSelector ? document.querySelector(targetSelector) : null;
      if (!input) {
        return;
      }

      const nextType = input.getAttribute('type') === 'password' ? 'text' : 'password';
      input.setAttribute('type', nextType);
      button.textContent = nextType === 'password' ? 'Show' : 'Hide';
    });
  });

  document.querySelectorAll('[data-confirm]').forEach((button) => {
    button.addEventListener('click', (event) => {
      const message = button.getAttribute('data-confirm') || 'Are you sure?';
      if (!window.confirm(message)) {
        event.preventDefault();
      }
    });
  });

  const paymentButton = document.querySelector('[data-dummy-pay]');
  if (paymentButton) {
    paymentButton.addEventListener('click', () => {
      alert('Payment successful (demo)');
      const formId = paymentButton.getAttribute('data-form-target');
      const form = formId ? document.querySelector(formId) : null;
      if (form) {
        const field = form.querySelector('input[name="dummy_payment"]');
        if (field) {
          field.value = '1';
        }
        form.submit();
      }
    });
  }

  document.querySelectorAll('[data-slot]').forEach((slot) => {
    slot.addEventListener('click', () => {
      document.querySelectorAll('[data-slot]').forEach((node) => node.classList.remove('is-selected'));
      slot.classList.add('is-selected');
      const value = slot.getAttribute('data-slot') || '';
      const hiddenInput = document.querySelector('[data-booking-time-input]');
      const selectInput = document.querySelector('[data-booking-time-select]');
      const visibleInput = document.getElementById('selected_time_display');
      if (hiddenInput) {
        hiddenInput.value = value;
      }
      if (selectInput) {
        selectInput.value = value;
      }
      if (visibleInput) {
        visibleInput.value = value;
      }
    });
  });

  document.querySelectorAll('[data-booking-time-select]').forEach((select) => {
    select.addEventListener('change', () => {
      const value = select.value;
      document.querySelectorAll('[data-slot]').forEach((node) => {
        node.classList.toggle('is-selected', node.getAttribute('data-slot') === value);
      });
      const hiddenInput = document.querySelector('[data-booking-time-input]');
      const visibleInput = document.getElementById('selected_time_display');
      if (hiddenInput) {
        hiddenInput.value = value;
      }
      if (visibleInput) {
        visibleInput.value = value;
      }
    });
  });
});
