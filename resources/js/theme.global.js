// The login theme is the shared contract for classic pages and ES modules.
(() => {
  if (window.SLMC_THEME) return;
  function apply() {
    const saved = localStorage.getItem('theme');
    const dark = saved === 'dark' || (saved === null && matchMedia('(prefers-color-scheme: dark)').matches);
    document.documentElement.classList.toggle('dark', dark);
    document.documentElement.dataset.theme = dark ? 'dark' : 'light';
    const button = document.getElementById('darkModeToggle');
    if (button) {
      button.setAttribute('aria-pressed', String(dark));
      button.setAttribute('aria-label', dark ? 'Switch to light theme' : 'Switch to dark theme');
    }
  }
  function toggle() {
    localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'light' : 'dark');
    apply();
  }
  window.SLMC_THEME = Object.freeze({ apply, toggle });
  apply();
  document.addEventListener('DOMContentLoaded', apply, { once: true });
  // Own this control during migration so legacy click handlers cannot toggle it twice.
  document.addEventListener('click', event => {
    if (!(event.target instanceof Element) || !event.target.closest('#darkModeToggle')) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    toggle();
  }, true);
  window.addEventListener('storage', event => { if (event.key === 'theme') apply(); });
  matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    if (localStorage.getItem('theme') === null) apply();
  });
})();
