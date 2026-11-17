<?php
// Include only from the isolated checkout renderer.
if (!defined('HOSTCMS') || !defined('KLIMAT_CHECKOUT_PREVIEW')) {
    if (defined('HOSTCMS') && class_exists('Core_Page', FALSE)) Core_Page::instance()->response->status(404);
    http_response_code(404); exit;
}
function klimat_checkout_header() { ?>
  <header class="header"><div class="wrap header-inner">
    <a class="brand" href="/modern-home/">КЛИМАТ-СЕРВИС<small>Оборудование · монтаж · сервис</small></a>
    <nav aria-label="Главная навигация"><a href="/modern-catalog/">Каталог</a><a href="/modern-delivery/">Доставка и оплата</a><a href="/modern-contacts/">Контакты</a></nav>
    <a class="header-phone" href="tel:+79054113948" aria-label="Позвонить: +7 (905) 411-39-48"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h3l2 5-2 2c1 2 2 3 4 4l2-2 5 2v3c0 2-2 4-4 3C9 19 5 15 3 7 2 5 5 3 7 3Z"/></svg><span>+7 (905) 411-39-48</span></a>
    <a class="header-cart" href="/modern-cart/">Корзина</a>
  </div></header>
<?php }
function klimat_checkout_footer() { ?>
  <footer class="footer"><div class="wrap"><div><strong>КЛИМАТ-СЕРВИС</strong><p>Климатические решения для дома и бизнеса.<br>Москва и Московская область.</p></div><div><strong><a href="tel:+79054113948">+7 (905) 411-39-48</a></strong><p>Москва, ул. Перовская, 20<br><a href="/modern-delivery/">Доставка и оплата</a> · <a href="/modern-contacts/">Контакты</a></p></div></div></footer>
<div id="ks-messenger-widget">
					<button type="button" class="ks-chat-toggle" aria-label="Открыть мессенджеры" title="Написать нам" aria-expanded="false" aria-controls="ks-chat-panel">
						<svg viewBox="0 0 64 60" aria-hidden="true" focusable="false"><g class="ks-chat-bubble"><path d="M32 4C17.1 4 5 14.1 5 26.5C5 33.1 8.5 39 14.4 43.1L10 55L24.3 48.1C26.8 48.7 29.4 49 32 49C46.9 49 59 38.9 59 26.5C59 14.1 46.9 4 32 4Z" fill="white"></path><g fill="#0089c9"><circle cx="23" cy="26.5" r="3"></circle><circle cx="32" cy="26.5" r="3"></circle><circle cx="41" cy="26.5" r="3"></circle></g></g><path class="ks-chat-close" d="m21 19 22 22m0-22L21 41" fill="none" stroke="white" stroke-width="3" stroke-linecap="round"></path></svg>
					</button>
					<div id="ks-chat-panel" class="ks-chat-panel" role="group" aria-label="Написать в мессенджере" hidden="hidden">
						<a class="ks-chat-link" href="https://t.me/klimat5servis" target="_blank" rel="noopener noreferrer" aria-label="Написать в Telegram"><span class="ks-chat-logo"><img src="/assets/header-20261116/telegram.svg" width="38" height="38" alt=""></span></a>
						<a class="ks-chat-link" href="https://max.ru/u/f9LHodD0cOLjX0SKaPjyc-__Xtd96IrNpMNkx4pqxmQmNL8yuBC4ysq5LRg" target="_blank" rel="noopener noreferrer" aria-label="Написать в MAX"><span class="ks-chat-logo"><img src="/assets/footer-20261116/max.webp" width="38" height="38" alt=""></span></a>
						<a class="ks-chat-link" href="https://wa.me/79054113948" target="_blank" rel="noopener noreferrer" aria-label="Написать в WhatsApp"><span class="ks-chat-logo"><svg viewBox="0 0 32 32" aria-hidden="true" focusable="false"><circle cx="16" cy="16" r="16" fill="#25d366"></circle><path d="M23.3 8.7a10.2 10.2 0 0 0-16 12.2L6 26l5.2-1.4A10.2 10.2 0 0 0 23.3 8.7ZM16 24a8.4 8.4 0 0 1-4.3-1.2l-.3-.2-3.1.8.8-3-.2-.3A8.3 8.3 0 1 1 16 24Z" fill="#fff"></path><path d="M12.4 10.8c-.2-.5-.5-.5-.7-.5h-.6c-.2 0-.6.1-.8.4-.3.3-1.1 1-1.1 2.4s1.1 2.8 1.3 3c.1.2 2.1 3.2 5.2 4.4 2.6 1 3.1.8 3.6.7.6-.1 1.8-.7 2-1.4.3-.7.3-1.3.2-1.4-.1-.1-.3-.2-.6-.4l-2.1-1c-.3-.1-.5-.2-.7.2-.2.3-.8 1-1 1.2-.2.2-.4.2-.7.1-.3-.2-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6l.5-.6.3-.5c.1-.2 0-.4 0-.6l-.8-1.8Z" fill="#fff"></path></svg></span></a>
					</div>
				</div>
				

<?php }
