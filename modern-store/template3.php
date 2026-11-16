<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/assets/audit-20261116/render.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<meta name="format-detection" content="telephone=no">
<?php klimat_page_metadata(); ?>
<meta name="keywords" content="<?php Core_Page::instance()->showKeywords()?>" />
<?php klimat_seo_head(); ?>
<?php klimat_inline_css('/assets/audit-20261116/fixes.css'); ?>
<?php klimat_inline_css('/images/t8903001/css/normalize.css'); ?>
<?php klimat_inline_css('/images/t8903001/css/slick.css'); ?>
<?php klimat_inline_css('/images/t8903001/css/jquery.arcticmodal-0.3.css'); ?>
<?php klimat_inline_css('/images/t8903001/css/jquery.nice-number.min.css'); ?>



<?php 
Core_Page::instance()->showCss();
?>

<?php klimat_inline_css('/images/t8903001/css/media.css'); ?>




<?php $klimat_defer_home = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) === '/' ? ' defer' : ''; ?>
<!-- On the homepage, dependencies keep execution order without blocking HTML parsing. -->
<!-- jQuery -->
<script<?php echo $klimat_defer_home; ?> src="/images/t8903001/js/jquery-3.4.1.min.js?v=clean-20261116"></script>

<!-- jQuery Nice Number -->
<script<?php echo $klimat_defer_home; ?> src="/images/t8903001/js/jquery.nice-number.min.js"></script>

 
<?php
$klimat_shop_object = Core_Page::instance()->object;
$klimat_has_filters = strpos(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/category/') === 0
    && $klimat_shop_object instanceof Shop_Controller_Show
    && !(int) $klimat_shop_object->item;
if ($klimat_has_filters) { ?>
<link rel="stylesheet" href="/hostcmsfiles/asmp_filter/css/tooltipster.css" type="text/css"/>
<link rel="stylesheet" href="/hostcmsfiles/asmp_filter/css/ion.rangeSlider.min.css" type="text/css"/>
<link rel="stylesheet" href="/hostcmsfiles/asmp_filter/css/filters401888562.css" type="text/css"/>

<script defer src="/hostcmsfiles/asmp_filter/js/jquery.tooltipster.min.js" type="text/javascript"></script>
<script defer src="/hostcmsfiles/asmp_filter/js/ion.rangeSlider.min.js" type="text/javascript"></script>
<script defer src="/hostcmsfiles/asmp_filter/js/filters401888562.js" type="text/javascript"></script>

<?php } ?>
<?php klimat_inline_css('/assets/header-20261116/style.css'); ?>
<?php klimat_inline_css('/assets/footer-20261116/style.css'); ?>
<?php klimat_inline_css('/assets/sitewide-20261116/typography.css'); ?>
<script defer src="/assets/header-20261116/header.js?v=3"></script>
<script defer src="/assets/footer-20261116/cookie-notice.js?v=1"></script>
</head>
<body>


    <header class="main-header ks-header">
      <div class="container ks-header-row">
        <?php if (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) === '/') { ?><h1 class="visually-hidden">Кондиционеры и сплит-системы с установкой в Москве</h1><?php } ?>
        <a href="/" class="ks-brand" title="Климат-Сервис — на главную">
          <img src="/assets/audit-20261116/22a35fd77b155587.webp" width="42" height="36" alt="">
          <span><strong>Климат-Сервис</strong><small>Интернет-магазин</small></span>
        </a>
        <nav class="ks-navigation" id="ks-navigation" aria-label="Основное меню">
          <div class="ks-mobile-search"><form class="ks-search-form" action="/search/" method="get" role="search"><label class="visually-hidden" for="ks-mobile-search">Поиск по товарам</label><input id="ks-mobile-search" type="search" name="text" placeholder="Поиск по товарам" required><button class="ks-icon" type="submit" aria-label="Найти"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 4.5 4.5"/></svg></button></form></div>
          	<?php
	ob_start();
	$Structure_Controller_Show = new Structure_Controller_Show(
		Core_Entity::factory('Site', CURRENT_SITE));

	$Structure_Controller_Show->xsl(
		Core_Entity::factory('Xsl')->getByName('ВерхнееМенюСайт3')
	)
	->menu(2)
       ->showShopGroups(TRUE)
	->show();
	$klimat_header_menu = ob_get_clean();
	echo str_replace('<span class="main-nav__caret"></span>', '<button class="ks-submenu-toggle" type="button" aria-label="Разделы каталога" aria-expanded="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg></button>', $klimat_header_menu);
	?>
          <div class="ks-mobile-contact"><a class="ks-phone-number" href="tel:89054113948">8 (905) 411-39-48</a><?php if (Core::moduleIsActive('form')) { ?><a class="ks-callback" data-fancybox data-type="ajax" data-src="/kontakty/callback/" href="javascript:;">Обратный звонок</a><?php } ?><a href="/category/favorite/">Избранное</a></div>
        </nav>
        <div class="ks-tools">
          <button class="ks-icon ks-search-toggle" type="button" aria-label="Открыть поиск" aria-expanded="false" aria-controls="ks-search-panel"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 4.5 4.5"/></svg></button>
          <button class="ks-icon ks-contact-toggle" type="button" aria-label="Телефон и обратный звонок" aria-expanded="false" aria-controls="ks-contact-panel"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 3 3 5-2.5 2.5a17 17 0 0 0 6 6L16 14l5 3-1 4C11 22 2 13 3 4Z"/></svg></button>
                          <ul class="header-actions">

								<?php
								if (Core::moduleIsActive('shop'))
								{
									$Shop_Favorite_Controller_Show = new Shop_Favorite_Controller_Show(
										Core_Entity::factory('Shop', 1)
									);
									$Shop_Favorite_Controller_Show
										->xsl(
											Core_Entity::factory('Xsl')->getByName('МагазинИзбранноеКраткоеСайт3')
										)
										->itemsPropertiesList(FALSE)
										->show();
								}
								?>



                    <li class="header-actions__item header-actions__item--basket"  id="little_cart">


<?php
if (Core::moduleIsActive('shop'))
{
// Краткая корзина
$Shop_Cart_Controller_Show = new Shop_Cart_Controller_Show(
Core_Entity::factory('Shop', 1)
);
$Shop_Cart_Controller_Show
->xsl(
Core_Entity::factory('Xsl')->getByName('МагазинКорзинаКраткаяСайт3')
)
->couponText(isset($_SESSION) ? Core_Array::get($_SESSION, 'coupon_text') : '')
->show();
}
?>

                       
                    </li>
                </ul>

          <button class="ks-icon ks-menu-toggle" type="button" aria-label="Открыть меню" aria-expanded="false" aria-controls="ks-navigation"><svg viewBox="0 0 24 24" aria-hidden="true"><path class="ks-menu-lines" d="M4 7h16M4 12h16M4 17h16"/><path class="ks-menu-cross" d="m6 6 12 12M18 6 6 18"/></svg></button>
        </div>
        <div class="ks-popover ks-search-panel" id="ks-search-panel" hidden><form class="ks-search-form" action="/search/" method="get" role="search"><label class="visually-hidden" for="s">Поиск по товарам</label><input id="s" type="search" name="text" placeholder="Поиск по товарам" required><button class="ks-icon" type="submit" aria-label="Найти"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 4.5 4.5"/></svg></button></form></div>
        <div class="ks-popover ks-contact-panel" id="ks-contact-panel" hidden><a class="ks-phone-number" href="tel:89054113948">8 (905) 411-39-48</a><?php if (Core::moduleIsActive('form')) { ?><a class="ks-callback" data-fancybox data-type="ajax" data-src="/kontakty/callback/" href="javascript:;">Обратный звонок</a><?php } ?><?php
// Если модуль пользователей сайта доступен
if (Core::moduleIsActive('siteuser')) { ?>
 <div class="ks-account">
  <p>
<?php
				if (is_null(Core_Entity::factory('Siteuser')->getCurrent()))
				{
					?>

                       
                          
                                <a data-fancybox data-type="ajax" data-src="/users/?AjaxАuthorization" href="javascript:;">Личный кабинет</a><br/>
                                <a href="/users/registration/">Регистрация</a>
                          
                       
					<?php
				}
				else
				{?>

                                <a href="/users/">Личный кабинет</a><br>
                                <a href="/users/?action=exit">Выход</a>

<? } ?>
</p></div>
<? } 	?>



</div>
      </div>
    </header>
    <div class="ks-backdrop" hidden></div>

    <div class="page" role="main" id="main-content">
        <div class="container">



		<?php
		ob_start();
        Core_Page::instance()->execute();
        echo klimat_optimize_content(ob_get_clean());
		?>




            <div class="feedback">
                <p><strong>Остались вопросы?</strong> Задайте их сейчас, позвонив по телефону<br> <a href="tel:89054113948">8 (905) 411-39-48</a> или через форму обратной связи</p>
                <a href="/kontakty/feedback/">Написать нам</a>
            </div>
        </div>
    </div>

    <footer class="main-footer">
        <div class="main-footer__top">
            <div class="container">
                <div class="main-footer__logo-box">
                    <a class="main-footer__logo logo" title="Климат-Сервис">
                        <img class="logo__img" src="/assets/audit-20261116/22a35fd77b155587.webp" width="50" height="43" alt="Климат-Сервис">
                        <p class="logo__text">
                            <strong>Климат-Сервис</strong>
                            <span>Интернет-магазин</span>
                        </p>
                    </a>
                    <p class="main-footer__copyright main-footer__copyright--desktop">© 2005-<? echo date("Y"); ?>, «Климат-Сервис» </p>
                    <a class="main-footer__agreement main-footer__agreement--desktop" href="/politics/">Пользовательское соглашение</a>
<button class="ks-cookie-settings main-footer__agreement--desktop" type="button" data-cookie-open>О файлах cookie</button>
                </div>


	<?php
	$Structure_Controller_Show = new Structure_Controller_Show(
		Core_Entity::factory('Site', CURRENT_SITE));

	$Structure_Controller_Show->xsl(
		Core_Entity::factory('Xsl')->getByName('НижнееМенюСайт3')
	)
	->menu(2)
	->show();
	?>


                <ul class="main-footer__user user user--footer">


<?php
// Если модуль пользователей сайта доступен
if (Core::moduleIsActive('siteuser')) { ?>
 <li class="user__item">
 <p class="user__item-text">
<?php
				if (is_null(Core_Entity::factory('Siteuser')->getCurrent()))
				{
					?>


                                <a data-fancybox data-type="ajax" data-src="/users/?AjaxАuthorization" href="javascript:;">Личный кабинет</a><br/>
                                <a href="/users/registration/">Регистрация</a>

					<?php
				}
				else
				{?>


                                <a href="/users/">Личный кабинет</a><br>
                                <a href="/users/registration/">Мои данные</a><br>
                                <a href="/users/order/">Мои заказы</a><br>
                                <a href="/users/?action=exit">Выход</a>
   

<? } ?>
</p></li>
<? } 	?>



                   
                   
                
                    <li class="user__item">
                        <p class="user__item-text">
                            <a href="tel:89054113948">8 (905) 411-39-48</a><br>
                            г. Москва, ул. Перовская, дом 20<br>
                            <a href="mailto:klimat5ervis@yandex.ru">klimat5ervis@yandex.ru</a><br/>
                       

<span class="ks-footer-messengers" role="group" aria-label="Мессенджеры">
  <a href="https://t.me/klimat5servis" aria-label="Написать в Telegram" title="Telegram"><img src="/assets/header-20261116/telegram.svg" width="36" height="36" alt="" loading="lazy" decoding="async"></a>
  <a href="https://max.ru/u/f9LHodD0cOLjX0SKaPjyc-__Xtd96IrNpMNkx4pqxmQmNL8yuBC4ysq5LRg" aria-label="Написать в MAX" title="MAX"><img src="/assets/footer-20261116/max.webp" width="36" height="36" alt="" loading="lazy" decoding="async"></a>
</span>

 </p>

                    </li>


 

 


                </ul>
                <p class="main-footer__copyright main-footer__copyright--mobile">© 2005-<? echo date("Y"); ?>, «Климат-Сервис» </p>
                <a class="main-footer__agreement main-footer__agreement--mobile" href="/politics/">Пользовательское соглашение</a>
<button class="ks-cookie-settings main-footer__agreement--mobile" type="button" data-cookie-open>О файлах cookie</button>
            </div>
        </div>
        <div class="main-footer__bottom">
            <div class="container">
                <p>
                    Вся представленная на сайте информация, касающаяся стоимости,
                    носит информационный характер и ни при каких условиях не является
                    публичной офертой,<br> определяемой положениями Статьи 437 (2)
                    Гражданского кодекса Российской Федерации.
                </p>
            </div>
        </div>
    </footer>


<!-- jQuery UI -->
<script defer src="/images/t8903001/js/jquery-ui.min.js"></script>
<!-- Slick Slider -->
<script defer src="/images/t8903001/js/slick.js"></script>
<!-- Arctic Modal -->
<script defer src="/images/t8903001/js/jquery.arcticmodal-0.3.min.js"></script>
<!-- Masked Input -->
<script defer src="/images/t8903001/js/jquery.maskedinput.min.js"></script>
<!-- Basic Init -->
<script defer src="/images/t8903001/js/basic-init.js"></script>
<!-- Fancybox -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.css" />
<script defer src="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.js"></script>
<!-- Штатные файлы хостцмс -->
<script defer src="/images/t8903001/js/hostcms.js"></script>
<script defer src="/images/t8903001/js/main.js"></script>
<!-- Код для табов -->
<script defer src="/images/t8903001/js/tabs.js"></script>
<!-- Код шаблона  -->
<script defer src="/images/t8903001/js/template.js"></script>
<!-- Комплекты с редактированием  -->
<script defer src="/images/t8903001/js/complect_edit.js"></script>


  
<!-- Yandex.Metrika counter -->
<script type="text/javascript">
    (function(m,e,t,r,i,k,a){
        m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
        m[i].l=1*new Date();
        for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
        k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
    })(window, document,'script','https://mc.yandex.ru/metrika/tag.js?id=106704540', 'ym');

    ym(106704540, 'init', {ssr:true, webvisor:true, clickmap:true, ecommerce:"dataLayer", referrer: document.referrer, url: location.href, accurateTrackBounce:true, trackLinks:true});
</script>
<noscript><div><img src="https://mc.yandex.ru/watch/106704540" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
<!-- /Yandex.Metrika counter -->



<section class="ks-cookie-notice" id="ks-cookie-notice" aria-label="Информация о файлах cookie" hidden>
  <div class="ks-cookie-copy"><p>Используем cookie для работы корзины и статистики посещений.</p><button type="button" class="ks-cookie-details-toggle" aria-expanded="false" aria-controls="ks-cookie-details">Подробнее</button></div>
  <button type="button" class="ks-cookie-dismiss" data-cookie-dismiss>Понятно</button>
  <p id="ks-cookie-details" hidden>Технические cookie помогают сохранять товары в корзине. Яндекс Метрика собирает статистику посещений. Управлять сохранением cookie можно в настройках браузера.</p>
</section>
<style>
/* Messenger launcher — isolated from the header and checkout controls. */
#ks-messenger-widget{--ks-chat-offset:0px;position:fixed;right:calc(24px + env(safe-area-inset-right,0px));bottom:calc(24px + env(safe-area-inset-bottom,0px) + var(--ks-chat-offset));z-index:998;font:600 14px/1.3 "Open Sans",Arial,sans-serif}
#ks-messenger-widget *{box-sizing:border-box}
#ks-messenger-widget .ks-chat-toggle{display:flex;align-items:center;justify-content:center;width:60px;height:60px;padding:0;border:0;border-radius:50%;color:#fff;background:#0089c9;box-shadow:0 6px 24px #036b9b40;cursor:pointer;transition:background .18s,transform .18s}
#ks-messenger-widget .ks-chat-toggle:hover{background:#0373a7;transform:translateY(-2px)}
#ks-messenger-widget .ks-chat-toggle svg{width:40px;height:40px;display:block}
#ks-messenger-widget .ks-chat-close{display:none}
#ks-messenger-widget .ks-chat-toggle[aria-expanded="true"] .ks-chat-bubble{display:none}
#ks-messenger-widget .ks-chat-toggle[aria-expanded="true"] .ks-chat-close{display:block}
#ks-messenger-widget .ks-chat-panel{position:absolute;right:0;bottom:74px;display:flex;flex-direction:column;gap:10px;width:60px;max-width:calc(100vw - 32px);max-height:calc(100vh - 120px - var(--ks-chat-offset));max-height:calc(100dvh - 120px - var(--ks-chat-offset));overflow:auto;padding:4px;animation:ks-chat-in .18s ease-out}
#ks-messenger-widget .ks-chat-panel[hidden]{display:none!important}
#ks-messenger-widget .ks-chat-link{display:flex;align-items:center;justify-content:center;gap:0;min-height:48px;color:#183342;text-decoration:none;flex-shrink:0;border-radius:26px;outline-offset:0}
#ks-messenger-widget .ks-chat-logo{display:flex;align-items:center;justify-content:center;flex:0 0 48px;width:48px;height:48px;border-radius:0;background:transparent;box-shadow:none}
#ks-messenger-widget .ks-chat-logo img,#ks-messenger-widget .ks-chat-logo svg{display:block;width:44px;height:44px;max-width:none}
#ks-messenger-widget .ks-chat-toggle:focus-visible,#ks-messenger-widget .ks-chat-link:focus-visible{outline:3px solid #f5bd27;outline-offset:3px}
@keyframes ks-chat-in{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}
@media(max-width:600px){#ks-messenger-widget{right:calc(16px + env(safe-area-inset-right,0px));bottom:calc(16px + env(safe-area-inset-bottom,0px) + var(--ks-chat-offset))}#ks-messenger-widget .ks-chat-toggle{width:56px;height:56px}#ks-messenger-widget .ks-chat-panel{bottom:68px}}
@media(prefers-reduced-motion:reduce){#ks-messenger-widget .ks-chat-panel{animation:none}#ks-messenger-widget .ks-chat-toggle{transition:none}}
</style>
<div id="ks-messenger-widget">
  <button type="button" class="ks-chat-toggle" aria-label="Открыть мессенджеры" title="Написать нам" aria-expanded="false" aria-controls="ks-chat-panel">
    <svg viewBox="0 0 64 60" aria-hidden="true" focusable="false"><g class="ks-chat-bubble"><path d="M32 4C17.1 4 5 14.1 5 26.5C5 33.1 8.5 39 14.4 43.1L10 55L24.3 48.1C26.8 48.7 29.4 49 32 49C46.9 49 59 38.9 59 26.5C59 14.1 46.9 4 32 4Z" fill="white"/><g fill="#0089c9"><circle cx="23" cy="26.5" r="3"/><circle cx="32" cy="26.5" r="3"/><circle cx="41" cy="26.5" r="3"/></g></g><path class="ks-chat-close" d="m21 19 22 22m0-22L21 41" fill="none" stroke="white" stroke-width="3" stroke-linecap="round"/></svg>
  </button>
  <div id="ks-chat-panel" class="ks-chat-panel" role="group" aria-label="Написать в мессенджере" hidden="hidden">
    <a class="ks-chat-link" href="https://t.me/klimat5servis" target="_blank" rel="noopener noreferrer" aria-label="Написать в Telegram"><span class="ks-chat-logo"><img src="/assets/header-20261116/telegram.svg" width="38" height="38" alt=""/></span></a>
    <a class="ks-chat-link" href="https://max.ru/u/f9LHodD0cOLjX0SKaPjyc-__Xtd96IrNpMNkx4pqxmQmNL8yuBC4ysq5LRg" target="_blank" rel="noopener noreferrer" aria-label="Написать в MAX"><span class="ks-chat-logo"><img src="/assets/footer-20261116/max.webp" width="38" height="38" alt=""/></span></a>
    <a class="ks-chat-link" href="https://wa.me/79054113948" target="_blank" rel="noopener noreferrer" aria-label="Написать в WhatsApp"><span class="ks-chat-logo"><svg viewBox="0 0 32 32" aria-hidden="true" focusable="false"><circle cx="16" cy="16" r="16" fill="#25d366"/><path d="M23.3 8.7a10.2 10.2 0 0 0-16 12.2L6 26l5.2-1.4A10.2 10.2 0 0 0 23.3 8.7ZM16 24a8.4 8.4 0 0 1-4.3-1.2l-.3-.2-3.1.8.8-3-.2-.3A8.3 8.3 0 1 1 16 24Z" fill="#fff"/><path d="M12.4 10.8c-.2-.5-.5-.5-.7-.5h-.6c-.2 0-.6.1-.8.4-.3.3-1.1 1-1.1 2.4s1.1 2.8 1.3 3c.1.2 2.1 3.2 5.2 4.4 2.6 1 3.1.8 3.6.7.6-.1 1.8-.7 2-1.4.3-.7.3-1.3.2-1.4-.1-.1-.3-.2-.6-.4l-2.1-1c-.3-.1-.5-.2-.7.2-.2.3-.8 1-1 1.2-.2.2-.4.2-.7.1-.3-.2-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6l.5-.6.3-.5c.1-.2 0-.4 0-.6l-.8-1.8Z" fill="#fff"/></svg></span></a>
  </div>
</div>
<script>
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
</script>
</body>
</html>