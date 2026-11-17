(function () {
  'use strict';
  var form = document.getElementById('checkout-preview-form');
  var total = document.getElementById('summary-total');
  function update() {
    if (!form || !total) return;
    var selected = form.querySelector('input[name="delivery"]:checked');
    var currency = total.getAttribute('data-currency');
    function money(value) { return value.toLocaleString('ru-RU', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ' + currency; }
    form.querySelectorAll('[data-payment-delivery]').forEach(function (group) {
      var active = selected && group.getAttribute('data-payment-delivery') === selected.value;
      group.hidden = !active;
      group.querySelectorAll('input').forEach(function (input) { input.disabled = !active; });
      if (active && !group.querySelector(':checked')) {
        var first = group.querySelector('input');
        if (first) first.checked = true;
      }
    });
    if (selected) {
      var price = Number(selected.getAttribute('data-price'));
      document.getElementById('summary-delivery').textContent = money(price);
      total.textContent = money(Number(total.getAttribute('data-subtotal')) + price);
    }
  }
  if (form) form.addEventListener('change', update);
  update();
  var notice = document.getElementById('error-summary') || document.getElementById('review');
  if (notice) notice.focus();
  var toggle = document.querySelector('.ks-chat-toggle');
  var panel = document.getElementById('ks-chat-panel');
  if (toggle && panel) {
    toggle.addEventListener('click', function () {
      var open = toggle.getAttribute('aria-expanded') === 'true';
      toggle.setAttribute('aria-expanded', String(!open)); panel.hidden = open;
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !panel.hidden) { panel.hidden = true; toggle.setAttribute('aria-expanded','false'); toggle.focus(); }
    });
  }
}());
