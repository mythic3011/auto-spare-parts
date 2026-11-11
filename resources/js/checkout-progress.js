// Progressive checkout: the server commits the order before returning a destination.
(() => {
  const form = document.getElementById('checkoutForm');
  const progress = document.getElementById('checkoutProgress');
  const button = document.getElementById('checkoutButton');
  const feedback = document.getElementById('checkoutFeedback');
  if (!form || !progress || !button || !feedback) return;
  let submitting = false;
  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (submitting) return;
    submitting = true;
    button.disabled = true;
    button.textContent = 'Placing order…';
    form.setAttribute('aria-busy', 'true');
    feedback.hidden = true;
    progress.hidden = false;
    try {
      const response = await fetch(form.action, {
        method: 'POST', credentials: 'same-origin',
        headers: { Accept: 'application/json' }, body: new FormData(form),
      });
      if (!response.headers.get('Content-Type')?.includes('application/json')) {
        throw new Error('Could not confirm the checkout result. Check your order history before trying again.');
      }
      const result = await response.json();
      if (!response.ok) throw new Error(result.error || 'Checkout failed. Please try again.');
      if (result.redirect !== 'checkout.php') throw new Error('Order submitted. Open your order history to check its status.');
      window.location.assign(new URL(result.redirect, window.location.href));
    } catch (error) {
      feedback.textContent = error instanceof Error ? error.message : 'Checkout failed.';
      feedback.hidden = false;
      progress.hidden = true;
      form.removeAttribute('aria-busy');
      button.disabled = false;
      button.textContent = 'Checkout →';
      submitting = false;
    }
  });
})();
