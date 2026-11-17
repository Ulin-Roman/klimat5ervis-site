<?php
Core_Page::instance()->staticCache(FALSE);
// Separate storefront preview: native engine, no shared HTML cache.
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: private, no-store');
?>