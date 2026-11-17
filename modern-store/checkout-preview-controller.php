<?php
/** Isolated validation/review only. Canonical checkout libraries must never be included. */
function klimat_checkout_response($status = NULL, array $headers = array())
{
    $response = defined('HOSTCMS') && class_exists('Core_Page', FALSE) ? Core_Page::instance()->response : NULL;
    if ($status !== NULL) {
        if ($response) $response->status($status);
        http_response_code($status);
    }
    foreach ($headers as $name=>$value) {
        if ($response) $response->header($name, $value);
        header($name . ': ' . $value);
    }
}
if (!defined('HOSTCMS') || !defined('KLIMAT_CHECKOUT_PREVIEW')) { klimat_checkout_response(404); exit; }
require_once __DIR__ . '/checkout-public-data.php';
require_once __DIR__ . '/checkout-view.php';
function klimat_checkout_preview_run($selfPath)
{
    if ($selfPath !== '/modern-cart/?view=checkout') { klimat_checkout_response(404); return; }
    klimat_checkout_response(200, array(
        'Cache-Control'=>'private, no-store, max-age=0',
        'Pragma'=>'no-cache',
        'X-Robots-Tag'=>'noindex, nofollow, noarchive',
        'Referrer-Policy'=>'no-referrer',
        'X-Content-Type-Options'=>'nosniff',
        'Content-Security-Policy'=>"default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; connect-src 'none'; form-action 'self'; base-uri 'none'; object-src 'none'; frame-ancestors 'self'"
    ));
    $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
    if (!in_array($method,array('GET','POST'),TRUE)) { klimat_checkout_response(405, array('Allow'=>'GET, POST')); return; }
    if (count($_GET) !== 1 || !isset($_GET['view']) || !is_string($_GET['view']) || $_GET['view'] !== 'checkout'
        || (int)(isset($_SERVER['CONTENT_LENGTH']) ? $_SERVER['CONTENT_LENGTH'] : 0) > 16384 || $_FILES) {
        klimat_checkout_response(400); echo 'Некорректный запрос.'; return;
    }
    // The only session writes belong to this preview's random CSRF token and expiry.
    Core_Session::start();
    if (!Core_Session::isActive()) { klimat_checkout_response(503); echo 'Проверка временно недоступна.'; return; }
    $state = isset($_SESSION['klimatCheckoutPreview20261117']) ? $_SESSION['klimatCheckoutPreview20261117'] : array();
    if (!is_array($state) || !isset($state['token'],$state['expires']) || !is_string($state['token'])
        || strlen($state['token']) !== 64 || !ctype_xdigit($state['token']) || !is_int($state['expires']) || $state['expires'] < time()) {
        $state = array('token'=>bin2hex(random_bytes(32)), 'expires'=>time()+3600);
        $_SESSION['klimatCheckoutPreview20261117'] = $state;
    }
    $guestCart = isset($_SESSION['hostcmsCart'][1]) && is_array($_SESSION['hostcmsCart'][1]) ? $_SESSION['hostcmsCart'][1] : array();
    Core_Session::close();
    try {
        $data = klimat_checkout_snapshot($guestCart);
    } catch (Throwable $exception) {
        klimat_checkout_response(503);
        echo '<!doctype html><html lang="ru"><meta charset="utf-8"><meta name="robots" content="noindex"><title>Проверка оформления</title><h1>Проверка временно недоступна</h1><p>Корзина сохранена. <a href="/modern-cart/">Вернуться в корзину</a></p></html>';
        return;
    }
    $values = array('name'=>'','phone'=>'','email'=>'','address'=>'','description'=>'','delivery'=>'','payment'=>'');
    $errors = array(); $review = FALSE;
    if ($method === 'GET' && $data['deliveries']) {
        $values['delivery'] = (string)array_key_first($data['deliveries']);
        $values['payment'] = (string)array_key_first($data['deliveries'][$values['delivery']]['payments']);
    }
    if ($method === 'POST') {
        $allowed = array('preview_action','preview_csrf','name','phone','email','address','description','delivery');
        foreach ($data['deliveries'] as $conditionId=>$option) $allowed[] = 'payment_for_'.$conditionId;
        foreach ($_POST as $key=>$value) {
            if (!in_array($key,$allowed,TRUE) || !is_string($value)) { klimat_checkout_response(400); echo 'Некорректный запрос.'; return; }
        }
        if (!isset($_POST['preview_action'],$_POST['preview_csrf']) || $_POST['preview_action'] !== 'validate'
            || !hash_equals($state['token'],$_POST['preview_csrf'])) {
            klimat_checkout_response(403); echo 'Срок проверки истёк. Откройте страницу заново из корзины.'; return;
        }
        $limits = array('name'=>120,'phone'=>32,'email'=>254,'address'=>300,'description'=>1000,'delivery'=>12);
        foreach ($limits as $key=>$limit) {
            $value = isset($_POST[$key]) ? trim($_POST[$key]) : '';
            if (!mb_check_encoding($value,'UTF-8') || mb_strlen($value,'UTF-8') > $limit || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/',$value)) {
                $errors[$key] = 'Поле содержит недопустимые данные.';
            } else $values[$key] = $value;
        }
        if ($values['name'] === '') $errors['name'] = 'Укажите ваше имя.';
        $digits = preg_replace('/\D/','',$values['phone']);
        if (!preg_match('/^[+0-9() .-]+$/',$values['phone']) || strlen($digits) < 10 || strlen($digits) > 15) $errors['phone'] = 'Укажите телефон: от 10 до 15 цифр.';
        if (!filter_var($values['email'],FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Укажите действующий адрес электронной почты.';
        if (!isset($data['deliveries'][$values['delivery']])) $errors['delivery'] = 'Выберите доступный способ доставки.';
        else {
            $paymentKey = 'payment_for_'.$values['delivery'];
            $values['payment'] = isset($_POST[$paymentKey]) ? $_POST[$paymentKey] : '';
            if (!isset($data['deliveries'][$values['delivery']]['payments'][$values['payment']])) $errors['payment'] = 'Выберите доступный способ оплаты.';
        }
        if (!$data['items']) $errors['cart'] = 'Добавьте товар в корзину.';
        $review = !$errors;
        if ($errors) klimat_checkout_response(422);
    }
    klimat_checkout_view($data,$values,$errors,$review,$state['token'],$selfPath);
}
