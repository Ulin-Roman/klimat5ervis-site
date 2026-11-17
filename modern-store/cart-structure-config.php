<?php
Core_Page::instance()->staticCache(FALSE);
// Cart preview uses the existing cart controller, never order/payment handlers.
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: private, no-store');
Core_Session::start();
$shop = Core_Entity::factory('Shop', 1);
$cartPath = Core_Page::instance()->structure->getPath();
if (empty($_SESSION['ksModernCartCsrf'])) $_SESSION['ksModernCartCsrf'] = bin2hex(random_bytes(24));
$cartController = Shop_Cart_Controller::instance();
$cartMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = isset($_POST['cart_token']) && is_string($_POST['cart_token']) ? $_POST['cart_token'] : '';
    if (!hash_equals($_SESSION['ksModernCartCsrf'], $token)) {
        $cartMessage = 'Обновите страницу и повторите действие.';
        $_SESSION['ksModernCartMessage'] = $cartMessage;
    } else {
        $rows = $cartController->getAll($shop);
        $remove = isset($_POST['remove']) && is_scalar($_POST['remove']) ? (int) $_POST['remove'] : 0;
        foreach ($rows as $row) {
            if ($remove && $remove === (int) $row->shop_item_id) {
                $cartController->clear()->shop_item_id($row->shop_item_id)->delete();
            } elseif (!$remove && isset($_POST['recount'])) {
                $key = 'quantity_' . $row->shop_item_id;
                $raw = isset($_POST[$key]) && is_scalar($_POST[$key]) ? trim((string) $_POST[$key]) : '';
                if ($raw === '' || !is_numeric($raw) || !is_finite((float) $raw) || (float) $raw < 0 || (float) $raw > 9999) {
                    $cartMessage = 'Укажите корректное количество от 0 до 9999.';
                    continue;
                }
                if ((float) $raw === 0.0) {
                    $cartController->clear()->shop_item_id($row->shop_item_id)->delete();
                    continue;
                }
                $cartController->clear()->checkStock(FALSE)->shop_item_id($row->shop_item_id)
                    ->quantity((float) $raw)->postpone($row->postpone)->shop_warehouse_id($row->shop_warehouse_id)->update();
                if ($cartController->getLastError()) $cartMessage = 'Не удалось изменить количество. Проверьте товар в основном магазине.';
            }
        }
        if (!$remove && isset($_POST['recount']) && isset($_POST['coupon_text']) && is_string($_POST['coupon_text'])) {
            $_SESSION['hostcmsOrder']['coupon_text'] = trim(mb_substr($_POST['coupon_text'], 0, 200, 'UTF-8'));
        }
        $_SESSION['ksModernCartMessage'] = $cartMessage;
        header('Location: ' . $cartPath, TRUE, 303);
        exit;
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['add']) && is_scalar($_GET['add'])) {
    $id = (int) $_GET['add'];
    $raw = isset($_GET['count']) && is_scalar($_GET['count']) ? $_GET['count'] : 1;
    if ($id > 0 && is_numeric($raw) && is_finite((float) $raw) && (float) $raw > 0 && (float) $raw <= 9999) {
        $cartController->clear()->checkStock(FALSE)->shop_item_id($id)->quantity((float) $raw)->add();
        if ($cartController->getLastError()) $cartMessage = 'Не удалось добавить товар. Проверьте его доступность в основном магазине.';
    } else {
        $cartMessage = 'Проверьте товар и его количество.';
    }
    $_SESSION['ksModernCartMessage'] = $cartMessage;
    header('Location: ' . $cartPath, TRUE, 303);
    exit;
}
?>