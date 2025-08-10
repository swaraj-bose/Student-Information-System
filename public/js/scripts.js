document.addEventListener('DOMContentLoaded', () => {
  // Add basic form validation before submit
  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', (e) => {
      const inputs = form.querySelectorAll('input[required], select[required]');
      let valid = true;

      inputs.forEach(input => {
        // Remove previous error styles
        input.style.borderColor = '';
        input.classList.remove('shake');

        if (!input.value.trim()) {
          valid = false;
          input.style.borderColor = 'red';
          input.classList.add('shake');
          setTimeout(() => input.classList.remove('shake'), 300);
        } else {
          input.style.borderColor = '#1dcd9f';
        }
      });

      if (!valid) {
        e.preventDefault(); // Stop form submission
        alert('Please fill in all required fields.');
      }
    });
  });

  // Input hover styling
  document.querySelectorAll('input, select').forEach(el => {
    el.addEventListener('mouseenter', () => {
      el.style.borderColor = '#1dcd9f';
    });
    el.addEventListener('mouseleave', () => {
      if (!el.matches(':focus') && !el.value.trim()) {
        el.style.borderColor = '#444';
      }
    });
  });

  // Add shake animation CSS
  const style = document.createElement('style');
  style.innerHTML = `
    .shake {
      animation: shake 0.3s ease-in-out;
    }
    @keyframes shake {
      0% { transform: translateX(0); }
      25% { transform: translateX(-4px); }
      50% { transform: translateX(4px); }
      75% { transform: translateX(-4px); }
      100% { transform: translateX(0); }
    }
  `;
  document.head.appendChild(style);
});

