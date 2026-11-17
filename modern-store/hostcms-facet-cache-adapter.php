<?php
// Proposal only. Require after normal HostCMS bootstrap; do not use as a public endpoint.
if (!defined('HOSTCMS')) { http_response_code(404); exit; }
require_once __DIR__ . '/FacetOptionsCache.php';

/**
 * Returns three raw string-option arrays, or NULL to keep the exact existing native path.
 * Brands, selected values, product restrictions, prices, cart and XML remain outside the cache.
 */
function klimat_public_facet_values_20261117($shop, array $shopScopedDefinitions, string $provisionedDirectory): ?array
{
    if (!defined('CURRENT_SITE') || (int) CURRENT_SITE !== 2 || (int) $shop->id !== 1 || (int) $shop->site_id !== 2) return null;
    $expected = array(22 => 'Инверторное управление', 28 => 'Охлаждающая способность, тыс. btu', 39 => 'Wi-fi управление');
    // Reuse the native shop-scoped findAll() array; never change its ORM/query state.
    $definitions = array(); $seen = array();
    foreach ($shopScopedDefinitions as $property) {
        if (!is_object($property)) return null;
        $id = (int) $property->id;
        if (!isset($expected[$id])) continue;
        if (isset($seen[$id]) || trim((string) $property->name) !== $expected[$id] || (int) $property->type !== 1) return null;
        $seen[$id] = true;
        $definitions[] = array('id' => $id, 'name' => trim((string) $property->name), 'type' => 1, 'list_id' => (int) $property->list_id);
    }
    if (count($definitions) !== 3) return null;
    $key = KlimatFacetOptionsCache20261117::key(2, 1, 'string-facets-v1', $definitions);
    $cache = new KlimatFacetOptionsCache20261117($provisionedDirectory, 300);
    return $cache->remember($key, static function () use ($expected) {
        $values = array();
        foreach ($expected as $id => $unusedName) {
            $rows = Core_QueryBuilder::select('value')->from('property_value_strings')
                ->where('property_id', '=', $id)->where('value', '!=', '')
                ->groupBy('value')->orderBy('value', 'ASC')->limit(200)->execute()->asAssoc()->result();
            $values[$id] = array();
            foreach ($rows as $row) {
                if (mb_strlen($row['value'], 'UTF-8') <= 80) $values[$id][] = $row['value'];
            }
        }
        return $values;
    });
}
