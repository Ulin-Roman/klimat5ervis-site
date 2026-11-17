<?php
/** Public, guest-only projection. No Show controllers and no canonical checkout includes. */
if (!defined('HOSTCMS') || !defined('KLIMAT_CHECKOUT_PREVIEW')) {
    if (defined('HOSTCMS') && class_exists('Core_Page', FALSE)) Core_Page::instance()->response->status(404);
    http_response_code(404); exit;
}
function klimat_checkout_public_item($item, $shop, array $itemChain = array())
{
    if (!$item->loaded() || (int)$item->shop_id !== 1 || !(int)$item->active) return FALSE;
    if (isset($itemChain[(int)$item->id]) || count($itemChain) >= 20) return FALSE;
    $itemChain[(int)$item->id] = TRUE;
    if ((int)$item->modification_id) {
        $baseItem = Core_Entity::factory('Shop_Item')->find((int)$item->modification_id);
        if (!klimat_checkout_public_item($baseItem, $shop, $itemChain)) return FALSE;
    }
    $now = date('Y-m-d H:i:s');
    if ($item->start_datetime !== '0000-00-00 00:00:00' && $item->start_datetime > $now) return FALSE;
    if ($item->end_datetime !== '0000-00-00 00:00:00' && $item->end_datetime < $now) return FALSE;
    // Resolve public inheritance with a bound; never load a customer or their groups.
    $access = (int)$item->siteuser_group_id;
    $parentId = (int)$item->shop_group_id;
    $seen = array();
    for ($depth = 0; $parentId && $depth < 50; $depth++) {
        if (isset($seen[$parentId])) return FALSE;
        $seen[$parentId] = TRUE;
        $group = Core_Entity::factory('Shop_Group')->find($parentId);
        if (!$group->loaded() || (int)$group->shop_id !== 1 || !(int)$group->active || (int)$group->siteuser_group_id > 0) return FALSE;
        if ($access === -1) $access = (int)$group->siteuser_group_id;
        $parentId = (int)$group->parent_id;
    }
    if ($parentId) return FALSE;
    if ($access === -1) $access = (int)$shop->siteuser_group_id;
    return $access === 0;
}
function klimat_checkout_number($value)
{
    if (!is_numeric($value) || !is_finite((float)$value) || (float)$value < 0 || (float)$value > 1000000000) {
        throw new RuntimeException('Unsupported public amount');
    }
    return (float)$value;
}
/** Native public base/quantity price, then native currency conversion and public tax rules.
 * Do not use getPrices(): an active Shop_Discount::isActive() reads the current customer.
 */
function klimat_checkout_public_price($item, $shop, $quantity)
{
    $calculator = new Shop_Item_Controller();
    $calculator->siteuser(NULL)->count($quantity);
    $price = $calculator->getSpecialprice($calculator->getPrice($item), $item);
    $price = klimat_checkout_number($price);
    if (!(int)$item->Shop_Currency->id || !(int)$shop->Shop_Currency->id) throw new RuntimeException('Currency unavailable');
    $price *= Shop_Controller::instance()->getCurrencyCoefficientInShopCurrency($item->Shop_Currency, $shop->Shop_Currency);
    if ((int)$item->shop_tax_id) {
        $tax = $item->Shop_Tax;
        if ((int)$tax->id && !(int)$tax->tax_is_included) $price += $price * klimat_checkout_number($tax->rate) / 100;
    }
    return klimat_checkout_number(Shop_Controller::instance()->round($price));
}
function klimat_checkout_snapshot(array $guestCart)
{
    if (!defined('CURRENT_SITE') || (int)CURRENT_SITE !== 2) throw new RuntimeException('Invalid site context');
    $shop = Core_Entity::factory('Shop')->find(1);
    if (!$shop->loaded() || (int)$shop->site_id !== 2 || (int)$shop->siteuser_group_id !== 0) throw new RuntimeException('Shop unavailable');
    if (count($guestCart) > 100) throw new RuntimeException('Cart exceeds preview limit');
    $currency = $shop->Shop_Currency;
    if (!(int)$currency->id) throw new RuntimeException('Currency unavailable');
    $data = array('items'=>array(), 'deliveries'=>array(), 'subtotal'=>0.0,
        'currency'=>$currency->code === 'RUB' ? '₽' : (string)$currency->code,
        'pricing_note'=>'Предварительная цена без акционных скидок, купонов, подарков, бонусов и персональных условий. Окончательные условия — в действующем оформлении.');
    $weight = 0.0;
    // Isolated price calculation; do not inherit a canonical checkout coupon.
    Shop_Item_Controller::coupon('');
    foreach ($guestCart as $itemId=>$row) {
        if (!is_array($row) || !ctype_digit((string)$itemId) || (int)$itemId < 1) throw new RuntimeException('Invalid cart shape');
        // Default driver keeps warehouse ID inside each numeric item row, never in the key.
        $warehouse = isset($row['shop_warehouse_id']) ? $row['shop_warehouse_id'] : 0;
        if ((!is_int($warehouse) && !is_string($warehouse)) || !ctype_digit((string)$warehouse)) throw new RuntimeException('Unsupported warehouse shape');
        if (!empty($row['postpone'])) continue;
        $quantity = isset($row['quantity']) ? klimat_checkout_number($row['quantity']) : 0;
        if ($quantity <= 0) continue;
        if ($quantity > 1000) throw new RuntimeException('Quantity exceeds preview limit');
        $item = Core_Entity::factory('Shop_Item')->find((int)$itemId);
        if (!klimat_checkout_public_item($item, $shop)) continue;
        // Base/quantity pricing avoids discount eligibility's implicit customer lookup.
        $unit = klimat_checkout_public_price($item, $shop, $quantity);
        $line = klimat_checkout_number(Shop_Controller::instance()->round($unit * $quantity));
        $image = '';
        $file = (string)$item->image_small !== '' ? (string)$item->image_small : (string)$item->image_large;
        if ($file !== '' && basename($file) === $file) $image = $item->getItemHref() . rawurlencode($file);
        $data['items'][] = array('id'=>(int)$item->id,'name'=>(string)$item->name,'quantity'=>$quantity,
            'unit_price'=>$unit,'line_total'=>$line,'image'=>$image,'warehouse_id'=>(int)$warehouse);
        $data['subtotal'] += $line;
        $weight += klimat_checkout_number($item->weight) * $quantity;
    }
    $data['subtotal'] = klimat_checkout_number(Shop_Controller::instance()->round($data['subtotal']));
    if (!$data['items']) return $data;
    $deliveries = $shop->Shop_Deliveries;
    $deliveries->queryBuilder()
        ->select('shop_deliveries.id','shop_deliveries.shop_id','shop_deliveries.name','shop_deliveries.type','shop_deliveries.active','shop_deliveries.sorting')
        ->where('shop_deliveries.active','=',1)->where('shop_deliveries.type','=',0)
        ->join('shop_delivery_siteuser_groups','shop_delivery_siteuser_groups.shop_delivery_id','=','shop_deliveries.id')
        ->where('shop_delivery_siteuser_groups.siteuser_group_id','=',0)->groupBy('shop_deliveries.id');
    foreach ($deliveries->findAll() as $delivery) {
        $conditions = new Shop_Delivery_Condition_Controller();
        $condition = $conditions->shop_country_id(0)->shop_country_location_id(0)->shop_country_location_city_id(0)
            ->shop_country_location_city_area_id(0)->timeFrom('')->timeTo('')->totalWeight($weight)
            ->totalAmount($data['subtotal'])->getShopDeliveryCondition($delivery);
        if (!$condition || !(int)$condition->id) continue;
        $conditionPrices = $condition->getPriceArray();
        // A fresh model prevents relation/query conditions leaking between delivery options.
        $payments = new Shop_Payment_System_Model();
        $payments->queryBuilder()
            ->select('shop_payment_systems.id','shop_payment_systems.name','shop_payment_systems.sorting')
            ->where('shop_payment_systems.shop_id','=',1)
            ->where('shop_payment_systems.active','=',1)
            ->join('shop_delivery_payment_systems','shop_delivery_payment_systems.shop_payment_system_id','=','shop_payment_systems.id')
            ->where('shop_delivery_payment_systems.shop_delivery_id','=',(int)$delivery->id)
            ->join('shop_payment_system_siteuser_groups','shop_payment_system_siteuser_groups.shop_payment_system_id','=','shop_payment_systems.id')
            ->where('shop_payment_system_siteuser_groups.siteuser_group_id','=',0)->groupBy('shop_payment_systems.id');
        $publicPayments = array();
        foreach ($payments->findAll() as $payment) {
            $publicPayments[(int)$payment->id] = array('id'=>(int)$payment->id,'name'=>(string)$payment->name);
        }
        if (!$publicPayments) continue;
        $data['deliveries'][(int)$condition->id] = array('id'=>(int)$delivery->id,'condition_id'=>(int)$condition->id,
            'name'=>(string)$delivery->name,'price'=>klimat_checkout_number($conditionPrices['price_tax']),'payments'=>$publicPayments);
    }
    return $data;
}
