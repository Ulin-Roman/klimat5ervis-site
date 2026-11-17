<?php
// HostCMS dynamic-page configuration. The source independently authenticates every request.
// Do not let HostCMS create a static mirror of this authenticated maintenance page.
Core_Page::instance()->staticCache(FALSE);
if (!headers_sent()) {
    header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('X-Robots-Tag: noindex, nofollow, noarchive');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('X-Frame-Options: SAMEORIGIN');
}
