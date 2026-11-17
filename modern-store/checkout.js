(function () {
  'use strict';
  // Static UI only: no form submission, fetch, XHR, cookies, storage or backend calls.
  var productPrice = 39990;
  var deliveryPrice = 300;
  var deliveryName = 'Курьерская служба';
  var reviewed = false;
  var inputs = ['checkout-name', 'checkout-phone', 'checkout-email', 'checkout-address'].map(function (id) { return document.getElementById(id); });
  var address = document.getElementById('checkout-address');
  var summary = document.getElementById('error-summary');
  var dialog = document.getElementById('review-dialog');
  var reviewButton = document.getElementById('review-button');
  function money(value) { return new Intl.NumberFormat('ru-RU').format(value) + ' ₽'; }
  function setError(input, message) {
    var output = document.getElementById(input.id + '-error');
    input.setAttribute('aria-invalid', message ? 'true' : 'false');
    output.textContent = message;
    output.hidden = !message;
    return !message;
  }
  function validate(input) {
    if (input.disabled) return setError(input, '');
    var value = input.value.trim(), message = '';
    if (!value && input.required) {
      message = input.id === 'checkout-name' ? 'Укажите ваше имя.' : input.id === 'checkout-phone' ? 'Укажите телефон для связи.' : input.id === 'checkout-email' ? 'Укажите электронную почту.' : 'Укажите адрес для курьерской доставки.';
    } else if (input.id === 'checkout-email' && input.validity.typeMismatch) {
      message = 'Проверьте адрес электронной почты, например name@example.ru.';
    } else if (input.id === 'checkout-phone' && value) {
      var digits = value.replace(/\D/g, '');
      if (digits.length < 10 || digits.length > 15 || !/^[+\d\s().-]+$/.test(value)) message = 'Укажите телефон: от 10 до 15 цифр.';
    }
    return setError(input, message);
  }
  function updateDelivery() {
    var pickup = document.querySelector('input[name="preview_delivery"]:checked').value === 'pickup';
    deliveryPrice = pickup ? 0 : 300;
    deliveryName = pickup ? 'Самовывоз' : 'Курьерская служба';
    address.disabled = pickup;
    address.required = !pickup;
    document.getElementById('address-field').hidden = pickup;
    document.getElementById('pickup-help').hidden = !pickup;
    document.getElementById('summary-delivery').textContent = pickup ? 'Бесплатно' : money(deliveryPrice);
    document.getElementById('summary-total').textContent = money(productPrice + deliveryPrice);
    if (pickup) setError(address, '');
    if (reviewed) updateErrorSummary(false);
  }
  function updateErrorSummary(focus) {
    var invalid = inputs.filter(function (input) { return !validate(input); });
    summary.hidden = invalid.length === 0;
    summary.textContent = invalid.length ? 'Проверьте отмеченные поля: ' + invalid.length + '.' : '';
    if (focus && invalid.length) { invalid[0].focus(); invalid[0].scrollIntoView({block:'center',behavior:'auto'}); }
    return invalid.length === 0;
  }
  function addReviewRow(label, value) {
    var row = document.createElement('div'), term = document.createElement('dt'), detail = document.createElement('dd');
    term.textContent = label; detail.textContent = value; row.appendChild(term); row.appendChild(detail);
    document.getElementById('review-details').appendChild(row);
  }
  inputs.forEach(function (input) {
    input.addEventListener('blur', function () { if (input.value.trim() || reviewed) validate(input); });
    input.addEventListener('input', function () { if (reviewed) updateErrorSummary(false); });
  });
  document.querySelectorAll('input[name="preview_delivery"]').forEach(function (input) { input.addEventListener('change', updateDelivery); });
  reviewButton.addEventListener('click', function () {
    reviewed = true;
    if (!updateErrorSummary(true)) return;
    var details = document.getElementById('review-details');
    while (details.firstChild) details.removeChild(details.firstChild);
    addReviewRow('Имя', document.getElementById('checkout-name').value.trim());
    addReviewRow('Телефон', document.getElementById('checkout-phone').value.trim());
    addReviewRow('Электронная почта', document.getElementById('checkout-email').value.trim());
    addReviewRow('Получение', deliveryName + ' · ' + (deliveryPrice ? money(deliveryPrice) : 'бесплатно'));
    if (!address.disabled) addReviewRow('Адрес', address.value.trim());
    var comment = document.getElementById('checkout-comment').value.trim();
    if (comment) addReviewRow('Комментарий', comment);
    addReviewRow('Оплата', 'Наличными');
    document.getElementById('review-total').textContent = money(productPrice + deliveryPrice);
    dialog.showModal();
  });
  document.querySelectorAll('[data-close-review]').forEach(function (button) { button.addEventListener('click', function () { dialog.close(); }); });
  dialog.addEventListener('close', function () { reviewButton.focus(); });
  updateDelivery();
}());

// Approved messenger launcher from pinned baseline 0bf8a48.

					(function () {
					'use strict';
					var root = document.getElementById('ks-messenger-widget');
					if (!root) return;
					var toggle = root.querySelector('.ks-chat-toggle');
					var panel = root.querySelector('.ks-chat-panel');
					function close(restoreFocus) {
					panel.hidden = true;
					toggle.setAttribute('aria-expanded', 'false');
					toggle.setAttribute('aria-label', 'Открыть мессенджеры');
					if (restoreFocus) toggle.focus();
					}
					toggle.addEventListener('click', function () {
					var open = panel.hidden;
					panel.hidden = !open;
					toggle.setAttribute('aria-expanded', String(open));
					toggle.setAttribute('aria-label', open ? 'Закрыть мессенджеры' : 'Открыть мессенджеры');
					});
					document.addEventListener('click', function (event) {
					if (!root.contains(event.target)) close(false);
					});
					document.addEventListener('keydown', function (event) {
					if (panel.hidden) return;
					if (event.key === 'Escape') {
					event.preventDefault();
					close(root.contains(document.activeElement));
					}
					});
					root.addEventListener('focusout', function (event) {
					if (event.relatedTarget) {
					if (!root.contains(event.relatedTarget)) close(false);
					}
					});
					var notice = document.getElementById('ks-cookie-notice');
					function position() {
					var offset = 0;
					if (notice) {
					if (!notice.hidden) offset = notice.getBoundingClientRect().height + 12;
					}
					root.style.setProperty('--ks-chat-offset', offset + 'px');
					}
					if (notice) {
					new MutationObserver(position).observe(notice, {attributes:true, subtree:true, attributeFilter:['hidden','class']});
					}
					window.addEventListener('resize', position);
					position();
					}());

