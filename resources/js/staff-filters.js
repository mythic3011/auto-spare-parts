// Filter the currently loaded staff records without changing API or pagination contracts.
(() => {
  function init() {
    const catalog = document.getElementById('sparePartsTableBody');
    const orders = document.getElementById('orderTableBody');
    const report = document.getElementById('reportTableBody');
    const body = catalog || orders || report;
    if (!body) return;
    const wrapper = body.closest('.staff-table-scroll');
    if (!wrapper) return;
    const toolbar = document.createElement('form');
    toolbar.className = 'staff-filters';
    toolbar.setAttribute('role', 'search');
    toolbar.setAttribute('aria-label', 'Filter ' + (catalog ? 'catalog' : orders ? 'orders' : 'report'));
    const fields = {};
    function field(key, label, type = 'search', options = null) {
      const container = document.createElement('label');
      container.textContent = label;
      const input = document.createElement(options ? 'select' : 'input');
      input.id = 'staff-filter-' + key;
      input.name = key;
      if (options) {
        for (const [value, text] of options) {
          const option = document.createElement('option'); option.value = value; option.textContent = text; input.append(option);
        }
      } else input.type = type;
      if (key === 'search') { input.placeholder = catalog ? 'Product ID, name or description…' : orders ? 'Order ID, manager, dealer or address…' : 'Part ID, name or order number…'; container.className = 'staff-filter-search'; }
      container.append(input); toolbar.append(container); fields[key] = input;
    }
    field('search', 'Search');
    if (catalog) {
      field('category', 'Category', '', [['', 'All categories'], ['1', 'Category A'], ['2', 'Category B'], ['3', 'Category C'], ['4', 'Category D']]);
      field('stock', 'Stock', '', [['', 'All stock'], ['available', 'In stock'], ['empty', 'Out of stock']]);
      field('locked', 'Order protection', '', [['', 'All products'], ['true', 'Locked by orders'], ['false', 'Not in orders']]);
    } else if (orders) {
      const statuses = ['Pending', 'Confirmed', 'Processing', 'Shipping', 'Shipped', 'Delivered', 'Cancelled'];
      field('status', 'Status', '', [['', 'All statuses'], ...statuses.map(status => [status, status])]);
      field('from', 'Ordered from', 'date'); field('to', 'Ordered through', 'date');
    } else {
      field('sales', 'Sales', '', [['', 'All products'], ['sold', 'With sales'], ['none', 'No sales']]);
    }
    const reset = document.createElement('button'); reset.type = 'reset'; reset.textContent = 'Clear filters'; reset.className = 'staff-filter-reset'; toolbar.append(reset);
    const status = document.createElement('p'); status.className = 'staff-filter-count'; status.setAttribute('role', 'status'); status.setAttribute('aria-live', 'polite');
    const empty = document.createElement('div'); empty.className = 'staff-filter-empty'; empty.hidden = true;
    const emptyTitle = document.createElement('strong'); emptyTitle.textContent = 'No matching records';
    const emptyText = document.createElement('p'); emptyText.textContent = 'Try another search or clear the filters.'; empty.append(emptyTitle, emptyText);
    wrapper.before(toolbar, status); wrapper.after(empty);
    function apply() {
      const query = fields.search.value.trim().toLocaleLowerCase();
      const invalidRange = Boolean(fields.from?.value && fields.to?.value && fields.from.value > fields.to.value);
      fields.to?.setCustomValidity(invalidRange ? 'End date must be on or after the start date.' : '');
      const rows = [...body.rows].filter(row => row.dataset.search !== undefined);
      let visible = 0;
      for (const row of rows) {
        const data = row.dataset;
        let matches = data.search.toLocaleLowerCase().includes(query);
        for (const key of ['category', 'locked', 'status']) if (fields[key]?.value) matches &&= data[key] === fields[key].value;
        if (fields.stock?.value) matches &&= fields.stock.value === 'available' ? Number(data.stock) > 0 : Number(data.stock) === 0;
        if (fields.sales?.value) matches &&= fields.sales.value === 'sold' ? Number(data.sales) > 0 : Number(data.sales) === 0;
        if (fields.from?.value) matches &&= data.date >= fields.from.value;
        if (fields.to?.value) matches &&= data.date <= fields.to.value;
        matches &&= !invalidRange;
        row.hidden = !matches;
        if (matches) visible++;
        else row.querySelectorAll('input[type=checkbox]').forEach(input => { input.checked = false; });
      }
      status.textContent = invalidRange ? 'Choose an end date on or after the start date.' : `${visible} of ${rows.length} records`;
      empty.hidden = invalidRange || rows.length === 0 || visible > 0;
      const all = document.getElementById('checkbox-all');
      if (all) { all.checked = false; all.indeterminate = false; }
    }
    toolbar.addEventListener('submit', event => event.preventDefault());
    toolbar.addEventListener('input', apply); toolbar.addEventListener('change', apply);
    toolbar.addEventListener('reset', event => {
      event.preventDefault();
      for (const input of Object.values(fields)) input.value = '';
      apply();
    });
    new MutationObserver(apply).observe(body, { childList: true });
    const all = document.getElementById('checkbox-all');
    all?.addEventListener('change', () => {
      for (const row of body.rows) row.querySelectorAll('input[type=checkbox]').forEach(input => { input.checked = !row.hidden && all.checked; });
    });
    apply();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true }); else init();
})();
