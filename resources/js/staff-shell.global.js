// Shared responsive navigation for every staff page.
(() => {
  function initialize() {
    const toggle = document.getElementById('mobileMenuButton');
    const menu = document.getElementById('mobileMenu');
    if (!toggle || !menu) return;
    function close() { menu.hidden = true; toggle.setAttribute('aria-expanded', 'false'); }
    // Capture preserves one owner while older workflow modules retain their listeners.
    toggle.addEventListener('click', event => {
      event.stopImmediatePropagation();
      menu.hidden = !menu.hidden;
      menu.style.removeProperty('display');
      toggle.setAttribute('aria-expanded', String(!menu.hidden));
    }, true);
    document.addEventListener('keydown', event => { if (event.key === 'Escape') { close(); toggle.focus(); } });
    matchMedia('(min-width: 900px)').addEventListener('change', event => { if (event.matches) close(); });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
  else initialize();
})();
