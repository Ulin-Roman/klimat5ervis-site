<?php
// Modern catalog preview. Existing shop, cart and production templates are preserved.
$getScalar = static function ($key) {
    return isset($_GET[$key]) && is_scalar($_GET[$key]) ? trim((string) $_GET[$key]) : '';
};
if ($getScalar('view') === 'responsive') {
    echo <<<'RESPONSIVE_HTML'
<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Проверка адаптивности нового каталога</title><style>body{margin:0;font:14px Arial,sans-serif;background:#edf2f5;color:#182c37}header{padding:14px 20px;background:white;border-bottom:1px solid #ccd8df;display:flex;gap:18px;align-items:center;flex-wrap:wrap}label{display:flex;gap:10px;align-items:center}select{padding:8px}a{color:#007d9f}#stage{overflow:auto;padding:20px}iframe{display:block;width:390px;height:820px;border:1px solid #cbd8df;background:#fff}output{font-size:12px}</style></head><body><header><strong>Проверка адаптивности каталога</strong><label>Ширина<select id="width" aria-label="Ширина экрана"><option>320</option><option>375</option><option selected>390</option><option>480</option><option>768</option><option>1024</option><option>1280</option><option>1440</option><option>1920</option></select></label><label>Страница<select id="page" aria-label="Тип страницы"><option value="catalog">Каталог</option><option value="category">Категория</option><option value="product">Товар</option><option value="home">Главная магазина</option><option value="live-product">Товар магазина</option><option value="cart">Корзина магазина</option><option value="modern-cart">Новая корзина</option><option value="modern-home">Новая главная</option></select></label><label>Высота<select id="height" aria-label="Высота экрана"><option>480</option><option selected>820</option></select></label><output id="result" role="status">Загрузка…</output><a href="/modern-catalog/">Открыть каталог</a></header><div id="stage"><iframe id="frame" title="Новый каталог" src="/modern-catalog/?q=Ballu&amp;min=30000&amp;max=40000&amp;sort=price-up"></iframe></div><script>const f=document.getElementById('frame'),w=document.getElementById('width'),r=document.getElementById('result'),p=document.getElementById('page');const paths={catalog:'/modern-catalog/?q=Ballu&min=30000&max=40000&sort=price-up',category:'/modern-catalog/kassetnye-split-sistemy/',product:'/modern-catalog/nastennye-split-sistemy/7715/',home:'/', 'live-product':'/category/nastennye-split-sistemy/7715/',cart:'/category/cart/','modern-cart':'/modern-cart/','modern-home':'/modern-home/'};p.addEventListener('change',()=>{r.textContent='Загрузка…';f.src=paths[p.value]});function report(){const d=f.contentDocument;if(!d)return;const html=d.documentElement;r.textContent='Видимая ширина: '+html.clientWidth+' · ширина страницы: '+html.scrollWidth+' · карточек: '+d.querySelectorAll('.product').length+' · H1: '+d.querySelectorAll('h1').length+(html.scrollWidth<=html.clientWidth?' · без горизонтальной прокрутки':' · есть переполнение')}w.addEventListener('change',()=>{f.style.width=w.value+'px';requestAnimationFrame(report)});document.getElementById('height').addEventListener('change',function(){f.style.height=this.value+'px';report()});f.addEventListener('load',report);</script></body></html>
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
$base = Core_Page::instance()->structure->getPath();
$c->pattern(rawurldecode(Core_Str::rtrimUri($base)) . '({path}/)(page-{page}/)')->limit(24)->parseUrl();
if (Core_Page::instance()->response->getStatus() >= 400) return;
if (!$c->item && !$c->group) $c->group(FALSE);
if (!$c->item && $getScalar('page') !== '') $c->page($page - 1)->offset(($page - 1) * 24);
$c
    ->cache(FALSE)->showPanel(FALSE)->groupsMode('none')->itemsProperties(FALSE)->comments(FALSE)->tags(FALSE)
    ->associatedItems(FALSE)->modifications(TRUE)->specialprices(TRUE)
    ->viewed(FALSE)->favorite(FALSE);
$filterParams = array();
$filterChips = array();
$xmlValue = static function ($name, $value) { return Core::factory('Core_Xml_Entity')->name($name)->value($value); };
if (!$c->item) {
    $brandNode = Core::factory('Core_Xml_Entity')->name('modern_brands');
    $selectedBrand = $getScalar('brand');
    $validBrand = 0;
    $producerModels = $shop->Shop_Producers->getAllByActive(1);
    usort($producerModels, static function ($a, $b) { return strcasecmp($a->name, $b->name); });
    foreach ($producerModels as $producer) {
        $brandNode->addEntity(Core::factory('Core_Xml_Entity')->name('option')->addAttribute('id', (string) $producer->id)->value($producer->name));
        if ((string) $producer->id === $selectedBrand) {
            $validBrand = (int) $producer->id;
            $filterChips['brand'] = $producer->name;
        }
    }
    $c->addEntity($brandNode)->addEntity($xmlValue('modern_brand', $validBrand));
    if ($validBrand) {
        $filterParams['brand'] = $validBrand;
        $c->shopItems()->queryBuilder()->where('shop_items.shop_producer_id', '=', $validBrand);
    }
    $filterNode = Core::factory('Core_Xml_Entity')->name('modern_filters');
    $fieldDefinitions = array(
        'Инверторное управление' => array('inverter', 'Инвертор'),
        'Wi-fi управление' => array('wifi', 'Wi-Fi'),
        'Охлаждающая способность, тыс. btu' => array('capacity', 'Мощность, тыс. BTU')
    );
    $cardPropertyIds = array();
    $usedFields = array();
    $defs = Core_Entity::factory('Shop_Item_Property_List', (int) $shop->id)->Properties->findAll();
    foreach ($defs as $definition) {
        $definitionName = trim($definition->name);
        if (!in_array((int) $definition->type, array(1, 3), TRUE) || !isset($fieldDefinitions[$definitionName])) continue;
        list($key, $label) = $fieldDefinitions[$definitionName];
        if (isset($usedFields[$key])) continue;
        $usedFields[$key] = TRUE;
        $selectedValue = $getScalar($key);
        $allowed = array();
        if ((int) $definition->type === 3) {
            $options = Core_Entity::factory('List', (int) $definition->list_id)->List_Items->getAllByActive(1);
            foreach ($options as $option) $allowed[(int) $option->id] = $option->value;
        } else {
            // Only public, allowlisted technical property values; no full item scan.
            $options = Core_QueryBuilder::select('value')->from('property_value_strings')
                ->where('property_id', '=', (int) $definition->id)->where('value', '!=', '')
                ->groupBy('value')->orderBy('value', 'ASC')->limit(200)->execute()->asAssoc()->result();
            foreach ($options as $option) {
                if (mb_strlen($option['value'], 'UTF-8') <= 80) $allowed[$option['value']] = $option['value'];
            }
        }
        if ($key === 'capacity') {
            uasort($allowed, static function ($a, $b) {
                $left = (float) str_replace(',', '.', $a);
                $right = (float) str_replace(',', '.', $b);
                return ($left <=> $right) ?: strnatcasecmp($a, $b);
            });
        }
        $selectedId = isset($allowed[$selectedValue]) ? $selectedValue : '';
        if ($selectedId !== '') {
            $filterParams[$key] = $selectedId;
            $filterChips[$key] = $label . ': ' . $allowed[$selectedId];
            $c->addFilter('property', (int) $definition->id, '=', (int) $definition->type === 3 ? (int) $selectedId : $selectedId)->filterStrictMode(TRUE);
        }
        $fieldNode = Core::factory('Core_Xml_Entity')->name('field')
            ->addAttribute('name', $key)->addAttribute('label', $label)
            ->addAttribute('property_id', (string) $definition->id)->addAttribute('selected', (string) $selectedId);
        foreach ($allowed as $optionId => $optionLabel) {
            $fieldNode->addEntity(Core::factory('Core_Xml_Entity')->name('option')->addAttribute('id', (string) $optionId)->value($optionLabel));
        }
        $filterNode->addEntity($fieldNode);
        $cardPropertyIds[] = (int) $definition->id;
    }
    $c->addEntity($filterNode)->itemsProperties($cardPropertyIds ?: FALSE)->itemsPropertiesList($cardPropertyIds ?: FALSE);
}
$cartQuantity = 0;
foreach (Shop_Cart_Controller::instance()->getAll($shop) as $cartRow) {
    if (!$cartRow->postpone) $cartQuantity += $cartRow->quantity;
}
$c->addEntity($xmlValue('modern_cart_count', $cartQuantity));

if (!$c->item) {
if ($q !== '') {
    $c->shopItems()->queryBuilder()->where('shop_items.name', 'LIKE', '%' . addcslashes($q, '\\%_') . '%');
}
$prices = array();
if ($min !== '') $prices['price_from'] = (float) $min;
if ($max !== '') $prices['price_to'] = (float) $max;
if ($prices) $c->setFilterPricesConditions($prices);
if ($sort === 'name') $c->orderBy('shop_items.name', 'ASC');
else $c->orderBy('absolute_price', $sort === 'price-up' ? 'ASC' : 'DESC');
$c->shopItems()->queryBuilder()->orderBy('shop_items.id', 'ASC');
}
if ($c->item) {
    $publicNames = json_decode('["Максимальная длина трассы","Расход воздуха, м³/час","Класс энергоэффективности (охлаждение/обогрев)","Гарантийный срок","Wi-fi управление","Пульт дистанционного управления","Тип внутреннего блока","Диаметр жидкой магистрали, мм","Диаметр газовой магистрали, мм","Диапазон t наружного воздуха (обогрев), °c","Перепад высот","Хладагент","Электропитание","Габаритный размер (внутреннего блока)","Габаритный размер (внешнего блока)","Вес (внешний блок)","Вес (внутренний блок)","Страна сборки","Цвет","Инверторное управление","Уровень шума в/б, дб","Режим работы","Страна бренда","Охлаждающая способность, тыс. btu","Потребляемая мощность (охлаждение)","Потребляемая мощность (обогрев)","Завод","Марка компрессора","Диапазон t наружного воздуха (холод), °c","Мощность охлаждения","Мощность обогрева","Обслуживаемая площадь","Уровень шума","Инвертор","Площадь помещения","Мощность (охлаждение)","Мощность (обогрев)","Площадь","Холодопроизводительность","Теплопроизводительность","Изображения товаров"]', TRUE);
    $publicPropertyIds = array();
    $definitions = Core_Entity::factory('Shop_Item_Property_List', (int) $shop->id)->Properties;
    foreach ($definitions->findAll() as $definition) {
        if (in_array($definition->name, $publicNames, TRUE)
            || in_array($definition->tag_name, array('img', 'dopimg', 'description'), TRUE)) {
            $publicPropertyIds[] = (int) $definition->id;
        }
    }
    $c->itemsProperties($publicPropertyIds ?: FALSE)->itemsPropertiesList($publicPropertyIds ?: FALSE);
}
$formPath = $base;
$groupName = '';
if (!$c->item && $c->group) {
    $group = Core_Entity::factory('Shop_Group', $c->group);
    $formPath .= $group->getPath();
    $groupName = $group->name;
}
$queryParams = array_merge(array('q' => $q, 'min' => $min, 'max' => $max, 'sort' => $sort), $filterParams);
$queryParams = array_filter($queryParams, static function ($value) { return $value !== ''; });
$query = $formPath . '?' . http_build_query($queryParams) . '&page=';
if ($q !== '') $filterChips['q'] = 'Поиск: ' . $q;
if ($min !== '') $filterChips['min'] = 'От ' . number_format((float) $min, 0, ',', ' ') . ' ₽';
if ($max !== '') $filterChips['max'] = 'До ' . number_format((float) $max, 0, ',', ' ') . ' ₽';
$chips = Core::factory('Core_Xml_Entity')->name('modern_chips');
foreach ($filterChips as $key => $label) {
    $remaining = $queryParams;
    unset($remaining[$key]);
    $chips->addEntity(Core::factory('Core_Xml_Entity')->name('chip')->value($label)
        ->addAttribute('href', $formPath . ($remaining ? '?' . http_build_query($remaining) : '')));
}
$c->addEntity($chips);
$searchParams = Core::factory('Core_Xml_Entity')->name('modern_search_params');
foreach ($queryParams as $key => $value) {
    if ($key !== 'q') $searchParams->addEntity(Core::factory('Core_Xml_Entity')->name('param')->addAttribute('name', $key)->value($value));
}
$c->addEntity($searchParams);
foreach (array('modern_item_id' => (int) $c->item, 'modern_group_name' => $groupName, 'modern_form_action' => $formPath, 'modern_q' => $q, 'modern_min' => $min, 'modern_max' => $max, 'modern_sort' => $sort, 'modern_query' => $query) as $name => $value) {
    $c->addEntity(Core::factory('Core_Xml_Entity')->name($name)->value($value));
}
echo '<!doctype html>';
$c->xsl(Core_Entity::factory('Xsl')->getByName('ModernCatalog2026'))->show();
?>