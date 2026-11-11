// Public demo credentials only. The template is disabled outside development/testing.
(() => {
  const dialog = document.getElementById('demoAccountsDialog');
  if (!(dialog instanceof HTMLDialogElement)) return;
  const status = document.getElementById('demoAccountsStatus');
  document.getElementById('demoAccountsOpen')?.addEventListener('click', () => dialog.showModal());
  document.getElementById('demoAccountsClose')?.addEventListener('click', () => dialog.close());
  async function copy(text, label) {
    try {
      await navigator.clipboard.writeText(text);
      status.textContent = `${label} copied.`;
    } catch {
      status.textContent = 'Clipboard unavailable. Select the value and copy it manually.';
    }
  }
  dialog.querySelectorAll('[data-demo-account]').forEach((account) => {
    const email = account.querySelector('[data-demo-email]');
    const password = account.querySelector('[data-demo-password]');
    email.addEventListener('dblclick', () => copy(email.value, 'Email'));
    password.addEventListener('dblclick', () => copy(password.value, 'Password'));
    account.querySelector('[data-demo-copy]').addEventListener('click', () => {
      copy(`Email: ${email.value}\nPassword: ${password.value}`, 'Credentials');
    });
    account.querySelector('[data-demo-fill]').addEventListener('click', () => {
      for (const [id, value] of [['username', email.value], ['password', password.value]]) {
        const input = document.getElementById(id);
        if (!(input instanceof HTMLInputElement)) return;
        input.value = value;
        input.dispatchEvent(new Event('input', { bubbles: true }));
      }
      dialog.close();
      document.getElementById('loginButton')?.focus();
    });
  });
})();
