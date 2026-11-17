<?php
// Curated storefront preview. Prices and visibility remain native to shop1.
$base = Core_Page::instance()->structure->getPath();
$requestPath = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if (rtrim($requestPath, '/') !== rtrim($base, '/')) {
    Core_Page::instance()->response->status(404);
    echo '<!doctype html><html lang="ru"><meta charset="utf-8"><title>Страница не найдена</title><h1>Страница не найдена</h1><a href="' . htmlspecialchars($base, ENT_QUOTES, 'UTF-8') . '">На главную магазина</a></html>';
    return;
}
$shop = Core_Entity::factory('Shop', 1);
$c = new Shop_Controller_Show($shop);
$c->pattern(rawurldecode(Core_Str::rtrimUri($base)) . '({path}/)(page-{page}/)')->limit(8)->parseUrl();
if (Core_Page::instance()->response->getStatus() >= 400) return;
$c->group(FALSE)->cache(FALSE)->showPanel(FALSE)->groupsMode('none')->comments(FALSE)->tags(FALSE)
    ->associatedItems(FALSE)->modifications(TRUE)->specialprices(TRUE)->viewed(FALSE)->favorite(FALSE);
// Same eight models selected in the original homepage; no popularity claim.
$c->shopItems()->queryBuilder()->where('shop_items.id', 'IN', array(7715, 7972, 8375, 7195, 7531, 8103, 8104, 9356));
$c->orderBy('absolute_price', 'ASC');
$c->shopItems()->queryBuilder()->orderBy('shop_items.id', 'ASC');
$labels = array('Инверторное управление' => 'Инвертор', 'Wi-fi управление' => 'Wi-Fi', 'Охлаждающая способность, тыс. btu' => 'Мощность, тыс. BTU');
$fields = Core::factory('Core_Xml_Entity')->name('modern_filters');
$ids = array();
foreach (Core_Entity::factory('Shop_Item_Property_List', (int) $shop->id)->Properties->findAll() as $definition) {
    $name = trim($definition->name);
    if (isset($labels[$name])) {
        $ids[] = (int) $definition->id;
        $fields->addEntity(Core::factory('Core_Xml_Entity')->name('field')->addAttribute('property_id', (string) $definition->id)->addAttribute('label', $labels[$name]));
    }
}
$c->itemsProperties($ids ?: FALSE)->itemsPropertiesList($ids ?: FALSE)->addEntity($fields);
$quantity = 0;
foreach (Shop_Cart_Controller::instance()->getAll($shop) as $row) { if (!$row->postpone) $quantity += $row->quantity; }
$c->addEntity(Core::factory('Core_Xml_Entity')->name('modern_cart_count')->value($quantity));
echo '<!doctype html>';
ob_start();
$c->xsl(Core_Entity::factory('Xsl')->getByName('ModernHome2026'))->show();
$ksImageHtml = ob_get_clean();

// Optional responsive photographs: guarded CMS manifest; native source remains the fallback.
$ksImageHelper = realpath(defined('CMS_FOLDER') ? CMS_FOLDER : $_SERVER['DOCUMENT_ROOT']) . '/hostcmsfiles/.klimat-image-build-20261117/images-render.php';
if (is_file($ksImageHelper) && !is_link($ksImageHelper)) require_once $ksImageHelper;
echo function_exists('klimat_modern_image_html') ? klimat_modern_image_html($ksImageHtml) : $ksImageHtml;

?>