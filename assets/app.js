document.addEventListener('click', (event) => {
  const row = event.target.closest('.select-row');
  if (row && row.dataset.id) {
    const form = document.querySelector('[data-selection-form]');
    if (form) {
      document.querySelectorAll('.select-row.selected').forEach(el => el.classList.remove('selected'));
      row.classList.add('selected');
      Object.entries(row.dataset).forEach(([key, value]) => {
        const field = form.elements.namedItem(key);
        if (!field) return;
        if (field.type === 'checkbox') field.checked = value === '1'; else field.value = value;
      });
    }
  }
  const step = event.target.closest('[data-step]');
  if (step) {
    const input = step.parentElement.querySelector('input');
    input.value = Math.max(0, Math.min(10, Number(input.value) + Number(step.dataset.step)));
    step.parentElement.classList.toggle('has-quantity', Number(input.value) > 0);
  }
  const viewButton = event.target.closest('[data-shopping-view]');
  if (viewButton) setShoppingView(viewButton.dataset.shoppingView);
  const movement = event.target.closest('[data-direction]');
  if (movement) movement.form.elements.direction.value = movement.dataset.direction;
  const confirmButton = event.target.closest('[data-confirm]');
  if (confirmButton && !confirm(confirmButton.dataset.confirm)) event.preventDefault();
});

let shoppingOriginalOrder;

function setShoppingView(view) {
  const list = document.querySelector('[data-shopping-list]');
  if (!list) return;
  shoppingOriginalOrder ??= [...list.children];
  const alphabetical = view === 'alphabetical';
  const rows = [...list.querySelectorAll('.shopping-row')];
  rows.sort((a, b) => alphabetical
    ? a.dataset.name.localeCompare(b.dataset.name, 'de', { sensitivity: 'base' })
    : Number(a.dataset.originalIndex) - Number(b.dataset.originalIndex));
  (alphabetical ? rows : shoppingOriginalOrder).forEach(item => list.append(item));
  list.classList.toggle('alphabetical', alphabetical);
  list.querySelectorAll('[data-location-heading]').forEach(heading => heading.hidden = alphabetical);
  document.querySelectorAll('[data-shopping-view]').forEach(button => {
    const active = button.dataset.shoppingView === view;
    button.classList.toggle('active', active);
    button.setAttribute('aria-pressed', String(active));
  });
}

document.addEventListener('DOMContentLoaded', () => {
  const selected = document.querySelector('.select-list > .select-row.selected');
  if (selected) selected.click();
});

document.addEventListener('change', (event) => {
  if (event.target.matches('[data-hide-completed], [data-buy-completed]')) {
    updateBuyVisibility();
  }
});

function updateBuyVisibility() {
  const list = document.querySelector('.buy-list');
  const filter = document.querySelector('[data-hide-completed]');
  if (!list || !filter) return;
  const hideCompleted = filter.checked;
  list.querySelectorAll('[data-buy-row]').forEach(row => {
    row.hidden = hideCompleted && row.querySelector('[data-buy-completed]').checked;
  });
  let heading;
  let visibleInGroup = false;
  [...list.children, null].forEach(item => {
    if (!item || item.matches('[data-buy-heading]')) {
      if (heading) heading.hidden = !visibleInGroup;
      heading = item;
      visibleInGroup = false;
    } else if (item.matches('[data-buy-row]') && !item.hidden) {
      visibleInGroup = true;
    }
  });
}
