<?php
// Fixed native document; no document/structure IDs or content are accepted from the request.
$infoSourceId = 11;
$infoDocumentId = 9;
$infoSection = 'delivery';
$infoRoute = '/modern-delivery/';
$infoSiteId = 2;
$page = Core_Page::instance();
$fail = static function ($status = 404) {
    Core_Page::instance()->response->status($status);
    http_response_code($status);
    $title = $status === 404 ? 'Страница не найдена' : 'Страница временно недоступна';
    echo '<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="robots" content="noindex,nofollow"><title>' . $title . '</title></head><body><h1>' . $title . '</h1><a href="/modern-home/">На главную магазина</a></body></html>';
};
$base = $page->structure->getPath();
$requestPath = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));
if ((int) $page->structure->site_id !== $infoSiteId || rtrim($base, '/') !== rtrim($infoRoute, '/')
    || rtrim($requestPath, '/') !== rtrim($base, '/')) { $fail(); return; }
$source = Core_Entity::factory('Structure')->find($infoSourceId);
if (!$source->loaded() || (int) $source->id !== $infoSourceId || (int) $source->type !== 0
    || (int) $source->document_id !== $infoDocumentId) { $fail(); return; }

// Validate the finite parent chain BEFORE invoking recursive inherited visibility lookup.
$chain = array(); $seen = array(); $node = $source;
for ($depth = 0; $depth < 64; $depth++) {
    $nodeId = (int) $node->id;
    if (!$node->loaded() || $nodeId < 1 || isset($seen[$nodeId]) || (int) $node->active !== 1
        || (int) $node->site_id !== $infoSiteId) { $fail(); return; }
    $seen[$nodeId] = true; $chain[] = $node;
    $parentId = (int) $node->parent_id;
    if ($parentId === 0) break;
    if ($parentId < 0) { $fail(); return; }
    $node = Core_Entity::factory('Structure')->find($parentId);
}
if ($parentId !== 0) { $fail(); return; }
foreach ($chain as $ancestor) {
    if ((int) $ancestor->getSiteuserGroupId() !== 0) { $fail(); return; }
}
$document = Core_Entity::factory('Document')->find($infoDocumentId);
if (!$document->loaded() || (int) $document->id !== $infoDocumentId || (int) $document->site_id !== $infoSiteId) { $fail(); return; }

// Current stored HTML, without admin WYSIWYG, shortcodes or execution side effects.
$body = (string) $document->text;
$h1Count = 0; $h1Stack = array();
$headingTokens = '~<!--[\s\S]*?-->|<(script|style|textarea|title)\b[^>]*>[\s\S]*?</\1\s*>|</?h1\b(?:"[^"]*"|\x27[^\x27]*\x27|[^\x27">])*>~i';
$body = preg_replace_callback($headingTokens, static function ($match) use (&$h1Count, &$h1Stack) {
    $tag = $match[0];
    if (preg_match('~^<h1\b~i', $tag)) {
        $replacement = ++$h1Count === 1 ? 'h1' : 'h2'; $h1Stack[] = $replacement;
        return $replacement === 'h1' ? $tag : preg_replace('~^<h1\b~i', '<h2', $tag, 1);
    }
    if (preg_match('~^</h1\b~i', $tag)) {
        return array_pop($h1Stack) === 'h2' ? preg_replace('~^</h1\b~i', '</h2', $tag, 1) : $tag;
    }
    return $tag;
}, $body);
if (!is_string($body)) { $fail(503); return; }

$quantity = 0;
$shop = Core_Entity::factory('Shop', 1);
foreach (Shop_Cart_Controller::instance()->getAll($shop) as $row) {
    if (!$row->postpone) $quantity += $row->quantity;
}
$xml = Core::factory('Core_Xml_Entity')->name('modern_page');
$fields = array('title' => (string) $source->name, 'source_url' => $source->getPath(), 'section' => $infoSection,
    'has_h1' => $h1Count > 0 ? '1' : '0', 'content' => $body, 'modern_cart_count' => $quantity);
foreach ($fields as $name => $value) $xml->addEntity(Core::factory('Core_Xml_Entity')->name($name)->value($value));
$xsl = Core_Entity::factory('Xsl')->getByName('ModernInfo2026');
if (!$xsl || !(int) $xsl->id) { $fail(503); return; }
$html = Xsl_Processor::instance()->xml($xml->getXml())->xsl($xsl)->process();
echo '<!doctype html>';
echo $html;
