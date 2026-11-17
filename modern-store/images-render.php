<?php
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) { http_response_code(404); exit; }
/* Include once after XSL rendering; no GD, no writes, no changes to zoom links. */
if (!function_exists('klimat_modern_image_html')) {
function klimat_modern_image_html($html)
{
    if (!is_string($html) || !class_exists('DOMDocument')) return $html;
    try {
    $root = realpath(defined('CMS_FOLDER') ? CMS_FOLDER : (isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : ''));
    if (!$root || $root === DIRECTORY_SEPARATOR || is_link(__DIR__)) return $html;
    $read = static function ($file) {
        if (is_link($file) || !is_file($file) || filesize($file) > 2097152) return null;
        $raw = @file_get_contents($file);
        $guard = "<?php http_response_code(404); exit; __halt_compiler();\n";
        if ($raw === false || strncmp($raw, $guard, strlen($guard)) !== 0) return null;
        $data = json_decode(substr($raw, strlen($guard)), true);
        return is_array($data) ? $data : null;
    };
    $index = $read(__DIR__ . '/manifest.php');
    if (!$index || ($index['schema'] ?? 0) !== 1 || ($index['root_hash'] ?? '') !== hash('sha256', $root)) return $html;
    $buckets = $resolved = array();
    $safe = static function ($url) use ($root) {
        if (!is_string($url)) return false;
        if (!preg_match('~^/upload/shop_1/(?:[A-Za-z0-9_-]+/)*item_([0-9]+)/(?:small_)?(?:item_image([0-9]+)|shop_property_file_[0-9]+_[0-9]+)\.(?:jpe?g|png)$~iD', $url, $m)) return false;
        if (isset($m[2]) && $m[2] !== '' && $m[1] !== $m[2]) return false;
        $path = $root;
        foreach (explode('/', ltrim($url, '/')) as $part) { $path .= '/' . $part; if (is_link($path)) return false; }
        return is_file($path) && realpath($path) === $path ? $path : false;
    };
    $lookup = static function ($url) use ($root, $read, $safe, &$buckets, &$resolved) {
        if (array_key_exists($url, $resolved)) return $resolved[$url];
        $resolved[$url] = null;
        $alias = $safe($url); if (!$alias) return null;
        $bucket = substr(hash('sha256', $url), 0, 2);
        if (!array_key_exists($bucket, $buckets)) $buckets[$bucket] = $read(__DIR__ . '/map-' . $bucket . '.php');
        $entry = $buckets[$bucket][$url] ?? null;
        if (!is_array($entry)) return null;
        $source = $safe($entry['source'] ?? ''); if (!$source) return null;
        clearstatcache(true, $alias); clearstatcache(true, $source);
        if (@filemtime($alias) !== ($entry['alias_mtime'] ?? -1) || @filesize($alias) !== ($entry['alias_size'] ?? -1)
            || @filemtime($source) !== ($entry['mtime'] ?? -1) || @filesize($source) !== ($entry['size'] ?? -1)) return null;
        $variants = array();
        foreach (is_array($entry['variants'] ?? null) ? $entry['variants'] : array() as $v) {
            if (!is_array($v) || !is_string($v['url'] ?? null) || !preg_match('~^/assets/catalog-images-20261117/[a-f0-9]{64}\.webp$~D', $v['url'])
                || !is_int($v['w'] ?? null) || !is_int($v['h'] ?? null) || $v['w'] < 1 || $v['w'] > 1280 || $v['h'] < 1 || $v['h'] > 1280) continue;
            $path = $root . $v['url'];
            if (is_link($root . '/assets') || is_link($root . '/assets/catalog-images-20261117') || is_link($path)
                || !is_file($path) || @filesize($path) !== ($v['bytes'] ?? -1)) continue;
            $variants[$v['w']] = $v;
        }
        if (!$variants) return null;
        ksort($variants, SORT_NUMERIC); return $resolved[$url] = array_values($variants);
    };
    $srcset = static function ($variants) { return implode(', ', array_map(static function ($v) { return $v['url'] . ' ' . $v['w'] . 'w'; }, $variants)); };
    $choose = static function ($variants, $width) { foreach ($variants as $v) if ($v['w'] >= $width) return $v['url']; return end($variants)['url']; };
    $detailSizes = '(max-width: 760px) calc(100vw - 48px), (max-width: 1100px) 55vw, 680px';
    $cardSizes = '(max-width: 480px) calc((100vw - 72px) / 2), (max-width: 760px) calc((100vw - 64px) / 2), (max-width: 1100px) 30vw, 360px';
    $picture = 0;
    // Tokenize raw-text blocks first. Parse/serialize only individual opening tags, never the full document.
    $pattern = '~<!--[\s\S]*?-->|<(script|style|textarea|title)\b[^>]*>[\s\S]*?</\1\s*>|</?picture\b(?:"[^"]*"|\x27[^\x27]*\x27|[^\x27">])*>|<(?:img|a)\b(?:"[^"]*"|\x27[^\x27]*\x27|[^\x27">])*>~i';
    $result = preg_replace_callback($pattern, static function ($match) use (&$picture, $lookup, $srcset, $choose, $detailSizes, $cardSizes) {
        $tag = $match[0];
        if (preg_match('~^</picture\b~i', $tag)) { $picture = max(0, $picture - 1); return $tag; }
        if (preg_match('~^<picture\b~i', $tag)) { $picture++; return $tag; }
        if (!preg_match('~^<(img|a)\b~i', $tag, $type) || $picture) return $tag;
        $previous = libxml_use_internal_errors(true);
        $doc = new DOMDocument('1.0', 'UTF-8');
        $loaded = $doc->loadHTML('<?xml encoding="UTF-8"><html><body>' . $tag . '</body></html>', LIBXML_NONET);
        libxml_clear_errors(); libxml_use_internal_errors($previous);
        if (!$loaded) return $tag;
        $node = $doc->getElementsByTagName(strtolower($type[1]))->item(0); if (!$node) return $tag;
        if (strtolower($type[1]) === 'img') {
            if ($node->hasAttribute('data-src') || $node->hasAttribute('data-srcset')) return $tag;
            $variants = $lookup($node->getAttribute('src')); if (!$variants) return $tag;
            $isDetail = $node->getAttribute('id') === 'main-photo';
            $node->setAttribute('src', $choose($variants, $isDetail ? 720 : 360));
            $node->setAttribute('srcset', $srcset($variants));
            if (!$node->hasAttribute('sizes')) $node->setAttribute('sizes', $isDetail ? $detailSizes : $cardSizes);
        } else {
            if (!preg_match('~(?:^|\s)photo-thumb(?:\s|$)~', $node->getAttribute('class'))) return $tag;
            $variants = $lookup($node->getAttribute('href')); if (!$variants) return $tag;
            $node->setAttribute('data-display', $choose($variants, 720));
            $node->setAttribute('data-display-srcset', $srcset($variants));
            $node->setAttribute('data-display-sizes', $detailSizes);
        }
        $opening = '<' . strtolower($type[1]);
        foreach ($node->attributes as $attr) $opening .= ' ' . $attr->name . '="' . htmlspecialchars($attr->value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
        return $opening . '>';
    }, $html);
    return is_string($result) ? $result : $html;
    } catch (Throwable $ignored) { return $html; }
}
}
