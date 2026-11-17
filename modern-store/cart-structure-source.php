<?php
if (array_key_exists('view', $_GET)) {
    Core_Page::instance()->staticCache(FALSE);
    // Native frontend sends Core_Response after PHP headers; update both.
    $ksCheckoutRespond = static function ($status = NULL, array $headers = array()) {
        $response = Core_Page::instance()->response;
        if ($status !== NULL) {
            $response->status($status);
            http_response_code($status);
        }
        foreach ($headers as $name=>$value) {
            $response->header($name, $value);
            header($name . ': ' . $value);
        }
    };
    $ksCheckoutRespond(NULL, array(
        'X-Robots-Tag'=>'noindex, nofollow, noarchive',
        'Cache-Control'=>'private, no-store, max-age=0',
        'Pragma'=>'no-cache',
        'Referrer-Policy'=>'no-referrer',
        'X-Content-Type-Options'=>'nosniff',
        'Content-Security-Policy'=>"default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; connect-src 'none'; form-action 'self'; base-uri 'none'; object-src 'none'; frame-ancestors 'self'"
    ));
    ini_set('display_errors', '0');
    if (!defined('CURRENT_SITE') || (int) CURRENT_SITE !== 2
        || count($_GET) !== 1 || !is_string($_GET['view']) || $_GET['view'] !== 'checkout') {
        $ksCheckoutRespond(400);
        echo 'Некорректный запрос.';
        return;
    }
    $ksCheckoutHelper = CMS_FOLDER . 'hostcmsfiles/.klimat-checkout-preview-20261117/checkout-preview-controller.php';
    if (!is_file($ksCheckoutHelper) || is_link($ksCheckoutHelper)) {
        $ksCheckoutRespond(503);
        echo 'Проверка оформления временно недоступна.';
        return;
    }
    $ksCheckoutBufferLevel = ob_get_level();
    try {
        if (!defined('KLIMAT_CHECKOUT_PREVIEW')) define('KLIMAT_CHECKOUT_PREVIEW', TRUE);
        require_once $ksCheckoutHelper;
        ob_start();
        klimat_checkout_preview_run('/modern-cart/?view=checkout');
        $ksCheckoutHtml = ob_get_clean();
        $ksCheckoutImageHelper = CMS_FOLDER . 'hostcmsfiles/.klimat-image-build-20261117/images-render.php';
        try {
            if (is_file($ksCheckoutImageHelper) && !is_link($ksCheckoutImageHelper)) require_once $ksCheckoutImageHelper;
            if (function_exists('klimat_modern_image_html')) $ksCheckoutHtml = klimat_modern_image_html($ksCheckoutHtml);
        } catch (Throwable $ksCheckoutImageError) {
            // Optional image enhancement: the original generated HTML remains the fallback.
        }
        echo $ksCheckoutHtml;
    } catch (Throwable $ksCheckoutError) {
        while (ob_get_level() > $ksCheckoutBufferLevel) ob_end_clean();
        $ksCheckoutRespond(503);
        echo 'Проверка оформления временно недоступна.';
    }
    return;
}
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