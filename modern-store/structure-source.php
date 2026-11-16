<?php
// Modern catalog preview. Existing shop, cart and production templates are preserved.
$getScalar = static function ($key) {
    return isset($_GET[$key]) && is_scalar($_GET[$key]) ? trim((string) $_GET[$key]) : '';
};
if ($getScalar('view') === 'responsive') {
    echo <<<'RESPONSIVE_HTML'
<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Проверка адаптивности нового каталога</title><style>body{margin:0;font:14px Arial,sans-serif;background:#edf2f5;color:#182c37}header{padding:14px 20px;background:white;border-bottom:1px solid #ccd8df;display:flex;gap:18px;align-items:center;flex-wrap:wrap}label{display:flex;gap:10px;align-items:center}select{padding:8px}a{color:#007d9f}#stage{overflow:auto;padding:20px}iframe{display:block;width:390px;height:820px;border:1px solid #cbd8df;background:#fff}output{font-size:12px}</style></head><body><header><strong>Проверка адаптивности каталога</strong><label>Ширина<select id="width" aria-label="Ширина экрана"><option>320</option><option>375</option><option selected>390</option><option>480</option><option>768</option><option>1024</option><option>1280</option><option>1440</option><option>1920</option></select></label><output id="result" role="status">Загрузка…</output><a href="/modern-catalog/">Открыть каталог</a></header><div id="stage"><iframe id="frame" title="Новый каталог" src="/modern-catalog/?q=Ballu&amp;min=30000&amp;max=40000&amp;sort=price-up"></iframe></div><script>const f=document.getElementById('frame'),w=document.getElementById('width'),r=document.getElementById('result');function report(){const d=f.contentDocument;if(!d)return;const html=d.documentElement;r.textContent='Видимая ширина: '+html.clientWidth+' · ширина страницы: '+html.scrollWidth+' · карточек: '+d.querySelectorAll('.product').length+' · H1: '+d.querySelectorAll('h1').length+(html.scrollWidth<=html.clientWidth?' · без горизонтальной прокрутки':' · есть переполнение')}w.addEventListener('change',()=>{f.style.width=w.value+'px';requestAnimationFrame(report)});f.addEventListener('load',report);</script></body></html>
RESPONSIVE_HTML;
    return;
}
$q = mb_substr($getScalar('q'), 0, 100, 'UTF-8');
$sort = $getScalar('sort');
if (!in_array($sort, array('name', 'price-up', 'price-down'), TRUE)) $sort = 'name';
$priceValue = static function ($raw) {
    return $raw !== '' && is_numeric($raw) && is_finite((float) $raw)
        ? (string) min(100000000, max(0, (float) $raw)) : '';
};
$min = $priceValue($getScalar('min'));
$max = $priceValue($getScalar('max'));
$page = min(1000, max(1, (int) $getScalar('page')));
$shop = Core_Entity::factory('Shop', 1);
$c = new Shop_Controller_Show($shop);
$c->group(FALSE)->limit(24)->page($page - 1)->offset(($page - 1) * 24)
    ->groupsMode('none')->itemsProperties(FALSE)->comments(FALSE)->tags(FALSE)
    ->associatedItems(FALSE)->modifications(TRUE)->specialprices(TRUE)
    ->viewed(FALSE)->favorite(FALSE);
if ($q !== '') {
    $c->shopItems()->queryBuilder()->where('shop_items.name', 'LIKE', '%' . addcslashes($q, '\\%_') . '%');
}
$prices = array();
if ($min !== '') $prices['price_from'] = (float) $min;
if ($max !== '') $prices['price_to'] = (float) $max;
if ($prices) $c->setFilterPricesConditions($prices);
if ($sort === 'name') $c->orderBy('shop_items.name', 'ASC');
else $c->orderBy('absolute_price', $sort === 'price-up' ? 'ASC' : 'DESC');
$query = '/modern-catalog/?' . http_build_query(array('q' => $q, 'min' => $min, 'max' => $max, 'sort' => $sort)) . '&page=';
foreach (array('modern_q' => $q, 'modern_min' => $min, 'modern_max' => $max, 'modern_sort' => $sort, 'modern_query' => $query) as $name => $value) {
    $c->addEntity(Core::factory('Core_Xml_Entity')->name($name)->value($value));
}
echo '<!doctype html>';
$c->xsl(Core_Entity::factory('Xsl')->getByName('ModernCatalog2026'))->show();
?>