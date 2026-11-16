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
          <div class="ks-messengers" aria-label="Связаться с нами">
            <a href="https://t.me/klimat5servis" aria-label="Написать в Telegram" title="Telegram"><img src="/assets/header-20261116/telegram.svg" width="36" height="36" alt=""></a>
            <a href="https://max.ru/u/f9LHodD0cOLjX0SKaPjyc-__Xtd96IrNpMNkx4pqxmQmNL8yuBC4ysq5LRg" aria-label="Написать в MAX" title="MAX"><img src="/assets/footer-20261116/max.webp" width="36" height="36" alt=""></a>
          </div>
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
</body>
</html>