<?php
Core_Session::start();
$shop = Core_Entity::factory('Shop', 1);
$controller = new Shop_Cart_Controller_Show($shop);
$message = isset($_SESSION['ksModernCartMessage']) ? $_SESSION['ksModernCartMessage'] : '';
unset($_SESSION['ksModernCartMessage']);
if (isset($cartMessage) && $cartMessage !== '') $message = $cartMessage;
$controller->xsl(Core_Entity::factory('Xsl')->getByName('ModernCart2026'))
    ->cartUrl(Core_Page::instance()->structure->getPath())
    ->itemsProperties(FALSE)->itemsPropertiesList(FALSE)->associatedItems(FALSE)
    ->couponText(Core_Str::stripTags(Core_Array::get(Core_Array::getSession('hostcmsOrder', array()), 'coupon_text')))
    ->addEntity(Core::factory('Core_Xml_Entity')->name('modern_cart_path')->value(Core_Page::instance()->structure->getPath()))
    ->addEntity(Core::factory('Core_Xml_Entity')->name('modern_cart_token')->value($_SESSION['ksModernCartCsrf']))
    ->addEntity(Core::factory('Core_Xml_Entity')->name('modern_cart_message')->value($message))
    ->addEntity(Core::factory('Core_Xml_Entity')->name('minAllPrice')->value(0));
echo '<!doctype html>';
ob_start();
$controller->show();
$ksImageHtml = ob_get_clean();

// Optional responsive photographs: guarded CMS manifest; native source remains the fallback.
$ksImageHelper = realpath(defined('CMS_FOLDER') ? CMS_FOLDER : $_SERVER['DOCUMENT_ROOT']) . '/hostcmsfiles/.klimat-image-build-20261117/images-render.php';
if (is_file($ksImageHelper) && !is_link($ksImageHelper)) require_once $ksImageHelper;
echo function_exists('klimat_modern_image_html') ? klimat_modern_image_html($ksImageHtml) : $ksImageHtml;

?>